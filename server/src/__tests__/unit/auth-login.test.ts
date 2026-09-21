import { describe, expect, it } from 'vitest';
import { authenticateUser } from '../../auth-login.js';
import { createClient } from '../../clients-store.js';
import { createUser } from '../../users-store.js';

describe('authenticateUser', () => {
  it('se o e-mail também é da equipe, entra sempre na interface da equipe', async () => {
    const email = `dual-${Date.now()}@test.com`;
    await createUser({
      email,
      name: 'Staff Teste',
      password: 'SenhaStaff@1',
      role: 'comercial',
    });
    await createClient({
      email,
      name: 'Cliente Teste',
      password: 'SenhaCliente@1',
      cpf: `${Date.now()}`.slice(-11).padStart(11, '2'),
    });

    const staffAuth = await authenticateUser(email, 'SenhaStaff@1');
    expect(staffAuth?.user.role).toBe('comercial');

    const clientPasswordAsStaff = await authenticateUser(email, 'SenhaCliente@1');
    expect(clientPasswordAsStaff?.user.role).toBe('comercial');
    expect(clientPasswordAsStaff?.user.name).toBe('Staff Teste');
  });

  it('rejeita senha incorreta sem confundir contas', async () => {
    const email = `wrong-${Date.now()}@test.com`;
    await createUser({
      email,
      name: 'Staff Teste',
      password: 'SenhaStaff@1',
      role: 'admin',
    });

    const auth = await authenticateUser(email, 'SenhaErrada@1');
    expect(auth).toBeNull();
  });
});
