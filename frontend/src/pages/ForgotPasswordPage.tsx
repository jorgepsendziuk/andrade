import { useState } from 'react';
import { Link } from 'react-router-dom';
import { AlertCircle, ArrowLeft, CheckCircle, Mail } from 'lucide-react';
import { requestPasswordReset } from '../lib/api';
import { SeoHead } from '../components/seo/SeoHead';
import { BrandLogo } from '../components/ui/BrandLogo';
import { PORTAL_LOGIN } from '../lib/portal-routes';

export function ForgotPasswordPage() {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setMessage('');
    setLoading(true);
    try {
      const result = await requestPasswordReset(email);
      setMessage(result.message);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao solicitar recuperação');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-[100dvh] bg-gradient-to-br from-brand-800 via-brand-700 to-brand-900 flex flex-col items-center justify-center p-4">
      <SeoHead title="Recuperar senha | Portal Andrade" description="Recupere o acesso ao portal." path="/entrar/esqueci-senha" noindex />

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
          <h1 className="text-xl font-display font-bold text-brand-800 mb-2">Esqueci minha senha</h1>
          <p className="text-sm text-slate-600 mb-5">
            Informe o e-mail da sua conta. Se estiver cadastrado, enviaremos um link para criar uma nova senha.
          </p>

          {error && (
            <div className="flex items-center gap-2 bg-red-50 text-red-600 px-3 py-2.5 rounded-xl mb-4 text-sm">
              <AlertCircle size={16} />
              {error}
            </div>
          )}

          {message ? (
            <div className="space-y-4">
              <div className="flex items-start gap-2 bg-emerald-50 text-emerald-800 px-3 py-3 rounded-xl text-sm">
                <CheckCircle size={18} className="flex-shrink-0 mt-0.5" />
                <p>{message}</p>
              </div>
              <Link to={PORTAL_LOGIN} className="inline-flex items-center gap-2 text-sm text-brand-600 hover:underline">
                <ArrowLeft size={16} /> Voltar ao login
              </Link>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label htmlFor="forgot-email" className="block text-sm font-medium text-slate-700 mb-1">
                  E-mail
                </label>
                <div className="relative">
                  <Mail className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
                  <input
                    id="forgot-email"
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
              <button
                type="submit"
                disabled={loading}
                className="w-full bg-brand-600 text-white py-3 rounded-xl hover:bg-brand-700 transition-colors font-semibold text-sm disabled:opacity-50"
              >
                {loading ? 'Enviando…' : 'Enviar link de recuperação'}
              </button>
              <Link to={PORTAL_LOGIN} className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-brand-600">
                <ArrowLeft size={16} /> Voltar ao login
              </Link>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
