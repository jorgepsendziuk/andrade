import { useState, useEffect, useRef } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { Menu, X, MessageCircle, User } from 'lucide-react';
import { useCms } from '../../context/CmsContext';
import { useFocusTrap } from '../../hooks/useFocusTrap';
import { AccessibilityControl } from '../a11y/AccessibilityControl';
import { BrandLogo } from '../ui/BrandLogo';
import { trackWhatsAppClick } from '../../lib/google-analytics';

export function Header() {
  const { content } = useCms();
  const [mobileOpen, setMobileOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const mobileMenuRef = useRef<HTMLDivElement>(null);
  const location = useLocation();
  const isHome = location.pathname === '/';
  const mobileMenuId = 'mobile-nav-menu';

  useFocusTrap(mobileMenuRef, mobileOpen);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 10);
    window.addEventListener('scroll', onScroll);
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  useEffect(() => {
    if (!mobileOpen) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setMobileOpen(false);
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [mobileOpen]);

  useEffect(() => {
    setMobileOpen(false);
  }, [location.pathname]);

  if (!content) return null;
  const { site, navigation } = content;

  const mainNav = navigation.filter((n) => n.type === 'anchor' && !n.highlight);
  const ctaNav = navigation.find((n) => n.highlight);
  const enterNav = navigation.find((n) => n.id === 'nav-entrar' || n.id === 'nav-login');

  const navHref = (href: string) => (isHome ? href : `/${href}`);

  const enterHref = enterNav?.href || '/entrar';
  const isEnterPage =
    location.pathname === '/entrar' ||
    location.pathname.startsWith('/entrar/') ||
    location.pathname.startsWith('/portal') ||
    location.pathname.startsWith('/admin') ||
    location.pathname === '/conta';

  return (
    <header
      className={`fixed top-0 left-0 right-0 z-50 transition-shadow ${scrolled ? 'shadow-md' : ''} bg-white border-b border-brand-100`}
    >
      <div className="max-w-6xl mx-auto px-3 md:px-4">
        <div className="flex items-center justify-between h-14 md:h-16 gap-2">
          <a href={isHome ? '#inicio' : '/'} className="flex-shrink-0" aria-label={`${site.title} — ir para o início`}>
            <BrandLogo
              src={site.logo}
              alt=""
              imgClassName="h-8 md:h-10 w-auto object-contain"
              markClassName="text-brand-600"
            />
          </a>

          <nav className="hidden lg:flex items-center gap-0.5 xl:gap-1 flex-1 justify-center min-w-0" aria-label="Menu principal">
            {mainNav.map((item) => (
              <a
                key={item.id}
                href={navHref(item.href!)}
                className="text-[10px] xl:text-[11px] font-semibold text-brand-700 hover:text-accent whitespace-nowrap px-1.5 xl:px-2 py-1 rounded transition-colors"
              >
                {item.label}
              </a>
            ))}
          </nav>

          <div className="hidden lg:flex items-center gap-1.5 flex-shrink-0">
            <AccessibilityControl variant="header" />
            <Link
              to={enterHref}
              className={`flex items-center gap-1.5 text-[11px] font-semibold px-2.5 py-2 rounded-lg transition-colors ${
                isEnterPage
                  ? 'bg-brand-100 text-brand-700'
                  : 'text-brand-700 hover:text-brand-500 hover:bg-brand-50'
              }`}
            >
              <User size={16} aria-hidden />
              {enterNav?.label || 'Entrar'}
            </Link>
            {ctaNav && (
              <a
                href={`https://wa.me/${site.whatsapp}`}
                target="_blank"
                rel="noopener noreferrer"
                className="btn-primary-sm !py-2 !px-2.5 xl:!px-4 !text-[10px] xl:!text-xs whitespace-nowrap"
                onClick={(e) => {
                  e.preventDefault();
                  trackWhatsAppClick(`https://wa.me/${site.whatsapp}`);
                }}
              >
                <MessageCircle size={15} className="flex-shrink-0" aria-hidden />
                <span className="hidden xl:inline">{ctaNav.label}</span>
                <span className="xl:hidden">WhatsApp</span>
              </a>
            )}
          </div>

          <div className="flex lg:hidden items-center gap-1 flex-shrink-0">
            <AccessibilityControl variant="header" />
            <Link
              to={enterHref}
              className="p-2 text-brand-700"
              aria-label={enterNav?.label || 'Entrar'}
            >
              <User size={20} aria-hidden />
            </Link>
            <button
              type="button"
              className="p-2 text-brand-700"
              onClick={() => setMobileOpen(!mobileOpen)}
              aria-expanded={mobileOpen}
              aria-controls={mobileMenuId}
              aria-label={mobileOpen ? 'Fechar menu de navegação' : 'Abrir menu de navegação'}
            >
              {mobileOpen ? <X size={22} aria-hidden /> : <Menu size={22} aria-hidden />}
            </button>
          </div>
        </div>
      </div>

      {mobileOpen && (
        <div
          ref={mobileMenuRef}
          id={mobileMenuId}
          className="lg:hidden bg-white border-t border-brand-100 py-3 px-4 space-y-1 max-h-[70vh] overflow-y-auto"
          role="navigation"
          aria-label="Menu mobile"
        >
          {mainNav.map((item) => (
            <a
              key={item.id}
              href={navHref(item.href!)}
              onClick={() => setMobileOpen(false)}
              className="block text-sm font-semibold text-brand-700 py-2"
            >
              {item.label}
            </a>
          ))}
          <Link
            to={enterHref}
            onClick={() => setMobileOpen(false)}
            className="flex items-center gap-2 text-sm font-semibold text-brand-600 py-2"
          >
            <User size={16} aria-hidden />
            {enterNav?.label || 'Entrar'}
          </Link>
          {ctaNav && (
            <a
              href={`https://wa.me/${site.whatsapp}`}
              target="_blank"
              rel="noopener noreferrer"
              onClick={(e) => {
                e.preventDefault();
                setMobileOpen(false);
                trackWhatsAppClick(`https://wa.me/${site.whatsapp}`);
              }}
              className="btn-primary-sm w-full justify-center mt-2"
            >
              <MessageCircle size={16} aria-hidden />
              {ctaNav.label}
            </a>
          )}
        </div>
      )}
    </header>
  );
}
