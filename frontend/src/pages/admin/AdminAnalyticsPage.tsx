import { useCallback, useEffect, useState } from 'react';
import {
  ExternalLink,
  BarChart3,
  LineChart,
  MousePointerClick,
  Settings,
  RefreshCw,
  Users,
  Eye,
  Globe,
  AlertCircle,
} from 'lucide-react';
import { SeoHead } from '../../components/seo/SeoHead';
import { fetchAdminStats, fetchGa4Dashboard } from '../../lib/api';
import type { AdminStats } from '../../types/user';
import type { Ga4DashboardData } from '../../types/ga4';

const GA_MEASUREMENT_ID = import.meta.env.VITE_GA_MEASUREMENT_ID as string | undefined;
const GA_PROPERTY_ID = (import.meta.env.VITE_GA4_PROPERTY_ID as string | undefined) || '347102827';
const GA_ACCOUNT_ID = '163733965';

const GA_HOME = `https://analytics.google.com/analytics/web/#/p${GA_PROPERTY_ID}/reports/intelligenthome`;
const GA_REALTIME = `https://analytics.google.com/analytics/web/#/p${GA_PROPERTY_ID}/reports/realtime`;
const GA_STREAMS = `https://analytics.google.com/analytics/web/#/a${GA_ACCOUNT_ID}p${GA_PROPERTY_ID}/admin/streams/table`;

function formatNumber(value: number | null | undefined): string {
  if (value == null) return '—';
  return value.toLocaleString('pt-BR');
}

function formatShortDate(iso: string): string {
  const [, month, day] = iso.split('-');
  return `${day}/${month}`;
}

