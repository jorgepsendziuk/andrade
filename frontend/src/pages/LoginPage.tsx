import { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  AlertCircle,
  ArrowRight,
  Briefcase,
  ClipboardList,
  ExternalLink,
  FileText,
  Lock,
  Mail,
  Settings,
  UserCircle,
} from 'lucide-react';
import { useCms } from '../context/CmsContext';
import { fetchMe, getToken, setToken } from '../lib/api';
import { SeoHead } from '../components/seo/SeoHead';
import { BrandLogo } from '../components/ui/BrandLogo';
import { PORTAL_CLIENT_PROCESS, PORTAL_LOGIN, PORTAL_STAFF_HOME } from '../lib/portal-routes';

export const WEBMAIL_URL = 'https://email.andradeisencoes.com.br';

function AccessCard({
  icon: Icon,
  title,
  accentClass,
  items,
}: {
  icon: typeof UserCircle;
  title: string;
  accentClass: string;
  items: { icon: typeof FileText; label: string }[];
}) {
  return (
    <div className="rounded-xl border border-slate-200 bg-slate-50/80 p-3.5 h-full">
      <div className="flex items-center gap-2 mb-2.5">
        <div className={`w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${accentClass}`}>
          <Icon size={16} />
        </div>
        <p className="font-display font-bold text-brand-800 text-sm">{title}</p>
      </div>
      <ul className="space-y-1.5">
        {items.map((item) => (
          <li key={item.label} className="flex items-start gap-2 text-xs text-slate-600 leading-snug">
            <item.icon size={13} className="text-brand-500 shrink-0 mt-0.5" />
            <span>{item.label}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}

export function LoginPage() {
  const { login, logout } = useCms();
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [checkingSession, setCheckingSession] = useState(true);

  useEffect(() => {
    const token = getToken();
    if (!token) {
      setCheckingSession(false);
      return;
    }
    fetchMe()
      .then((user) => {
        navigate(user.role === 'cliente' ? PORTAL_CLIENT_PROCESS : PORTAL_STAFF_HOME, { replace: true });
      })
      .catch(() => {
        setToken(null);
        logout();
      })
      .finally(() => setCheckingSession(false));
  }, [navigate, logout]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const result = await login(email, password);
      const user = result as { role?: string };
      navigate(user?.role === 'cliente' ? PORTAL_CLIENT_PROCESS : PORTAL_STAFF_HOME);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao fazer login');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-[100dvh] bg-gradient-to-br from-brand-800 via-brand-700 to-brand-900 flex flex-col items-center justify-center p-4 sm:p-6">
      <SeoHead
        title="Portal Andrade Isenções"
        description="Acesso ao portal Andrade Isenções."
        path={PORTAL_LOGIN}
        noindex
      />

      {checkingSession ? (
        <div className="text-brand-100 text-sm">Carregando…</div>
      ) : (
        <div className="w-full max-w-2xl">
          <Link to="/" className="flex justify-center mb-6">
            <BrandLogo
              src="/assets/logo/logo-header.png"
              alt="Andrade Isenções"
              imgClassName="h-10 md:h-11 brightness-0 invert"
              markClassName="text-brand-100"
            />
          </Link>

          <div className="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div className="px-5 sm:px-8 pt-6 pb-5 border-b border-slate-100">
              <div className="grid sm:grid-cols-2 gap-3">
                <AccessCard
                  icon={UserCircle}
                  title="Clientes"
                  accentClass="bg-emerald-100 text-emerald-700"
                  items={[
                    { icon: ClipboardList, label: 'Acompanhe o processo de isenção PCD' },
                    { icon: FileText, label: 'Envie e consulte documentos' },
                    { icon: ArrowRight, label: 'Veja as etapas da compra do veículo' },
                  ]}
                />
                <AccessCard
                  icon={Briefcase}
                  title="Equipe"
                  accentClass="bg-brand-100 text-brand-700"
                  items={[
                    { icon: Settings, label: 'Site, mídia e configurações' },
                    { icon: Mail, label: 'Contatos e atendimento' },
                    { icon: ClipboardList, label: 'Gestão de processos PCD' },
                  ]}
                />
              </div>

              <div className="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 rounded-xl bg-accent-light/60 border border-accent/15 px-4 py-3">
                <p className="text-xs text-slate-600">
                  <span className="font-semibold text-brand-800">Ainda não tem conta?</span>
                  {' '}Comece seu cadastro de isenção PCD.
                </p>
                <Link
                  to="/iniciar"
                  className="inline-flex items-center justify-center gap-1.5 shrink-0 px-4 py-2 rounded-lg bg-accent text-white text-xs font-semibold hover:bg-accent-hover transition-colors shadow-sm"
                >
                  Iniciar processo
                  <ArrowRight size={14} />
                </Link>
              </div>
            </div>

            <div className="px-5 sm:px-8 py-6">
              {error && (
                <div className="flex items-center gap-2 bg-red-50 text-red-600 px-3 py-2.5 rounded-xl mb-4 text-sm">
                  <AlertCircle size={16} className="flex-shrink-0" />
                  {error}
                </div>
              )}

              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid sm:grid-cols-2 gap-4">
                  <div>
                    <label htmlFor="login-email" className="block text-sm font-medium text-slate-700 mb-1">
                      E-mail
                    </label>
                    <div className="relative">
                      <Mail className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                      <input
                        id="login-email"
                        type="email"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm"
                        placeholder="seu@email.com"
                        autoComplete="email"
                        required
                      />
                    </div>
                  </div>

                  <div>
                    <label htmlFor="login-password" className="block text-sm font-medium text-slate-700 mb-1">
                      Senha
                    </label>
                    <div className="relative">
                      <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                      <input
                        id="login-password"
                        type="password"
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm"
                        autoComplete="current-password"
                        required
                      />
                    </div>
                  </div>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full bg-brand-600 text-white py-3 rounded-xl hover:bg-brand-700 transition-colors font-semibold text-sm disabled:opacity-50 shadow-sm"
                >
                  {loading ? 'Entrando…' : 'Entrar'}
                </button>
              </form>
            </div>

            <div className="px-5 sm:px-8 pb-6">
              <a
                href={WEBMAIL_URL}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-brand-300 hover:bg-brand-50/60 transition-colors group"
              >
                <div className="w-9 h-9 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center flex-shrink-0">
                  <Mail size={17} />
                </div>
                <div className="min-w-0 flex-1 text-left">
                  <p className="text-sm font-semibold text-brand-800">Webmail corporativo</p>
                  <p className="text-xs text-slate-500 truncate">email.andradeisencoes.com.br</p>
                </div>
                <ExternalLink size={15} className="text-slate-400 group-hover:text-brand-600 flex-shrink-0" />
              </a>
            </div>
          </div>

          <Link
            to="/"
            className="block text-center text-sm text-brand-100 hover:text-white mt-5 transition-colors"
          >
            ← Voltar ao site
          </Link>
        </div>
      )}
    </div>
  );
}
