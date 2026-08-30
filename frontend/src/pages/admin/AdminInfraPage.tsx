import { useEffect, useState } from 'react';
import {
  AlertCircle,
  Archive,
  Cloud,
  DollarSign,
  ExternalLink,
  HardDrive,
  Loader2,
  RefreshCw,
  Server,
  Terminal,
} from 'lucide-react';
import { SeoHead } from '../../components/seo/SeoHead';
import { fetchInfraInfo, triggerBackup } from '../../lib/api';
import {
  formatDualCost,
  formatExchangeRateLabel,
  getBillingTotals,
} from '../../lib/currency';
import { buildGcpServices, GCP_OVERVIEW_LINKS } from '../../data/gcp-infra';
import type { BillingServiceCost, GcpServiceDoc, InfraInfo } from '../../types/infra';

const ENV_LABELS: Record<InfraInfo['environment'], { label: string; className: string }> = {
  'cloud-run': { label: 'Produção (Cloud Run)', className: 'bg-emerald-100 text-emerald-800' },
  local: { label: 'Desenvolvimento local', className: 'bg-amber-100 text-amber-800' },
  vercel: { label: 'Vercel', className: 'bg-slate-100 text-slate-700' },
};

function CostAmount({
  amount,
  currency,
  exchangeRate,
  size = 'md',
}: {
  amount: number;
  currency: string;
  exchangeRate: InfraInfo['billing']['exchangeRate'];
  size?: 'md' | 'lg';
}) {
  const { primary, secondary } = formatDualCost(amount, currency, exchangeRate);
  const primaryClass = size === 'lg' ? 'text-3xl font-bold' : 'text-sm font-semibold';
  return (
    <div className="text-right tabular-nums">
      <p className={`${primaryClass} text-brand-800`}>{primary}</p>
      <p className={`${size === 'lg' ? 'text-base' : 'text-xs'} text-slate-500`}>≈ {secondary}</p>
    </div>
  );
}

function CostBadge({
  cost,
  exchangeRate,
}: {
  cost: BillingServiceCost;
  exchangeRate: InfraInfo['billing']['exchangeRate'];
}) {
  const isEstimate = cost.source === 'estimate';
  const { primary, secondary } = formatDualCost(cost.amount, cost.currency, exchangeRate);
  return (
    <div className="flex flex-col items-end gap-1">
      <div className="flex items-center gap-2 flex-wrap justify-end">
        <div className="text-right tabular-nums">
          <span className="text-lg font-bold text-brand-800">{primary}</span>
          <p className="text-xs text-slate-500">≈ {secondary}</p>
        </div>
        <span
          className={`text-[10px] uppercase tracking-wide font-semibold px-2 py-0.5 rounded-full ${
            isEstimate ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'
          }`}
        >
          {isEstimate ? 'Estimativa' : 'Faturamento real'}
        </span>
      </div>
    </div>
  );
}

function InfoRow({ label, value }: { label: string; value: React.ReactNode }) {
  return (
    <div className="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-3 py-2 border-b border-slate-100 last:border-0">
      <dt className="text-sm text-slate-500 sm:w-40 shrink-0">{label}</dt>
      <dd className="text-sm text-slate-800 font-medium break-all">{value}</dd>
    </div>
  );
}

