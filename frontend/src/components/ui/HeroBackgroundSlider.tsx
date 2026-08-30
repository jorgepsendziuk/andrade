import { useState, useEffect, useCallback } from 'react';
import { usePrefersReducedMotion } from '../../hooks/usePrefersReducedMotion';

interface HeroBackgroundSliderProps {
  slides: string[];
  autoPlayMs?: number;
}

export function HeroBackgroundSlider({ slides, autoPlayMs = 5500 }: HeroBackgroundSliderProps) {
  const [index, setIndex] = useState(0);
  const prefersReducedMotion = usePrefersReducedMotion();

  const next = useCallback(() => {
    setIndex((i) => (i + 1) % slides.length);
  }, [slides.length]);

  useEffect(() => {
    if (slides.length <= 1 || prefersReducedMotion) return;
    const t = setInterval(next, autoPlayMs);
    return () => clearInterval(t);
  }, [next, slides.length, autoPlayMs, prefersReducedMotion]);

  if (!slides.length) return null;

  return (
    <div
      className="absolute inset-0 overflow-hidden"
      role="region"
      aria-roledescription="carrossel"
      aria-label="Fotos de clientes com seus veículos"
    >
      {slides.map((slide, i) => (
        <img
          key={`${slide}-${i}`}
          src={slide}
          alt=""
          aria-hidden={i !== index}
          className={`absolute inset-0 w-full h-full object-cover transition-opacity duration-[1200ms] ease-in-out ${
            i === index ? 'opacity-100' : 'opacity-0'
          }`}
          loading={i === 0 ? 'eager' : 'lazy'}
          fetchPriority={i === 0 ? 'high' : 'auto'}
          decoding={i === 0 ? 'sync' : 'async'}
        />
      ))}

      {slides.length > 1 && (
        <div className="absolute bottom-20 md:bottom-24 right-4 md:right-8 flex gap-1.5 z-10" role="tablist" aria-label="Selecionar foto">
          {slides.map((_, i) => (
            <button
              key={i}
              type="button"
              role="tab"
              aria-selected={i === index}
              onClick={() => setIndex(i)}
              aria-label={`Foto ${i + 1} de ${slides.length}`}
              className={`h-1.5 rounded-full transition-all shadow-sm ${
                i === index ? 'bg-white w-5' : 'bg-white/50 w-1.5 hover:bg-white/80'
              }`}
            />
          ))}
        </div>
      )}
    </div>
  );
}
