import { useEffect, useState, useMemo } from 'react';
import { useParams, Link } from 'react-router-dom';
import { PageLayout } from '../components/layout/PageLayout';
import { SeoHead } from '../components/seo/SeoHead';
import { JsonLd } from '../components/seo/JsonLd';
import { fetchGuiaArticle } from '../lib/api';
import type { GuiaArticle } from '../types/site';
import { WhatsAppLink } from '../components/ui/WhatsAppLink';
import { ArrowRight } from 'lucide-react';
import { buildArticleSchema, buildBreadcrumbSchema } from '../lib/seo';

export function ArticlePage() {
  const { slug } = useParams<{ slug: string }>();
  const [article, setArticle] = useState<GuiaArticle | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  useEffect(() => {
    if (!slug) return;
    fetchGuiaArticle(slug)
      .then(setArticle)
      .catch(() => setError(true))
      .finally(() => setLoading(false));
  }, [slug]);

  const pageTitle = article
    ? `${article.title} | Guia PCD — Andrade Isenções`
    : 'Guia PCD | Andrade Isenções';

  const jsonLd = useMemo(() => {
    if (!article || !slug) return null;
    return [
      buildArticleSchema({
        title: article.title,
        description: article.metaDescription,
        path: `/guia/${slug}`,
        publishedAt: article.publishedAt,
      }),
      buildBreadcrumbSchema([
        { name: 'Início', path: '/' },
        { name: 'Guia PCD', path: '/guia' },
        { name: article.title },
      ]),
    ];
  }, [article, slug]);

  if (loading) {
    return (
      <PageLayout>
        <div className="py-20 text-center text-text-secondary">Carregando...</div>
      </PageLayout>
    );
  }

  if (error || !article) {
    return (
      <PageLayout>
        <SeoHead title="Artigo não encontrado | Andrade Isenções" description="Artigo não encontrado." noindex />
        <div className="py-20 text-center">
          <h1 className="font-display text-2xl font-bold text-brand-800 mb-4">Artigo não encontrado</h1>
          <Link to="/guia" className="text-brand-600 hover:underline">Voltar ao Guia PCD</Link>
        </div>
      </PageLayout>
    );
  }

  return (
    <PageLayout>
      <SeoHead
        title={pageTitle}
        description={article.metaDescription}
        path={`/guia/${slug}`}
        type="article"
      />
      {jsonLd && <JsonLd data={jsonLd} />}

      <article className="py-10 md:py-14" itemScope itemType="https://schema.org/Article">
        <div className="max-w-6xl mx-auto px-4 grid lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2">
            <nav aria-label="Trilha de navegação" className="text-xs text-text-secondary mb-6">
              <ol className="flex flex-wrap items-center gap-1 list-none p-0 m-0">
                <li><Link to="/" className="hover:text-brand-600">Início</Link></li>
                <li aria-hidden="true">/</li>
                <li><Link to="/guia" className="hover:text-brand-600">Guia PCD</Link></li>
                <li aria-hidden="true">/</li>
                <li><span className="text-brand-700" aria-current="page">{article.title}</span></li>
              </ol>
            </nav>

            <h1 className="font-display text-3xl md:text-4xl font-bold text-brand-800 mb-6" itemProp="headline">
              {article.title}
            </h1>

            <div className="prose prose-sm max-w-none space-y-4" itemProp="articleBody">
              {article.content.map((paragraph, i) => (
                <p key={i} className="text-text-secondary text-sm leading-relaxed">{paragraph}</p>
              ))}
            </div>
          </div>

          <aside className="space-y-6">
            <div className="bg-brand-800 rounded-2xl p-6 text-white sticky top-24">
              <h2 className="font-display font-bold text-lg mb-3">Precisa de ajuda?</h2>
              <p className="text-brand-100 text-xs mb-4">
                Nossa equipe especializada pode analisar seu caso e orientar sobre os benefícios aplicáveis.
              </p>
              <WhatsAppLink className="btn-primary w-full">
                Falar com especialista
                <ArrowRight size={16} />
              </WhatsAppLink>
            </div>

            {article.relatedConditions && article.relatedConditions.length > 0 && (
              <div className="bg-surface rounded-xl p-5 border border-brand-100">
                <h2 className="font-display font-bold text-brand-800 text-sm mb-3">Condições relacionadas</h2>
                <ul className="space-y-2">
                  {article.relatedConditions.map((condSlug) => (
                    <li key={condSlug}>
                      <Link
                        to={`/isencao-pcd/${condSlug}`}
                        className="text-brand-600 text-xs hover:underline capitalize"
                      >
                        {condSlug.replace(/-/g, ' ')}
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </aside>
        </div>
      </article>
    </PageLayout>
  );
}
