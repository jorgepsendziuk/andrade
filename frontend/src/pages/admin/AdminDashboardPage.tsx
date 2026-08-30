import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  Inbox,
  Mail,
  Pencil,
  Settings,
  TrendingUp,
  Users,
  Globe,
  MousePointerClick,
  FileText,
  BookOpen,
  CheckCircle2,
  XCircle,
  AlertCircle,
  ExternalLink,
  BarChart3,
  Database,
  Clock,
} from 'lucide-react';
import { fetchAdminStats } from '../../lib/api';
import type { AdminStats } from '../../types/user';
import { SeoHead } from '../../components/seo/SeoHead';
import { useAdminUser } from '../../components/admin/AdminShell';

const STATUS_LABELS = { new: 'Novo', read: 'Lido', archived: 'Arquivado' } as const;
const STATUS_STYLES = {
  new: 'bg-accent/10 text-accent',
  read: 'bg-slate-100 text-slate-600',
  archived: 'bg-slate-100 text-slate-400',
} as const;

function formatNumber(value: number | null | undefined): string {
  if (value == null) return '—';
  return value.toLocaleString('pt-BR');
}

function formatRelativeTime(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return 'agora';
  if (mins < 60) return `${mins} min`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `${hours}h`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `${days}d`;
  return new Date(iso).toLocaleDateString('pt-BR');
}

function StatCard({
  label,
  value,
  sub,
  icon: Icon,
  to,
  accent,
}: {
  label: string;
  value: string | number;
  sub?: string;
  icon: typeof Inbox;
  to?: string;
  accent?: boolean;
}) {
  const inner = (
    <div
      className={`rounded-xl border p-5 shadow-sm hover:shadow-md transition-shadow h-full ${
        accent ? 'border-green-100 bg-green-50/40' : 'bg-white border-slate-200'
      }`}
    >
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <p className="text-sm text-slate-500">{label}</p>
          <p className="text-3xl font-bold text-brand-800 mt-1 tabular-nums">{value}</p>
          {sub && <p className="text-xs text-slate-400 mt-1 truncate">{sub}</p>}
        </div>
        <div className={`p-2.5 rounded-lg flex-shrink-0 ${accent ? 'bg-green-100 text-green-700' : 'bg-brand-50 text-brand-600'}`}>
          <Icon size={22} />
        </div>
      </div>
    </div>
  );
  return to ? <Link to={to} className="block">{inner}</Link> : inner;
}

