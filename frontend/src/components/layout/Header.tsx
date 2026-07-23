import { useState, useEffect } from 'react';
import { Menu, X, ChevronDown } from 'lucide-react';
import { useCms } from '../../context/CmsContext';
import type { NavItem } from '../../types/site';

function NavDropdown({ item }: { item: NavItem }) {
  const [open, setOpen] = useState(false);
  return (
    <div className="relative" onMouseEnter={() => setOpen(true)} onMouseLeave={() => setOpen(false)}>
      <button className="flex items-center gap-1 text-xs font-semibold tracking-wider text-brand-700 hover:text-brand-500 uppercase">
        {item.label}
        <ChevronDown size={12} className={`transition-transform ${open ? 'rotate-180' : ''}`} />
      </button>
      {open && item.children && (
        <div className="absolute top-full right-0 bg-white shadow-xl rounded py-2 min-w-[180px] border border-slate-100 z-50">
          {item.children.map((child) => (
            <a
              key={child.id}
              href={child.href}
              target={child.type === 'external' ? '_blank' : undefined}
              rel={child.type === 'external' ? 'noopener noreferrer' : undefined}
              className="block px-4 py-2 text-xs text-slate-600 hover:bg-brand-50 hover:text-brand-600 uppercase tracking-wide"
            >
              {child.label}
            </a>
          ))}
        </div>
      )}
    </div>
  );
}

export function Header() {
  const { content } = useCms();
  const [mobileOpen, setMobileOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 10);
    window.addEventListener('scroll', onScroll);
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  if (!content) return null;
  const { site, navigation } = content;

  const mainNav = navigation.filter((n) => n.type === 'anchor' && !n.highlight);
  const ctaNav = navigation.find((n) => n.highlight);
  const loginNav = navigation.find((n) => n.type === 'dropdown');

  return (
    <header className={`fixed top-0 left-0 right-0 z-50 transition-shadow ${scrolled ? 'shadow-md' : ''} bg-white border-b border-slate-100`}>
      <div className="max-w-6xl mx-auto px-4">
        <div className="flex items-center justify-between h-16 md:h-[72px]">
          <a href="#inicio" className="flex-shrink-0 py-2">
            <img src={site.logo} alt={site.title} className="h-10 md:h-12 w-auto object-contain" />
          </a>

          <nav className="hidden lg:flex items-center gap-5 xl:gap-7">
            {mainNav.map((item) => (
              <a
                key={item.id}
                href={item.href}
                className="text-xs font-semibold tracking-wider text-brand-700 hover:text-brand-500 uppercase transition-colors"
              >
                {item.label}
              </a>
            ))}
            {loginNav && <NavDropdown item={loginNav} />}
          </nav>

          <div className="hidden lg:block">
            {ctaNav && (
              <a href={ctaNav.href} className="btn-green-sm text-xs">
                {ctaNav.label}
              </a>
            )}
          </div>

          <button className="lg:hidden p-2 text-brand-700" onClick={() => setMobileOpen(!mobileOpen)} aria-label="Menu">
            {mobileOpen ? <X size={22} /> : <Menu size={22} />}
          </button>
        </div>
      </div>

      {mobileOpen && (
        <div className="lg:hidden bg-white border-t border-slate-100 py-4 px-4 space-y-3">
          {mainNav.map((item) => (
            <a
              key={item.id}
              href={item.href}
              onClick={() => setMobileOpen(false)}
              className="block text-sm font-semibold text-brand-700 uppercase tracking-wide"
            >
              {item.label}
            </a>
          ))}
          {ctaNav && (
            <a href={ctaNav.href} onClick={() => setMobileOpen(false)} className="btn-green-sm w-full text-center">
              {ctaNav.label}
            </a>
          )}
        </div>
      )}
    </header>
  );
}
