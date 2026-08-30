import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { X } from 'lucide-react';

const CONSENT_KEY = 'andrade_cookie_consent';

function loadGa4() {
  const gaId = import.meta.env.VITE_GA_MEASUREMENT_ID as string | undefined;
  if (!gaId || typeof window === 'undefined') return;

  const script = document.createElement('script');
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtag/js?id=${gaId}`;
  document.head.appendChild(script);
  window.dataLayer = window.dataLayer || [];
  function gtag(...args: unknown[]) {
    window.dataLayer!.push(args);
  }
  gtag('js', new Date());
  gtag('config', gaId);
}

export function CookieBanner() {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const consent = localStorage.getItem(CONSENT_KEY);
    if (!consent) {
      setVisible(true);
    } else if (consent === 'analytics') {
      loadGa4();
    }
  }, []);

  const accept = (type: 'essential' | 'analytics') => {
    localStorage.setItem(CONSENT_KEY, type);
    setVisible(false);
    if (type === 'analytics') loadGa4();
  };

  if (!visible) return null;

  return (
    <div className="fixed bottom-0 inset-x-0 z-50 p-4 md:p-6" role="dialog" aria-label="Consentimento de cookies">
      <div className="max-w-4xl mx-auto bg-white rounded-2xl shadow-2xl border border-brand-100 p-5 md:flex md:items-center md:gap-6">
        <div className="flex-1 text-sm text-slate-600 mb-4 md:mb-0">
          <p className="font-semibold text-brand-800 mb-1">Cookies e privacidade</p>
          <p>
            Usamos cookies essenciais para o funcionamento do site e, com seu consentimento, cookies analíticos (Google Analytics).
            Saiba mais na{' '}
            <Link to="/cookies" className="text-brand-600 underline">Política de Cookies</Link> e{' '}
            <Link to="/privacidade" className="text-brand-600 underline">Privacidade</Link>.
          </p>
        </div>
        <div className="flex flex-col sm:flex-row gap-2 flex-shrink-0">
          <button type="button" onClick={() => accept('essential')} className="btn-secondary text-xs py-2 px-4">
            Apenas essenciais
          </button>
          <button type="button" onClick={() => accept('analytics')} className="btn-primary text-xs py-2 px-4">
            Aceitar todos
          </button>
        </div>
        <button type="button" onClick={() => accept('essential')} className="absolute top-3 right-3 md:hidden text-slate-400" aria-label="Fechar">
          <X size={18} />
        </button>
      </div>
    </div>
  );
}
