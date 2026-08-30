import { describe, expect, it } from 'vitest';
import { createClient, findClientByEmail } from '../../clients-store.js';
import { getDataDir } from '../../data-paths.js';
import fs from 'fs';
import path from 'path';

describe('clients-store', () => {
  it('cria cliente sem RG sem gravar undefined no JSON', async () => {
    const email = `sem-rg-${Date.now()}@test.com`;
    const client = await createClient({
      email,
      name: 'Cliente Sem RG',
      password: 'Senha@1234',
      cpf: `${Date.now()}`.slice(-11).padStart(11, '1'),
    });

    expect(client.email).toBe(email);
    expect(client.rg).toBeUndefined();

    const raw = fs.readFileSync(path.join(getDataDir(), 'clients.json'), 'utf-8');
    expect(raw).not.toContain('"rg": undefined');
    expect(JSON.parse(raw).find((c: { id: string }) => c.id === client.id).rg).toBeUndefined();

    const found = await findClientByEmail(email);
    expect(found?.name).toBe('Cliente Sem RG');
  });
});
