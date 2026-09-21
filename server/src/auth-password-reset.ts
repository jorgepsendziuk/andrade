import { findClientByEmail, listClients, updateClientPassword } from './clients-store.js';
import { normalizeAuthEmail } from './auth-email.js';
import { sendPasswordResetEmail, sendPortalAccessEmail } from './mail-service.js';
import {
  createPasswordResetToken,
  findValidPasswordResetToken,
  markPasswordResetTokenUsed,
} from './password-reset-store.js';
import { getAdminSettings } from './settings-store.js';
import { findUserByEmail, updatePassword } from './users-store.js';

const GENERIC_MESSAGE =
  'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha em alguns minutos.';

async function getSiteUrl(): Promise<string> {
  const settings = await getAdminSettings();
  return settings.integrations.siteUrl.replace(/\/$/, '');
}

export async function buildClientPasswordResetUrl(
  client: { id: string; email: string }
): Promise<string> {
  const { token } = await createPasswordResetToken({
    email: client.email,
    userId: client.id,
    accountType: 'cliente',
  });
  const siteUrl = await getSiteUrl();
  return `${siteUrl}/entrar/redefinir-senha?token=${encodeURIComponent(token)}`;
}

export async function sendClientPasswordResetLink(client: {
  id: string;
  email: string;
  name: string;
}): Promise<{ sent: boolean; error: string | null }> {
  const resetUrl = await buildClientPasswordResetUrl(client);
  return sendPasswordResetEmail({
    name: client.name,
    email: client.email,
    resetUrl,
  });
}

export async function sendClientPortalAccessEmail(client: {
  id: string;
  email: string;
  name: string;
}): Promise<{ sent: boolean; error: string | null }> {
  const siteUrl = await getSiteUrl();
  const loginUrl = `${siteUrl}/entrar`;
  const resetUrl = await buildClientPasswordResetUrl(client);
  return sendPortalAccessEmail({
    name: client.name,
    email: client.email,
    loginUrl,
    resetUrl,
  });
}

export async function sendPasswordResetToAllActiveClients(options?: {
  limit?: number;
  delayMs?: number;
}): Promise<{
  total: number;
  sent: number;
  failed: { email: string; error: string }[];
}> {
  const limit = options?.limit ?? 5000;
  const delayMs = options?.delayMs ?? 300;
  const clients = (await listClients(limit)).filter((c) => c.active !== false);
  const failed: { email: string; error: string }[] = [];
  let sent = 0;

  for (const client of clients) {
    const result = await sendClientPasswordResetLink(client);
    if (result.sent) {
      sent += 1;
      console.log(`✓ ${client.email}`);
    } else {
      failed.push({ email: client.email, error: result.error || 'Falha ao enviar.' });
      console.error(`✗ ${client.email}: ${result.error || 'Falha ao enviar.'}`);
    }
    if (delayMs > 0) {
      await new Promise((resolve) => setTimeout(resolve, delayMs));
    }
  }

  return { total: clients.length, sent, failed };
}

export async function requestPasswordReset(email: string): Promise<{ message: string; sent: boolean }> {
  const normalized = normalizeAuthEmail(email);
  if (!normalized) {
    throw new Error('Informe seu e-mail.');
  }

  const client = await findClientByEmail(normalized);
  if (client && client.active !== false) {
    const { sent } = await sendClientPasswordResetLink(client);
    return { message: GENERIC_MESSAGE, sent };
  }

  const staff = await findUserByEmail(normalized);
  if (staff && staff.active !== false) {
    const { token } = await createPasswordResetToken({
      email: staff.email,
      userId: staff.id,
      accountType: 'staff',
    });
    const siteUrl = await getSiteUrl();
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
  const password = newPassword.trim();
  if (!password || password.length < 8) {
    throw new Error('A nova senha deve ter no mínimo 8 caracteres.');
  }

  const record = await findValidPasswordResetToken(token.trim());
  if (!record) throw new Error('Link inválido ou expirado.');

  if (record.accountType === 'cliente') {
    const ok = await updateClientPassword(record.userId, password);
    if (!ok) throw new Error('Não foi possível redefinir a senha.');
  } else {
    const ok = await updatePassword(record.userId, password);
    if (!ok) throw new Error('Não foi possível redefinir a senha.');
  }

  await markPasswordResetTokenUsed(record.id);
}
