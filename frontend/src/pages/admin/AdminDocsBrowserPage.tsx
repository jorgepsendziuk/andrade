import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Building2, ChevronRight, Folder, Home, Loader2, Upload, Users } from 'lucide-react';
import { SeoHead } from '../../components/seo/SeoHead';
import { browseDocs, uploadToDocsPrefix } from '../../lib/portal-api';
import { formatStorageSegment } from '../../lib/storage-paths';
import type { DocsBrowseResult } from '../../types/process';

function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

const ROOT_SHORTCUTS = [
  { prefix: 'clientes/', label: 'Clientes', icon: Users },
  { prefix: 'empresa/', label: 'Empresa', icon: Building2 },
] as const;

export function AdminDocsBrowserPage() {
  const [searchParams, setSearchParams] = useSearchParams();
  const prefix = searchParams.has('prefix') ? (searchParams.get('prefix') ?? '') : '';
  const [data, setData] = useState<DocsBrowseResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');

  const load = () => {
    setLoading(true);
    browseDocs(prefix)
      .then(setData)
      .catch((err) => setError(err instanceof Error ? err.message : 'Erro'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, [prefix]);

  const navigate = (newPrefix: string) => {
    if (!newPrefix) {
      setSearchParams({});
      return;
    }
    setSearchParams({ prefix: newPrefix });
  };

  const parts = prefix.replace(/\/$/, '').split('/').filter(Boolean);
  const atRoot = parts.length === 0;

  const handleUpload = async (file: File) => {
    if (!prefix) {
      setError('Entre em uma pasta antes de enviar arquivos.');
      return;
    }
    setUploading(true);
    try {
      await uploadToDocsPrefix(prefix, file);
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro no upload');
    } finally {
      setUploading(false);
    }
  };

  return (
    <>
      <SeoHead title="Arquivos | Portal Andrade" description="Navegador de arquivos privados." noindex />
      <h1 className="text-2xl font-display font-extrabold text-brand-800 mb-1">Navegador de arquivos</h1>
      <p className="text-slate-500 text-sm mb-4">
        Repositório privado: documentos de clientes, arquivos internos da empresa e demais pastas do bucket.
      </p>

      {error && <div className="mb-4 bg-red-50 text-red-600 px-4 py-3 rounded-xl text-sm">{error}</div>}

      <nav className="flex items-center gap-1 text-sm text-slate-500 mb-4 flex-wrap">
        <button
          type="button"
          onClick={() => navigate('')}
          className={`hover:text-brand-600 flex items-center gap-1 ${atRoot ? 'text-brand-700 font-medium' : ''}`}
        >
          <Home size={14} /> Raiz
        </button>
        {parts.map((part, i) => {
          const p = `${parts.slice(0, i + 1).join('/')}/`;
          const isLast = i === parts.length - 1;
          return (
            <span key={p} className="flex items-center gap-1">
              <ChevronRight size={14} />
              <button
                type="button"
                onClick={() => navigate(p)}
                className={`hover:text-brand-600 ${isLast ? 'text-brand-700 font-medium' : ''}`}
              >
                {formatStorageSegment(part)}
              </button>
            </span>
          );
        })}
      </nav>

      {atRoot && (
        <div className="flex flex-wrap gap-2 mb-4">
          {ROOT_SHORTCUTS.map(({ prefix: shortcutPrefix, label, icon: Icon }) => (
            <button
              key={shortcutPrefix}
              type="button"
              onClick={() => navigate(shortcutPrefix)}
              className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-700 hover:bg-brand-50 hover:border-brand-200 hover:text-brand-800"
            >
              <Icon size={16} />
              {label}
            </button>
          ))}
        </div>
      )}

      <div className="bg-white rounded-xl border">
        {loading ? (
          <div className="flex justify-center py-16">
            <Loader2 className="animate-spin text-brand-500" size={32} />
          </div>
        ) : (
          <>
            {data?.folders.map((folder) => (
              <button
                key={folder.prefix}
                type="button"
                onClick={() => navigate(folder.prefix)}
                className="w-full flex items-center gap-3 px-4 py-3 border-b hover:bg-brand-50 text-left"
              >
                <Folder size={20} className="text-amber-500" />
                <span className="font-medium text-sm">{formatStorageSegment(folder.name)}/</span>
              </button>
            ))}
            {data?.files.map((file) => (
              <div key={file.objectName} className="flex items-center justify-between px-4 py-3 border-b text-sm">
                <span>{file.name}</span>
                <span className="text-slate-400 text-xs">{formatBytes(file.size)}</span>
              </div>
            ))}
            {!data?.folders.length && !data?.files.length && (
              <p className="text-center py-12 text-slate-400 text-sm">
                {atRoot ? 'Nenhuma pasta no repositório ainda.' : 'Pasta vazia'}
              </p>
            )}
          </>
        )}
      </div>

      {prefix ? (
        <label className="mt-4 inline-flex items-center gap-2 btn-primary cursor-pointer text-sm">
          <Upload size={16} />
          {uploading ? 'Enviando…' : 'Upload nesta pasta'}
          <input
            type="file"
            className="hidden"
            accept=".pdf,image/jpeg,image/png"
            disabled={uploading}
            onChange={(e) => {
              const file = e.target.files?.[0];
              if (file) handleUpload(file);
            }}
          />
        </label>
      ) : (
        <p className="mt-4 text-xs text-slate-500">Entre em uma pasta para enviar arquivos.</p>
      )}
    </>
  );
}
