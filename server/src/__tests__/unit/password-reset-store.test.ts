import { describe, expect, it } from 'vitest';
import {
  createPasswordResetToken,
  findValidPasswordResetToken,
  markPasswordResetTokenUsed,
} from '../../password-reset-store.js';

describe('password-reset-store', () => {
  it('cria e valida token de recuperação', async () => {
    const { token } = await createPasswordResetToken({
      email: 'cliente@test.com',
      userId: 'client-1',
      accountType: 'cliente',
    });

    const record = await findValidPasswordResetToken(token);
    expect(record?.email).toBe('cliente@test.com');
    expect(record?.userId).toBe('client-1');
  });

  it('invalida token após uso', async () => {
    const { token, record } = await createPasswordResetToken({
      email: 'staff@test.com',
      userId: 'user-1',
      accountType: 'staff',
    });

    await markPasswordResetTokenUsed(record.id);
    const afterUse = await findValidPasswordResetToken(token);
    expect(afterUse).toBeNull();
  });
});