function ServiceCard({
  service,
  exchangeRate,
}: {
  service: GcpServiceDoc;
  exchangeRate: InfraInfo['billing']['exchangeRate'];
}) {
  return (
    <article className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
      <div className="p-5 flex-1">
        <div className="flex items-start justify-between gap-3">
          <h3 className="font-semibold text-brand-800 text-lg">{service.title}</h3>
          {service.cost && <CostBadge cost={service.cost} exchangeRate={exchangeRate} />}
        </div>
        <p className="text-sm text-slate-500 mt-1">{service.description}</p>
        {service.cost?.details && service.cost.details.length > 0 && (
          <p className="text-xs text-slate-400 mt-2">{service.cost.details.join(' · ')}</p>
        )}
        <ul className="mt-4 space-y-1.5">
          {service.details.map((line) => (
            <li key={line} className="text-sm text-slate-600 flex gap-2">
              <span className="text-brand-400 shrink-0">•</span>
              <span>{line}</span>
            </li>
          ))}
        </ul>
      </div>
      <div className="px-5 py-3 bg-slate-50 border-t border-slate-100 flex flex-wrap gap-2">
        {service.links.map((link) => (
          <a
            key={link.href}
            href={link.href}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:text-brand-700 bg-white border border-slate-200 px-2.5 py-1.5 rounded-lg hover:border-brand-300 transition-colors"
          >
            {link.label}
            <ExternalLink size={12} />
          </a>
        ))}
        {service.script && (
          <span className="inline-flex items-center gap-1.5 text-xs text-slate-500 bg-white border border-dashed border-slate-200 px-2.5 py-1.5 rounded-lg font-mono">
            <Terminal size={12} />
            {service.script}
          </span>
        )}
      </div>
    </article>
  );
}

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  if (bytes < 1024 * 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} GB`;
}

function BackupCard({
  backup,
  exchangeRate,
  onRun,
  running,
}: {
  backup: InfraInfo['backup'];
  exchangeRate: InfraInfo['billing']['exchangeRate'];
  onRun: () => void;
  running: boolean;
}) {
  const backupCost = {
    serviceId: 'backup',
    label: 'Backup',
    amount: backup.estimatedMonthlyCostUsd,
    currency: 'USD',
    source: 'estimate' as const,
  };

  return (
    <section className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <Archive className="text-brand-500" size={20} />
            <h2 className="font-semibold text-brand-800 text-lg">Backup diário automático</h2>
          </div>
          <p className="text-sm text-slate-500 mt-1">
            Firestore + buckets GCS (mídia e documentos) · {backup.schedule}
          </p>
        </div>
        <button
          type="button"
          onClick={onRun}
          disabled={running}
          className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 disabled:opacity-50"
        >
          {running ? <Loader2 size={16} className="animate-spin" /> : <RefreshCw size={16} />}
          {running ? 'Executando…' : 'Executar agora'}
        </button>
      </div>
      <div className="p-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <p className="text-xs text-slate-500">Bucket de backup</p>
          <p className="text-sm font-medium text-slate-800 mt-0.5">
            {backup.bucket ? `gs://${backup.bucket}` : 'Local (dev)'}
          </p>
        </div>
        <div>
          <p className="text-xs text-slate-500">Último backup</p>
          <p className="text-sm font-medium text-slate-800 mt-0.5">
            {backup.lastRun
              ? `${backup.lastRun.date} · ${backup.lastRun.status}`
              : 'Nenhum ainda'}
          </p>
          {backup.lastRun && (
            <p className="text-xs text-slate-400 mt-0.5">
              {backup.lastRun.firestore.documents} docs · {formatBytes(backup.lastRun.totalBytes)}
            </p>
          )}
        </div>
        <div>
          <p className="text-xs text-slate-500">Armazenamento de backup</p>
          <p className="text-sm font-medium text-slate-800 mt-0.5">{formatBytes(backup.storageBytes)}</p>
          <p className="text-xs text-slate-400 mt-0.5">Retenção: {backup.retentionDays} dias</p>
        </div>
        <div>
          <p className="text-xs text-slate-500">Custo estimado (Nearline)</p>
          <CostAmount amount={backupCost.amount} currency="USD" exchangeRate={exchangeRate} size="md" />
        </div>
      </div>
      {backup.recentRuns.length > 0 && (
        <div className="px-5 pb-5">
          <p className="text-xs font-medium text-slate-500 mb-2">Execuções recentes</p>
          <div className="flex flex-wrap gap-2">
            {backup.recentRuns.map((run) => (
              <span
                key={run.date}
                className={`text-xs px-2.5 py-1 rounded-full border ${
                  run.status === 'success'
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                    : run.status === 'failed'
                      ? 'bg-red-50 text-red-700 border-red-200'
                      : 'bg-amber-50 text-amber-700 border-amber-200'
                }`}
              >
                {run.date} · {formatBytes(run.totalBytes)}
              </span>
            ))}
          </div>
        </div>
      )}
      {!backup.schedulerConfigured && (
        <div className="mx-5 mb-5 flex items-start gap-2 bg-blue-50 border border-blue-100 text-blue-800 px-4 py-3 rounded-xl text-sm">
          <AlertCircle size={18} className="shrink-0 mt-0.5" />
          <p>
            Agendamento automático pendente. Execute{' '}
            <code className="bg-blue-100 px-1 rounded">npm run setup:backup</code> para criar o Cloud
            Scheduler (03:00 BRT).
          </p>
        </div>
      )}
    </section>
  );
}

