import { useState, useEffect, useCallback } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';
import type { AboutCard } from '../../types/site';

export function AboutSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('quem-somos');
  const [slide, setSlide] = useState(0);

  const data = section?.data as {
    title: string;
    cards: AboutCard[];
    historyTitle: string;
    historyParagraphs: string[];
    officeSlides?: string[];
    officeImage?: string;
  } | undefined;

  const slides =
    data?.officeSlides?.length ? data.officeSlides : data?.officeImage ? [data.officeImage] : [];

  const next = useCallback(() => {
    if (slides.length > 1) setSlide((c) => (c + 1) % slides.length);
  }, [slides.length]);

  useEffect(() => {
    if (slides.length <= 1) return;
    const t = setInterval(next, 4500);
    return () => clearInterval(t);
  }, [next, slides.length]);

  if (!section?.enabled || !data) return null;

  const updateCard = (cardId: string, field: 'heading' | 'text', value: string) => {
    const cards = data.cards.map((c) => (c.id === cardId ? { ...c, [field]: value } : c));
    updateSection('quem-somos', { cards });
  };

  return (
    <section id="quem-somos" className="py-12 md:py-16 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('quem-somos', { title: v })}
          as="h2"
          className="section-title"
        />

        <div className="grid md:grid-cols-3 gap-4 mb-10">
          {data.cards.map((card) => (
            <div key={card.id} className="section-box overflow-hidden">
              <div className="bg-brand-100 px-4 py-3 border-b border-brand-200">
                <EditableText
                  value={card.heading}
                  onChange={(v) => updateCard(card.id, 'heading', v)}
                  as="h3"
                  className="text-brand-700 font-bold text-center uppercase text-sm tracking-wide"
                />
              </div>
              <div className="p-4">
                <EditableText
                  value={card.text}
                  onChange={(v) => updateCard(card.id, 'text', v)}
                  as="p"
                  className="text-slate-600 text-sm leading-relaxed text-justify"
                  multiline
                />
              </div>
            </div>
          ))}
        </div>

        <div className="grid lg:grid-cols-5 gap-8 items-start">
          <div className="lg:col-span-3 space-y-4">
            <EditableText
              value={data.historyTitle}
              onChange={(v) => updateSection('quem-somos', { historyTitle: v })}
              as="h3"
              className="text-brand-600 font-bold text-lg mb-2"
            />
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
                className="text-slate-600 text-sm leading-relaxed text-justify"
                multiline
              />
            ))}
          </div>

          {/* Slideshow escritório + equipe */}
          {slides.length > 0 && (
            <div className="lg:col-span-2">
              <div className="relative rounded-lg shadow-md overflow-hidden bg-slate-100 h-[280px] md:h-[360px]">
                <div
                  className="flex h-full w-full transition-transform duration-700 ease-in-out"
                  style={{ transform: `translateX(-${slide * 100}%)` }}
                >
                  {slides.map((src, i) => (
                    <div key={i} className="w-full flex-shrink-0 h-full">
                      <img
                        src={src}
                        alt={`Escritório Andrade ${i + 1}`}
                        className="w-full h-full object-cover"
                      />
                    </div>
                  ))}
                </div>

                {slides.length > 1 && (
                  <>
                    <button
                      onClick={() => setSlide((c) => (c - 1 + slides.length) % slides.length)}
                      className="absolute left-2 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-1.5 rounded-full transition-colors"
                      aria-label="Anterior"
                    >
                      <ChevronLeft size={18} />
                    </button>
                    <button
                      onClick={next}
                      className="absolute right-2 top-1/2 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-1.5 rounded-full transition-colors"
                      aria-label="Próximo"
                    >
                      <ChevronRight size={18} />
                    </button>
                    <div className="absolute bottom-2 left-0 right-0 flex justify-center gap-1.5">
                      {slides.map((_, i) => (
                        <button
                          key={i}
                          onClick={() => setSlide(i)}
                          aria-label={`Foto ${i + 1}`}
                          className={`h-1.5 rounded-full transition-all ${
                            i === slide ? 'bg-white w-5' : 'bg-white/50 w-1.5'
                          }`}
                        />
                      ))}
                    </div>
                  </>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </section>
  );
}
