import { useRef, useState, type ImgHTMLAttributes } from 'react';
import { ExternalLink, ImageIcon, Loader2 } from 'lucide-react';
import { useCms } from '../../context/CmsContext';
import { getToken, uploadFile } from '../../lib/api';

function imageOpenUrl(src: string): string {
  if (/^https?:\/\//i.test(src)) return src;
  if (src.startsWith('/') && typeof window !== 'undefined') return `${window.location.origin}${src}`;
  return src;
}

interface EditableImageProps extends Omit<ImgHTMLAttributes<HTMLImageElement>, 'src' | 'onChange'> {
  src: string;
  onChange: (url: string) => void;
  wrapperClassName?: string;
}

export function EditableImage({
  src,
  alt = '',
  onChange,
  className = '',
  wrapperClassName = '',
  ...imgProps
}: EditableImageProps) {
  const { isEditing } = useCms();
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

  if (!isEditing) {
    return <img src={src} alt={alt} className={className} {...imgProps} />;
  }

  return (
    <div className={`relative group ${wrapperClassName}`}>
      <img src={src} alt={alt} className={className} {...imgProps} />
      <a
        href={imageOpenUrl(src)}
        target="_blank"
        rel="noopener noreferrer"
        title="Abrir foto em nova aba"
        className="absolute top-2 right-2 z-10 inline-flex items-center gap-1 rounded-md bg-black/70 px-2 py-1 text-[10px] font-semibold text-white opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity hover:bg-black/85"
        onClick={(e) => e.stopPropagation()}
      >
        <ExternalLink size={12} />
        Abrir
      </a>
      <button
        type="button"
        onClick={() => inputRef.current?.click()}
        disabled={uploading}
        className="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-black/55 text-white text-xs font-semibold opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity cursor-pointer disabled:cursor-wait"
        aria-label="Trocar foto"
      >
        {uploading ? (
          <Loader2 size={22} className="animate-spin" />
        ) : (
          <>
            <ImageIcon size={22} />
            <span>Trocar foto</span>
            <span className="text-[10px] font-normal text-white/80 max-w-[90%] truncate" title={src}>
              {src}
            </span>
          </>
        )}
      </button>
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
      {error && (
        <p className="absolute bottom-1 left-1 right-1 text-[10px] text-red-100 bg-red-900/80 rounded px-1 py-0.5 text-center">
          {error}
        </p>
      )}
    </div>
  );
}
