import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { GCS_BUCKET, isGcsEnabled } from './media-store.js';
import { GCS_DOCS_BUCKET, isDocsGcsEnabled } from './docs-store.js';
import { getFirestore, useFirestore } from './firestore-client.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
import { backupsDir, getDataDir, privateDocsDir } from './data-paths.js';

const LOCAL_BACKUP_DIR = backupsDir();
const LOCAL_DATA_DIR = getDataDir();
const LOCAL_DOCS_DIR = privateDocsDir();

export const BACKUP_BUCKET = process.env.GCS_BACKUP_BUCKET || 'andrade-backups';
export const BACKUP_RETENTION_DAYS = Number(process.env.BACKUP_RETENTION_DAYS || 90);
export const BACKUP_CRON_SECRET = process.env.BACKUP_CRON_SECRET || '';
export const BACKUP_SCHEDULE_LABEL = 'Diário às 03:00 (horário de Brasília)';

export const FIRESTORE_BACKUP_COLLECTIONS = [
  'site_content',
  'contact_submissions',
  'admin_users',
  'app_settings',
  'processes',
  'clients',
  'process_files',
  'conductors',
  'audit_logs',
  'staff_alerts',
] as const;

export const BACKUP_NEARLINE_USD_PER_GB_MONTH = 0.016;

export interface BackupManifest {
  date: string;
  startedAt: string;
  completedAt: string;
  trigger: 'manual' | 'scheduler' | 'cli';
  status: 'success' | 'partial' | 'failed';
  firestore: { collections: number; documents: number; bytes: number };
  gcs: { buckets: { name: string; files: number; bytes: number }[] };
  totalBytes: number;
  error?: string;
}

export interface BackupStatus {
  enabled: boolean;
  bucket: string | null;
  retentionDays: number;
  schedule: string;
  schedulerConfigured: boolean;
  lastRun: BackupManifest | null;
  storageBytes: number;
  estimatedMonthlyCostUsd: number;
  recentRuns: { date: string; status: BackupManifest['status']; totalBytes: number }[];
}

type BackupTrigger = BackupManifest['trigger'];

function todayKey(): string {
  return new Date().toISOString().slice(0, 10);
}

function serializeFirestoreValue(value: unknown): unknown {
  if (value == null) return value;
  if (typeof value === 'object' && 'toDate' in value && typeof (value as { toDate: () => Date }).toDate === 'function') {
    return (value as { toDate: () => Date }).toDate().toISOString();
  }
  if (Array.isArray(value)) return value.map(serializeFirestoreValue);
  if (typeof value === 'object') {
    return Object.fromEntries(
      Object.entries(value as Record<string, unknown>).map(([k, v]) => [k, serializeFirestoreValue(v)])
    );
  }
  return value;
}

function getStorage() {
  return import('@google-cloud/storage').then(({ Storage }) => {
    return new Storage({
      projectId: process.env.GOOGLE_CLOUD_PROJECT || process.env.GCP_PROJECT,
    });
  });
}

function shouldReadBackupFromGcs(): boolean {
  return Boolean(process.env.K_SERVICE) && Boolean(BACKUP_BUCKET);
}

function shouldUseGcsBackup(): boolean {
  return shouldReadBackupFromGcs() && (isGcsEnabled() || isDocsGcsEnabled());
}

async function readJsonFromGcs<T>(bucketName: string, objectName: string): Promise<T | null> {
  try {
    const storage = await getStorage();
    const [buf] = await storage.bucket(bucketName).file(objectName).download();
    return JSON.parse(buf.toString('utf-8')) as T;
  } catch {
    return null;
  }
}

async function writeJsonToGcs(bucketName: string, objectName: string, data: unknown): Promise<void> {
  const storage = await getStorage();
  const body = JSON.stringify(data, null, 2);
  await storage.bucket(bucketName).file(objectName).save(body, {
    contentType: 'application/json',
    resumable: false,
  });
}

async function getBucketTotalBytes(bucketName: string): Promise<number> {
  if (!bucketName) return 0;
  try {
    const storage = await getStorage();
    const [files] = await storage.bucket(bucketName).getFiles({ autoPaginate: true });
    return files.reduce((sum, f) => sum + Number(f.metadata.size ?? 0), 0);
  } catch {
    return 0;
  }
}

