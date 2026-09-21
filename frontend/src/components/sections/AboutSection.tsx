import { useState, useEffect, useCallback } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableImage } from '../cms/EditableImage';
import { useCms } from '../../context/CmsContext';
import { usePrefersReducedMotion } from '../../hooks/usePrefersReducedMotion';

export function AboutSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('quem-somos');
  const [slide, setSlide] = useState(0);
  const prefersReducedMotion = usePrefersReducedMotion();

  const data = section?.data as {
    title: string;
    quote: string;
    founderName: string;
    founderImage?: string;
    officeSlides?: string[];
    historyTitle: string;
    historyParagraphs: string[];
  };

  const slides =
    data?.officeSlides?.length
      ? data.officeSlides
      : data?.founderImage
        ? [data.founderImage]
        : [];

  const next = useCallback(() => {
    if (slides.length > 1) setSlide((c) => (c + 1) % slides.length);
  }, [slides.length]);

  useEffect(() => {
    if (slides.length <= 1 || prefersReducedMotion) return;
    const t = setInterval(next, 4500);
    return () => clearInterval(t);
  }, [next, slides.length, prefersReducedMotion]);

  if (!section?.enabled || !data) return null;

  return (
    <section id="sobre" className="py-10 md:py-12 bg-white" aria-labelledby="sobre-heading">
      <div className="max-w-6xl mx-auto px-4">
        <h2 id="sobre-heading" className="sr-only">
          Sobre a Andrade Isenções
        </h2>

        <blockquote className="relative text-center max-w-3xl mx-auto mb-8 md:mb-10 px-2">
          <span
            className="block text-5xl md:text-6xl text-accent/35 font-serif leading-none select-none mb-1"
            aria-hidden
          >
            “
          </span>
          <EditableText
            value={data.quote}
            onChange={(v) => updateSection('quem-somos', { quote: v })}
            as="p"
            className="text-xl md:text-2xl font-display font-medium text-brand-800 leading-snug italic"
            multiline
          />
          <footer className="mt-4">
            <cite className="not-italic text-brand-600 font-semibold text-sm md:text-base block">
              {data.founderName}
            </cite>
          </footer>
        </blockquote>

        <div className="grid lg:grid-cols-2 gap-8 lg:gap-10 items-stretch">
          {slides.length > 0 && (
            <div className="relative rounded-2xl overflow-hidden shadow-[var(--shadow-card)] border border-brand-100 h-[300px] md:h-[420px]">
              <div
                className="flex h-full w-full transition-transform duration-700 ease-in-out"
                style={{ transform: `translateX(-${slide * 100}%)` }}
              >
                {slides.map((src, i) => (
                  <div key={i} className="w-full flex-shrink-0 h-full">
                    <EditableImage
                      src={src}
                      alt={`Escritório Andrade ${i + 1}`}
                      onChange={(url) => {
                        const officeSlides = [...slides];
                        officeSlides[i] = url;
                        updateSection('quem-somos', {
                          officeSlides,
                          founderImage: officeSlides[0],
                        });
                      }}
                      className="w-full h-full object-cover"
                      loading="lazy"
                      wrapperClassName="w-full h-full"
                    />
                  </div>
                ))}
              </div>

              {slides.length > 1 && (
                <>
                  <button
                    onClick={() => setSlide((c) => (c - 1 + slides.length) % slides.length)}
                    className="absolute left-3 top-1/2 -translate-y-1/2 bg-white/95 hover:bg-white text-brand-700 p-2 rounded-full shadow-md transition-colors"
                    aria-label="Anterior"
                  >
                    <ChevronLeft size={18} />
                  </button>
                  <button
                    onClick={next}
                    className="absolute right-3 top-1/2 -translate-y-1/2 bg-white/95 hover:bg-white text-brand-700 p-2 rounded-full shadow-md transition-colors"
                    aria-label="Próximo"
                  >
                    <ChevronRight size={18} />
                  </button>
                  <div className="absolute bottom-4 left-0 right-0 flex justify-center gap-1.5">
                    {slides.map((_, i) => (
                      <button
                        key={i}
                        onClick={() => setSlide(i)}
                        aria-label={`Foto ${i + 1}`}
                        className={`h-1.5 rounded-full transition-all ${
                          i === slide ? 'bg-accent w-5' : 'bg-white/70 w-1.5'
                        }`}
                      />
                    ))}
                  </div>
                </>
              )}
            </div>
          )}

          <div className="flex flex-col justify-center">
            <div>
              <EditableText
                value={data.historyTitle}
                onChange={(v) => updateSection('quem-somos', { historyTitle: v })}
                as="h3"
                className="font-display font-bold text-brand-800 text-sm uppercase tracking-widest mb-4"
              />
              <div className="space-y-3.5">
                {data.historyParagraphs.map((p, i) => (
                  <EditableText
                    key={i}
                    value={p}
                    onChange={(v) => {
                      const historyParagraphs = [...data.historyParagraphs];
                      historyParagraphs[i] = v;
                      updateSection('quem-somos', { historyParagraphs });
                    }}
                    as="p"
                    className={`leading-relaxed text-justify ${
                      i === 0
                        ? 'text-brand-800 text-sm md:text-[15px] font-medium'
                        : 'text-text-secondary text-sm md:text-base'
                    }`}
                    multiline
                  />
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
