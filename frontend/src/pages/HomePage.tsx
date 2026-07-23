import { Header } from '../components/layout/Header';
import { Footer } from '../components/layout/Footer';
import { SectionRenderer } from '../components/sections/SectionRenderer';
import { CmsToolbar } from '../components/cms/CmsToolbar';
import { useCms } from '../context/CmsContext';
import { MessageCircle, ArrowUp } from 'lucide-react';
import { useState, useEffect } from 'react';

export function HomePage() {
  const { content, loading, isEditing } = useCms();
  const [showScrollTop, setShowScrollTop] = useState(false);

  useEffect(() => {
    const onScroll = () => setShowScrollTop(window.scrollY > 400);
    window.addEventListener('scroll', onScroll);
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-brand-600">
        <div className="text-white text-center">
          <div className="w-10 h-10 border-4 border-white/30 border-t-white rounded-full animate-spin mx-auto mb-3" />
          <p className="text-sm font-medium">Carregando Andrade Isenções...</p>
        </div>
      </div>
    );
  }

  if (!content) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p className="text-red-500">Erro ao carregar. Verifique se o servidor está rodando.</p>
      </div>
    );
  }

  return (
    <div className={isEditing ? 'cms-editing' : ''}>
      <Header />
      <main>
        <SectionRenderer />
      </main>
      <Footer />

      <a
        href={`https://wa.me/${content.site.whatsapp}`}
        target="_blank"
        rel="noopener noreferrer"
        className="fixed bottom-5 left-5 z-50 bg-accent text-white p-3.5 rounded-full shadow-lg hover:bg-accent-hover hover:scale-105 transition-all"
        aria-label="WhatsApp"
      >
        <MessageCircle size={26} />
      </a>

      {showScrollTop && (
        <button
          onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
          className="fixed bottom-5 left-[4.5rem] z-50 bg-brand-600 text-white p-2.5 rounded-full shadow-lg hover:bg-brand-700 transition-all"
          aria-label="Ir ao topo"
        >
          <ArrowUp size={20} />
        </button>
      )}

      <CmsToolbar />
    </div>
  );
}
