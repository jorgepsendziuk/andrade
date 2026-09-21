import fs from 'fs';
import path from 'path';
import { randomUUID } from 'crypto';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';
import type { AuditLogRecord } from './types/process.js';

const AUDIT_FILE = dataFile('audit-logs.json');
const COLLECTION = 'audit_logs';

function readFileLogs(): AuditLogRecord[] {
  try {
    return JSON.parse(fs.readFileSync(AUDIT_FILE, 'utf-8'));
  } catch {
    return [];
  }
}

function writeFileLogs(logs: AuditLogRecord[]) {
  fs.mkdirSync(path.dirname(AUDIT_FILE), { recursive: true });
  fs.writeFileSync(AUDIT_FILE, JSON.stringify(logs, null, 2), 'utf-8');
}

export async function logAudit(input: Omit<AuditLogRecord, 'id' | 'createdAt'>): Promise<void> {
  const record: AuditLogRecord = {
    id: randomUUID(),
    ...input,
    createdAt: new Date().toISOString(),
  };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(record.id).set(stripUndefined(record));
  } else {
    const logs = readFileLogs();
    logs.unshift(record);
    writeFileLogs(logs.slice(0, 5000));
  }
}

export async function findAuditLogById(id: string): Promise<AuditLogRecord | null> {
  if (useFirestore) {
    const db = await getFirestore();
    const doc = await db.collection(COLLECTION).doc(id).get();
    return doc.exists ? (doc.data() as AuditLogRecord) : null;
  }
  return readFileLogs().find((log) => log.id === id) ?? null;
}

export async function listAuditLogs(options?: {
  limit?: number;
  resourceType?: string;
  resourceId?: string;
  userId?: string;
  action?: string;
  search?: string;
}): Promise<AuditLogRecord[]> {
  const limit = Math.min(Math.max(options?.limit ?? 200, 1), 1000);
  let logs: AuditLogRecord[];

  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).orderBy('createdAt', 'desc').limit(800).get();
    logs = snap.docs.map((d) => d.data() as AuditLogRecord);
  } else {
    logs = readFileLogs();
  }

  const search = options?.search?.trim().toLowerCase();
  return logs
    .filter((log) => {
      if (options?.resourceType && log.resourceType !== options.resourceType) return false;
      if (options?.resourceId && log.resourceId !== options.resourceId) return false;
      if (options?.userId && log.userId !== options.userId) return false;
      if (options?.action && log.action !== options.action) return false;
      if (!search) return true;
      const hay = [log.userEmail, log.summary, log.resourceType, log.resourceId, log.action, log.objectName]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();
      return hay.includes(search);
    })
    .slice(0, limit);
}
