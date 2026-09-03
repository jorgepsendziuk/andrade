import { useMemo, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { AlertCircle, ArrowLeft, CheckCircle, Lock } from 'lucide-react';
import { resetPasswordWithToken } from '../lib/api';
import { SeoHead } from '../components/seo/SeoHead';
import { BrandLogo } from '../components/ui/BrandLogo';
import { PORTAL_LOGIN } from '../lib/portal-routes';

export function ResetPasswordPage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const token = useMemo(() => searchParams.get('token') || '', [searchParams]);
  const [password, setPassword] = useState('');
  const [confirm, setConfirm] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [done, setDone] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (password.length < 8) {
      setError('A senha deve ter no mínimo 8 caracteres.');
      return;
    }
    if (password !== confirm) {
      setError('As senhas não coincidem.');
      return;
    }
    setLoading(true);
    try {
      await resetPasswordWithToken(token, password);
      setDone(true);
      window.setTimeout(() => navigate(PORTAL_LOGIN, { replace: true }), 2500);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao redefinir senha');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-[100dvh] bg-gradient-to-br from-brand-800 via-brand-700 to-brand-900 flex flex-col items-center justify-center p-4">
      <SeoHead title="Nova senha | Portal Andrade" description="Defina uma nova senha de acesso." path="/entrar/redefinir-senha" noindex />

      <div className="w-full max-w-md">
        <Link to="/" className="flex justify-center mb-6">
          <BrandLogo
            src="/assets/logo/logo-header.png"
            alt="Andrade Isenções"
            imgClassName="h-10 md:h-11 brightness-0 invert"
            markClassName="text-brand-100"
          />
        </Link>

        <div className="bg-white rounded-2xl shadow-2xl p-6">
          <h1 className="text-xl font-display font-bold text-brand-800 mb-2">Criar nova senha</h1>

          {!token ? (
            <div className="space-y-4">
              <p className="text-sm text-red-600">Link inválido. Solicite uma nova recuperação de senha.</p>
              <Link to="/entrar/esqueci-senha" className="text-sm text-brand-600 hover:underline">
                Solicitar novo link
              </Link>
            </div>
          ) : done ? (
            <div className="flex items-start gap-2 bg-emerald-50 text-emerald-800 px-3 py-3 rounded-xl text-sm">
              <CheckCircle size={18} className="flex-shrink-0 mt-0.5" />
              <p>Senha redefinida! Redirecionando para o login…</p>
            </div>
          ) : (
            <>
              <p className="text-sm text-slate-600 mb-5">Escolha uma nova senha com no mínimo 8 caracteres.</p>

              {error && (
                <div className="flex items-center gap-2 bg-red-50 text-red-600 px-3 py-2.5 rounded-xl mb-4 text-sm">
                  <AlertCircle size={16} />
                  {error}
                </div>
              )}

              <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                  <label htmlFor="new-password" className="block text-sm font-medium text-slate-700 mb-1">
                    Nova senha
                  </label>
                  <div className="relative">
                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                    <input
                      id="new-password"
                      type="password"
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm"
                      autoComplete="new-password"
                      required
                    />
                  </div>
                </div>
                <div>
                  <label htmlFor="confirm-password" className="block text-sm font-medium text-slate-700 mb-1">
                    Confirmar senha
                  </label>
                  <div className="relative">
                    <Lock className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                    <input
                      id="confirm-password"
                      type="password"
                      value={confirm}
                      onChange={(e) => setConfirm(e.target.value)}
                      className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none text-sm"
                      autoComplete="new-password"
                      required
                    />
                  </div>
                </div>
                <button
                  type="submit"
                  disabled={loading}
                  className="w-full bg-brand-600 text-white py-3 rounded-xl hover:bg-brand-700 transition-colors font-semibold text-sm disabled:opacity-50"
                >
                  {loading ? 'Salvando…' : 'Salvar nova senha'}
                </button>
                <Link to={PORTAL_LOGIN} className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-brand-600">
                  <ArrowLeft size={16} /> Voltar ao login
                </Link>
              </form>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
