import { Header } from './Header';
import { Footer } from './Footer';
import { CookieBanner } from './CookieBanner';
import { CmsToolbar } from '../cms/CmsToolbar';
import { SkipLink } from '../a11y/SkipLink';
import { AccessibilityControl } from '../a11y/AccessibilityControl';
import { useCms } from '../../context/CmsContext';
import { MessageCircle, ArrowUp } from 'lucide-react';
import { useState, useEffect, type ReactNode } from 'react';
import { trackWhatsAppClick } from '../../lib/google-analytics';

export function PageLayout({ children }: { children: ReactNode }) {
  const { content, loading, isEditing } = useCms();
  const [showScrollTop, setShowScrollTop] = useState(false);

  useEffect(() => {
    const onScroll = () => setShowScrollTop(window.scrollY > 400);
    window.addEventListener('scroll', onScroll);
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-brand-800" role="status" aria-live="polite" aria-busy="true">
        <div className="text-white text-center">
          <div className="w-10 h-10 border-4 border-white/30 border-t-white rounded-full animate-spin mx-auto mb-3" aria-hidden />
          <p className="text-sm font-medium">Carregando Andrade Isenções...</p>
        </div>
      </div>
    );
  }

  if (!content) {
    return (
      <div className="min-h-screen flex items-center justify-center" role="alert">
        <p className="text-red-500">Erro ao carregar. Verifique se o servidor está rodando.</p>
      </div>
    );
  }

  return (
    <div className={isEditing ? 'cms-editing' : ''}>
      <SkipLink />
      <Header />
      <main id="main-content" tabIndex={-1}>
        {children}
      </main>
      <Footer />

      <a
        href={`https://wa.me/${content.site.whatsapp}`}
        target="_blank"
        rel="noopener noreferrer"
        className="fixed bottom-5 left-5 z-50 bg-accent text-white p-3.5 rounded-full shadow-lg hover:bg-accent-hover hover:scale-105 transition-all"
        aria-label="Falar pelo WhatsApp"
        onClick={(e) => {
          e.preventDefault();
          trackWhatsAppClick(`https://wa.me/${content.site.whatsapp}`);
        }}
      >
        <MessageCircle size={26} aria-hidden />
      </a>

      {showScrollTop && (
        <button
          type="button"
          onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
          className="fixed bottom-5 left-[4.5rem] z-50 bg-brand-600 text-white p-2.5 rounded-full shadow-lg hover:bg-brand-700 transition-all"
          aria-label="Voltar ao topo da página"
        >
          <ArrowUp size={20} aria-hidden />
        </button>
      )}

      <AccessibilityControl variant="fab" />
      <CmsToolbar />
      <CookieBanner />
    </div>
  );
}
