import { describe, expect, it } from 'vitest';
import { upsertConductors } from '../../conductors-store.js';
import { getDataDir } from '../../data-paths.js';
import fs from 'fs';
import path from 'path';

describe('conductors-store', () => {
  it('grava condutores sem campo rg quando ausente', async () => {
    const processId = 'proc-conductor-test';
    const clientId = 'client-conductor-test';

    const records = await upsertConductors(processId, clientId, [
      { nome: 'Condutor Um', cpf: '12345678901' },
    ]);

    expect(records).toHaveLength(1);
    expect(records[0].rg).toBeUndefined();

    const raw = JSON.parse(fs.readFileSync(path.join(getDataDir(), 'conductors.json'), 'utf-8'));
    const saved = raw.find((r: { processId: string }) => r.processId === processId);
    expect(saved.rg).toBeUndefined();
    expect(Object.keys(saved)).not.toContain('rg');
  });
});
