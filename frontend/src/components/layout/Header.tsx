import { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { MessageCircle, User } from 'lucide-react';
import { useCms } from '../../context/CmsContext';
import { AccessibilityControl } from '../a11y/AccessibilityControl';
import { BrandLogo } from '../ui/BrandLogo';
import { trackWhatsAppClick } from '../../lib/google-analytics';
import { AnchorLink } from '../ui/AnchorLink';

function NavLabel({ label }: { label: string }) {
  const words = label.trim().split(/\s+/).filter(Boolean);
  if (words.length < 2) return label;
  const mid = Math.ceil(words.length / 2);
  return (
    <>
      {words.slice(0, mid).join(' ')}
      <br />
      {words.slice(mid).join(' ')}
    </>
  );
}

export function Header() {
  const { content } = useCms();
  const [scrolled, setScrolled] = useState(false);
  const location = useLocation();
  const isHome = location.pathname === '/';

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 10);
    window.addEventListener('scroll', onScroll);
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  if (!content) return null;
  const { site, navigation } = content;

  const mainNav = navigation.filter((n) => n.type === 'anchor' && !n.highlight);
  const extraNav = navigation.filter(
    (n) => n.type === 'internal' && n.id !== 'nav-entrar' && n.id !== 'nav-login'
  );
  const menuNav = [...mainNav, ...extraNav];
  const enterNav = navigation.find((n) => n.id === 'nav-entrar' || n.id === 'nav-login');

  const enterHref = enterNav?.href || '/entrar';
  const isEnterPage =
    location.pathname === '/entrar' ||
    location.pathname.startsWith('/entrar/') ||
    location.pathname.startsWith('/portal') ||
    location.pathname.startsWith('/admin') ||
    location.pathname === '/conta';

  return (
    <header
      className={`sticky top-0 z-50 transition-shadow ${scrolled ? 'shadow-md' : ''} bg-white border-b border-brand-100`}
    >
      <div className="max-w-7xl mx-auto px-2 sm:px-3 md:px-4">
        <div className="site-header-bar">
          <a href={isHome ? '#inicio' : '/'} className="site-header-logo" aria-label={`${site.title} — ir para o início`}>
            <BrandLogo
              src={site.logo}
              alt=""
              imgClassName="h-7 md:h-9 w-auto object-contain"
              markClassName="text-brand-600"
            />
          </a>

          <nav className="site-header-nav" aria-label="Menu principal">
            {menuNav.map((item) => (
              <AnchorLink key={item.id} href={item.href || '#'} className="site-header-nav-link">
                <NavLabel label={item.label} />
              </AnchorLink>
            ))}
          </nav>

          <div className="site-header-actions">
            <AccessibilityControl variant="header" />
            <Link
              to={enterHref}
              className={`site-header-icon-btn site-header-icon-btn--neutral ${isEnterPage ? 'is-active' : ''}`}
              aria-label={enterNav?.label || 'Entrar'}
            >
              <User size={18} aria-hidden />
            </Link>
            <a
              href={`https://wa.me/${site.whatsapp}`}
              target="_blank"
              rel="noopener noreferrer"
              className="site-header-icon-btn site-header-icon-btn--whatsapp"
              aria-label="Falar pelo WhatsApp"
              onClick={(e) => {
                e.preventDefault();
                trackWhatsAppClick(`https://wa.me/${site.whatsapp}`);
              }}
            >
              <MessageCircle size={18} aria-hidden />
            </a>
          </div>
        </div>
      </div>
    </header>
  );
}
