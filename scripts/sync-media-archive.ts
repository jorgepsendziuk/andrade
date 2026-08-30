#!/usr/bin/env npx tsx
/**
 * Envia todo o acervo de imagens (frontend/public/assets, exceto logo/marcas) para o GCS.
 * Restaura arquivos do git se necessário. Não altera site-content.json nem apaga locais.
 *
 *   export GCS_BUCKET=andrade-media
 *   npm run sync:media
 */
import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
import { createRequire } from 'module';
import { fileURLToPath } from 'url';
import { gcsPublicUrl } from '../server/src/media-store.js';

const require = createRequire(path.join(path.dirname(fileURLToPath(import.meta.url)), '../server/package.json'));
const { Storage } = require('@google-cloud/storage') as typeof import('@google-cloud/storage');

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.join(__dirname, '..');
const ASSETS_DIR = path.join(ROOT, 'frontend/public/assets');
const BUCKET = process.env.GCS_BUCKET || '';
const DRY_RUN = process.argv.includes('--dry-run');

const IMAGE_EXT = /\.(jpe?g|png|webp|gif|svg)$/i;
const SKIP_DIRS = new Set(['logo', 'brands', 'uploads']);

if (!BUCKET) {
  console.error('Defina GCS_BUCKET');
  process.exit(1);
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

function walkImages(dir: string): string[] {
  if (!fs.existsSync(dir)) return [];
  const out: string[] = [];
  for (const entry of fs.readdirSync(dir)) {
    if (dir === ASSETS_DIR && SKIP_DIRS.has(entry)) continue;
    const full = path.join(dir, entry);
    if (fs.statSync(full).isDirectory()) {
      out.push(...walkImages(full));
    } else if (IMAGE_EXT.test(entry)) {
      out.push(full);
    }
  }
  return out;
}

const localCount = walkImages(ASSETS_DIR).length;
if (localCount < 10) {
  console.log('→ Poucos arquivos locais — restaurando do git…');
  execSync('git checkout -- frontend/public/assets/', { cwd: ROOT, stdio: 'inherit' });
}

const files = walkImages(ASSETS_DIR).sort();
console.log(`→ Bucket: ${BUCKET}`);
console.log(`→ ${files.length} imagens para enviar\n`);

const storage = new Storage({
  projectId: process.env.GOOGLE_CLOUD_PROJECT || process.env.GCP_PROJECT,
});
const bucket = storage.bucket(BUCKET);

let uploaded = 0;
let skipped = 0;

for (const localPath of files) {
  const rel = path.relative(path.join(ROOT, 'frontend/public'), localPath).split(path.sep).join('/');
  const objectName = `media/${rel}`;
  const url = gcsPublicUrl(objectName);

  if (DRY_RUN) {
    console.log(`  [dry-run] ${objectName}`);
    continue;
  }

  const remote = bucket.file(objectName);
  const [exists] = await remote.exists();
  if (exists) {
    skipped++;
    continue;
  }

  await remote.save(fs.readFileSync(localPath), {
    metadata: {
      contentType: mimeFromExt(localPath),
      cacheControl: 'public, max-age=31536000, immutable',
    },
    resumable: false,
  });
  uploaded++;
  console.log(`  ↑ ${objectName}`);
}

console.log(`\n✓ Concluído: ${uploaded} enviados, ${skipped} já existiam`);
if (!DRY_RUN) {
  console.log(`  Biblioteca: https://storage.googleapis.com/${BUCKET}/media/`);
}
