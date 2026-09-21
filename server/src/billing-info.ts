import { GoogleAuth } from 'google-auth-library';
import { GCS_BUCKET, isGcsEnabled } from './media-store.js';
import { GCS_DOCS_BUCKET, isDocsGcsEnabled } from './docs-store.js';
import { getFirestore, useFirestore } from './firestore-client.js';
import { convertToBrl, convertToUsd, getUsdToBrlRate } from './exchange-rate.js';
import { BACKUP_NEARLINE_USD_PER_GB_MONTH, FIRESTORE_BACKUP_COLLECTIONS } from './backup-service.js';
import type { BillingServiceCost, BillingSummary } from './types/infra.js';

const DEFAULT_BILLING_ACCOUNT = '017163-F935E4-FCF7C4';
const CACHE_TTL_MS = 15 * 60 * 1000;

/** Preços aproximados em USD (região southamerica-east1, site de baixo tráfego). */
const PRICING = {
  gcsPerGbMonth: 0.023,
  gcsNearlinePerGbMonth: BACKUP_NEARLINE_USD_PER_GB_MONTH,
  cloudRunPerMillionRequests: 0.4,
  cloudRunLowTrafficMonth: 1.5,
  firestorePerGbMonth: 0.18,
  firestorePer100kReads: 0.06,
  dnsZoneMonth: 0.2,
  lbForwardingRuleMonth: 18,
  lbPerGbProcessed: 0.01,
  secretPerVersionMonth: 0.06,
  buildPerMinute: 0.003,
} as const;

const GCP_SERVICE_TO_ID: Record<string, string> = {
  'Cloud Run': 'cloud-run',
  'Cloud Storage': 'gcs',
  'Cloud Firestore': 'firestore',
  'Secret Manager': 'secret-manager',
  'Cloud DNS': 'cloud-dns',
  'Cloud Load Balancing': 'load-balancer',
  'Firebase Hosting': 'firebase-hosting',
  'Networking': 'load-balancer',
  'Cloud Build': 'cloud-build',
  'Artifact Registry': 'cloud-build',
  'Compute Engine': 'compute-engine',
  'BigQuery': 'billing-export',
  'Analytics Hub': 'ga4-api',
};

const SERVICE_LABELS: Record<string, string> = {
  'cloud-run': 'Cloud Run',
  gcs: 'Cloud Storage',
  firestore: 'Cloud Firestore',
  'secret-manager': 'Secret Manager',
  'cloud-dns': 'Cloud DNS',
  'load-balancer': 'Load Balancer + SSL (legado)',
  'firebase-hosting': 'Firebase Hosting',
  'cloud-build': 'Cloud Build + Artifact Registry',
  'compute-engine': 'Compute Engine',
  'ga4-api': 'Google Analytics Data API',
  'billing-export': 'BigQuery (exportação de faturamento)',
  backup: 'Backup diário (Cloud Storage Nearline)',
};

let cached: BillingSummary | null = null;
let cachedAt = 0;

function monthBounds(): { start: string; end: string; label: string } {
  const now = new Date();
  const start = new Date(now.getFullYear(), now.getMonth(), 1);
  const end = now;
  const fmt = (d: Date) => d.toISOString().slice(0, 10);
  const monthName = start.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
  return {
    start: fmt(start),
    end: fmt(end),
    label: monthName.charAt(0).toUpperCase() + monthName.slice(1),
  };
}

function round2(n: number): number {
  return Math.round(n * 100) / 100;
}

function billingTableIds(accountId: string): string[] {
  const suffix = accountId.replace(/-/g, '_');
  return [
    `gcp_billing_export_v1_${suffix}`,
    `gcp_billing_export_resource_v1_${suffix}`,
  ];
}

function parseBillingRows(rows: { f: { v: string }[] }[]): BillingServiceCost[] {
  if (!rows.length) return [];

  const currency = rows[0]?.f[1]?.v || 'USD';
  const byId = new Map<string, BillingServiceCost>();

  for (const row of rows) {
    const serviceName = row.f[0]?.v ?? 'Outros';
    const cost = Number(row.f[2]?.v ?? 0);
    const serviceId = GCP_SERVICE_TO_ID[serviceName] ?? 'other';
    const existing = byId.get(serviceId);
    if (existing) {
      existing.amount = round2(existing.amount + cost);
      existing.details?.push(`${serviceName}: ${currency} ${cost.toFixed(2)}`);
    } else {
      byId.set(serviceId, {
        serviceId,
        label: SERVICE_LABELS[serviceId] ?? serviceName,
        amount: round2(cost),
        currency,
        source: 'billing-export',
        details: [`${serviceName}: ${currency} ${cost.toFixed(2)}`],
      });
    }
  }

  return [...byId.values()];
}

