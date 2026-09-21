import { useState } from 'react';
import { Play, ExternalLink } from 'lucide-react';
import { EditableImage } from '../cms/EditableImage';
import { useCms } from '../../context/CmsContext';

interface MosaicData {
  mascot: string;
  g1Banner: string;
  g1Link: string;
  pescumaVideoId: string;
  pescumaPreviewGif?: string;
  pescumaThumb?: string;
}

function MascotFree({ src, onChange }: { src: string; onChange: (url: string) => void }) {
  return (
    <div className="relative w-full h-full min-h-[220px] md:min-h-[260px] flex items-end justify-center overflow-visible mascot-stage">
      <div className="mascot-car relative w-full max-w-[280px] md:max-w-[300px]">
        <div className="mascot-body relative">
          <div className="absolute -bottom-1 left-1/2 -translate-x-1/2 w-[78%] h-3 bg-black/8 rounded-[50%] blur-sm mascot-shadow" />
          <EditableImage
            src={src}
            alt="Mascote Andrade"
            onChange={onChange}
            className="relative z-10 block w-full h-auto object-contain drop-shadow-lg"
            draggable={false}
            loading="lazy"
          />
        </div>
      </div>
    </div>
  );
}

export function MediaSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('media');
  const [videoOpen, setVideoOpen] = useState<string | null>(null);

  const data = section?.data as {
    title?: string;
    subtitle?: string;
    mosaic?: MosaicData;
  } | undefined;
  const mosaic = data?.mosaic;

  if (!section?.enabled || !mosaic) return null;

  const updateMosaic = (patch: Partial<MosaicData>) => {
    updateSection('media', { mosaic: { ...mosaic, ...patch } });
  };

  const pescumaThumb =
    mosaic.pescumaPreviewGif ||
    mosaic.pescumaThumb ||
    `https://img.youtube.com/vi/${mosaic.pescumaVideoId}/hqdefault.jpg`;

  return (
    <section id="midia" className="py-10 md:py-12 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <h2 className="section-title">{data.title ?? 'Andrade na mídia'}</h2>
        <p className="section-subtitle">
          {data.subtitle ?? 'Acompanhe nossa presença na imprensa e conheça mais sobre nosso trabalho.'}
        </p>

        <div className="grid md:grid-cols-3 gap-4 md:gap-5 items-stretch">
          {/* 1/3 — Mascote solto no branco */}
          <div className="relative flex items-end justify-center min-h-[240px] md:min-h-[280px] overflow-visible">
            <MascotFree src={mosaic.mascot} onChange={(url) => updateMosaic({ mascot: url })} />
          </div>

          {/* 1/3 — Matéria G1 */}
          <a
            href={mosaic.g1Link}
            target="_blank"
            rel="noopener noreferrer"
            className="card-vivid flex flex-col p-4 md:p-5 group hover:-translate-y-0.5 min-h-[240px] md:min-h-[280px]"
          >
            <div className="flex items-center justify-between mb-3">
              <span className="text-sm font-bold uppercase tracking-widest text-brand-600 bg-brand-50 px-2 py-1 rounded">
                Matéria no G1
              </span>
              <ExternalLink
                size={15}
                className="text-brand-400 group-hover:text-accent transition-colors flex-shrink-0"
              />
            </div>
            <div className="flex-1 flex items-center justify-center py-2 min-h-[160px] md:min-h-[180px]">
              <EditableImage
                src={mosaic.g1Banner}
                alt="Andrade na mídia G1"
                onChange={(url) => updateMosaic({ g1Banner: url })}
                className="w-full h-full max-h-[160px] md:max-h-[200px] object-contain group-hover:scale-[1.02] transition-transform"
                loading="lazy"
              />
            </div>
            <p className="text-text-secondary text-sm leading-relaxed text-center group-hover:text-brand-700 transition-colors mt-auto pt-2 border-t border-brand-50">
              Escritório de MT facilita o direito à mobilidade para público PCD
            </p>
          </a>

          {/* 1/3 — Vídeo Pescuma */}
          <button
            type="button"
            onClick={() => setVideoOpen(mosaic.pescumaVideoId)}
            className="card-vivid relative group cursor-pointer overflow-hidden min-h-[240px] md:min-h-[280px] hover:-translate-y-0.5 text-left"
          >
            <EditableImage
              src={pescumaThumb}
              alt="Pescuma & Andrade"
              onChange={(url) => updateMosaic({ pescumaPreviewGif: url })}
              className="absolute inset-0 w-full h-full object-cover"
              loading="lazy"
              wrapperClassName="absolute inset-0"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-brand-900/85 via-brand-900/30 to-transparent" />
            <div className="relative z-10 h-full flex flex-col items-center justify-center p-4">
              <div className="w-14 h-14 rounded-full bg-white flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform mb-3">
                <Play size={24} className="text-brand-600 ml-1" fill="currentColor" />
              </div>
              <p className="text-white font-display font-bold text-sm text-center leading-snug">
                Pescuma &amp; Andrade
              </p>
              <p className="text-white/85 text-sm uppercase tracking-wider mt-1">Assista agora</p>
            </div>
          </button>
        </div>
      </div>

      {videoOpen && (
        <div
          className="fixed inset-0 z-[200] bg-black/90 flex items-center justify-center p-4"
          onClick={() => setVideoOpen(null)}
        >
          <div className="relative w-full max-w-4xl aspect-video" onClick={(e) => e.stopPropagation()}>
            <button
              onClick={() => setVideoOpen(null)}
              className="absolute -top-10 right-0 text-white text-sm hover:text-accent"
            >
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
