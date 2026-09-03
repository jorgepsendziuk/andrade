import { MessageCircle, Phone, Mail, MapPin } from 'lucide-react';
import { InstagramIcon } from '../ui/InstagramIcon';
import { BrandLogo } from '../ui/BrandLogo';
import { RegisteredMarkBadge } from '../ui/RegisteredMarkBadge';
import { useCms } from '../../context/CmsContext';
import { trackWhatsAppClick } from '../../lib/google-analytics';

const socialIcons: Record<string, React.ComponentType<{ size?: number }>> = {
  instagram: InstagramIcon,
  whatsapp: MessageCircle,
};

export function Footer() {
  const { content } = useCms();
  if (!content) return null;
  const { site, footer } = content;

  const footerData = footer ?? {
    description: site.description,
    columns: [],
    social: [],
  };

  const legalLinks = footerData.legalLinks ?? [
    { id: 'l1', label: 'Privacidade', href: '/privacidade' },
    { id: 'l2', label: 'Termos de Uso', href: '/termos' },
    { id: 'l3', label: 'Cookies', href: '/cookies' },
    { id: 'l4', label: 'Iniciar processo', href: '/iniciar' },
  ];

  return (
    <footer className="bg-brand-800 text-white" role="contentinfo">
      <div className="max-w-6xl mx-auto px-4 py-10 md:py-12">
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-8">
          <div className="col-span-2 md:col-span-1">
            <BrandLogo
              src={site.logo}
              alt={site.title}
              imgClassName="h-10 brightness-0 invert"
              markClassName="text-brand-200"
              className="mb-3"
            />
            <RegisteredMarkBadge className="mb-4" />
            <p className="text-brand-100 text-xs leading-relaxed mb-4">{footerData.description}</p>
            <div className="flex gap-3">
              {(footerData.social ?? []).map((s) => {
                const Icon = socialIcons[s.icon] || MessageCircle;
                return (
                  <a
                    key={s.id}
                    href={s.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-brand-200 hover:text-accent transition-colors"
                    aria-label={s.label}
                  >
                    <Icon size={20} />
                  </a>
                );
              })}
            </div>
          </div>

          {(footerData.columns ?? []).map((col) => (
            <div key={col.id}>
              <h4 className="font-display font-bold text-sm uppercase tracking-wide mb-3">{col.title}</h4>
              <ul className="space-y-2">
                {col.links.map((link) => (
                  <li key={link.id}>
                    <a
                      href={link.href}
                      className="text-brand-100 text-xs hover:text-accent transition-colors"
                    >
                      {link.label}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          ))}

          <div>
            <h4 className="font-display font-bold text-sm uppercase tracking-wide mb-3">
              {footerData.contactTitle || 'Contato'}
            </h4>
            <ul className="space-y-2 text-brand-100 text-xs">
              <li className="flex items-center gap-2">
                <MessageCircle size={14} className="text-accent flex-shrink-0" />
                <a
                  href={`https://wa.me/${site.whatsapp}`}
                  className="hover:text-accent"
                  onClick={(e) => {
                    e.preventDefault();
                    trackWhatsAppClick(`https://wa.me/${site.whatsapp}`);
                  }}
                >
                  WhatsApp
                </a>
              </li>
              <li className="flex items-center gap-2">
                <Phone size={14} className="text-accent flex-shrink-0" />
                <a href={`tel:${site.phone.replace(/\s/g, '')}`} className="hover:text-accent">
                  {site.phone}
                </a>
              </li>
              <li className="flex items-center gap-2">
                <Mail size={14} className="text-accent flex-shrink-0" />
                <a href={`mailto:${site.email}`} className="hover:text-accent">
                  {site.email}
                </a>
              </li>
              <li className="flex items-start gap-2">
                <MapPin size={14} className="text-accent flex-shrink-0 mt-0.5" />
                <span>{site.address}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div className="border-t border-brand-700 py-4">
        <div className="max-w-6xl mx-auto px-4 text-center space-y-1.5">
          <div className="flex flex-wrap justify-center gap-x-4 gap-y-1 text-xs text-brand-200 mb-2">
            {legalLinks.map((link) => (
              <a key={link.id} href={link.href} className="hover:text-accent transition-colors">
                {link.label}
              </a>
            ))}
          </div>
          <p className="text-brand-200 text-xs">{site.copyright}</p>
          <p className="text-brand-300/80 text-[10px]">
            {footerData.trademarkText || 'Andrade Isenções® é marca registrada. Todos os direitos reservados.'}
          </p>
        </div>
      </div>
    </footer>
  );
}
