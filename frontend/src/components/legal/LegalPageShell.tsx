import { type ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { ChevronRight, type LucideIcon } from 'lucide-react';
import { PageLayout } from '../layout/PageLayout';
import { JsonLd } from '../seo/JsonLd';
import { SeoHead } from '../seo/SeoHead';
import { buildBreadcrumbSchema, buildWebPageSchema } from '../../lib/seo';

export interface LegalTocItem {
  id: string;
  label: string;
}

export interface LegalRelatedLink {
  to: string;
  label: string;
}

interface LegalPageShellProps {
  title: string;
  description: string;
  path: string;
  icon: LucideIcon;
  updatedAt: string;
  breadcrumbLabel: string;
  toc?: LegalTocItem[];
  relatedLinks?: LegalRelatedLink[];
  callout?: ReactNode;
  children: ReactNode;
}

export function LegalPageShell({
  title,
  description,
  path,
  icon: Icon,
  updatedAt,
  breadcrumbLabel,
  toc,
  relatedLinks,
  callout,
  children,
}: LegalPageShellProps) {
  return (
    <PageLayout>
      <SeoHead title={`${title} | Andrade Isenções`} description={description} path={path} />
      <JsonLd
        data={[
          buildWebPageSchema({ title, description, path }),
          buildBreadcrumbSchema([
            { name: 'Início', path: '/' },
            { name: breadcrumbLabel },
          ]),
        ]}
      />

      <div className="bg-surface border-b border-brand-100">
        <div className="max-w-5xl mx-auto px-4 py-10 md:py-14">
          <nav className="flex items-center gap-1.5 text-xs text-text-secondary mb-6" aria-label="Breadcrumb">
            <Link to="/" className="hover:text-brand-600 transition-colors">
              Início
            </Link>
            <ChevronRight size={14} className="text-brand-300 shrink-0" aria-hidden />
            <span className="text-brand-700 font-medium">{breadcrumbLabel}</span>
          </nav>

          <div className="flex flex-col md:flex-row md:items-start gap-5">
            <div className="icon-circle-blue w-14 h-14 shrink-0">
              <Icon size={26} aria-hidden />
            </div>
            <div className="flex-1 min-w-0">
              <h1 className="font-display text-3xl md:text-4xl font-extrabold text-brand-800 leading-tight">
                {title}
              </h1>
              <p className="text-text-secondary text-sm md:text-base mt-3 max-w-2xl leading-relaxed">
                {description}
              </p>
              <p className="text-xs text-text-secondary mt-4 inline-flex items-center gap-2 bg-white/80 border border-brand-100 rounded-full px-3 py-1">
                <span className="w-1.5 h-1.5 rounded-full bg-accent shrink-0" aria-hidden />
                Última atualização: <strong className="font-semibold text-brand-800">{updatedAt}</strong>
              </p>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-5xl mx-auto px-4 py-10 md:py-14">
        <div className="flex flex-col lg:flex-row gap-8 lg:gap-12">
          {toc && toc.length > 0 && (
            <aside className="lg:w-56 shrink-0 order-2 lg:order-1">
              <div className="lg:sticky lg:top-24 section-box p-4">
                <p className="font-display text-xs font-bold uppercase tracking-wide text-brand-800 mb-3">
                  Nesta página
                </p>
                <nav className="legal-toc space-y-0.5" aria-label="Índice do documento">
                  {toc.map((item) => (
                    <a key={item.id} href={`#${item.id}`}>
                      {item.label}
                    </a>
                  ))}
                </nav>
              </div>
            </aside>
          )}

          <div className="flex-1 min-w-0 order-1 lg:order-2">
            {callout && <div className="legal-callout mb-8">{callout}</div>}

            <article className="section-box p-6 md:p-10 legal-prose">{children}</article>

            {relatedLinks && relatedLinks.length > 0 && (
              <div className="mt-8 grid sm:grid-cols-2 gap-3">
                {relatedLinks.map((link) => (
                  <Link
                    key={link.to}
                    to={link.to}
                    className="card-vivid p-4 flex items-center justify-between gap-2 group"
                  >
                    <span className="font-display font-semibold text-sm text-brand-800 group-hover:text-brand-600 transition-colors">
                      {link.label}
                    </span>
                    <ChevronRight size={16} className="text-brand-400 group-hover:text-brand-600 shrink-0" />
                  </Link>
                ))}
              </div>
            )}

            <div className="mt-8 text-center">
              <p className="text-sm text-text-secondary mb-3">Dúvidas sobre seus dados ou cookies?</p>
              <a href="mailto:comercial@andradeisencoes.com.br" className="btn-secondary text-xs">
                comercial@andradeisencoes.com.br
              </a>
            </div>
          </div>
        </div>
      </div>
    </PageLayout>
  );
}
