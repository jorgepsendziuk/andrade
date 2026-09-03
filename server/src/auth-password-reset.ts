import { findClientByEmail } from './clients-store.js';
import { updateClientPassword } from './clients-store.js';
import { sendPasswordResetEmail } from './mail-service.js';
import {
  createPasswordResetToken,
  findValidPasswordResetToken,
  markPasswordResetTokenUsed,
} from './password-reset-store.js';
import { getAdminSettings } from './settings-store.js';
import { findUserByEmail, updatePassword } from './users-store.js';

const GENERIC_MESSAGE =
  'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha em alguns minutos.';

export async function requestPasswordReset(email: string): Promise<{ message: string; sent: boolean }> {
  const normalized = email.trim().toLowerCase();
  if (!normalized) {
    throw new Error('Informe seu e-mail.');
  }

  const client = await findClientByEmail(normalized);
  if (client?.active) {
    const { token } = await createPasswordResetToken({
      email: client.email,
      userId: client.id,
      accountType: 'cliente',
    });
    const settings = await getAdminSettings();
    const siteUrl = settings.integrations.siteUrl.replace(/\/$/, '');
    const resetUrl = `${siteUrl}/entrar/redefinir-senha?token=${encodeURIComponent(token)}`;
    const { sent } = await sendPasswordResetEmail({
      name: client.name,
      email: client.email,
      resetUrl,
    });
    return { message: GENERIC_MESSAGE, sent };
  }

  const staff = await findUserByEmail(normalized);
  if (staff && staff.active !== false) {
    const { token } = await createPasswordResetToken({
      email: staff.email,
      userId: staff.id,
      accountType: 'staff',
    });
    const settings = await getAdminSettings();
    const siteUrl = settings.integrations.siteUrl.replace(/\/$/, '');
    const resetUrl = `${siteUrl}/entrar/redefinir-senha?token=${encodeURIComponent(token)}`;
    const { sent } = await sendPasswordResetEmail({
      name: staff.name,
      email: staff.email,
      resetUrl,
    });
    return { message: GENERIC_MESSAGE, sent };
  }

  return { message: GENERIC_MESSAGE, sent: false };
}

export async function resetPasswordWithToken(token: string, newPassword: string): Promise<void> {
  if (!token?.trim()) throw new Error('Link inválido ou expirado.');
  if (!newPassword || newPassword.length < 8) {
    throw new Error('A nova senha deve ter no mínimo 8 caracteres.');
  }

  const record = await findValidPasswordResetToken(token.trim());
  if (!record) throw new Error('Link inválido ou expirado.');

  if (record.accountType === 'cliente') {
    const ok = await updateClientPassword(record.userId, newPassword);
    if (!ok) throw new Error('Não foi possível redefinir a senha.');
  } else {
    const ok = await updatePassword(record.userId, newPassword);
    if (!ok) throw new Error('Não foi possível redefinir a senha.');
  }

  await markPasswordResetTokenUsed(record.id);
}
