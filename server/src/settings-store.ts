import fs from 'fs';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';
import type {
  AdminSettingsResponse,
  EmailSettings,
  EmailSettingsView,
  IntegrationSettings,
  StoredAppSettings,
} from './types/settings.js';

const SETTINGS_FILE = dataFile('app-settings.json');
const DOC_ID = 'main';
const COLLECTION = 'app_settings';

function defaultIntegrations(): IntegrationSettings {
  return {
    googlePlaceId: process.env.GOOGLE_PLACE_ID || '',
    instagramUsername: process.env.INSTAGRAM_USERNAME || 'andradeconsultoriae',
    instagramProfileUrl:
      process.env.INSTAGRAM_PROFILE_URL || 'https://www.instagram.com/andradeconsultoriae',
    siteUrl: process.env.SITE_URL || 'https://andradeisencoes.com.br',
  };
}

function readFileSettings(): StoredAppSettings {
  try {
    return JSON.parse(fs.readFileSync(SETTINGS_FILE, 'utf-8')) as StoredAppSettings;
  } catch {
    return {};
  }
}

async function readStoredSettings(): Promise<StoredAppSettings> {
  if (useFirestore) {
    const db = await getFirestore();
    const doc = await db.collection(COLLECTION).doc(DOC_ID).get();
    if (doc.exists) {
      return (doc.data() as StoredAppSettings) || {};
    }
    const bootstrap = readFileSettings();
    if (Object.keys(bootstrap).length > 0) {
      await writeStoredSettings(bootstrap);
    }
    return bootstrap;
  }
  return readFileSettings();
}

async function writeStoredSettings(data: StoredAppSettings): Promise<void> {
  const updatedAt = new Date().toISOString();
  const payload = { ...data, updatedAt };
  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(DOC_ID).set(stripUndefined(payload));
    return;
  }
  fs.writeFileSync(SETTINGS_FILE, JSON.stringify(payload, null, 2), 'utf-8');
}

export function resolveEmailSettings(stored: StoredAppSettings): EmailSettings {
  const fromStore = stored.email || {};
  return {
    smtpHost: fromStore.smtpHost || process.env.SMTP_HOST || '',
    smtpPort: fromStore.smtpPort ?? Number(process.env.SMTP_PORT || 587),
    smtpSecure: fromStore.smtpSecure ?? process.env.SMTP_SECURE === 'true',
    smtpUser: fromStore.smtpUser || process.env.SMTP_USER || '',
    smtpFrom: fromStore.smtpFrom || process.env.SMTP_FROM || process.env.SMTP_USER || '',
    contactEmail:
      fromStore.contactEmail || process.env.CONTACT_EMAIL || 'comercial@andradeisencoes.com.br',
    smtpPass: fromStore.smtpPass || process.env.SMTP_PASS || '',
  };
}

function emailSource(stored: StoredAppSettings): EmailSettingsView['source'] {
  const hasStore = Boolean(stored.email && Object.keys(stored.email).length > 0);
  const hasEnv = Boolean(process.env.SMTP_HOST || process.env.SMTP_USER);
  if (hasStore && hasEnv) return 'mixed';
  if (hasStore) return 'settings';
  return 'env';
}

function resolveIntegrations(stored: StoredAppSettings): IntegrationSettings {
  const defaults = defaultIntegrations();
  return {
    googlePlaceId: stored.integrations?.googlePlaceId ?? defaults.googlePlaceId,
    instagramUsername: stored.integrations?.instagramUsername ?? defaults.instagramUsername,
    instagramProfileUrl: stored.integrations?.instagramProfileUrl ?? defaults.instagramProfileUrl,
    siteUrl: stored.integrations?.siteUrl ?? defaults.siteUrl,
  };
}

export function validateEmailConfig(config: EmailSettings): string | null {
  if (!config.smtpHost?.trim()) return 'Servidor SMTP (host) não configurado.';
  if (!config.smtpUser?.trim()) return 'Usuário SMTP não configurado.';
  if (!config.smtpPass?.trim()) {
    return 'Senha SMTP não configurada. Defina no painel ou no Secret Manager do servidor.';
  }
  if (!config.contactEmail?.trim()) return 'E-mail de destino dos contatos não configurado.';
  return null;
}

export async function getAdminSettings(): Promise<AdminSettingsResponse> {
  const stored = await readStoredSettings();
  const emailResolved = resolveEmailSettings(stored);
  const integrations = resolveIntegrations(stored);
  const configError = validateEmailConfig(emailResolved);

  return {
    email: {
      smtpHost: emailResolved.smtpHost,
      smtpPort: emailResolved.smtpPort,
      smtpSecure: emailResolved.smtpSecure,
      smtpUser: emailResolved.smtpUser,
      smtpFrom: emailResolved.smtpFrom,
      contactEmail: emailResolved.contactEmail,
      smtpPassConfigured: Boolean(emailResolved.smtpPass?.trim()),
      source: emailSource(stored),
    },
    emailStatus: {
      ok: !configError,
      message: configError,
    },
    integrations,
    integrationStatus: {
      googleReviews: {
        configured: Boolean(process.env.GOOGLE_PLACES_API_KEY && integrations.googlePlaceId),
        source: process.env.GOOGLE_PLACES_API_KEY ? 'env (API key)' : 'não configurado',
      },
      instagram: {
        configured: Boolean(process.env.INSTAGRAM_ACCESS_TOKEN),
        source: process.env.INSTAGRAM_ACCESS_TOKEN ? 'env (token)' : 'não configurado',
      },
      analytics: {
        configured: Boolean(process.env.GA_MEASUREMENT_ID),
        measurementId: process.env.GA_MEASUREMENT_ID || null,
      },
    },
    updatedAt: stored.updatedAt || null,
  };
}

export async function getResolvedEmailSettings(): Promise<EmailSettings> {
  const stored = await readStoredSettings();
  return resolveEmailSettings(stored);
}

export async function updateEmailSettings(
  patch: Partial<EmailSettings> & { smtpPass?: string | null; clearSmtpPass?: boolean }
): Promise<AdminSettingsResponse> {
  const stored = await readStoredSettings();
  const current = resolveEmailSettings(stored);
  const nextEmail: Partial<EmailSettings> = {
    smtpHost: patch.smtpHost ?? current.smtpHost,
    smtpPort: patch.smtpPort ?? current.smtpPort,
    smtpSecure: patch.smtpSecure ?? current.smtpSecure,
    smtpUser: patch.smtpUser ?? current.smtpUser,
    smtpFrom: patch.smtpFrom ?? current.smtpFrom,
    contactEmail: patch.contactEmail ?? current.contactEmail,
  };

  if (patch.smtpPass !== undefined && patch.smtpPass !== null && patch.smtpPass !== '') {
    nextEmail.smtpPass = patch.smtpPass;
  } else if (stored.email?.smtpPass) {
    nextEmail.smtpPass = stored.email.smtpPass;
  } else if (current.smtpPass) {
    nextEmail.smtpPass = current.smtpPass;
  }

  await writeStoredSettings({
    ...stored,
    email: nextEmail,
  });

  return getAdminSettings();
}

export async function updateIntegrationSettings(
  patch: Partial<IntegrationSettings>
): Promise<AdminSettingsResponse> {
  const stored = await readStoredSettings();
  await writeStoredSettings({
    ...stored,
    integrations: {
      ...resolveIntegrations(stored),
      ...patch,
    },
  });
  return getAdminSettings();
}
