import { mapMailError } from './mail-errors.js';
import { getResolvedEmailSettings, validateEmailConfig } from './settings-store.js';
import type { EmailSettings } from './types/settings.js';

async function createTransporter(config: EmailSettings) {
  const nodemailer = await import('nodemailer');
  return nodemailer.createTransport({
    host: config.smtpHost,
    port: config.smtpPort,
    secure: config.smtpSecure,
    auth: {
      user: config.smtpUser,
      pass: config.smtpPass,
    },
  });
}

export async function sendContactEmail(payload: {
  name: string;
  email: string;
  phone: string;
  message: string;
}): Promise<{ sent: boolean; error: string | null }> {
  const config = await getResolvedEmailSettings();
  const configError = validateEmailConfig(config);
  if (configError) {
    return { sent: false, error: configError };
  }

  try {
    const transporter = await createTransporter(config);
    await transporter.sendMail({
      from: config.smtpFrom || config.smtpUser,
      to: config.contactEmail,
      replyTo: payload.email,
      subject: `Contato via site — ${payload.name}`,
      text: `Nome: ${payload.name}\nE-mail: ${payload.email}\nTelefone: ${payload.phone || '—'}\n\nMensagem:\n${payload.message}`,
    });
    return { sent: true, error: null };
  } catch (err) {
    console.error('contact email error', err);
    return { sent: false, error: mapMailError(err).message };
  }
}

export async function sendPortalWelcomeEmail(payload: {
  name: string;
  email: string;
}): Promise<{ sent: boolean; error: string | null }> {
  const config = await getResolvedEmailSettings();
  const configError = validateEmailConfig(config);
  if (configError) {
    return { sent: false, error: configError };
  }

  try {
    const transporter = await createTransporter(config);
    await transporter.sendMail({
      from: config.smtpFrom || config.smtpUser,
      to: payload.email,
      subject: 'Bem-vindo ao portal Andrade Isenções',
      text: `Olá, ${payload.name}!\n\nSeu cadastro foi realizado com sucesso. Acesse o portal em /entrar para acompanhar seu processo de isenção PCD.\n\nEquipe Andrade Consultoria e Isenções`,
    });
    return { sent: true, error: null };
  } catch (err) {
    console.error('portal welcome email error', err);
    return { sent: false, error: mapMailError(err).message };
  }
}

export async function sendTestEmail(to: string): Promise<{ sent: boolean; error: string | null }> {
  const config = await getResolvedEmailSettings();
  const configError = validateEmailConfig(config);
  if (configError) {
    return { sent: false, error: configError };
  }

  try {
    const transporter = await createTransporter(config);
    await transporter.sendMail({
      from: config.smtpFrom || config.smtpUser,
      to,
      subject: 'Teste de e-mail — Andrade Isenções',
      text: `Este é um e-mail de teste enviado pelo painel administrativo em ${new Date().toLocaleString('pt-BR', { timeZone: 'America/Cuiaba' })}.\n\nSe você recebeu esta mensagem, o SMTP está configurado corretamente.`,
    });
    return { sent: true, error: null };
  } catch (err) {
    console.error('test email error', err);
    return { sent: false, error: mapMailError(err).message };
  }
}
