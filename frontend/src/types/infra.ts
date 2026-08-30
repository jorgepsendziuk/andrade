export interface GcsBucketInfo {
  name: string;
  publicUrl?: string;
  location?: string;
  storageClass?: string;
  bytes?: number;
  private?: boolean;
}

export interface BackupStatus {
  enabled: boolean;
  bucket: string | null;
  retentionDays: number;
  schedule: string;
  schedulerConfigured: boolean;
  lastRun: {
    date: string;
    startedAt: string;
    completedAt: string;
    trigger: string;
    status: 'success' | 'partial' | 'failed';
    firestore: { collections: number; documents: number; bytes: number };
    gcs: { buckets: { name: string; files: number; bytes: number }[] };
    totalBytes: number;
    error?: string;
  } | null;
  storageBytes: number;
  estimatedMonthlyCostUsd: number;
  recentRuns: { date: string; status: string; totalBytes: number }[];
}

export interface InfraInfo {
  environment: 'cloud-run' | 'local' | 'vercel';
  projectId: string | null;
  projectNumber: string | null;
  region: string;
  serviceName: string;
  siteUrl: string;
  gcs: {
    enabled: boolean;
    bucket: GcsBucketInfo | null;
    docsBucket: GcsBucketInfo | null;
  };
  firestore: {
    enabled: boolean;
    collections: { name: string; description: string }[];
  };
  backup: BackupStatus;
  secrets: string[];
  ga4: {
    propertyId: string;
    configured: boolean;
  };
  serviceAccounts: {
    cloudRun: string | null;
    ga4Reader: string | null;
  };
  dns: {
    zone: string;
    domain: string;
  };
  loadBalancer: {
    name: string;
    globalIpName: string;
  };
  gcpAccount: string;
  billing: BillingSummary;
}

export type BillingSource = 'billing-export' | 'estimate';

export interface ExchangeRate {
  usdToBrl: number;
  fetchedAt: string;
  source: string;
}

export interface BillingServiceCost {
  serviceId: string;
  label: string;
  amount: number;
  currency: string;
  source: BillingSource;
  details?: string[];
}

export interface BillingSummary {
  source: BillingSource;
  configured: boolean;
  periodLabel: string;
  periodStart: string;
  periodEnd: string;
  currency: string;
  total: number;
  totalUsd: number;
  totalBrl: number;
  exchangeRate: ExchangeRate;
  services: BillingServiceCost[];
  billingAccountId: string;
  reportsUrl: string;
  setupHint?: string;
  fetchedAt: string;
  environment: 'cloud-run' | 'local' | 'vercel';
}

export interface GcpServiceLink {
  label: string;
  href: string;
}

export interface GcpServiceDoc {
  id: string;
  title: string;
  description: string;
  details: string[];
  links: GcpServiceLink[];
  script?: string;
  cost?: BillingServiceCost;
}
