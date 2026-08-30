import type { GcpServiceDoc, InfraInfo } from '../types/infra';

export function buildGcpConsoleUrl(
  path: string,
  projectId: string | null | undefined
): string {
  const project = projectId || 'smart-tractor-257319';
  const sep = path.includes('?') ? '&' : '?';
  return `https://console.cloud.google.com${path}${sep}project=${project}`;
}

export function buildGcpServices(infra: InfraInfo | null): GcpServiceDoc[] {
  const projectId = infra?.projectId;
  const region = infra?.region ?? 'southamerica-east1';
  const serviceName = infra?.serviceName ?? 'andrade-isencoes';
  const bucket = infra?.gcs.bucket?.name ?? 'andrade-media';
  const domain = infra?.dns.domain ?? 'andradeisencoes.com.br';
  const dnsZone = infra?.dns.zone ?? 'andrade';
  const lbName = infra?.loadBalancer.name ?? 'andrade-isencoes-lb';
  const ga4PropertyId = infra?.ga4.propertyId ?? '347102827';

  const services: GcpServiceDoc[] = [
    {
      id: 'cloud-run',
      title: 'Cloud Run',
      description: 'Hospeda o site e a API Node.js em produção.',
      details: [
        `Serviço: ${serviceName}`,
        `Região: ${region} (São Paulo)`,
        'Recursos: 512 MiB RAM, 1 vCPU',
        'Escala: 0–3 instâncias (serverless)',
        'Porta: 8080',
      ],
      links: [
        {
          label: 'Console Cloud Run',
          href: buildGcpConsoleUrl(`/run/detail/${region}/${serviceName}`, projectId),
        },
        {
          label: 'Logs do serviço',
          href: buildGcpConsoleUrl(
            `/run/detail/${region}/${serviceName}/logs`,
            projectId
          ),
        },
      ],
      script: 'npm run deploy:cloudrun',
    },
    {
      id: 'gcs',
      title: 'Cloud Storage',
      description: 'Armazena fotos do CMS e documentos privados de clientes.',
      details: [
        `Mídia pública: gs://${bucket}`,
        infra?.gcs.docsBucket?.name
          ? `Documentos privados: gs://${infra.gcs.docsBucket.name}`
          : 'Documentos privados: gs://andrade-docs',
        infra?.gcs.bucket?.location
          ? `Localização: ${infra.gcs.bucket.location}`
          : `Localização: ${region}`,
        infra?.gcs.bucket?.storageClass
          ? `Classe: ${infra.gcs.bucket.storageClass}`
          : 'Classe: Standard',
        'Mídia: leitura pública · Docs: URLs assinadas (LGPD)',
      ],
      links: [
        {
          label: 'Explorar bucket',
          href: buildGcpConsoleUrl(`/storage/browser/${bucket}`, projectId),
        },
        {
          label: 'URL pública',
          href: `https://storage.googleapis.com/${bucket}/`,
        },
      ],
      script: 'bash scripts/gcp-setup-media.sh',
    },
    {
      id: 'firestore',
      title: 'Firestore',
      description: 'Banco de dados NoSQL para conteúdo, contatos, usuários e configurações.',
      details: [
        'Modo: Native',
        ...(infra?.firestore.collections ?? []).map((c) => `Coleção ${c.name}: ${c.description}`),
      ],
      links: [
        {
          label: 'Console Firestore',
          href: buildGcpConsoleUrl('/firestore/databases/-default-/data', projectId),
        },
      ],
    },
    {
      id: 'secret-manager',
      title: 'Secret Manager',
      description: 'Credenciais sensíveis injetadas no Cloud Run em tempo de deploy.',
      details: [
        ...(infra?.secrets ?? ['SMTP_PASS', 'JWT_SECRET', 'GA4_SERVICE_ACCOUNT_JSON']).map(
          (s) => `Secret: ${s}`
        ),
        'Montados como variáveis de ambiente no serviço',
      ],
      links: [
        {
          label: 'Console Secret Manager',
          href: buildGcpConsoleUrl('/security/secret-manager', projectId),
        },
      ],
      script: 'bash scripts/gcp-setup-secrets.sh',
    },
    {
      id: 'cloud-dns',
      title: 'Cloud DNS',
      description: 'Gerencia os registros DNS do domínio principal.',
      details: [
        `Zona: ${dnsZone}`,
        `Domínio: ${domain}`,
        'Registros A (apex + www) apontam para o Load Balancer',
        'Registros de e-mail (MX, SPF) preservados',
      ],
      links: [
        {
          label: 'Console Cloud DNS',
          href: buildGcpConsoleUrl(`/net-services/dns/zones/${dnsZone}`, projectId),
        },
        {
          label: 'Site em produção',
          href: `https://${domain}`,
        },
      ],
      script: 'bash scripts/gcp-setup-domain.sh',
    },
    {
      id: 'firebase-hosting',
      title: 'Firebase Hosting',
      description: 'Domínio customizado, SSL e CDN na frente do Cloud Run (substitui o Load Balancer).',
      details: [
        `Domínio: ${domain}`,
        'Rewrite: todo tráfego → Cloud Run',
        'SSL gerenciado pelo Firebase',
        'Custo: free tier (10 GB/mês transferência)',
      ],
      links: [
        {
          label: 'Console Firebase Hosting',
          href: `https://console.firebase.google.com/project/${projectId ?? 'smart-tractor-257319'}/hosting/sites`,
        },
        {
          label: 'Site em produção',
          href: `https://${domain}`,
        },
      ],
      script: 'npm run setup:firebase',
    },
    {
      id: 'load-balancer',
      title: 'Load Balancer + SSL (legado)',
      description: 'Application LB global — pode ser removido após migração Firebase (~US$ 18/mês).',
      details: [
        `URL map: ${lbName}`,
        `IP global: ${infra?.loadBalancer.globalIpName ?? 'andrade-isencoes-ip'}`,
        'Substituído por Firebase Hosting',
        'Remover: npm run teardown:loadbalancer',
      ],
      links: [
        {
          label: 'Console Load Balancing',
          href: buildGcpConsoleUrl('/net-services/loadbalancing/list/loadBalancers', projectId),
        },
        {
          label: 'Certificados SSL',
          href: buildGcpConsoleUrl('/net-services/ssl-certificates/list', projectId),
        },
      ],
      script: 'bash scripts/gcp-setup-domain.sh',
    },
    {
      id: 'cloud-build',
      title: 'Cloud Build + Artifact Registry',
      description: 'Build da imagem Docker e deploy automático via gcloud run deploy --source.',
      details: [
        'Build a partir do Dockerfile na raiz do repositório',
        'APIs: cloudbuild.googleapis.com, artifactregistry.googleapis.com',
        'Usado pelo script de deploy',
      ],
      links: [
        {
          label: 'Histórico de builds',
          href: buildGcpConsoleUrl('/cloud-build/builds', projectId),
        },
        {
          label: 'Artifact Registry',
          href: buildGcpConsoleUrl('/artifacts', projectId),
        },
      ],
      script: 'npm run deploy:cloudrun',
    },
    {
      id: 'backup',
      title: 'Backup diário',
      description: 'Cópia automática do Firestore e dos buckets GCS (mídia + documentos).',
      details: [
        infra?.backup.bucket ? `Bucket: gs://${infra.backup.bucket} (Nearline)` : 'Bucket: andrade-backups',
        `Agendamento: ${infra?.backup.schedule ?? 'Diário às 03:00 BRT'}`,
        `Retenção: ${infra?.backup.retentionDays ?? 90} dias`,
        infra?.backup.lastRun
          ? `Último backup: ${infra.backup.lastRun.date} (${infra.backup.lastRun.status})`
          : 'Nenhum backup executado ainda',
        infra?.backup.schedulerConfigured
          ? 'Cloud Scheduler configurado'
          : 'Configure: npm run setup:backup',
      ],
      links: [
        {
          label: 'Bucket de backup',
          href: buildGcpConsoleUrl(
            `/storage/browser/${infra?.backup.bucket ?? 'andrade-backups'}`,
            projectId
          ),
        },
        {
          label: 'Cloud Scheduler',
          href: buildGcpConsoleUrl('/cloudscheduler', projectId),
        },
      ],
      script: 'npm run setup:backup',
    },
    {
      id: 'ga4-api',
      title: 'Google Analytics Data API',
      description: 'Leitura de métricas GA4 no painel admin (visitas, páginas, fontes).',
      details: [
        `Propriedade GA4: ${ga4PropertyId}`,
        'Conta GA: AndradeIsencoes (163733965)',
        infra?.ga4.configured
          ? 'Service account: configurada via Secret Manager'
          : 'Service account: pendente (rodar gcp-setup-ga4.sh)',
        infra?.serviceAccounts.ga4Reader
          ? `SA dedicada: ${infra.serviceAccounts.ga4Reader}`
          : 'SA dedicada: andrade-ga4-reader@…',
      ],
      links: [
        {
          label: 'Google Analytics',
          href: `https://analytics.google.com/analytics/web/#/p${ga4PropertyId}/reports/intelligenthome`,
        },
        {
          label: 'API Analytics Data',
          href: buildGcpConsoleUrl('/apis/library/analyticsdata.googleapis.com', projectId),
        },
      ],
      script: 'bash scripts/gcp-setup-ga4.sh',
    },
  ];

  const costById = new Map(
    (infra?.billing?.services ?? []).map((cost) => [cost.serviceId, cost])
  );

  return services.map((service) => ({
    ...service,
    cost: costById.get(service.id),
  }));
}

export const GCP_OVERVIEW_LINKS = [
  {
    label: 'Console GCP (projeto)',
    href: (projectId: string | null) =>
      buildGcpConsoleUrl('/home/dashboard', projectId),
  },
  {
    label: 'IAM e administradores',
    href: (projectId: string | null) =>
      buildGcpConsoleUrl('/iam-admin/iam', projectId),
  },
  {
    label: 'APIs habilitadas',
    href: (projectId: string | null) =>
      buildGcpConsoleUrl('/apis/dashboard', projectId),
  },
  {
    label: 'Faturamento',
    href: () => 'https://console.cloud.google.com/billing',
  },
] as const;
