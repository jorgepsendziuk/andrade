import { getContactsStorageMode } from './contacts-store.js';
import { GCS_BUCKET, isGcsEnabled } from './media-store.js';
import { GCS_DOCS_BUCKET, isDocsGcsEnabled } from './docs-store.js';
import { useFirestore } from './firestore-client.js';
import { getBillingSummary } from './billing-info.js';
import { getBackupStatus } from './backup-service.js';
import type { GcsBucketInfo, InfraInfo } from './types/infra.js';

const DEFAULT_REGION = 'southamerica-east1';
const DEFAULT_SERVICE = 'andrade-isencoes';
const DEFAULT_PROJECT = 'smart-tractor-257319';
const GCP_ACCOUNT = 'andradeisencoescloud@gmail.com';

const FIRESTORE_COLLECTIONS = [
  { name: 'site_content', description: 'Conteúdo do site (CMS)' },
  { name: 'contact_submissions', description: 'Formulários de contato recebidos' },
  { name: 'admin_users', description: 'Usuários do painel administrativo' },
  { name: 'app_settings', description: 'Configurações de e-mail e integrações' },
  { name: 'clients', description: 'Cadastro de clientes do portal' },
  { name: 'processes', description: 'Processos de isenção PCD/Táxi' },
  { name: 'process_files', description: 'Metadados de documentos anexados' },
  { name: 'conductors', description: 'Condutores cadastrados por processo' },
  { name: 'audit_logs', description: 'Trilha de auditoria LGPD' },
  { name: 'staff_alerts', description: 'Alertas da equipe sobre edições do cliente' },
] as const;

const SECRET_NAMES = ['SMTP_PASS', 'JWT_SECRET', 'GA4_SERVICE_ACCOUNT_JSON', 'BACKUP_CRON_SECRET'] as const;

function detectEnvironment(): InfraInfo['environment'] {
  if (process.env.K_SERVICE) return 'cloud-run';
  if (process.env.VERCEL) return 'vercel';
  return 'local';
}

async function fetchGcsBucketInfo(
  projectId: string | null,
  bucketName: string,
  options: { publicUrl?: string; isPrivate?: boolean } = {}
): Promise<GcsBucketInfo | null> {
  if (!bucketName) return null;

  const base: GcsBucketInfo = {
    name: bucketName,
    private: options.isPrivate,
    publicUrl: options.publicUrl,
  };

  if (!isGcsEnabled() && !isDocsGcsEnabled()) return base;

  try {
    const { Storage } = await import('@google-cloud/storage');
    const storage = new Storage({ projectId: projectId || undefined });
    const bucket = storage.bucket(bucketName);
    const [metadata] = await bucket.getMetadata();
    const [files] = await bucket.getFiles({ autoPaginate: true });
    const bytes = files.reduce((sum, f) => sum + Number(f.metadata.size ?? 0), 0);
    return {
      ...base,
      location: metadata.location ?? undefined,
      storageClass: metadata.storageClass ?? undefined,
      bytes,
    };
  } catch {
    return base;
  }
}

export async function getInfraInfo(forceBilling = false): Promise<InfraInfo> {
  const projectId =
    process.env.GOOGLE_CLOUD_PROJECT || process.env.GCP_PROJECT || DEFAULT_PROJECT;
  const projectNumber = process.env.GCP_PROJECT_NUMBER || null;
  const region = process.env.GCP_REGION || DEFAULT_REGION;
  const serviceName = process.env.K_SERVICE || process.env.GCP_SERVICE || DEFAULT_SERVICE;
  const siteUrl = process.env.SITE_URL || 'https://andradeisencoes.com.br';
  const ga4PropertyId = process.env.GA4_PROPERTY_ID || '347102827';
  const ga4Configured = Boolean(process.env.GA4_SERVICE_ACCOUNT_JSON?.trim());

  const bucket = await fetchGcsBucketInfo(projectId, GCS_BUCKET, {
    publicUrl: GCS_BUCKET ? `https://storage.googleapis.com/${GCS_BUCKET}/` : undefined,
  });
  const docsBucket = await fetchGcsBucketInfo(projectId, GCS_DOCS_BUCKET, { isPrivate: true });
  const environment = detectEnvironment();
  const backup = await getBackupStatus();

  const billing = await getBillingSummary({
    projectId,
    serviceName,
    environment,
    secretCount: SECRET_NAMES.length,
    force: forceBilling,
    backupStorageBytes: backup.storageBytes,
  });

  return {
    environment,
    projectId,
    projectNumber,
    region,
    serviceName,
    siteUrl,
    gcs: {
      enabled: isGcsEnabled(),
      bucket,
      docsBucket,
    },
    firestore: {
      enabled: useFirestore || getContactsStorageMode() === 'firestore',
      collections: [...FIRESTORE_COLLECTIONS],
    },
    backup,
    secrets: [...SECRET_NAMES],
    ga4: {
      propertyId: ga4PropertyId,
      configured: ga4Configured,
    },
    serviceAccounts: {
      cloudRun: projectNumber ? `${projectNumber}-compute@developer.gserviceaccount.com` : null,
      ga4Reader: projectId ? `andrade-ga4-reader@${projectId}.iam.gserviceaccount.com` : null,
    },
    dns: {
      zone: 'andrade',
      domain: 'andradeisencoes.com.br',
    },
    loadBalancer: {
      name: 'andrade-isencoes-lb',
      globalIpName: 'andrade-isencoes-ip',
    },
    gcpAccount: GCP_ACCOUNT,
    billing,
  };
}
