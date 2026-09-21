import { Link } from 'react-router-dom';
import { PageLayout } from '../components/layout/PageLayout';
import { SeoHead } from '../components/seo/SeoHead';
import { JsonLd } from '../components/seo/JsonLd';
import { useCms } from '../context/CmsContext';
import { buildBreadcrumbSchema, buildWebPageSchema } from '../lib/seo';
import { WhatsAppLink } from '../components/ui/WhatsAppLink';

export function AboutPage() {
  const { content } = useCms();
  const aboutSection = content?.sections.find((s) => s.id === 'quem-somos');
  const data = (aboutSection?.data ?? {}) as {
    quote?: string;
    founderName?: string;
    founderImage?: string;
    officeSlides?: string[];
    historyTitle?: string;
    historyParagraphs?: string[];
  };

  const paragraphs = data.historyParagraphs ?? [
    'A Andrade Isenções nasceu com o propósito de oferecer um atendimento mais humano, transparente e especializado às pessoas com deficiência que buscam informações e orientação para aquisição de veículos com benefícios fiscais.',
  ];

  return (
    <PageLayout>
      <SeoHead
        title="Quem Somos | Andrade Isenções"
        description="Conheça a história da Andrade Isenções — assessoria especializada em processos PCD para compra de veículos zero km em Mato Grosso."
        path="/quem-somos"
      />
      <JsonLd
        data={[
          buildWebPageSchema({
            title: 'Quem Somos — Andrade Isenções',
            description: 'História e missão da Andrade Isenções.',
            path: '/quem-somos',
          }),
          buildBreadcrumbSchema([
            { name: 'Início', path: '/' },
            { name: 'Quem Somos' },
          ]),
        ]}
      />

      <div className="py-10 md:py-14 bg-surface min-h-[60vh]">
        <div className="max-w-4xl mx-auto px-4">
          <h1 className="font-display text-3xl md:text-4xl font-bold text-brand-800 text-center mb-8">
            Quem Somos
          </h1>

          {data.quote && (
            <blockquote className="text-center mb-10">
              <p className="text-xl md:text-2xl font-display font-medium text-brand-800 italic leading-snug">
                “{data.quote}”
              </p>
              {data.founderName && (
                <cite className="not-italic text-brand-600 font-semibold text-sm mt-4 block">
                  {data.founderName}
                </cite>
              )}
            </blockquote>
          )}

          {data.officeSlides && data.officeSlides.length > 0 && (
            <div className="grid sm:grid-cols-2 gap-4 mb-10">
              {data.officeSlides.map((src, i) => (
                <img
                  key={i}
                  src={src}
                  alt={`Escritório Andrade Isenções ${i + 1}`}
                  className="rounded-xl shadow-md w-full h-48 object-cover"
                  loading="lazy"
                />
              ))}
            </div>
          )}

          <div className="bg-white rounded-2xl p-6 md:p-8 border border-brand-100 shadow-sm space-y-4">
            <h2 className="font-display font-bold text-brand-800 text-xl">
              {data.historyTitle || 'Nossa História'}
            </h2>
            {paragraphs.map((p, i) => (
              <p key={i} className="text-text-secondary text-base leading-relaxed text-justify">
                {p}
              </p>
            ))}
          </div>

          <div className="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
            <Link to="/iniciar" className="btn-primary text-sm text-center">
              Fazer análise PCD
            </Link>
            <WhatsAppLink className="btn-secondary text-sm text-center">
              Falar com especialista
            </WhatsAppLink>
          </div>
        </div>
      </div>
    </PageLayout>
  );
}
