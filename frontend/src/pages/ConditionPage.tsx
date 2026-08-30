import { useEffect, useState, useMemo } from 'react';
import { useParams, Link } from 'react-router-dom';
import { PageLayout } from '../components/layout/PageLayout';
import { SeoHead } from '../components/seo/SeoHead';
import { JsonLd } from '../components/seo/JsonLd';
import { fetchCondition } from '../lib/api';
import type { ConditionPage } from '../types/site';
import { WhatsAppLink } from '../components/ui/WhatsAppLink';
import { ArrowRight } from 'lucide-react';
import {
  buildBreadcrumbSchema,
  buildFaqSchema,
  buildWebPageSchema,
} from '../lib/seo';

export function ConditionPage() {
  const { slug } = useParams<{ slug: string }>();
  const [condition, setCondition] = useState<ConditionPage | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  useEffect(() => {
    if (!slug) return;
    fetchCondition(slug)
      .then(setCondition)
      .catch(() => setError(true))
      .finally(() => setLoading(false));
  }, [slug]);

  const pageTitle = condition
    ? `${condition.title} | Andrade Isenções`
    : 'Isenção PCD | Andrade Isenções';

  const jsonLd = useMemo(() => {
    if (!condition || !slug) return null;
    return [
      buildWebPageSchema({
        title: condition.title,
        description: condition.metaDescription,
        path: `/isencao-pcd/${slug}`,
      }),
      buildBreadcrumbSchema([
        { name: 'Início', path: '/' },
        { name: 'Isenção PCD', path: '/#tenho-direito' },
        { name: condition.title },
      ]),
      buildFaqSchema(condition.faq),
    ];
  }, [condition, slug]);

  if (loading) {
    return (
      <PageLayout>
        <div className="py-20 text-center text-text-secondary">Carregando...</div>
      </PageLayout>
    );
  }

  if (error || !condition) {
    return (
      <PageLayout>
        <SeoHead title="Página não encontrada | Andrade Isenções" description="Página não encontrada." noindex />
        <div className="py-20 text-center">
          <h1 className="font-display text-2xl font-bold text-brand-800 mb-4">Página não encontrada</h1>
          <Link to="/" className="text-brand-600 hover:underline">Voltar ao início</Link>
        </div>
      </PageLayout>
    );
  }

  const whatsappMsg = `Olá! Tenho interesse em saber sobre isenção PCD para ${condition.title.replace('Isenção PCD — ', '')}.`;

  return (
    <PageLayout>
      <SeoHead
        title={pageTitle}
        description={condition.metaDescription}
        path={`/isencao-pcd/${slug}`}
        type="article"
      />
      {jsonLd && <JsonLd data={jsonLd} />}

      <article className="py-10 md:py-14" itemScope itemType="https://schema.org/WebPage">
        <div className="max-w-3xl mx-auto px-4">
          <nav aria-label="Trilha de navegação" className="text-xs text-text-secondary mb-6">
            <ol className="flex flex-wrap items-center gap-1 list-none p-0 m-0">
              <li>
                <Link to="/" className="hover:text-brand-600">Início</Link>
              </li>
              <li aria-hidden="true">/</li>
              <li>
                <Link to="/#tenho-direito" className="hover:text-brand-600">Isenção PCD</Link>
              </li>
              <li aria-hidden="true">/</li>
              <li>
                <span className="text-brand-700" aria-current="page">{condition.title}</span>
              </li>
            </ol>
          </nav>

          <h1 className="font-display text-3xl md:text-4xl font-bold text-brand-800 mb-6" itemProp="name">
            {condition.title}
          </h1>

          <section className="mb-8">
            <h2 className="font-display font-bold text-brand-700 text-lg mb-3">Quem pode ter direito?</h2>
            <p className="text-text-secondary text-sm leading-relaxed" itemProp="description">{condition.whoCan}</p>
          </section>

          {condition.allConditions && condition.allConditions.length > 0 && (
            <section className="mb-10">
              <h2 className="font-display font-bold text-brand-700 text-lg mb-4">
                Enfermidades que podem dar direito à isenção
              </h2>
              <ul className="grid sm:grid-cols-2 gap-2 list-none p-0 m-0">
                {condition.allConditions.map((item) => (
                  <li
                    key={item}
                    className="flex items-start gap-2 text-sm text-brand-800 bg-surface rounded-lg px-3 py-2 border border-brand-100"
                  >
                    <span className="text-accent font-bold mt-0.5" aria-hidden>✓</span>
                    <span>{item}</span>
                  </li>
                ))}
              </ul>
            </section>
          )}

          <section className="mb-8">
            <h2 className="font-display font-bold text-brand-700 text-lg mb-3">Quais benefícios podem existir?</h2>
            <ul className="space-y-2">
              {condition.benefits.map((b, i) => (
                <li key={i} className="flex items-start gap-2 text-sm text-text-secondary">
                  <span className="text-accent font-bold" aria-hidden>✓</span> {b}
                </li>
              ))}
            </ul>
          </section>

          <section className="mb-8">
            <h2 className="font-display font-bold text-brand-700 text-lg mb-3">Documentos necessários</h2>
            <ul className="space-y-2">
              {condition.documents.map((d, i) => (
                <li key={i} className="text-sm text-text-secondary">• {d}</li>
              ))}
            </ul>
          </section>

          <section className="mb-8">
            <h2 className="font-display font-bold text-brand-700 text-lg mb-3">Como funciona?</h2>
            <ol className="space-y-3">
              {condition.howItWorks.map((step, i) => (
                <li key={i} className="flex gap-3 text-sm text-text-secondary">
                  <span className="font-display font-bold text-accent flex-shrink-0">{String(i + 1).padStart(2, '0')}</span>
                  {step}
                </li>
              ))}
            </ol>
          </section>

          <section className="mb-10">
            <h2 className="font-display font-bold text-brand-700 text-lg mb-4">Perguntas frequentes</h2>
            <div className="space-y-3">
              {condition.faq.map((item, i) => (
                <details key={i} className="bg-surface rounded-xl border border-brand-100 group">
                  <summary className="font-semibold text-brand-800 text-sm p-4 cursor-pointer list-none flex justify-between items-center">
                    {item.question}
                    <span className="text-brand-400 group-open:rotate-180 transition-transform ml-2" aria-hidden>▼</span>
                  </summary>
                  <p className="text-text-secondary text-sm leading-relaxed px-4 pb-4">{item.answer}</p>
                </details>
              ))}
            </div>
          </section>

          <div className="bg-brand-800 rounded-2xl p-6 md:p-8 text-center text-white">
            <h2 className="font-display font-bold text-xl mb-3">Fale com um especialista</h2>
            <p className="text-brand-100 text-sm mb-6">
              Cada caso possui características específicas. Nossa equipe analisa sua situação e orienta você.
            </p>
            <WhatsAppLink message={whatsappMsg} className="btn-primary">
              Analisar meu caso
              <ArrowRight size={18} />
            </WhatsAppLink>
          </div>
        </div>
      </article>
    </PageLayout>
  );
}