function IntegrationPill({ ok, label }: { ok: boolean; label: string }) {
  return (
    <span
      className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${
        ok ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-500'
      }`}
    >
      {ok ? <CheckCircle2 size={12} /> : <XCircle size={12} />}
      {label}
    </span>
  );
}

function MiniBarChart({
  data,
  valueKey,
  colorClass = 'bg-brand-500',
}: {
  data: { date: string; count?: number; users?: number }[];
  valueKey: 'count' | 'users';
  colorClass?: string;
}) {
  if (!data.length) return null;
  const max = Math.max(...data.map((d) => d[valueKey] ?? 0), 1);

  return (
    <div className="flex items-end gap-1.5 h-28">
      {data.map((d) => {
        const val = d[valueKey] ?? 0;
        const h = Math.max((val / max) * 100, 4);
        return (
          <div key={d.date} className="flex-1 flex flex-col items-center gap-1 min-w-0">
            <span className="text-[10px] font-medium text-brand-700 tabular-nums">{val || ''}</span>
            <div className={`w-full rounded-t-md min-h-[4px] ${colorClass}`} style={{ height: `${h}%` }} />
            <span className="text-[9px] text-slate-400">
              {d.date.slice(8)}/{d.date.slice(5, 7)}
            </span>
          </div>
        );
      })}
    </div>
  );
}

export function AdminDashboardPage() {
  const user = useAdminUser();
  const [stats, setStats] = useState<AdminStats | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchAdminStats()
      .then(setStats)
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  const c = stats?.contacts;
  const ga4 = stats?.integrations.ga4;

  return (
    <>
      <SeoHead title="Dashboard | Portal Andrade" description="Painel administrativo da Andrade Isenções." noindex />
      <div className="max-w-6xl mx-auto space-y-6">
        <div className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold text-brand-800">
              Olá, {user?.name?.split(' ')[0] || 'equipe'}!
            </h1>
            <p className="text-slate-500 mt-1">
              {stats?.site.title || 'Andrade Isenções'} — visão geral do site e do sistema
            </p>
          </div>
          {stats?.system.siteUrl && (
            <a
              href={stats.system.siteUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border bg-white text-sm text-brand-700 hover:border-brand-300"
            >
              <ExternalLink size={15} />
              Ver site ao vivo
            </a>
          )}
        </div>

        {user?.mustChangePassword && (
          <div className="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm flex items-start gap-2">
            <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
            <span>
              Por segurança,{' '}
              <Link to="/portal/usuarios" className="font-semibold underline">
                altere sua senha
              </Link>{' '}
              — a senha inicial é igual ao seu e-mail.
            </span>
          </div>
        )}

        {loading ? (
          <div className="text-center py-16 text-slate-400 text-sm">Carregando dashboard…</div>
        ) : (
          <>
            {/* Métricas principais */}
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
              <StatCard
                label="Contatos novos"
                value={c?.new ?? '—'}
                sub="aguardando leitura"
                icon={Inbox}
                to="/portal/contatos"
              />
              <StatCard
                label="Contatos hoje"
                value={c?.today ?? '—'}
                sub={`${c?.total ?? 0} no total`}
                icon={Mail}
                to="/portal/contatos"
              />
              <StatCard
                label="Usuários online"
                value={ga4?.configured ? formatNumber(ga4.activeUsers) : '—'}
                sub={ga4?.configured ? 'GA4 tempo real' : 'GA4 pendente'}
                icon={MousePointerClick}
                to="/portal/analytics"
                accent={ga4?.configured && (ga4.activeUsers ?? 0) > 0}
              />
              <StatCard
                label="Visitas (7 dias)"
                value={ga4?.configured ? formatNumber(ga4.sessions7d) : '—'}
                sub={ga4?.configured ? `${formatNumber(ga4.users7d)} usuários` : 'Configure o GA4'}
                icon={Globe}
                to="/portal/analytics"
              />
            </div>

            <div className="grid lg:grid-cols-3 gap-5">
              {/* Contatos recentes */}
              <div className="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div className="flex items-center justify-between mb-4">
                  <h2 className="font-semibold text-brand-800">Contatos recentes</h2>
                  <Link to="/portal/contatos" className="text-xs text-brand-600 hover:underline font-medium">
                    Ver todos →
                  </Link>
                </div>
                {stats?.recentContacts.length === 0 ? (
                  <p className="text-sm text-slate-400 py-6 text-center">Nenhum contato ainda.</p>
                ) : (
                  <div className="divide-y">
                    {stats?.recentContacts.map((contact) => (
                      <Link
                        key={contact.id}
                        to="/portal/contatos"
                        className="flex items-center gap-3 py-3 first:pt-0 last:pb-0 hover:bg-slate-50 -mx-2 px-2 rounded-lg transition-colors"
                      >
                        <div className="flex-1 min-w-0">
                          <p className="font-medium text-sm text-slate-800 truncate">{contact.name}</p>
                          <p className="text-xs text-slate-500 truncate">{contact.email}</p>
                        </div>
                        <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${STATUS_STYLES[contact.status]}`}>
                          {STATUS_LABELS[contact.status]}
                        </span>
                        <div className="text-right flex-shrink-0">
                          <p className="text-xs text-slate-400">{formatRelativeTime(contact.createdAt)}</p>
                          {contact.emailSent ? (
                            <CheckCircle2 size={12} className="text-green-500 ml-auto mt-0.5" />
                          ) : (
                            <AlertCircle size={12} className="text-amber-500 ml-auto mt-0.5" />
                          )}
                        </div>
                      </Link>
                    ))}
                  </div>
                )}
              </div>

              {/* Site & CMS */}
              <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm space-y-4">
                <h2 className="font-semibold text-brand-800">Site & conteúdo</h2>
                <div className="space-y-3 text-sm">
                  <div className="flex justify-between">
                    <span className="text-slate-500">Seções ativas</span>
                    <span className="font-semibold text-brand-800">
                      {stats?.site.sectionsEnabled}/{stats?.site.sectionsTotal}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-slate-500">Páginas PCD</span>
                    <span className="font-semibold text-brand-800">{stats?.content.conditions}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-slate-500">Artigos do guia</span>
                    <span className="font-semibold text-brand-800">{stats?.content.guiaArticles}</span>
                  </div>
                  {stats?.site.lastUpdated && (
                    <div className="flex items-start gap-2 pt-2 border-t text-xs text-slate-400">
                      <Clock size={14} className="flex-shrink-0 mt-0.5" />
                      <span>
                        CMS atualizado em{' '}
                        {new Date(stats.site.lastUpdated).toLocaleString('pt-BR')}
                      </span>
                    </div>
                  )}
                </div>
                <Link
                  to="/portal/site"
                  className="flex items-center justify-center gap-2 w-full py-2.5 rounded-lg border border-brand-200 text-brand-700 text-sm font-medium hover:bg-brand-50 transition-colors"
                >
                  <Pencil size={16} />
                  Editar site
                </Link>
              </div>
            </div>

            {/* Gráficos */}
            <div className="grid lg:grid-cols-2 gap-5">
              {c && c.last7Days.length > 0 && (
                <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                  <h2 className="font-semibold text-brand-800 mb-4">Contatos — últimos 7 dias</h2>
                  <MiniBarChart data={c.last7Days} valueKey="count" />
                  <div className="flex gap-4 mt-4 pt-4 border-t text-xs text-slate-500">
                    <span><strong className="text-brand-800">{c.read}</strong> lidos</span>
                    <span><strong className="text-brand-800">{c.archived}</strong> arquivados</span>
                    <span><strong className="text-brand-800">{c.emailSentRate}%</strong> e-mail OK</span>
                  </div>
                </div>
              )}

              <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <div className="flex items-center justify-between mb-4">
                  <h2 className="font-semibold text-brand-800">Tráfego do site</h2>
                  <Link to="/portal/analytics" className="text-xs text-brand-600 hover:underline font-medium">
                    Analytics →
                  </Link>
                </div>
                {ga4?.configured ? (
                  <div className="grid grid-cols-3 gap-3">
                    <div className="text-center p-3 rounded-lg bg-slate-50">
                      <p className="text-2xl font-bold text-brand-800 tabular-nums">{formatNumber(ga4.users7d)}</p>
                      <p className="text-[10px] text-slate-500 mt-1 uppercase tracking-wide">Usuários</p>
                    </div>
                    <div className="text-center p-3 rounded-lg bg-slate-50">
                      <p className="text-2xl font-bold text-brand-800 tabular-nums">{formatNumber(ga4.sessions7d)}</p>
                      <p className="text-[10px] text-slate-500 mt-1 uppercase tracking-wide">Sessões</p>
                    </div>
                    <div className="text-center p-3 rounded-lg bg-slate-50">
                      <p className="text-2xl font-bold text-brand-800 tabular-nums">{formatNumber(ga4.pageviews7d)}</p>
                      <p className="text-[10px] text-slate-500 mt-1 uppercase tracking-wide">Views</p>
                    </div>
                  </div>
                ) : (
                  <div className="text-sm text-slate-500 bg-amber-50 border border-amber-100 rounded-lg p-4">
                    <p className="font-medium text-amber-800 mb-1">GA4 não conectado</p>
                    <p className="text-xs">{ga4?.error || 'Adicione a service account como Leitor no Google Analytics.'}</p>
                  </div>
                )}
              </div>
            </div>

            {/* Integrações & sistema */}
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
              <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <h2 className="font-semibold text-brand-800 mb-3">Integrações</h2>
                <div className="flex flex-wrap gap-2">
                  <IntegrationPill ok={stats?.integrations.email.ok ?? false} label="E-mail SMTP" />
                  <IntegrationPill ok={stats?.integrations.ga4.configured ?? false} label="Google Analytics" />
                  <IntegrationPill ok={stats?.integrations.googleReviews ?? false} label="Google Reviews" />
                  <IntegrationPill ok={stats?.integrations.instagram ?? false} label="Instagram" />
                </div>
                {!stats?.integrations.email.ok && stats?.integrations.email.message && (
                  <p className="text-xs text-amber-700 mt-3 bg-amber-50 rounded-lg p-2">
                    {stats.integrations.email.message}
                  </p>
                )}
                {(user?.role === 'admin' || user?.role === 'editor') && (
                  <Link
                    to="/portal/configuracoes"
                    className="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline mt-3 font-medium"
                  >
                    <Settings size={13} />
                    Configurações
                  </Link>
                )}
              </div>

              <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <h2 className="font-semibold text-brand-800 mb-3">Conteúdo publicado</h2>
                <div className="flex gap-4">
                  <div className="flex items-center gap-2">
                    <FileText size={18} className="text-brand-500" />
                    <div>
                      <p className="text-xl font-bold text-brand-800">{stats?.content.conditions}</p>
                      <p className="text-[10px] text-slate-500">condições PCD</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    <BookOpen size={18} className="text-brand-500" />
                    <div>
                      <p className="text-xl font-bold text-brand-800">{stats?.content.guiaArticles}</p>
                      <p className="text-[10px] text-slate-500">artigos guia</p>
                    </div>
                  </div>
                </div>
                <a
                  href="/guia"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-1 text-xs text-brand-600 hover:underline mt-3 font-medium"
                >
                  <ExternalLink size={13} />
                  Ver guia no site
                </a>
              </div>

              <div className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
                <h2 className="font-semibold text-brand-800 mb-3">Sistema</h2>
                <div className="space-y-2 text-sm">
                  <div className="flex items-center gap-2 text-slate-600">
                    <Database size={15} className="text-slate-400" />
                    Contatos: <strong>{stats?.system.contactsStorage === 'firestore' ? 'Firestore' : 'Arquivo local'}</strong>
                  </div>
                  {stats?.users && (
                    <div className="flex items-center gap-2 text-slate-600">
                      <Users size={15} className="text-slate-400" />
                      Usuários: <strong>{stats.users.active} ativos</strong> / {stats.users.total}
                    </div>
                  )}
                  <div className="flex items-center gap-2 text-slate-600">
                    <BarChart3 size={15} className="text-slate-400" />
                    Perfil: <strong className="capitalize">{user?.role}</strong>
                  </div>
                </div>
              </div>
            </div>

            {/* Ações rápidas */}
            <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
              <Link
                to="/portal/contatos"
                className="flex items-center gap-3 bg-white rounded-xl border p-4 hover:border-brand-300 transition-colors"
              >
                <Inbox className="text-brand-500" />
                <span className="font-medium text-slate-700 text-sm">Contatos</span>
                {(c?.new ?? 0) > 0 && (
                  <span className="ml-auto bg-accent text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full">
                    {c?.new}
                  </span>
                )}
              </Link>
              <Link
                to="/portal/site"
                className="flex items-center gap-3 bg-white rounded-xl border p-4 hover:border-brand-300 transition-colors"
              >
                <Pencil className="text-brand-500" />
                <span className="font-medium text-slate-700 text-sm">Editar site</span>
              </Link>
              <Link
                to="/portal/analytics"
                className="flex items-center gap-3 bg-white rounded-xl border p-4 hover:border-brand-300 transition-colors"
              >
                <TrendingUp className="text-brand-500" />
                <span className="font-medium text-slate-700 text-sm">Analytics</span>
              </Link>
              {user?.role === 'admin' && (
                <Link
                  to="/portal/usuarios"
                  className="flex items-center gap-3 bg-white rounded-xl border p-4 hover:border-brand-300 transition-colors"
                >
                  <Users className="text-brand-500" />
                  <span className="font-medium text-slate-700 text-sm">Usuários</span>
                </Link>
              )}
            </div>
          </>
        )}
      </div>
    </>
  );
}
