#!/usr/bin/env npx tsx
/**
 * Migra fotos de conteúdo de frontend/public para o GCS e atualiza site-content.json.
 *
 * Uso:
 *   export GCS_BUCKET=andrade-media
 *   export GCP_PROJECT=seu-projeto
 *   npm run migrate:media
 *
 * Opções:
 *   --dry-run   lista arquivos sem enviar
 *   --no-delete não remove arquivos locais após upload
 */
import fs from 'fs';
import path from 'path';
import { createRequire } from 'module';
import { fileURLToPath } from 'url';
import { gcsPublicUrl, isBuildAssetPath } from '../server/src/media-store.js';

const require = createRequire(path.join(path.dirname(fileURLToPath(import.meta.url)), '../server/package.json'));
const { Storage } = require('@google-cloud/storage') as typeof import('@google-cloud/storage');

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.join(__dirname, '..');
const PUBLIC_DIR = path.join(ROOT, 'frontend/public');
const CONTENT_FILE = path.join(ROOT, 'server/data/site-content.json');

const BUCKET = process.env.GCS_BUCKET || '';
const DRY_RUN = process.argv.includes('--dry-run');
const NO_DELETE = process.argv.includes('--no-delete');

if (!BUCKET) {
  console.error('Defina GCS_BUCKET (ex: export GCS_BUCKET=andrade-media)');
  process.exit(1);
}

function collectAssetPaths(value: unknown, paths = new Set<string>()): Set<string> {
  if (typeof value === 'string' && value.startsWith('/assets/') && !isBuildAssetPath(value)) {
    paths.add(value);
  } else if (Array.isArray(value)) {
    value.forEach((item) => collectAssetPaths(item, paths));
  } else if (value && typeof value === 'object') {
    Object.values(value).forEach((item) => collectAssetPaths(item, paths));
  }
  return paths;
}

function replaceAssetPaths(value: unknown, map: Map<string, string>): unknown {
  if (typeof value === 'string' && map.has(value)) {
    return map.get(value);
  }
  if (Array.isArray(value)) {
    return value.map((item) => replaceAssetPaths(item, map));
  }
  if (value && typeof value === 'object') {
    return Object.fromEntries(
      Object.entries(value).map(([k, v]) => [k, replaceAssetPaths(v, map)])
    );
  }
  return value;
}

function mimeFromExt(filePath: string): string {
  const ext = path.extname(filePath).toLowerCase();
  const map: Record<string, string> = {
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.png': 'image/png',
    '.webp': 'image/webp',
    '.gif': 'image/gif',
    '.svg': 'image/svg+xml',
  };
  return map[ext] || 'application/octet-stream';
}

async function uploadFile(
  storage: Storage,
  localPath: string,
  assetPath: string
): Promise<string> {
  const relative = assetPath.replace(/^\//, '');
  const objectName = `media/${relative}`;
  const url = gcsPublicUrl(objectName);

  if (DRY_RUN) {
    console.log(`  [dry-run] ${assetPath} → ${url}`);
    return url;
  }

  const bucket = storage.bucket(BUCKET);
  const remote = bucket.file(objectName);
  const [exists] = await remote.exists();
  if (exists) {
    console.log(`  ✓ já existe: ${objectName}`);
    return url;
  }

  const buffer = fs.readFileSync(localPath);
  await remote.save(buffer, {
    metadata: {
      contentType: mimeFromExt(localPath),
      cacheControl: 'public, max-age=31536000, immutable',
    },
    resumable: false,
  });
  console.log(`  ↑ enviado: ${assetPath}`);
  return url;
}

const content = JSON.parse(fs.readFileSync(CONTENT_FILE, 'utf-8')) as Record<string, unknown>;
const assetPaths = [...collectAssetPaths(content)].sort();

console.log(`→ Bucket: ${BUCKET}`);
console.log(`→ ${assetPaths.length} paths de conteúdo em site-content.json\n`);

const storage = new Storage({
  projectId: process.env.GOOGLE_CLOUD_PROJECT || process.env.GCP_PROJECT,
});

const urlMap = new Map<string, string>();
const uploadedLocalFiles: string[] = [];

for (const assetPath of assetPaths) {
  const localPath = path.join(PUBLIC_DIR, assetPath);
  if (!fs.existsSync(localPath)) {
    console.warn(`  ⚠ arquivo ausente: ${localPath}`);
    continue;
  }
  const url = await uploadFile(storage, localPath, assetPath);
  urlMap.set(assetPath, url);
  uploadedLocalFiles.push(localPath);
}

if (urlMap.size === 0) {
  console.log('Nenhum arquivo migrado.');
  process.exit(0);
}

if (!DRY_RUN) {
  const updated = replaceAssetPaths(content, urlMap);
  fs.writeFileSync(CONTENT_FILE, `${JSON.stringify(updated, null, 2)}\n`);
  console.log(`\n✓ site-content.json atualizado (${urlMap.size} URLs)`);
}

if (!DRY_RUN && !NO_DELETE) {
  console.log('\n→ Removendo arquivos migrados e legado do public/…');
  for (const file of uploadedLocalFiles) {
    if (fs.existsSync(file)) fs.unlinkSync(file);
  }

  const legacyDirs = [
    'assets/carousel',
    'assets/testimonials',
    'assets/consorcio',
    'assets/team',
    'assets/services',
    'assets/contact',
    'assets/hero/delivery',
  ];
  for (const dir of legacyDirs) {
    const full = path.join(PUBLIC_DIR, dir);
    if (fs.existsSync(full)) {
      fs.rmSync(full, { recursive: true, force: true });
      console.log(`  ✗ removido: ${dir}/`);
    }
  }

  // Remove pastas vazias em assets/hero, assets/about etc.
  const pruneEmpty = (dir: string) => {
    if (!fs.existsSync(dir)) return;
    for (const entry of fs.readdirSync(dir)) {
      const full = path.join(dir, entry);
      if (fs.statSync(full).isDirectory()) pruneEmpty(full);
    }
    if (fs.readdirSync(dir).length === 0) {
      fs.rmdirSync(dir);
      console.log(`  ✗ pasta vazia: ${path.relative(PUBLIC_DIR, dir)}/`);
    }
  };
  pruneEmpty(path.join(PUBLIC_DIR, 'assets/hero'));
  pruneEmpty(path.join(PUBLIC_DIR, 'assets/about'));
}

console.log('\n✓ Migração concluída.');
if (!DRY_RUN) {
  console.log('  Para produção: npm run restore:content');
}
