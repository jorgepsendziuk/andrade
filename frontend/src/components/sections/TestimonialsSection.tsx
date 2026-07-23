import { useState, useEffect, useCallback } from 'react';
import { ChevronLeft, ChevronRight, ExternalLink } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';

const VISIBLE = 3;

function ReviewsCarousel({ slides }: { slides: string[] }) {
  const [index, setIndex] = useState(0);
  const maxIndex = Math.max(0, slides.length - VISIBLE);

  const next = useCallback(() => {
    setIndex((i) => (i >= maxIndex ? 0 : i + 1));
  }, [maxIndex]);

  useEffect(() => {
    if (slides.length <= VISIBLE) return;
    const timer = setInterval(next, 3200);
    return () => clearInterval(timer);
  }, [next, slides.length]);

  if (!slides.length) return null;

  const slideWidth = 100 / VISIBLE;

  return (
    <div className="relative">
      <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div
          className="flex transition-transform duration-700 ease-[cubic-bezier(0.4,0,0.2,1)]"
          style={{ transform: `translateX(-${index * slideWidth}%)` }}
        >
          {slides.map((slide, i) => (
            <div key={i} className="flex-shrink-0 px-1.5 py-2" style={{ width: `${slideWidth}%` }}>
              <div className="h-[220px] sm:h-[260px] md:h-[300px] flex items-center justify-center bg-slate-50 rounded-md overflow-hidden">
                <img
                  src={slide}
                  alt={`Avaliação ${i + 1}`}
                  className="max-w-full max-h-full object-contain"
                />
              </div>
            </div>
          ))}
        </div>
      </div>

      {slides.length > VISIBLE && (
        <>
          <button
            onClick={() => setIndex((i) => (i <= 0 ? maxIndex : i - 1))}
            className="absolute left-0 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-brand-600 p-2 rounded-r shadow-md border border-slate-200 transition-colors"
            aria-label="Anterior"
          >
            <ChevronLeft size={20} />
          </button>
          <button
            onClick={next}
            className="absolute right-0 top-1/2 -translate-y-1/2 bg-white/90 hover:bg-white text-brand-600 p-2 rounded-l shadow-md border border-slate-200 transition-colors"
            aria-label="Próximo"
          >
            <ChevronRight size={20} />
          </button>
          <div className="flex justify-center gap-1.5 mt-3">
            {Array.from({ length: maxIndex + 1 }).map((_, i) => (
              <button
                key={i}
                onClick={() => setIndex(i)}
                className={`h-1.5 rounded-full transition-all ${
                  i === index ? 'bg-brand-500 w-5' : 'bg-brand-300 w-1.5 hover:bg-brand-400'
                }`}
                aria-label={`Posição ${i + 1}`}
              />
            ))}
          </div>
        </>
      )}
    </div>
  );
}

export function TestimonialsSection() {
  const { getSection, updateSection, content } = useCms();
  const section = getSection('clientes');

  const data = section?.data as {
    title: string;
    description: string;
    reviewsUrl: string;
    slides: string[];
    collageImage?: string;
  } | undefined;

  const slides = data?.slides?.length
    ? data.slides
    : data?.collageImage
      ? [data.collageImage]
      : [];

  if (!section?.enabled || !data) return null;

  return (
    <section id="clientes" className="py-10 md:py-12 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('clientes', { title: v })}
          as="h2"
          className="section-title mb-6"
        />

        <EditableText
          value={data.description}
          onChange={(v) => updateSection('clientes', { description: v })}
          as="p"
          className="text-center text-slate-600 text-sm mb-5 max-w-xl mx-auto"
          multiline
        />

        <div className="text-center mb-6">
          <a
            href={data.reviewsUrl || content?.site.googleReviewsUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-2 text-brand-600 hover:text-brand-700 font-semibold text-sm underline"
          >
            Ver avaliações no Google
            <ExternalLink size={15} />
          </a>
        </div>

        <ReviewsCarousel slides={slides} />
      </div>
    </section>
  );
}
