import { useRef, useState } from 'react';
import { ExternalLink, ImageIcon, Loader2 } from 'lucide-react';
import { getToken, uploadFile } from '../../lib/api';

function imageOpenUrl(src: string): string {
  if (/^https?:\/\//i.test(src)) return src;
  if (src.startsWith('/') && typeof window !== 'undefined') return `${window.location.origin}${src}`;
  return src;
}

interface ImageUploadFieldProps {
  label: string;
  value: string;
  onChange: (url: string) => void;
  hint?: string;
}

export function ImageUploadField({ label, value, onChange, hint }: ImageUploadFieldProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleFile = async (file: File) => {
    const token = getToken();
    if (!token) {
      setError('Faça login para enviar fotos.');
      return;
    }
    setUploading(true);
    setError(null);
    try {
      const { url } = await uploadFile(file, token);
      onChange(url);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha no upload');
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between gap-2">
        <span className="text-sm font-medium text-slate-700">{label}</span>
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={() => inputRef.current?.click()}
            disabled={uploading}
            className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-brand-200 text-brand-700 text-xs font-semibold hover:bg-brand-50 disabled:opacity-50"
          >
            {uploading ? <Loader2 size={14} className="animate-spin" /> : <ImageIcon size={14} />}
            Enviar imagem
          </button>
          {value && (
            <a
              href={imageOpenUrl(value)}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50"
            >
              <ExternalLink size={14} />
              Abrir em nova aba
            </a>
          )}
        </div>
      </div>
      {value && (
        <div className="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
          <img src={value} alt="" className="h-12 w-auto max-w-[120px] object-contain bg-white rounded" />
          <input
            type="text"
            value={value}
            onChange={(e) => onChange(e.target.value)}
            className="flex-1 text-xs text-slate-600 bg-white border border-slate-200 rounded px-2 py-1.5 font-mono"
            aria-label={`URL ${label}`}
          />
        </div>
      )}
      {hint && <p className="text-xs text-slate-400">{hint}</p>}
      {error && <p className="text-xs text-red-600">{error}</p>}
      <input
        ref={inputRef}
        type="file"
        accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
        className="sr-only"
        onChange={(e) => {
          const file = e.target.files?.[0];
          if (file) void handleFile(file);
          e.target.value = '';
        }}
      />
    </div>
  );
}