async function runBillingQuery(
  client: Awaited<ReturnType<GoogleAuth['getClient']>>,
  projectId: string,
  dataset: string,
  table: string,
  filterProjectId: string
): Promise<BillingServiceCost[] | null> {
  const query = `
    SELECT
      service.description AS service_name,
      currency,
      ROUND(SUM(cost), 4) AS total_cost
    FROM \`${projectId}.${dataset}.${table}\`
    WHERE project.id = @filterProject
      AND DATE(usage_start_time) >= DATE_TRUNC(CURRENT_DATE(), MONTH)
      AND cost_type IN ('regular', 'tax', 'adjustment')
    GROUP BY service_name, currency
    HAVING total_cost != 0
    ORDER BY total_cost DESC
  `;

  const res = await client.request<{ rows?: { f: { v: string }[] }[] }>({
    url: `https://bigquery.googleapis.com/bigquery/v2/projects/${projectId}/queries`,
    method: 'POST',
    data: {
      query,
      useLegacySql: false,
      parameterMode: 'NAMED',
      queryParameters: [
        {
          name: 'filterProject',
          parameterType: { type: 'STRING' },
          parameterValue: { value: filterProjectId },
        },
      ],
    },
  });

  const rows = res.data.rows ?? [];
  if (!rows.length) return null;
  return parseBillingRows(rows);
}

async function queryBillingExport(
  projectId: string,
  dataset: string,
  billingAccountId: string,
  filterProjectId: string
): Promise<BillingServiceCost[] | null> {
  const auth = new GoogleAuth({
    scopes: ['https://www.googleapis.com/auth/bigquery.readonly'],
  });
  const client = await auth.getClient();

  for (const table of billingTableIds(billingAccountId)) {
    try {
      const services = await runBillingQuery(client, projectId, dataset, table, filterProjectId);
      if (services?.length) return services;
    } catch (err) {
      const message = err instanceof Error ? err.message : String(err);
      if (message.includes('Not found') || message.includes('notFound')) continue;
      console.warn(`billing export query failed (${table})`, message);
    }
  }

  return null;
}

async function fetchMonitoringSum(
  projectId: string,
  metricType: string,
  extraFilter: string,
  days: number
): Promise<number | null> {
  try {
    const auth = new GoogleAuth({
      scopes: ['https://www.googleapis.com/auth/monitoring.read'],
    });
    const client = await auth.getClient();
    const end = new Date();
    const start = new Date(end.getTime() - days * 24 * 60 * 60 * 1000);
    const filter = `metric.type="${metricType}" AND ${extraFilter}`;
    const params = new URLSearchParams({
      filter,
      'interval.startTime': start.toISOString(),
      'interval.endTime': end.toISOString(),
      'aggregation.alignmentPeriod': `${Math.min(days, 31) * 86400}s`,
      'aggregation.perSeriesAligner': 'ALIGN_SUM',
    });

    const res = await client.request<{
      timeSeries?: { points?: { value?: { doubleValue?: number; int64Value?: string } }[] }[];
    }>({
      url: `https://monitoring.googleapis.com/v3/projects/${projectId}/timeSeries?${params}`,
    });

    let total = 0;
    for (const series of res.data.timeSeries ?? []) {
      for (const point of series.points ?? []) {
        const v = point.value?.doubleValue ?? Number(point.value?.int64Value ?? 0);
        if (Number.isFinite(v)) total += v;
      }
    }
    return total;
  } catch {
    return null;
  }
}

async function getGcsBucketBytes(projectId: string, bucketName: string): Promise<number> {
  if (!bucketName) return 0;
  try {
    const { Storage } = await import('@google-cloud/storage');
    const storage = new Storage({ projectId });
    const [files] = await storage.bucket(bucketName).getFiles({ autoPaginate: true });
    return files.reduce((sum, f) => sum + Number(f.metadata.size ?? 0), 0);
  } catch {
    return 0;
  }
}

async function getGcsBytes(projectId: string): Promise<{ media: number; docs: number; total: number }> {
  const media = isGcsEnabled() && GCS_BUCKET ? await getGcsBucketBytes(projectId, GCS_BUCKET) : 0;
  const docs = isDocsGcsEnabled() && GCS_DOCS_BUCKET ? await getGcsBucketBytes(projectId, GCS_DOCS_BUCKET) : 0;
  return { media, docs, total: media + docs };
}

