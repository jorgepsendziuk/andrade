import { useRef, useState } from 'react';
import {
  AlertTriangle,
  Download,
  ExternalLink,
  FileUp,
  FolderOpen,
  Loader2,
  Pencil,
  RefreshCw,
  Trash2,
  Upload,
} from 'lucide-react';
import { Link } from 'react-router-dom';
import {
  deleteAdminFile,
  downloadAdminFile,
  openAdminFile,
  replaceAdminFile,
  updateAdminFile,
  uploadAdminProcessFile,
} from '../../lib/portal-api';
import { REQUIRED_DOCS, formatFileSize } from '../../lib/process-steps';
import { FILE_TYPE_ICONS, mimeIcon } from '../../lib/process-ui-icons';
import { FILE_TYPE_LABELS, type FileTypeCode, type ProcessFileRecord } from '../../types/process';

interface ProcessDocumentsPanelProps {
  processId: string;
  storagePrefix: string;
  files: ProcessFileRecord[];
  onUpdated: () => void;
}

const ALL_FILE_TYPES = Object.keys(FILE_TYPE_LABELS) as FileTypeCode[];

export function ProcessDocumentsPanel({
  processId,
  storagePrefix,
  files,
  onUpdated,
}: ProcessDocumentsPanelProps) {
  const [busyId, setBusyId] = useState<string | null>(null);
  const [error, setError] = useState('');
  const [uploadType, setUploadType] = useState<FileTypeCode>('outros');
  const [editingNameId, setEditingNameId] = useState<string | null>(null);
  const [nameDraft, setNameDraft] = useState('');
  const replaceRef = useRef<Record<string, HTMLInputElement | null>>({});
  const uploadRef = useRef<HTMLInputElement>(null);

  const uploadedTypes = new Set(files.map((f) => f.fileType));
  const missing = REQUIRED_DOCS.filter((t) => !uploadedTypes.has(t));

  const run = async (id: string, fn: () => Promise<void>) => {
    setBusyId(id);
    setError('');
    try {
      await fn();
      onUpdated();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha na operação');
    } finally {
      setBusyId(null);
    }
  };

  const handleUpload = async (file: File) => {
    await run('upload', async () => {
      await uploadAdminProcessFile(processId, file, uploadType);
    });
    if (uploadRef.current) uploadRef.current.value = '';
  };

  const sorted = [...files].sort((a, b) => b.createdAt.localeCompare(a.createdAt));

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-5">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div className="flex items-center gap-2">
          <FileUp size={20} className="text-brand-600" />
          <div>
            <h2 className="font-display font-bold text-brand-800">Documentos ({files.length})</h2>
            <p className="text-xs text-slate-500">Gerencie tipos, substitua ou exclua arquivos</p>
          </div>
        </div>
        <Link
          to={`/portal/arquivos?prefix=${encodeURIComponent(storagePrefix)}`}
          className="inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:underline"
        >
          <FolderOpen size={14} />
          {storagePrefix.replace(/\/$/, '').replace('clientes/', '')}
        </Link>
      </div>

      {error && (
        <div className="mb-4 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm">{error}</div>
      )}

      {missing.length > 0 && (
        <div className="mb-4 flex items-start gap-2 bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-900">
          <AlertTriangle size={16} className="shrink-0 mt-0.5" />
          <p>
            <span className="font-semibold">Documentos obrigatórios pendentes:</span>{' '}
            {missing.map((m) => FILE_TYPE_LABELS[m]).join(', ')}
          </p>
        </div>
      )}

      {sorted.length === 0 ? (
        <p className="text-sm text-slate-500 text-center py-8">Nenhum documento anexado ainda.</p>
      ) : (
        <div className="space-y-2 mb-4">
          {sorted.map((f) => {
            const TypeIcon = FILE_TYPE_ICONS[f.fileType];
            const MimeIcon = mimeIcon(f.mimeType);
            const busy = busyId === f.id;

            return (
              <div
                key={f.id}
                className="flex flex-col sm:flex-row sm:items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-brand-200 bg-slate-50/50"
              >
                <div className="flex items-center gap-3 flex-1 min-w-0">
                  <div className="w-10 h-10 rounded-lg bg-white border border-slate-200 flex items-center justify-center shrink-0">
                    <TypeIcon size={18} className="text-brand-600" />
                  </div>
                  <div className="min-w-0 flex-1">
                    {editingNameId === f.id ? (
                      <div className="flex gap-2">
                        <input
                          value={nameDraft}
                          onChange={(e) => setNameDraft(e.target.value)}
                          className="flex-1 px-2 py-1 rounded border text-sm"
                          autoFocus
                        />
                        <button
                          type="button"
                          disabled={busy}
                          onClick={() =>
                            void run(f.id, async () => {
                              await updateAdminFile(f.id, { originalName: nameDraft });
                              setEditingNameId(null);
                            })
                          }
                          className="text-xs font-semibold text-brand-600"
                        >
                          Salvar
                        </button>
                      </div>
                    ) : (
                      <p className="font-medium text-sm text-slate-800 truncate">{f.originalName}</p>
                    )}
                    <p className="text-xs text-slate-500 flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-0.5">
                      <MimeIcon size={11} className="inline" />
                      <span>{formatFileSize(f.size)}</span>
                      <span>·</span>
                      <span>{new Date(f.createdAt).toLocaleDateString('pt-BR')}</span>
                    </p>
                    {f.humanPath && (
                      <p className="text-[10px] text-slate-400 truncate mt-0.5 font-mono" title={f.objectName}>
                        {f.humanPath}
                      </p>
                    )}
                  </div>
                </div>

                <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                  <select
                    value={f.fileType}
                    disabled={busy}
                    onChange={(e) =>
                      void run(f.id, async () => {
                        await updateAdminFile(f.id, { fileType: e.target.value as FileTypeCode });
                      })
                    }
                    className="px-2 py-1.5 rounded-lg border border-slate-200 text-xs bg-white min-w-[140px]"
                  >
                    {ALL_FILE_TYPES.map((t) => (
                      <option key={t} value={t}>
                        {FILE_TYPE_LABELS[t]}
                      </option>
                    ))}
                  </select>

                  <button
                    type="button"
                    disabled={busy}
                    title="Abrir"
                    onClick={() => void run(f.id, async () => openAdminFile(f.id))}
                    className="p-2 rounded-lg border bg-white hover:bg-brand-50 text-brand-600 disabled:opacity-50"
                  >
                    {busy ? <Loader2 size={15} className="animate-spin" /> : <ExternalLink size={15} />}
                  </button>
                  <button
                    type="button"
                    disabled={busy}
                    title="Baixar"
                    onClick={() => void run(f.id, async () => downloadAdminFile(f.id, f.originalName))}
                    className="p-2 rounded-lg border bg-white hover:bg-slate-50 text-slate-600 disabled:opacity-50"
                  >
                    <Download size={15} />
                  </button>
                  <button
                    type="button"
                    disabled={busy}
                    title="Renomear"
                    onClick={() => {
                      setEditingNameId(f.id);
                      setNameDraft(f.originalName);
                    }}
                    className="p-2 rounded-lg border bg-white hover:bg-slate-50 text-slate-600 disabled:opacity-50"
                  >
                    <Pencil size={15} />
                  </button>
                  <button
                    type="button"
                    disabled={busy}
                    title="Substituir arquivo"
                    onClick={() => replaceRef.current[f.id]?.click()}
                    className="p-2 rounded-lg border bg-white hover:bg-amber-50 text-amber-700 disabled:opacity-50"
                  >
                    <RefreshCw size={15} />
                  </button>
                  <input
                    ref={(el) => {
                      replaceRef.current[f.id] = el;
                    }}
                    type="file"
                    className="hidden"
                    accept=".pdf,image/jpeg,image/png,image/webp"
                    onChange={(e) => {
                      const file = e.target.files?.[0];
                      if (file) {
                        void run(f.id, async () => {
                          await replaceAdminFile(f.id, file);
                        });
                      }
                      e.target.value = '';
                    }}
                  />
                  <button
                    type="button"
                    disabled={busy}
                    title="Excluir"
                    onClick={() => {
                      if (!confirm(`Excluir "${f.originalName}"? Esta ação não pode ser desfeita.`)) return;
                      void run(f.id, async () => deleteAdminFile(f.id));
                    }}
                    className="p-2 rounded-lg border border-red-200 bg-white hover:bg-red-50 text-red-600 disabled:opacity-50"
                  >
                    <Trash2 size={15} />
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}

      <div className="border border-dashed border-slate-300 rounded-xl p-4 bg-slate-50/50">
        <p className="text-xs font-medium text-slate-600 mb-2 flex items-center gap-1.5">
          <Upload size={14} /> Enviar novo documento
        </p>
        <div className="flex flex-col sm:flex-row gap-2">
          <select
            value={uploadType}
            onChange={(e) => setUploadType(e.target.value as FileTypeCode)}
            className="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white sm:min-w-[180px]"
          >
            {ALL_FILE_TYPES.map((t) => (
              <option key={t} value={t}>
                {FILE_TYPE_LABELS[t]}
              </option>
            ))}
          </select>
          <label className="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 cursor-pointer">
            {busyId === 'upload' ? <Loader2 size={16} className="animate-spin" /> : <Upload size={16} />}
            Selecionar arquivo
            <input
              ref={uploadRef}
              type="file"
              className="hidden"
              accept=".pdf,image/jpeg,image/png,image/webp"
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (file) void handleUpload(file);
              }}
            />
          </label>
        </div>
      </div>
    </div>
  );
}
