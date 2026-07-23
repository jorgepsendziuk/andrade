import { useState, useEffect, useCallback } from 'react';
import { ChevronLeft, ChevronRight, Play, Megaphone } from 'lucide-react';
import { useCms } from '../../context/CmsContext';

interface MosaicData {
  mascot: string;
  g1Banner: string;
  g1Link: string;
  pescumaVideoId: string;
  pescumaPreviewGif?: string;
  pescumaThumb?: string;
  institutionalVideoId: string;
  institutionalThumb?: string;
  sonhosSlides: string[];
  sonhosTitle: string;
  sonhosSubtitle: string;
  ctaText: string;
  ctaLink: string;
}

const VISIBLE_INFO = 3;

function VideoPlayOverlay({ label }: { label?: string }) {
  return (
    <div className="absolute inset-0 bg-black/25 flex flex-col items-center justify-center group-hover:bg-black/35 transition-colors">
      <div className="w-12 h-12 md:w-14 md:h-14 rounded-full bg-white/95 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
        <Play size={22} className="text-brand-600 ml-1" fill="currentColor" />
      </div>
      {label && (
        <p className="mt-2 text-white text-[10px] md:text-xs font-semibold drop-shadow-lg text-center px-2 leading-tight">
          {label}
        </p>
      )}
    </div>
  );
}

function Mascot({ src }: { src: string }) {
  return (
    <div className="absolute inset-0 mascot-stage">
      <div className="mascot-car absolute bottom-1 left-0 w-[88%]">
        <div className="mascot-body relative">
          <div className="absolute -bottom-0.5 left-1/2 -translate-x-1/2 w-[78%] h-2 bg-black/10 rounded-[50%] blur-sm mascot-shadow" />
          <img
            src={src}
            alt="Mascote Andrade"
            className="relative z-10 block w-full h-auto object-contain drop-shadow-[0_5px_10px_rgba(0,80,150,0.25)]"
            draggable={false}
          />
        </div>
      </div>
    </div>
  );
}

function InfoCarousel({ slides }: { slides: string[] }) {
  const [index, setIndex] = useState(0);
  const maxIndex = Math.max(0, slides.length - VISIBLE_INFO);

  const next = useCallback(() => {
    setIndex((i) => (i >= maxIndex ? 0 : i + 1));
  }, [maxIndex]);

  useEffect(() => {
    const t = setInterval(next, 3200);
    return () => clearInterval(t);
  }, [next]);

  if (!slides.length) return null;

  const slideWidth = 100 / VISIBLE_INFO;

  return (
    <div className="carousel-dark py-5 md:py-6">
      <div className="max-w-6xl mx-auto px-4 relative">
        <div className="overflow-hidden rounded-lg">
          <div
            className="flex transition-transform duration-700 ease-[cubic-bezier(0.4,0,0.2,1)]"
            style={{ transform: `translateX(-${index * slideWidth}%)` }}
          >
            {slides.map((slide, i) => (
              <div key={i} className="flex-shrink-0 px-1.5" style={{ width: `${slideWidth}%` }}>
                <div className="bg-brand-900/50 rounded-md overflow-hidden aspect-square flex items-center justify-center">
                  <img src={slide} alt={`Informação ${i + 1}`} className="max-w-full max-h-full object-contain" />
                </div>
              </div>
            ))}
          </div>
        </div>

        {slides.length > VISIBLE_INFO && (
          <>
            <button
              onClick={() => setIndex((i) => (i <= 0 ? maxIndex : i - 1))}
              className="absolute left-0 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/35 text-white p-2 rounded-r transition-colors"
              aria-label="Anterior"
            >
              <ChevronLeft size={22} />
            </button>
            <button
              onClick={next}
              className="absolute right-0 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/35 text-white p-2 rounded-l transition-colors"
              aria-label="Próximo"
            >
              <ChevronRight size={22} />
            </button>
            <div className="flex justify-center gap-1.5 mt-3">
              {Array.from({ length: maxIndex + 1 }).map((_, i) => (
                <button
                  key={i}
                  onClick={() => setIndex(i)}
                  className={`h-1.5 rounded-full transition-all ${
                    i === index ? 'bg-white w-5' : 'bg-white/40 w-1.5'
                  }`}
                  aria-label={`Posição ${i + 1}`}
                />
              ))}
            </div>
          </>
        )}
      </div>
    </div>
  );
}