async function estimateFirestoreUsage(projectId: string): Promise<{
  docs: number;
  estimatedBytes: number;
}> {
  if (!useFirestore) return { docs: 0, estimatedBytes: 0 };
  try {
    const db = await getFirestore();
    const collections = [...FIRESTORE_BACKUP_COLLECTIONS];
    let docs = 0;
    for (const name of collections) {
      const snap = await db.collection(name).count().get();
      docs += snap.data().count;
    }
    return { docs, estimatedBytes: docs * 4096 };
  } catch {
    return { docs: 0, estimatedBytes: 0 };
  }
}

async function buildBackupCostEstimate(backupStorageBytes: number): Promise<BillingServiceCost> {
  const gb = backupStorageBytes / 1024 ** 3;
  const amount = round2(gb * PRICING.gcsNearlinePerGbMonth);
  return {
    serviceId: 'backup',
    label: SERVICE_LABELS.backup,
    amount,
    currency: 'USD',
    source: 'estimate',
    details: [
      backupStorageBytes > 0
        ? `Armazenamento de backup: ${(backupStorageBytes / (1024 * 1024)).toFixed(1)} MB`
        : 'Sem backups ainda (custo após primeira execução)',
      `Nearline ~US$ ${PRICING.gcsNearlinePerGbMonth}/GB/mês`,
      'Retenção configurável (padrão 90 dias)',
    ],
  };
}

function mergeBackupCost(services: BillingServiceCost[], backupCost: BillingServiceCost): BillingServiceCost[] {
  const without = services.filter((s) => s.serviceId !== 'backup');
  if (backupCost.amount > 0 || !without.some((s) => s.serviceId === 'gcs')) {
    return [...without, backupCost];
  }
  return [...without, backupCost];
}

async function buildUsageEstimates(
  projectId: string,
  serviceName: string,
  environment: BillingSummary['environment'],
  secretCount: number,
  backupStorageBytes: number
): Promise<BillingServiceCost[]> {
  const daysInMonth = new Date().getDate();
  const monthFraction = daysInMonth / 30;
  const costs: BillingServiceCost[] = [];

  const gcs = await getGcsBytes(projectId);
  const gcsGb = gcs.total / 1024 ** 3;
  const gcsMonthly = gcsGb * PRICING.gcsPerGbMonth;
  costs.push({
    serviceId: 'gcs',
    label: SERVICE_LABELS.gcs,
    amount: round2(gcsMonthly),
    currency: 'USD',
    source: 'estimate',
    details: [
      gcs.media > 0 ? `Mídia (${GCS_BUCKET}): ${(gcs.media / (1024 * 1024)).toFixed(1)} MB` : null,
      gcs.docs > 0 ? `Documentos (${GCS_DOCS_BUCKET}): ${(gcs.docs / (1024 * 1024)).toFixed(1)} MB` : null,
      `Tarifa ~US$ ${PRICING.gcsPerGbMonth}/GB/mês (Standard)`,
    ].filter(Boolean) as string[],
  });

  const requests = await fetchMonitoringSum(
    projectId,
    'run.googleapis.com/request_count',
    `resource.labels.service_name="${serviceName}"`,
    daysInMonth
  );
  const requestCost =
    requests != null
      ? (requests / 1_000_000) * PRICING.cloudRunPerMillionRequests
      : environment === 'cloud-run'
        ? PRICING.cloudRunLowTrafficMonth * monthFraction
        : 0;
  costs.push({
    serviceId: 'cloud-run',
    label: SERVICE_LABELS['cloud-run'],
    amount: round2(requestCost),
    currency: 'USD',
    source: 'estimate',
    details: [
      requests != null
        ? `Requisições no mês: ~${Math.round(requests).toLocaleString('pt-BR')}`
        : 'Tráfego baixo estimado (sem métricas)',
      '512 MiB, escala 0–3 instâncias',
    ],
  });

  const firestore = await estimateFirestoreUsage(projectId);
  const firestoreGb = firestore.estimatedBytes / (1024 ** 3);
  const firestoreCost = Math.max(0, firestoreGb - 1) * PRICING.firestorePerGbMonth;
  costs.push({
    serviceId: 'firestore',
    label: SERVICE_LABELS.firestore,
    amount: round2(firestoreCost),
    currency: 'USD',
    source: 'estimate',
    details: [
      `${firestore.docs} documentos em ${FIRESTORE_BACKUP_COLLECTIONS.length} coleções`,
      firestoreCost === 0 ? 'Dentro do free tier' : `~${firestoreGb.toFixed(3)} GB estimados`,
    ],
  });

  costs.push({
    serviceId: 'secret-manager',
    label: SERVICE_LABELS['secret-manager'],
    amount: round2(secretCount * PRICING.secretPerVersionMonth * monthFraction),
    currency: 'USD',
    source: 'estimate',
    details: [`${secretCount} secrets × US$ ${PRICING.secretPerVersionMonth}/versão/mês`],
  });

  if (environment === 'cloud-run') {
    costs.push({
      serviceId: 'firebase-hosting',
      label: SERVICE_LABELS['firebase-hosting'],
      amount: 0,
      currency: 'USD',
      source: 'estimate',
      details: ['Domínio + SSL + CDN — free tier (10 GB/mês)'],
    });

    // LB legado — remover após migração Firebase
    if (process.env.USE_LOAD_BALANCER === 'true') {
      costs.push({
        serviceId: 'load-balancer',
        label: SERVICE_LABELS['load-balancer'],
        amount: round2(PRICING.lbForwardingRuleMonth * monthFraction),
        currency: 'USD',
        source: 'estimate',
        details: [
          `Regra de encaminhamento ~US$ ${PRICING.lbForwardingRuleMonth}/mês`,
          'Remover após migração Firebase Hosting',
        ],
      });
    }

    costs.push({
      serviceId: 'cloud-dns',
      label: SERVICE_LABELS['cloud-dns'],
      amount: round2(PRICING.dnsZoneMonth * monthFraction),
      currency: 'USD',
      source: 'estimate',
      details: ['1 zona DNS (andrade)', 'Consultas: volume baixo'],
    });

    costs.push({
      serviceId: 'cloud-build',
      label: SERVICE_LABELS['cloud-build'],
      amount: round2(0.15 * monthFraction),
      currency: 'USD',
      source: 'estimate',
      details: ['Deploys ocasionais via Cloud Build'],
    });
  }

  costs.push({
    serviceId: 'ga4-api',
    label: SERVICE_LABELS['ga4-api'],
    amount: 0,
    currency: 'USD',
    source: 'estimate',
    details: ['API de leitura — geralmente sem custo no volume atual'],
  });

  costs.push(await buildBackupCostEstimate(backupStorageBytes));

  return costs;
}