function DailyUsersChart({ data }: { data: Ga4DashboardData['dailyUsers'] }) {
  if (!data.length) return null;
  const max = Math.max(...data.map((d) => d.users), 1);

  return (
    <div className="space-y-2">
      <div className="flex items-end gap-1.5 h-28">
        {data.map((day) => (
          <div key={day.date} className="flex-1 flex flex-col items-center gap-1 min-w-0">
            <span className="text-[10px] text-slate-500 tabular-nums">{day.users || ''}</span>
            <div
              className="w-full rounded-t bg-brand-500/80 min-h-[2px] transition-all"
              style={{ height: `${Math.max(4, (day.users / max) * 100)}%` }}
              title={`${day.date}: ${day.users} usuários`}
            />
            <span className="text-[9px] text-slate-400 truncate w-full text-center">
              {formatShortDate(day.date)}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}

export function AdminAnalyticsPage() {
  const [stats, setStats] = useState<AdminStats | null>(null);
  const [ga4, setGa4] = useState<Ga4DashboardData | null>(null);
  const [loadingGa4, setLoadingGa4] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadGa4 = useCallback(async (refresh = false) => {
    if (refresh) setRefreshing(true);
    else setLoadingGa4(true);
    try {
      const data = await fetchGa4Dashboard(refresh);
      setGa4(data);
    } catch (err) {
      console.error(err);
      setGa4({
        configured: false,
        propertyId: GA_PROPERTY_ID,
        error: 'Não foi possível carregar os dados do GA4.',
        activeUsers: null,
        users7d: null,
        sessions7d: null,
        pageviews7d: null,
        topPages: [],
        trafficSources: [],
        dailyUsers: [],
        fetchedAt: null,
      });
    } finally {
      setLoadingGa4(false);
      setRefreshing(false);
    }
  }, []);

  useEffect(() => {
    fetchAdminStats().then(setStats).catch(console.error);
    loadGa4();
  }, [loadGa4]);

  return (
    <>
      <SeoHead title="Analytics | Portal Andrade" description="Painel administrativo de analytics." noindex />
      <div className="max-w-5xl mx-auto space-y-6">
        <div className="flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 className="text-2xl font-bold text-brand-800">Analytics</h1>
            <p className="text-slate-500 mt-1">Métricas do site e formulário de contato</p>
          </div>
          <button
            type="button"
            onClick={() => loadGa4(true)}
            disabled={refreshing || loadingGa4}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border bg-white text-sm font-medium hover:bg-slate-50 disabled:opacity-50"
          >
            <RefreshCw size={16} className={refreshing ? 'animate-spin' : ''} />
            Atualizar GA4
          </button>
        </div>

        <div>
          <h2 className="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-3">
            Formulário de contato
          </h2>
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div className="bg-white rounded-xl border p-5">
              <p className="text-sm text-slate-500">Contatos (total)</p>
              <p className="text-3xl font-bold text-brand-800 mt-1">{stats?.contacts.total ?? '—'}</p>
            </div>
            <div className="bg-white rounded-xl border p-5">
              <p className="text-sm text-slate-500">Novos</p>
              <p className="text-3xl font-bold text-accent mt-1">{stats?.contacts.new ?? '—'}</p>
            </div>
            <div className="bg-white rounded-xl border p-5">
              <p className="text-sm text-slate-500">Taxa e-mail enviado</p>
              <p className="text-3xl font-bold text-brand-800 mt-1">
                {stats ? `${stats.contacts.emailSentRate}%` : '—'}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl border p-6 space-y-5">
          <div className="flex items-center gap-3">
            <BarChart3 className="text-brand-500" size={24} />
            <div className="flex-1">
              <h2 className="font-semibold text-brand-800">Google Analytics 4</h2>
              <p className="text-xs text-slate-500">
                Propriedade {GA_PROPERTY_ID}
                {GA_MEASUREMENT_ID ? ` · ${GA_MEASUREMENT_ID}` : ''}
                {ga4?.fetchedAt && (
                  <> · atualizado {new Date(ga4.fetchedAt).toLocaleTimeString('pt-BR')}</>
                )}
              </p>
            </div>
          </div>

          {loadingGa4 ? (
            <div className="flex justify-center py-12 text-slate-400 text-sm">Carregando dados do GA4…</div>
          ) : !ga4?.configured ? (
            <div className="text-sm text-slate-600 bg-amber-50 border border-amber-100 rounded-lg p-4 space-y-2">
              <div className="flex items-start gap-2">
                <AlertCircle size={18} className="text-amber-600 flex-shrink-0 mt-0.5" />
                <div className="space-y-2">
                  <p>
                    <strong>Integração GA4 pendente.</strong>{' '}
                    {ga4?.error || 'Configure o acesso da API para exibir visitas aqui.'}
                  </p>
                  {ga4?.setupHint && <p className="text-xs text-amber-800">{ga4.setupHint}</p>}
                  <p className="text-xs">
                    Enquanto isso, use os links abaixo para ver os relatórios completos no Google Analytics.
                  </p>
                </div>
              </div>
            </div>
          ) : (
            <>
              <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="rounded-xl border border-green-100 bg-green-50/50 p-4">
                  <div className="flex items-center gap-2 text-green-700 mb-1">
                    <MousePointerClick size={16} />
                    <p className="text-xs font-medium uppercase tracking-wide">Agora</p>
                  </div>
                  <p className="text-3xl font-bold text-brand-800">{formatNumber(ga4.activeUsers)}</p>
                  <p className="text-xs text-slate-500 mt-1">usuários ativos</p>
                </div>
                <div className="rounded-xl border p-4">
                  <div className="flex items-center gap-2 text-slate-500 mb-1">
                    <Users size={16} />
                    <p className="text-xs font-medium uppercase tracking-wide">7 dias</p>
                  </div>
                  <p className="text-3xl font-bold text-brand-800">{formatNumber(ga4.users7d)}</p>
                  <p className="text-xs text-slate-500 mt-1">usuários</p>
                </div>
                <div className="rounded-xl border p-4">
                  <div className="flex items-center gap-2 text-slate-500 mb-1">
                    <Globe size={16} />
                    <p className="text-xs font-medium uppercase tracking-wide">7 dias</p>
                  </div>
                  <p className="text-3xl font-bold text-brand-800">{formatNumber(ga4.sessions7d)}</p>
                  <p className="text-xs text-slate-500 mt-1">sessões</p>
                </div>
                <div className="rounded-xl border p-4">
                  <div className="flex items-center gap-2 text-slate-500 mb-1">
                    <Eye size={16} />
                    <p className="text-xs font-medium uppercase tracking-wide">7 dias</p>
                  </div>
                  <p className="text-3xl font-bold text-brand-800">{formatNumber(ga4.pageviews7d)}</p>
                  <p className="text-xs text-slate-500 mt-1">visualizações</p>
                </div>
              </div>

              <div className="grid lg:grid-cols-2 gap-5">
                <div className="rounded-xl border p-4">
                  <h3 className="text-sm font-semibold text-brand-800 mb-3">Usuários por dia (7 dias)</h3>
                  <DailyUsersChart data={ga4.dailyUsers} />
                </div>

                <div className="rounded-xl border p-4">
                  <h3 className="text-sm font-semibold text-brand-800 mb-3">Origem do tráfego</h3>
                  {ga4.trafficSources.length === 0 ? (
                    <p className="text-sm text-slate-400">Sem dados ainda.</p>
                  ) : (
                    <ul className="space-y-2">
                      {ga4.trafficSources.map((src) => {
                        const max = ga4.trafficSources[0]?.sessions || 1;
                        const pct = Math.round((src.sessions / max) * 100);
                        return (
                          <li key={src.channel}>
                            <div className="flex justify-between text-sm mb-1">
                              <span className="text-slate-700 truncate pr-2">{src.channel}</span>
                              <span className="text-slate-500 tabular-nums flex-shrink-0">
                                {formatNumber(src.sessions)}
                              </span>
                            </div>
                            <div className="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                              <div
                                className="h-full bg-brand-400 rounded-full"
                                style={{ width: `${pct}%` }}
                              />
                            </div>
                          </li>
                        );
                      })}
                    </ul>
                  )}
                </div>
              </div>

              <div className="rounded-xl border p-4">
                <h3 className="text-sm font-semibold text-brand-800 mb-3">Páginas mais vistas (7 dias)</h3>
                {ga4.topPages.length === 0 ? (
                  <p className="text-sm text-slate-400">Sem dados ainda.</p>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="text-left text-slate-500 border-b">
                          <th className="pb-2 font-medium">Página</th>
                          <th className="pb-2 font-medium text-right w-24">Views</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y">
                        {ga4.topPages.map((page) => (
                          <tr key={page.path}>
                            <td className="py-2 pr-4 font-mono text-xs text-slate-700 truncate max-w-xs">
                              {page.path}
                            </td>
                            <td className="py-2 text-right tabular-nums text-slate-600">
                              {formatNumber(page.views)}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            </>
          )}

          <div className="grid sm:grid-cols-2 gap-3 pt-2 border-t">
            <a
              href={GA_HOME}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-3 p-4 rounded-lg border hover:border-brand-300 transition-colors"
            >
              <LineChart size={20} className="text-brand-500" />
              <div>
                <p className="font-medium text-sm">Relatórios completos</p>
                <p className="text-xs text-slate-500">Abrir no Google Analytics</p>
              </div>
              <ExternalLink size={14} className="ml-auto text-slate-400" />
            </a>
            <a
              href={GA_REALTIME}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-3 p-4 rounded-lg border hover:border-brand-300 transition-colors"
            >
              <MousePointerClick size={20} className="text-brand-500" />
              <div>
                <p className="font-medium text-sm">Tempo real</p>
                <p className="text-xs text-slate-500">Detalhes ao vivo no GA4</p>
              </div>
              <ExternalLink size={14} className="ml-auto text-slate-400" />
            </a>
            <a
              href={GA_STREAMS}
              target="_blank"
              rel="noopener noreferrer"
              className="flex items-center gap-3 p-4 rounded-lg border hover:border-brand-300 transition-colors sm:col-span-2"
            >
              <Settings size={20} className="text-brand-500" />
              <div>
                <p className="font-medium text-sm">Configuração do fluxo</p>
                <p className="text-xs text-slate-500">Fluxos de dados · andradeisencoes.com.br</p>
              </div>
              <ExternalLink size={14} className="ml-auto text-slate-400" />
            </a>
          </div>
        </div>
      </div>
    </>
  );
}