function estimateBackupMonthlyCost(bytes: number): number {
  const gb = bytes / 1024 ** 3;
  return Math.round(gb * BACKUP_NEARLINE_USD_PER_GB_MONTH * 100) / 100;
}

function dirSize(dir: string): number {
  if (!fs.existsSync(dir)) return 0;
  let total = 0;
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    total += entry.isDirectory() ? dirSize(full) : fs.statSync(full).size;
  }
  return total;
}

function copyDirRecursive(src: string, dest: string) {
  fs.mkdirSync(dest, { recursive: true });
  for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
    const from = path.join(src, entry.name);
    const to = path.join(dest, entry.name);
    if (entry.isDirectory()) copyDirRecursive(from, to);
    else fs.copyFileSync(from, to);
  }
}

async function exportFirestoreToBackup(
  date: string,
  destRoot: string,
  useGcs: boolean
): Promise<{ collections: number; documents: number; bytes: number }> {
  const firestoreDir = path.join(destRoot, 'firestore');
  fs.mkdirSync(firestoreDir, { recursive: true });

  let collections = 0;
  let documents = 0;
  let bytes = 0;

  if (useFirestore) {
    const db = await getFirestore();
    for (const name of FIRESTORE_BACKUP_COLLECTIONS) {
      const snap = await db.collection(name).get();
      const docs = snap.docs.map((d) => ({
        id: d.id,
        ...serializeFirestoreValue(d.data()) as Record<string, unknown>,
      }));
      const json = JSON.stringify(docs, null, 2);
      const fileName = `${name}.json`;
      const localPath = path.join(firestoreDir, fileName);
      fs.writeFileSync(localPath, json, 'utf-8');
      collections += 1;
      documents += docs.length;
      bytes += Buffer.byteLength(json);

      if (useGcs) {
        const storage = await getStorage();
        await storage.bucket(BACKUP_BUCKET).upload(localPath, {
          destination: `daily/${date}/firestore/${fileName}`,
          resumable: false,
        });
      }
    }
    return { collections, documents, bytes };
  }

  const localCollections: Record<string, string> = {
    site_content: 'site-content.json',
    contact_submissions: 'contacts.json',
    admin_users: 'admin-users.json',
    app_settings: 'app-settings.json',
    processes: 'processes.json',
    clients: 'clients.json',
    process_files: 'process-files.json',
    conductors: 'conductors.json',
    audit_logs: 'audit-logs.json',
    staff_alerts: 'staff-alerts.json',
  };

  for (const [collection, file] of Object.entries(localCollections)) {
    const src = path.join(LOCAL_DATA_DIR, file);
    if (!fs.existsSync(src)) continue;
    const dest = path.join(firestoreDir, `${collection}.json`);
    fs.copyFileSync(src, dest);
    const size = fs.statSync(dest).size;
    collections += 1;
    try {
      const parsed = JSON.parse(fs.readFileSync(dest, 'utf-8'));
      documents += Array.isArray(parsed) ? parsed.length : 1;
    } catch {
      documents += 1;
    }
    bytes += size;
  }

  return { collections, documents, bytes };
}

async function copyGcsBucket(
  sourceBucket: string,
  destPrefix: string,
  concurrency = 12
): Promise<{ files: number; bytes: number }> {
  const storage = await getStorage();
  const [files] = await storage.bucket(sourceBucket).getFiles({ autoPaginate: true });
  let copied = 0;
  let bytes = 0;

  const queue = [...files.filter((f) => !f.name.endsWith('/'))];
  async function worker() {
    while (queue.length) {
      const file = queue.shift();
      if (!file) break;
      const size = Number(file.metadata.size ?? 0);
      const destName = `${destPrefix}${file.name}`;
      await file.copy(storage.bucket(BACKUP_BUCKET).file(destName));
      copied += 1;
      bytes += size;
    }
  }

  await Promise.all(Array.from({ length: Math.min(concurrency, Math.max(queue.length, 1)) }, () => worker()));
  return { files: copied, bytes };
}

