import { describe, expect, it } from 'vitest';
import { createClient } from '../../clients-store.js';
import { createProcess, findProcessById } from '../../processes-store.js';

describe('processes-store', () => {
  it('cria processo PCD vinculado ao cliente', async () => {
    const client = await createClient({
      email: `proc-${Date.now()}@test.com`,
      name: 'Processo Teste',
      password: 'Senha@1234',
      cpf: `${Date.now()}`.slice(-11).padStart(11, '2'),
    });

    const process = await createProcess({ clientId: client.id, modality: 'pcd' });
    expect(process.clientId).toBe(client.id);
    expect(process.status).toBe('ativo');
    expect(process.currentStep).toBe('documentacao');

    const loaded = await findProcessById(process.id);
    expect(loaded?.storageSlug).toBeTruthy();
  });
});
