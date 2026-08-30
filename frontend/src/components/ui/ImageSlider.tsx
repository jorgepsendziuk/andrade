import { useState, useEffect, useCallback, useRef } from 'react';
import { ChevronLeft, ChevronRight, X, ZoomIn } from 'lucide-react';
import { useFocusTrap } from '../../hooks/useFocusTrap';
import { usePrefersReducedMotion } from '../../hooks/usePrefersReducedMotion';

interface ImageSliderProps {
  slides: string[];
  title: string;
  subtitle?: string;
  altPrefix?: string;
  variant?: 'cover' | 'contain';
  autoPlayMs?: number;
  showHeader?: boolean;
}

export function ImageSlider({
  slides,
  title,
  subtitle,
  altPrefix = 'Imagem',
  variant = 'cover',
  autoPlayMs = 5000,
  showHeader = true,
}: ImageSliderProps) {
  const [index, setIndex] = useState(0);
  const [lightboxOpen, setLightboxOpen] = useState(false);
  const lightboxRef = useRef<HTMLDivElement>(null);
  const prefersReducedMotion = usePrefersReducedMotion();

  useFocusTrap(lightboxRef, lightboxOpen);

  const next = useCallback(() => {
    setIndex((i) => (i + 1) % slides.length);
  }, [slides.length]);

  const prev = useCallback(() => {
    setIndex((i) => (i - 1 + slides.length) % slides.length);
  }, [slides.length]);

  useEffect(() => {
    if (slides.length <= 1 || lightboxOpen || prefersReducedMotion) return;
    const t = setInterval(next, autoPlayMs);
    return () => clearInterval(t);
  }, [next, slides.length, autoPlayMs, lightboxOpen, prefersReducedMotion]);

  useEffect(() => {
    if (!lightboxOpen) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setLightboxOpen(false);
      if (e.key === 'ArrowRight') next();
      if (e.key === 'ArrowLeft') prev();
    };
    document.body.style.overflow = 'hidden';
    window.addEventListener('keydown', onKey);
    return () => {
      document.body.style.overflow = '';
      window.removeEventListener('keydown', onKey);
    };
  }, [lightboxOpen, next, prev]);

  if (!slides.length) return null;

  const isCover = variant === 'cover';
  const aspectClass = isCover ? 'aspect-[4/3]' : 'aspect-[3/4]';
  const fitClass = isCover ? 'object-cover' : 'object-contain bg-surface';

  const navBtn =
    'absolute top-1/2 -translate-y-1/2 z-10 bg-white/90 hover:bg-white text-brand-700 p-2 rounded-full shadow-md border border-brand-100 transition-colors';

  return (
    <>
      <div className="bg-white rounded-2xl border border-brand-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow h-full relative">
        <div className={`relative ${aspectClass} group`}>
          <div
            className="flex h-full transition-transform duration-700 ease-in-out"
            style={{ transform: `translateX(-${index * 100}%)` }}
          >
            {slides.map((slide, i) => (
              <button
                key={i}
                type="button"
                onClick={() => setLightboxOpen(true)}
                className="relative w-full flex-shrink-0 h-full cursor-zoom-in"
                aria-label={`Ampliar ${altPrefix} ${i + 1}`}
              >
                <img
                  src={slide}
                  alt={`${altPrefix} ${i + 1}`}
                  className={`w-full h-full ${fitClass}`}
                  loading={i === 0 ? 'eager' : 'lazy'}
                />
                <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                  <span className="bg-white/90 text-brand-800 text-xs font-semibold px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow">
                    <ZoomIn size={14} />
                    Ampliar
                  </span>
                </div>
              </button>
            ))}
          </div>

          {isCover && (
            <div className="absolute inset-0 bg-gradient-to-t from-brand-900/80 via-brand-900/20 to-transparent pointer-events-none" />
          )}

          {slides.length > 1 && (
            <>
              <button onClick={(e) => { e.stopPropagation(); prev(); }} className={`left-2 ${navBtn}`} aria-label="Anterior">
                <ChevronLeft size={18} />
              </button>
              <button onClick={(e) => { e.stopPropagation(); next(); }} className={`right-2 ${navBtn}`} aria-label="Próximo">
                <ChevronRight size={18} />
              </button>
              <div className={`absolute top-3 right-3 text-[10px] font-semibold px-2 py-1 rounded-full z-10 ${isCover ? 'bg-black/50 text-white' : 'bg-brand-100 text-brand-700'}`}>
                {index + 1} / {slides.length}
              </div>
            </>
          )}

          {isCover && showHeader && (
            <div className="absolute bottom-0 left-0 right-0 p-4 pointer-events-none">
              <h3 className="font-display font-bold text-base md:text-lg text-white">{title}</h3>
              {subtitle && <p className="text-white/90 text-xs mt-1 leading-snug">{subtitle}</p>}
            </div>
          )}
        </div>

        {!isCover && showHeader && (
          <div className="px-4 pt-4 pb-2">
            <h3 className="font-display font-bold text-base text-brand-800">{title}</h3>
            {subtitle && <p className="text-text-secondary text-xs mt-1">{subtitle}</p>}
          </div>
        )}

        {slides.length > 1 && (
          <div className={`flex justify-center gap-1.5 px-4 ${isCover ? 'absolute bottom-[4.5rem] left-0 right-0 z-10' : 'pb-4'}`}>
            {slides.map((_, i) => (
              <button
                key={i}
                onClick={() => setIndex(i)}
                aria-label={`Ir para slide ${i + 1}`}
                className={`h-1.5 rounded-full transition-all ${
                  i === index
                    ? 'bg-accent w-5'
                    : isCover
                      ? 'bg-white/60 w-1.5 hover:bg-white'
                      : 'bg-brand-200 w-1.5 hover:bg-brand-400'
                }`}
              />
            ))}
          </div>
        )}
      </div>

      {lightboxOpen && (
        <div
          ref={lightboxRef}
          role="dialog"
          aria-modal="true"
          aria-labelledby="lightbox-title"
          className="fixed inset-0 z-[300] bg-black/95 flex items-center justify-center"
          onClick={() => setLightboxOpen(false)}
        >
          <button
            type="button"
            onClick={() => setLightboxOpen(false)}
            className="absolute top-4 right-4 text-white/80 hover:text-white p-2 z-10"
            aria-label="Fechar visualização ampliada"
          >
            <X size={28} aria-hidden />
          </button>
          <div id="lightbox-title" className="absolute top-4 left-4 text-white/80 text-sm font-medium z-10">
            {title} — {index + 1} / {slides.length}
          </div>
          <div className="relative w-full max-w-5xl max-h-[90vh] mx-4 flex items-center justify-center" onClick={(e) => e.stopPropagation()}>
            <img src={slides[index]} alt={`${altPrefix} ${index + 1}`} className="max-w-full max-h-[85vh] object-contain rounded-lg" />
            {slides.length > 1 && (
              <>
                <button onClick={prev} className="absolute left-0 md:-left-14 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/20 text-white p-3 rounded-full transition-colors" aria-label="Anterior">
                  <ChevronLeft size={28} />
                </button>
                <button onClick={next} className="absolute right-0 md:-right-14 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/20 text-white p-3 rounded-full transition-colors" aria-label="Próximo">
                  <ChevronRight size={28} />
                </button>
              </>
            )}
          </div>
          {slides.length > 1 && (
            <div className="absolute bottom-6 left-0 right-0 flex justify-center gap-2 px-4 overflow-x-auto max-w-3xl mx-auto">
              {slides.map((slide, i) => (
                <button
                  type="button"
                  onClick={(e) => { e.stopPropagation(); setIndex(i); }}
                  aria-label={`Ir para ${altPrefix} ${i + 1}`}
                  className={`flex-shrink-0 w-14 h-14 rounded-lg overflow-hidden border-2 transition-all ${
                    i === index ? 'border-accent scale-110' : 'border-white/30 opacity-60 hover:opacity-100'
                  }`}
                >
                  <img src={slide} alt="" className="w-full h-full object-cover" />
                </button>
              ))}
            </div>
          )}
        </div>
      )}
    </>
  );
}