export function HeroSection() {
  const { getSection } = useCms();
  const section = getSection('hero');
  const [sonhosSlide, setSonhosSlide] = useState(0);
  const [videoOpen, setVideoOpen] = useState<string | null>(null);

  const data = section?.data as { mosaic?: MosaicData; carouselSlides?: string[] } | undefined;
  const mosaic = data?.mosaic;
  const carouselSlides = data?.carouselSlides ?? [];
  const sonhosSlides = mosaic?.sonhosSlides ?? [];

  const nextSonhos = useCallback(() => {
    if (sonhosSlides.length > 1) setSonhosSlide((c) => (c + 1) % sonhosSlides.length);
  }, [sonhosSlides.length]);

  useEffect(() => {
    const t = setInterval(nextSonhos, 4000);
    return () => clearInterval(t);
  }, [nextSonhos]);

  if (!section?.enabled || !mosaic) return null;

  const pescumaThumb =
    mosaic.pescumaPreviewGif ||
    mosaic.pescumaThumb ||
    `https://img.youtube.com/vi/${mosaic.pescumaVideoId}/hqdefault.jpg`;
  const institutionalThumb =
    mosaic.institutionalThumb ||
    `https://img.youtube.com/vi/${mosaic.institutionalVideoId}/hqdefault.jpg`;

  const heroBox = 'section-box overflow-hidden rounded-lg border border-slate-200/80 bg-white';
  const row1Aspect = 'aspect-[462/362] w-full';
  const row1Video = 'overflow-hidden rounded-lg';
  // Linha 2: mesma altura visual que a linha 1 (coluna 50% vs 33%)
  const row2Box = `${heroBox} aspect-[693/362] w-full`;

  return (
    <section id="inicio" className="pt-16 md:pt-[72px]">
      <div className="hero-gradient px-3 md:px-4 py-3 md:py-5">
        <div className="max-w-6xl mx-auto space-y-3 md:space-y-4">
          {/* Linha 1: mascote | G1 | Pescuma — proporção G1, sem bordas */}
          <div className="grid grid-cols-3 gap-2 md:gap-2.5 max-w-5xl mx-auto isolate">
            <div className={`${row1Aspect} relative z-30 overflow-visible`}>
              <Mascot src={mosaic.mascot} />
            </div>

            <a
              href={mosaic.g1Link}
              target="_blank"
              rel="noopener noreferrer"
              className={`${row1Aspect} relative z-10 block hover:opacity-95 transition-opacity`}
            >
              <img
                src={mosaic.g1Banner}
                alt="Andrade na mídia G1"
                className="w-full h-full object-contain object-center"
              />
            </a>

            <button
              onClick={() => setVideoOpen(mosaic.pescumaVideoId)}
              className={`${row1Aspect} ${row1Video} relative z-10 group cursor-pointer`}
            >
              <img src={pescumaThumb} alt="Pescuma & Andrade" className="w-full h-full object-cover object-top" />
              <VideoPlayOverlay label="Pescuma & Andrade — Assista o vídeo" />
            </button>
          </div>

          {/* Linha 2: Sonhos realizados | Assessoria */}
          <div className="grid grid-cols-2 gap-2 md:gap-2.5 max-w-5xl mx-auto">
            <div className={`${row2Box} relative`}>
              <div
                className="flex h-full transition-transform duration-700 ease-in-out"
                style={{ transform: `translateX(-${sonhosSlide * 100}%)` }}
              >
                {sonhosSlides.map((slide, i) => (
                  <div key={i} className="relative w-full flex-shrink-0 h-full">
                    <img src={slide} alt={`Entrega ${i + 1}`} className="w-full h-full object-cover" />
                  </div>
                ))}
              </div>
              <div className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-transparent pointer-events-none" />
              <div className="absolute bottom-0 left-0 right-0 p-3 md:p-4 text-white pointer-events-none">
                <h2 className="font-bold text-sm md:text-lg leading-tight">{mosaic.sonhosTitle}</h2>
                <p className="text-[10px] md:text-xs text-white/90 mt-1 leading-snug line-clamp-2">
                  {mosaic.sonhosSubtitle}
                </p>
              </div>
              {sonhosSlides.length > 1 && (
                <div className="absolute bottom-2 right-2 flex gap-1 pointer-events-none">
                  {sonhosSlides.map((_, i) => (
                    <span
                      key={i}
                      className={`block h-1.5 rounded-full transition-all ${
                        i === sonhosSlide ? 'bg-white w-4' : 'bg-white/50 w-1.5'
                      }`}
                    />
                  ))}
                </div>
              )}
            </div>

            {/* Baixo direita: Assessoria Completa */}
            <button
              onClick={() => setVideoOpen(mosaic.institutionalVideoId)}
              className={`${row2Box} relative group cursor-pointer`}
            >
              <img src={institutionalThumb} alt="Assessoria Completa" className="w-full h-full object-cover object-top" />
              <VideoPlayOverlay label="Assessoria Completa — Assista o vídeo" />
            </button>
          </div>

          <a
            href={mosaic.ctaLink}
            className="flex items-center justify-center gap-2 bg-brand-500 hover:bg-brand-600 text-white py-3 md:py-3.5 font-bold uppercase tracking-widest text-xs md:text-sm transition-colors rounded-lg shadow-md"
          >
            <Megaphone size={18} />
            {mosaic.ctaText}
          </a>
        </div>
      </div>

      <InfoCarousel slides={carouselSlides} />

      {videoOpen && (
        <div className="fixed inset-0 z-[200] bg-black/90 flex items-center justify-center p-4" onClick={() => setVideoOpen(null)}>
          <div className="relative w-full max-w-4xl aspect-video" onClick={(e) => e.stopPropagation()}>
            <button onClick={() => setVideoOpen(null)} className="absolute -top-10 right-0 text-white text-sm hover:text-accent">
              Fechar ✕
            </button>
            <iframe
              src={`https://www.youtube.com/embed/${videoOpen}?autoplay=1`}
              className="w-full h-full rounded-lg"
              allow="autoplay; encrypted-media"
              allowFullScreen
              title="Vídeo Andrade Isenções"
            />
          </div>
        </div>
      )}
    </section>
  );
}
