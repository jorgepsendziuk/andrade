import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PageLayout } from '../components/layout/PageLayout';
import { SeoHead } from '../components/seo/SeoHead';
import { JsonLd } from '../components/seo/JsonLd';
import { fetchGuiaArticles } from '../lib/api';
import type { GuiaArticle } from '../types/site';
import { ArrowRight } from 'lucide-react';
import { buildBreadcrumbSchema, buildWebPageSchema } from '../lib/seo';

const GUIA_DESCRIPTION =
  'Guia completo sobre isenção PCD: direitos, documentos, processo de compra de carro zero km, IPI, ICMS e IPVA. Informações da Andrade Isenções em Cuiabá-MT.';

export function GuiaPage() {
  const [articles, setArticles] = useState<GuiaArticle[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchGuiaArticles()
      .then(setArticles)
      .catch(console.error)
      .finally(() => setLoading(false));
  }, []);

  return (
    <PageLayout>
      <SeoHead
        title="Guia PCD — Isenção de Carro Zero km | Andrade Isenções"
        description={GUIA_DESCRIPTION}
        path="/guia"
      />
      <JsonLd
        data={[
          buildWebPageSchema({
            title: 'Guia PCD — Andrade Isenções',
            description: GUIA_DESCRIPTION,
            path: '/guia',
          }),
          buildBreadcrumbSchema([
            { name: 'Início', path: '/' },
            { name: 'Guia PCD' },
          ]),
        ]}
      />

      <div className="py-10 md:py-14 bg-surface min-h-[60vh]">
        <div className="max-w-4xl mx-auto px-4">
          <h1 className="font-display text-3xl md:text-4xl font-bold text-brand-800 text-center mb-3">
            Guia PCD
          </h1>
          <p className="text-text-secondary text-center text-sm mb-10 max-w-xl mx-auto">
            Informações completas sobre direitos, isenções e processos para compra de veículo PCD em Mato Grosso.
          </p>

          {loading ? (
            <p className="text-center text-text-secondary">Carregando artigos...</p>
          ) : (
            <div className="grid gap-4">
              {articles.map((article) => (
                <Link
                  key={article.slug}
                  to={`/guia/${article.slug}`}
                  className="bg-white rounded-xl p-5 border border-brand-100 hover:border-brand-300 hover:shadow-md transition-all group"
                >
                  <h2 className="font-display font-bold text-brand-800 group-hover:text-brand-600 transition-colors mb-2">
                    {article.title}
                  </h2>
                  <p className="text-text-secondary text-sm mb-3">{article.excerpt}</p>
                  <span className="text-accent text-xs font-semibold inline-flex items-center gap-1">
                    Ler artigo <ArrowRight size={14} aria-hidden />
                  </span>
                </Link>
              ))}
            </div>
          )}
        </div>
      </div>
    </PageLayout>
  );
}
