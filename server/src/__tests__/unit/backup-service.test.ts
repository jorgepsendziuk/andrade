import { describe, expect, it } from 'vitest';
import { runDailyBackup } from '../../backup-service.js';
import { getDataDir } from '../../data-paths.js';
import fs from 'fs';
import path from 'path';

describe('backup-service', () => {
  it('executa backup local com Firestore em arquivo', async () => {
    const manifest = await runDailyBackup('cli', { force: true });
    expect(manifest.status).toBe('success');
    expect(manifest.firestore.collections).toBeGreaterThan(0);

    const latestPath = path.join(getDataDir(), 'backups', 'latest.json');
    expect(fs.existsSync(latestPath)).toBe(true);
  });
});