export async function getBillingSummary(options: {
  projectId: string;
  serviceName: string;
  environment: 'cloud-run' | 'local' | 'vercel';
  secretCount: number;
  force?: boolean;
  backupStorageBytes?: number;
}): Promise<BillingSummary> {
  const now = Date.now();
  if (!options.force && cached && now - cachedAt < CACHE_TTL_MS) {
    return cached;
  }

  const { start, end, label } = monthBounds();
  const bqProject = process.env.BILLING_BQ_PROJECT || options.projectId;
  const bqDataset = process.env.BILLING_BQ_DATASET || '';
  const billingAccount = process.env.BILLING_ACCOUNT_ID || DEFAULT_BILLING_ACCOUNT;
  const tryBillingExport = Boolean(bqDataset);

  let services: BillingServiceCost[] | null = null;
  let source: BillingSummary['source'] = 'estimate';

  const backupStorageBytes = options.backupStorageBytes ?? 0;
  const backupCost = await buildBackupCostEstimate(backupStorageBytes);

  if (tryBillingExport) {
    services = await queryBillingExport(bqProject, bqDataset, billingAccount, options.projectId);
    if (services?.length) {
      source = 'billing-export';
      services = mergeBackupCost(services, backupCost);
    }
  }

  if (!services?.length) {
    services = await buildUsageEstimates(
      options.projectId,
      options.serviceName,
      options.environment,
      options.secretCount,
      backupStorageBytes
    );
    source = 'estimate';
  }

  const currency = services[0]?.currency ?? 'USD';
  const total = round2(services.reduce((sum, s) => sum + s.amount, 0));
  const exchangeRate = await getUsdToBrlRate(options.force);

  const totalUsd = currency === 'BRL' ? convertToUsd(total, exchangeRate.usdToBrl) : total;
  const totalBrl = currency === 'BRL' ? total : convertToBrl(total, exchangeRate.usdToBrl);

  const summary: BillingSummary = {
    source,
    configured: source === 'billing-export',
    periodLabel: label,
    periodStart: start,
    periodEnd: end,
    currency,
    total,
    totalUsd,
    totalBrl,
    exchangeRate,
    services,
    billingAccountId: billingAccount,
    reportsUrl: `https://console.cloud.google.com/billing/${billingAccount}/reports?project=${options.projectId}`,
    setupHint:
      source === 'estimate'
        ? 'Para custos reais do faturamento, habilite a exportação para BigQuery: bash scripts/gcp-setup-billing-export.sh'
        : undefined,
    fetchedAt: new Date().toISOString(),
    environment: options.environment,
  };

  cached = summary;
  cachedAt = now;
  return summary;
}
