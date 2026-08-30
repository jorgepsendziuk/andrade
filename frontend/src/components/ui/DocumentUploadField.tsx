import { useRef } from 'react';
import { CheckCircle, Upload } from 'lucide-react';

const ACCEPT = 'application/pdf,image/jpeg,image/png,image/webp,.pdf,.jpg,.jpeg,.png';

interface DocumentUploadFieldProps {
  label: string;
  required?: boolean;
  description?: string;
  file?: File;
  onSelect: (file: File) => void;
}

export function DocumentUploadField({
  label,
  required,
  description,
  file,
  onSelect,
}: DocumentUploadFieldProps) {
  const inputRef = useRef<HTMLInputElement>(null);

  return (
    <div className="text-sm">
      <p className="font-medium text-slate-800">
        {label}
        {required ? ' *' : ''}
      </p>
      {description && <p className="text-xs text-slate-500 mt-0.5 mb-2">{description}</p>}
      <button
        type="button"
        onClick={() => inputRef.current?.click()}
        className={`w-full flex items-center gap-3 p-4 border-2 border-dashed rounded-xl text-left transition-colors ${
          file
            ? 'border-green-300 bg-green-50 hover:bg-green-100/80'
            : 'border-brand-200 bg-white hover:bg-brand-50 hover:border-brand-300'
        }`}
      >
        {file ? (
          <>
            <CheckCircle size={20} className="text-green-600 flex-shrink-0" aria-hidden />
            <span className="flex-1 min-w-0">
              <span className="block truncate text-green-800 font-medium">{file.name}</span>
              <span className="block text-xs text-green-700 mt-0.5">Arquivo selecionado — toque para trocar</span>
            </span>
          </>
        ) : (
          <>
            <Upload size={20} className="text-brand-500 flex-shrink-0" aria-hidden />
            <span className="flex-1 text-slate-600">
              Toque para selecionar PDF ou foto (JPEG/PNG)
            </span>
          </>
        )}
      </button>
      <input
        ref={inputRef}
        type="file"
        accept={ACCEPT}
        className="sr-only"
        onChange={(e) => {
          const selected = e.target.files?.[0];
          if (selected) onSelect(selected);
          e.target.value = '';
        }}
      />
    </div>
  );
}
