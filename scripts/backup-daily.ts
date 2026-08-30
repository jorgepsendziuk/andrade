#!/usr/bin/env npx tsx
/** Backup diário manual (Firestore + GCS). Uso: npm run backup:daily */
import { runDailyBackup } from '../server/src/backup-service.js';

async function main() {
  console.log('→ Iniciando backup diário…');
  const manifest = await runDailyBackup('cli', { force: true });
  console.log('✓ Backup concluído');
  console.log(`  Data: ${manifest.date}`);
  console.log(`  Firestore: ${manifest.firestore.documents} docs em ${manifest.firestore.collections} coleções`);
  for (const bucket of manifest.gcs.buckets) {
    console.log(`  GCS ${bucket.name}: ${bucket.files} arquivos (${(bucket.bytes / (1024 * 1024)).toFixed(1)} MB)`);
  }
  console.log(`  Total: ${(manifest.totalBytes / (1024 * 1024)).toFixed(1)} MB`);
}

main().catch((err) => {
  console.error('✗ Falha no backup:', err instanceof Error ? err.message : err);
  process.exit(1);
});
