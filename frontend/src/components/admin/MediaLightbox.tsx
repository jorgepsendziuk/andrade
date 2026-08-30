import { useCallback, useEffect, useRef, useState } from 'react';
import {
  Check,
  ChevronLeft,
  ChevronRight,
  Copy,
  Download,
  ExternalLink,
  Maximize2,
  Minimize2,
  Square,
  Trash2,
  X,
  ZoomIn,
} from 'lucide-react';
import { useFocusTrap } from '../../hooks/useFocusTrap';
import { formatBytes, formatDate } from '../../lib/media-utils';
import type { MediaFile } from '../../types/media';

interface MediaLightboxProps {
  files: MediaFile[];
  index: number;
  onIndexChange: (index: number) => void;
  onClose: () => void;
  picked: Set<string>;
  onTogglePick: (objectName: string) => void;
  onCopy: (urls: string[]) => void;
  copied: boolean;
  onDownload: (file: MediaFile) => void;
  onDelete: (file: MediaFile) => void;
  busy?: boolean;
  duplicateCount?: number;
}

type ZoomMode = 'fit' | '100' | '150' | '200';

export function MediaLightbox({
  files,
  index,
  onIndexChange,
  onClose,
  picked,
  onTogglePick,
  onCopy,
  copied,
  onDownload,
  onDelete,
  busy,
  duplicateCount,
}: MediaLightboxProps) {
  const file = files[index];
  const lightboxRef = useRef<HTMLDivElement>(null);
  const [zoom, setZoom] = useState<ZoomMode>('fit');
  const [fullscreen, setFullscreen] = useState(false);
  const [dims, setDims] = useState<{ w: number; h: number } | null>(null);

  useFocusTrap(lightboxRef, true);

  const prev = useCallback(() => {
    onIndexChange((index - 1 + files.length) % files.length);
  }, [files.length, index, onIndexChange]);

  const next = useCallback(() => {
    onIndexChange((index + 1) % files.length);
  }, [files.length, index, onIndexChange]);

  useEffect(() => {
    setZoom('fit');
    setDims(null);
  }, [file?.objectName]);

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
      if (e.key === 'ArrowLeft') prev();
      if (e.key === 'ArrowRight') next();
      if (e.key === '+' || e.key === '=') setZoom((z) => (z === 'fit' ? '100' : z === '100' ? '150' : '200'));
      if (e.key === '-') setZoom((z) => (z === '200' ? '150' : z === '150' ? '100' : 'fit'));
    };
    document.body.style.overflow = 'hidden';
    window.addEventListener('keydown', onKey);
    return () => {
      document.body.style.overflow = '';
      window.removeEventListener('keydown', onKey);
    };
  }, [next, onClose, prev]);

  useEffect(() => {
    const onFs = () => setFullscreen(Boolean(document.fullscreenElement));
    document.addEventListener('fullscreenchange', onFs);
    return () => document.removeEventListener('fullscreenchange', onFs);
  }, []);

  const toggleFullscreen = async () => {
    if (!lightboxRef.current) return;
    if (document.fullscreenElement) {
      await document.exitFullscreen();
    } else {
      await lightboxRef.current.requestFullscreen();
    }
  };

  if (!file) return null;

  const zoomScale = zoom === 'fit' ? undefined : Number(zoom) / 100;

  return (
    <div
      className="fixed inset-0 z-50 bg-black/85 flex flex-col"
      role="dialog"
      aria-modal="true"
      aria-label={`Visualizar ${file.name}`}
    >
      <div ref={lightboxRef} className="flex flex-col flex-1 min-h-0">
        <div className="flex items-center justify-between gap-3 px-4 py-3 text-white shrink-0">
          <div className="min-w-0 flex-1">
            <p className="font-semibold truncate">{file.name}</p>
            <p className="text-xs text-white/60 truncate">
              {file.folder} · {formatBytes(file.size)}
              {dims ? ` · ${dims.w}×${dims.h}px` : ''}
              {files.length > 1 && ` · ${index + 1} de ${files.length}`}
            </p>
          </div>

          <div className="flex items-center gap-1 shrink-0">
            <button
              type="button"
              onClick={() => setZoom((z) => (z === 'fit' ? '100' : z === '100' ? '150' : z === '150' ? '200' : 'fit'))}
              className="p-2 rounded-lg hover:bg-white/10"
              title="Zoom (+/-)"
              aria-label="Alternar zoom"
            >
              <ZoomIn size={18} />
            </button>
            <button
              type="button"
              onClick={() => setZoom('fit')}
              className="hidden sm:inline px-2 py-1 rounded text-xs font-medium hover:bg-white/10"
            >
              {zoom === 'fit' ? 'Ajustar' : `${zoom}%`}
            </button>
            <button
              type="button"
              onClick={() => void toggleFullscreen()}
              className="p-2 rounded-lg hover:bg-white/10"
              aria-label={fullscreen ? 'Sair da tela cheia' : 'Tela cheia'}
            >
              {fullscreen ? <Minimize2 size={18} /> : <Maximize2 size={18} />}
            </button>
            <button
              type="button"
              onClick={onClose}
              className="p-2 rounded-lg hover:bg-white/10"
              aria-label="Fechar"
            >
              <X size={20} />
            </button>
          </div>
        </div>

        <div className="relative flex-1 min-h-0 flex items-center justify-center px-14 sm:px-20">
          {files.length > 1 && (
            <>
              <button
                type="button"
                onClick={prev}
                className="absolute left-2 sm:left-4 top-1/2 -translate-y-1/2 z-10 p-2 sm:p-3 rounded-full bg-white/10 hover:bg-white/20 text-white"
                aria-label="Imagem anterior"
              >
                <ChevronLeft size={24} />
              </button>
              <button
                type="button"
                onClick={next}
                className="absolute right-2 sm:right-4 top-1/2 -translate-y-1/2 z-10 p-2 sm:p-3 rounded-full bg-white/10 hover:bg-white/20 text-white"
                aria-label="Próxima imagem"
              >
                <ChevronRight size={24} />
              </button>
            </>
          )}

          <div className="w-full h-full overflow-auto flex items-center justify-center p-2">
            <img
              src={file.url}
              alt={file.name}
              onLoad={(e) => {
                const img = e.currentTarget;
                setDims({ w: img.naturalWidth, h: img.naturalHeight });
              }}
              className="max-w-full max-h-full transition-transform duration-200"
              style={
                zoomScale
                  ? { transform: `scale(${zoomScale})`, maxWidth: 'none', maxHeight: 'none' }
                  : { objectFit: 'contain' }
              }
              draggable={false}
            />
          </div>
        </div>

        {files.length > 1 && (
          <div className="shrink-0 px-4 pb-2 overflow-x-auto">
            <div className="flex gap-2 justify-center min-w-min py-1">
              {files.map((f, i) => (
                <button
                  key={f.objectName}
                  type="button"
                  onClick={() => onIndexChange(i)}
                  className={`shrink-0 w-14 h-14 rounded-lg overflow-hidden border-2 transition-all ${
                    i === index ? 'border-white ring-2 ring-white/40' : 'border-white/20 opacity-70 hover:opacity-100'
                  }`}
                  aria-label={`Ir para ${f.name}`}
                  aria-current={i === index}
                >
                  <img src={f.url} alt="" className="w-full h-full object-cover" />
                </button>
              ))}
            </div>
          </div>
        )}

        <div className="shrink-0 bg-white rounded-t-2xl sm:rounded-t-2xl max-h-[42vh] overflow-y-auto">
          <div className="px-5 py-4 space-y-4">
            {duplicateCount && duplicateCount > 1 && (
              <p className="text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs">
                Possível duplicata — {duplicateCount} arquivos semelhantes na biblioteca.
              </p>
            )}

            {file.inUse && (
              <p className="text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-xs">
                Esta imagem está em uso no site. Para excluir, troque-a primeiro no editor.
              </p>
            )}

            <dl className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
              <div>
                <dt className="text-slate-400 text-xs">Tamanho</dt>
                <dd className="font-medium">{formatBytes(file.size)}</dd>
              </div>
              <div>
                <dt className="text-slate-400 text-xs">Dimensões</dt>
                <dd className="font-medium">{dims ? `${dims.w} × ${dims.h}` : '—'}</dd>
              </div>
              <div>
                <dt className="text-slate-400 text-xs">Atualizado</dt>
                <dd className="font-medium">{formatDate(file.updatedAt)}</dd>
              </div>
              <div>
                <dt className="text-slate-400 text-xs">Tipo</dt>
                <dd className="font-medium truncate">{file.contentType.replace('image/', '').toUpperCase()}</dd>
              </div>
              <div className="col-span-2 sm:col-span-4">
                <dt className="text-slate-400 text-xs mb-1">URL</dt>
                <dd className="font-mono text-xs break-all bg-slate-50 rounded-lg p-2 border border-slate-100">
                  {file.url}
                </dd>
              </div>
            </dl>

            <div className="flex flex-wrap gap-2">
              <button
                type="button"
                onClick={() => onTogglePick(file.objectName)}
                className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium hover:bg-slate-50"
              >
                {picked.has(file.objectName) ? <Check size={14} /> : <Square size={14} />}
                {picked.has(file.objectName) ? 'Na seleção' : 'Selecionar'}
              </button>
              <button
                type="button"
                onClick={() => onCopy([file.url])}
                className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium hover:bg-slate-50"
              >
                {copied ? <Check size={14} className="text-accent" /> : <Copy size={14} />}
                {copied ? 'Copiado!' : 'Copiar URL'}
              </button>
              <a
                href={file.url}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium hover:bg-slate-50"
              >
                <ExternalLink size={14} />
                Abrir
              </a>
              <button
                type="button"
                onClick={() => onDownload(file)}
                className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium hover:bg-slate-50"
              >
                <Download size={14} />
                Baixar
              </button>
              <div className="hidden sm:flex items-center gap-1 ml-auto">
                <button
                  type="button"
                  onClick={() => setZoom('fit')}
                  className={`px-2 py-1.5 rounded text-xs font-medium ${zoom === 'fit' ? 'bg-slate-200' : 'hover:bg-slate-100'}`}
                >
                  Ajustar
                </button>
                {(['100', '150', '200'] as const).map((z) => (
                  <button
                    key={z}
                    type="button"
                    onClick={() => setZoom(z)}
                    className={`px-2 py-1.5 rounded text-xs font-medium ${zoom === z ? 'bg-slate-200' : 'hover:bg-slate-100'}`}
                  >
                    {z}%
                  </button>
                ))}
              </div>
              <button
                type="button"
                onClick={() => onDelete(file)}
                disabled={file.inUse || busy}
                className="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-red-200 text-red-600 text-sm font-medium hover:bg-red-50 disabled:opacity-40 sm:ml-0 ml-auto"
              >
                <Trash2 size={14} />
                Excluir
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