async function backupLocalGcsMirror(date: string, destRoot: string): Promise<{ buckets: { name: string; files: number; bytes: number }[] }> {
  const buckets: { name: string; files: number; bytes: number }[] = [];
  const mediaSrc = path.join(LOCAL_DATA_DIR, 'media');
  const docsSrc = LOCAL_DOCS_DIR;
  if (fs.existsSync(mediaSrc)) {
    const dest = path.join(destRoot, 'gcs', GCS_BUCKET || 'andrade-media');
    copyDirRecursive(mediaSrc, dest);
    const bytes = dirSize(dest);
    buckets.push({ name: GCS_BUCKET || 'andrade-media', files: 0, bytes });
  }
  if (fs.existsSync(docsSrc)) {
    const dest = path.join(destRoot, 'gcs', GCS_DOCS_BUCKET || 'andrade-docs');
    copyDirRecursive(docsSrc, dest);
    const bytes = dirSize(dest);
    buckets.push({ name: GCS_DOCS_BUCKET || 'andrade-docs', files: 0, bytes });
  }
  return { buckets };
}

async function cleanupOldBackups(useGcs: boolean) {
  const cutoff = new Date();
  cutoff.setDate(cutoff.getDate() - BACKUP_RETENTION_DAYS);
  const cutoffKey = cutoff.toISOString().slice(0, 10);

  if (useGcs) {
    const storage = await getStorage();
    const [files] = await storage.bucket(BACKUP_BUCKET).getFiles({ prefix: 'daily/', autoPaginate: true });
    const dates = new Set<string>();
    for (const file of files) {
      const match = file.name.match(/^daily\/(\d{4}-\d{2}-\d{2})\//);
      if (match) dates.add(match[1]);
    }
    for (const date of dates) {
      if (date >= cutoffKey) continue;
      const [oldFiles] = await storage.bucket(BACKUP_BUCKET).getFiles({ prefix: `daily/${date}/`, autoPaginate: true });
      await Promise.all(oldFiles.map((f) => f.delete({ ignoreNotFound: true })));
    }
    return;
  }

  const dailyDir = path.join(LOCAL_BACKUP_DIR, 'daily');
  if (!fs.existsSync(dailyDir)) return;
  for (const entry of fs.readdirSync(dailyDir)) {
    if (entry < cutoffKey) {
      fs.rmSync(path.join(dailyDir, entry), { recursive: true, force: true });
    }
  }
}

export async function getBackupStatus(): Promise<BackupStatus> {
  const useGcs = shouldReadBackupFromGcs();
  const bucket = useGcs ? BACKUP_BUCKET : null;
  let lastRun: BackupManifest | null = null;
  let storageBytes = 0;
  const recentRuns: BackupStatus['recentRuns'] = [];

  if (useGcs) {
    lastRun = await readJsonFromGcs<BackupManifest>(BACKUP_BUCKET, 'latest.json');
    storageBytes = await getBucketTotalBytes(BACKUP_BUCKET);
    try {
      const storage = await getStorage();
      const [files] = await storage.bucket(BACKUP_BUCKET).getFiles({ prefix: 'daily/', autoPaginate: true });
      const byDate = new Map<string, BackupManifest['status']>();
      const bytesByDate = new Map<string, number>();
      for (const file of files) {
        const match = file.name.match(/^daily\/(\d{4}-\d{2}-\d{2})\//);
        if (!match) continue;
        const date = match[1];
        bytesByDate.set(date, (bytesByDate.get(date) ?? 0) + Number(file.metadata.size ?? 0));
        if (file.name.endsWith('manifest.json')) {
          try {
            const [buf] = await file.download();
            const manifest = JSON.parse(buf.toString('utf-8')) as BackupManifest;
            byDate.set(date, manifest.status);
          } catch {
            byDate.set(date, 'partial');
          }
        }
      }
      for (const date of [...bytesByDate.keys()].sort().reverse().slice(0, 7)) {
        recentRuns.push({
          date,
          status: byDate.get(date) ?? 'partial',
          totalBytes: bytesByDate.get(date) ?? 0,
        });
      }
    } catch {
      // bucket pode não existir ainda
    }
  } else {
    const latestPath = path.join(LOCAL_BACKUP_DIR, 'latest.json');
    if (fs.existsSync(latestPath)) {
      lastRun = JSON.parse(fs.readFileSync(latestPath, 'utf-8')) as BackupManifest;
    }
    storageBytes = dirSize(LOCAL_BACKUP_DIR);
    const dailyDir = path.join(LOCAL_BACKUP_DIR, 'daily');
    if (fs.existsSync(dailyDir)) {
      for (const date of fs.readdirSync(dailyDir).sort().reverse().slice(0, 7)) {
        const manifestPath = path.join(dailyDir, date, 'manifest.json');
        if (!fs.existsSync(manifestPath)) continue;
        const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf-8')) as BackupManifest;
        recentRuns.push({ date, status: manifest.status, totalBytes: manifest.totalBytes });
      }
    }
  }

  return {
    enabled: true,
    bucket,
    retentionDays: BACKUP_RETENTION_DAYS,
    schedule: BACKUP_SCHEDULE_LABEL,
    schedulerConfigured: Boolean(BACKUP_CRON_SECRET),
    lastRun,
    storageBytes,
    estimatedMonthlyCostUsd: estimateBackupMonthlyCost(storageBytes),
    recentRuns,
  };
}

export async function runDailyBackup(
  trigger: BackupTrigger,
  options?: { force?: boolean }
): Promise<BackupManifest> {
  const date = todayKey();
  const startedAt = new Date().toISOString();
  const useGcs = shouldUseGcsBackup();

  if (!options?.force) {
    const status = await getBackupStatus();
    if (status.lastRun?.date === date && status.lastRun.status === 'success') {
      return status.lastRun;
    }
  }

  const destRoot = path.join(LOCAL_BACKUP_DIR, 'daily', date);
  fs.mkdirSync(destRoot, { recursive: true });

  const manifest: BackupManifest = {
    date,
    startedAt,
    completedAt: startedAt,
    trigger,
    status: 'success',
    firestore: { collections: 0, documents: 0, bytes: 0 },
    gcs: { buckets: [] },
    totalBytes: 0,
  };

  try {
    manifest.firestore = await exportFirestoreToBackup(date, destRoot, useGcs);

    if (useGcs) {
      if (GCS_BUCKET && isGcsEnabled()) {
        const media = await copyGcsBucket(GCS_BUCKET, `daily/${date}/gcs/${GCS_BUCKET}/`);
        manifest.gcs.buckets.push({ name: GCS_BUCKET, ...media });
      }
      if (GCS_DOCS_BUCKET && isDocsGcsEnabled()) {
        const docs = await copyGcsBucket(GCS_DOCS_BUCKET, `daily/${date}/gcs/${GCS_DOCS_BUCKET}/`);
        manifest.gcs.buckets.push({ name: GCS_DOCS_BUCKET, ...docs });
      }
    } else {
      const local = await backupLocalGcsMirror(date, destRoot);
      manifest.gcs.buckets = local.buckets;
    }

    manifest.totalBytes =
      manifest.firestore.bytes + manifest.gcs.buckets.reduce((sum, b) => sum + b.bytes, 0);
    manifest.completedAt = new Date().toISOString();

    const manifestPath = path.join(destRoot, 'manifest.json');
    fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2), 'utf-8');

    if (useGcs) {
      await writeJsonToGcs(BACKUP_BUCKET, `daily/${date}/manifest.json`, manifest);
      await writeJsonToGcs(BACKUP_BUCKET, 'latest.json', manifest);
    } else {
      fs.writeFileSync(path.join(LOCAL_BACKUP_DIR, 'latest.json'), JSON.stringify(manifest, null, 2), 'utf-8');
    }

    await cleanupOldBackups(useGcs);
    return manifest;
  } catch (err) {
    manifest.status = 'failed';
    manifest.error = err instanceof Error ? err.message : String(err);
    manifest.completedAt = new Date().toISOString();
    if (useGcs) {
      await writeJsonToGcs(BACKUP_BUCKET, 'latest.json', manifest).catch(() => undefined);
    } else {
      fs.writeFileSync(path.join(LOCAL_BACKUP_DIR, 'latest.json'), JSON.stringify(manifest, null, 2), 'utf-8');
    }
    throw err;
  }
}

export function isValidBackupCronSecret(secret: string | undefined): boolean {
  return Boolean(BACKUP_CRON_SECRET) && secret === BACKUP_CRON_SECRET;
}