function BillingSummaryCard({
  billing,
  onRefresh,
  refreshing,
}: {
  billing: InfraInfo['billing'];
  onRefresh: () => void;
  refreshing: boolean;
}) {
  const isReal = billing.source === 'billing-export';
  const sorted = [...billing.services].sort((a, b) => b.amount - a.amount);
  const totals = getBillingTotals(billing);

  return (
    <section className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div className="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div className="flex items-center gap-2">
            <DollarSign className="text-brand-500" size={20} />
            <h2 className="font-semibold text-brand-800 text-lg">Custos do mês</h2>
          </div>
          <p className="text-sm text-slate-500 mt-1">
            {billing.periodLabel} ({billing.periodStart} → {billing.periodEnd})
          </p>
          <p className="text-xs text-slate-400 mt-1">{formatExchangeRateLabel(billing.exchangeRate)}</p>
        </div>
        <div className="flex items-center gap-3">
          <div className="text-right">
            <p className="text-3xl font-bold text-brand-800 tabular-nums">{totals.usd}</p>
            <p className="text-base text-slate-600 tabular-nums">≈ {totals.brl}</p>
            <p className="text-xs text-slate-500 mt-0.5">
              {isReal ? 'Dados do faturamento GCP (BigQuery)' : 'Estimativa baseada no uso'}
            </p>
          </div>
          <button
            type="button"
            onClick={onRefresh}
            disabled={refreshing}
            className="p-2 rounded-lg border border-slate-200 text-slate-500 hover:text-brand-600 hover:border-brand-300 disabled:opacity-50"
            title="Atualizar custos"
          >
            <RefreshCw size={18} className={refreshing ? 'animate-spin' : ''} />
          </button>
        </div>
      </div>

      <div className="p-5">
        <div className="space-y-3">
          {sorted.map((item) => {
            const pct = billing.total > 0 ? (item.amount / billing.total) * 100 : 0;
            return (
              <div key={item.serviceId}>
                <div className="flex items-center justify-between text-sm mb-1 gap-3">
                  <span className="text-slate-700 font-medium">{item.label}</span>
                  <CostAmount
                    amount={item.amount}
                    currency={item.currency}
                    exchangeRate={billing.exchangeRate}
                  />
                </div>
                <div className="h-2 bg-slate-100 rounded-full overflow-hidden">
                  <div
                    className="h-full bg-brand-500 rounded-full transition-all"
                    style={{ width: `${Math.max(pct, item.amount > 0 ? 2 : 0)}%` }}
                  />
                </div>
              </div>
            );
          })}
        </div>

        {!isReal && billing.setupHint && (
          <div className="mt-5 flex items-start gap-2 bg-blue-50 border border-blue-100 text-blue-800 px-4 py-3 rounded-xl text-sm">
            <AlertCircle size={18} className="shrink-0 mt-0.5" />
            <div>
              <p>{billing.setupHint}</p>
              <p className="mt-1 text-blue-700 text-xs">
                Script: <code className="bg-blue-100 px-1 rounded">bash scripts/gcp-setup-billing-export.sh</code>
              </p>
            </div>
          </div>
        )}

        <div className="mt-4 flex flex-wrap gap-2">
          <a
            href={billing.reportsUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1.5 text-sm text-brand-600 hover:text-brand-700 border border-slate-200 px-3 py-2 rounded-lg hover:border-brand-300"
          >
            Relatório de faturamento GCP
            <ExternalLink size={14} />
          </a>
        </div>
      </div>
    </section>
  );
}

export function AdminInfraPage() {
  const [infra, setInfra] = useState<InfraInfo | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [backupRunning, setBackupRunning] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [backupMessage, setBackupMessage] = useState<string | null>(null);

  const load = (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoading(true);
    fetchInfraInfo(refresh)
      .then(setInfra)
      .catch((err) => setError(err instanceof Error ? err.message : 'Erro ao carregar'))
      .finally(() => {
        setLoading(false);
        setRefreshing(false);
      });
  };

  useEffect(() => {
    load();
  }, []);

  const handleBackup = () => {
    setBackupRunning(true);
    setBackupMessage(null);
    triggerBackup(true)
      .then((manifest) => {
        setBackupMessage(`Backup concluído: ${formatBytes(manifest.totalBytes)} em ${manifest.date}`);
        load(true);
      })
      .catch((err) => setBackupMessage(err instanceof Error ? err.message : 'Erro no backup'))
      .finally(() => setBackupRunning(false));
  };

  const services = buildGcpServices(infra);
  const env = infra ? ENV_LABELS[infra.environment] : null;

  return (
    <>
      <SeoHead
        title="Infraestrutura GCP | Portal Andrade"
        description="Resumo dos serviços Google Cloud usados pelo site."
        noindex
      />
      <div className="max-w-6xl mx-auto space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
          <div>
            <div className="flex items-center gap-2">
              <Cloud className="text-brand-500" size={24} />
              <h1 className="text-2xl font-bold text-brand-800">Infraestrutura GCP</h1>
            </div>
            <p className="text-slate-500 mt-1">
              Serviços, armazenamento, custos e links do Google Cloud
            </p>
          </div>
          {env && (
            <span className={`self-start text-xs font-semibold px-3 py-1 rounded-full ${env.className}`}>
              {env.label}
            </span>
          )}
        </div>

        {loading && (
          <div className="flex items-center gap-2 text-slate-500 py-8 justify-center">
            <Loader2 className="animate-spin" size={20} />
            Carregando informações do ambiente…
          </div>
        )}

        {error && (
          <div className="flex items-start gap-2 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm">
            <AlertCircle size={18} className="shrink-0 mt-0.5" />
            <div>
              <p>Não foi possível carregar dados em tempo real: {error}</p>
              <p className="mt-1 text-amber-700">Os cards abaixo usam valores padrão documentados.</p>
            </div>
          </div>
        )}

        {!loading && (
          <>
            {infra?.billing && (
              <BillingSummaryCard
                billing={infra.billing}
                onRefresh={() => load(true)}
                refreshing={refreshing}
              />
            )}

            {infra?.backup && infra.billing && (
              <BackupCard
                backup={infra.backup}
                exchangeRate={infra.billing.exchangeRate}
                onRun={handleBackup}
                running={backupRunning}
              />
            )}

            {backupMessage && (
              <div className="flex items-start gap-2 bg-slate-50 border border-slate-200 text-slate-700 px-4 py-3 rounded-xl text-sm">
                <Archive size={18} className="shrink-0 mt-0.5 text-brand-500" />
                <p>{backupMessage}</p>
              </div>
            )}

            <div className="grid lg:grid-cols-2 gap-4">
              <section className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div className="flex items-center gap-2 mb-4">
                  <Server className="text-brand-500" size={18} />
                  <h2 className="font-semibold text-brand-800">Projeto e ambiente</h2>
                </div>
                <dl>
                  <InfoRow label="Conta GCP" value={infra?.gcpAccount ?? 'andradeisencoescloud@gmail.com'} />
                  <InfoRow label="Projeto" value={infra?.projectId ?? 'smart-tractor-257319'} />
                  {infra?.projectNumber && (
                    <InfoRow label="Nº do projeto" value={infra.projectNumber} />
                  )}
                  <InfoRow label="Região" value={infra?.region ?? 'southamerica-east1'} />
                  <InfoRow label="Serviço Cloud Run" value={infra?.serviceName ?? 'andrade-isencoes'} />
                  <InfoRow
                    label="Site"
                    value={
                      <a
                        href={infra?.siteUrl ?? 'https://andradeisencoes.com.br'}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-brand-600 hover:underline inline-flex items-center gap-1"
                      >
                        {infra?.siteUrl ?? 'https://andradeisencoes.com.br'}
                        <ExternalLink size={12} />
                      </a>
                    }
                  />
                  {infra?.serviceAccounts.cloudRun && (
                    <InfoRow label="SA Cloud Run" value={infra.serviceAccounts.cloudRun} />
                  )}
                </dl>
              </section>

              <section className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div className="flex items-center gap-2 mb-4">
                  <HardDrive className="text-brand-500" size={18} />
                  <h2 className="font-semibold text-brand-800">Armazenamento ativo</h2>
                </div>
                <dl>
                  <InfoRow
                    label="GCS mídia"
                    value={
                      infra?.gcs.enabled
                        ? `gs://${infra.gcs.bucket?.name} (${infra.gcs.bucket?.bytes != null ? formatBytes(infra.gcs.bucket.bytes) : '—'})`
                        : 'Desativado (uploads locais)'
                    }
                  />
                  <InfoRow
                    label="GCS documentos"
                    value={
                      infra?.gcs.docsBucket?.name
                        ? `gs://${infra.gcs.docsBucket.name} (privado${infra.gcs.docsBucket.bytes != null ? ` · ${formatBytes(infra.gcs.docsBucket.bytes)}` : ''})`
                        : 'gs://andrade-docs'
                    }
                  />
                  {infra?.gcs.bucket?.publicUrl && (
                    <InfoRow
                      label="URL do bucket"
                      value={
                        <a
                          href={infra.gcs.bucket.publicUrl}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-brand-600 hover:underline inline-flex items-center gap-1"
                        >
                          {infra.gcs.bucket.publicUrl}
                          <ExternalLink size={12} />
                        </a>
                      }
                    />
                  )}
                  <InfoRow
                    label="Firestore"
                    value={infra?.firestore.enabled ? 'Ativo (produção)' : 'Arquivos locais (dev)'}
                  />
                  <InfoRow
                    label="Coleções"
                    value={
                      infra?.firestore.collections.length
                        ? infra.firestore.collections.map((c) => c.name).join(', ')
                        : 'site_content, contact_submissions, admin_users, app_settings'
                    }
                  />
                </dl>
              </section>
            </div>

            <section className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
              <h2 className="font-semibold text-brand-800 mb-3">Links rápidos do console</h2>
              <div className="flex flex-wrap gap-2">
                {GCP_OVERVIEW_LINKS.map((item) => (
                  <a
                    key={item.label}
                    href={item.href(infra?.projectId ?? null)}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1.5 text-sm text-brand-600 hover:text-brand-700 border border-slate-200 px-3 py-2 rounded-lg hover:border-brand-300 transition-colors"
                  >
                    {item.label}
                    <ExternalLink size={14} />
                  </a>
                ))}
              </div>
            </section>

            <div>
              <h2 className="font-semibold text-brand-800 mb-4">Serviços utilizados</h2>
              <div className="grid md:grid-cols-2 gap-4">
                {services.map((service) => (
                  <ServiceCard
                    key={service.id}
                    service={service}
                    exchangeRate={infra!.billing.exchangeRate}
                  />
                ))}
              </div>
            </div>

            <p className="text-xs text-slate-400 text-center pb-4">
              Estimativas usam uso real (armazenamento, métricas) quando disponível. Custos reais exigem
              exportação de faturamento para BigQuery. Scripts em{' '}
              <code className="bg-slate-100 px-1 rounded">scripts/</code>.
            </p>
          </>
        )}
      </div>
    </>
  );
}
