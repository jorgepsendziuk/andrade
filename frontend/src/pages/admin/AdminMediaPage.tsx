import { useEffect, useMemo, useRef, useState } from 'react';
import {
  Check,
  CheckSquare,
  ChevronDown,
  ChevronRight,
  Copy,
  Download,
  ExternalLink,
  Grid3x3,
  ImageIcon,
  Layers,
  List,
  Loader2,
  Search,
  Square,
  Trash2,
  Upload,
  X,
} from 'lucide-react';
import { MediaLightbox } from '../../components/admin/MediaLightbox';
import { SeoHead } from '../../components/seo/SeoHead';
import { deleteMediaFile, fetchMediaLibrary, getToken, uploadFile } from '../../lib/api';
import {
  buildDuplicateGroups,
  duplicateCountMap,
  filterDuplicateFiles,
  formatBytes,
  matchesSizeFilter,
  SIZE_FILTER_OPTIONS,
  SORT_OPTIONS,
  sortMediaFiles,
  type MediaSizeFilter,
  type MediaSort,
  type MediaViewMode,
} from '../../lib/media-utils';
import type { MediaFile } from '../../types/media';

async function downloadFile(file: MediaFile) {
  const res = await fetch(file.url);
  const blob = await res.blob();
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = file.name;
  a.click();
  URL.revokeObjectURL(url);
}

const REASON_LABEL = {
  'same-name': 'Mesmo nome',
  'same-size': 'Mesmo tamanho',
  'same-stem': 'Nome parecido',
} as const;

export function AdminMediaPage() {
  const [files, setFiles] = useState<MediaFile[]>([]);
  const [storage, setStorage] = useState<'gcs' | 'local'>('gcs');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [folder, setFolder] = useState('all');
  const [sizeFilter, setSizeFilter] = useState<MediaSizeFilter>('all');
  const [sort, setSort] = useState<MediaSort>('date-desc');
  const [viewMode, setViewMode] = useState<MediaViewMode>('grid');
  const [duplicatesOnly, setDuplicatesOnly] = useState(false);
  const [previewIndex, setPreviewIndex] = useState<number | null>(null);
  const [picked, setPicked] = useState<Set<string>>(new Set());
  const [copied, setCopied] = useState(false);
  const [bulkBusy, setBulkBusy] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [expandedGroups, setExpandedGroups] = useState<Set<string>>(new Set());
  const inputRef = useRef<HTMLInputElement>(null);

  const load = () => {
    setLoading(true);
    setError('');
    fetchMediaLibrary()
      .then((data) => {
        setFiles(data.files);
        setStorage(data.storage);
        setPicked((prev) => {
          const next = new Set<string>();
          for (const id of prev) {
            if (data.files.some((f) => f.objectName === id)) next.add(id);
          }
          return next;
        });
      })
      .catch((err) => setError(err instanceof Error ? err.message : 'Erro ao carregar'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, []);

  const duplicateGroups = useMemo(() => buildDuplicateGroups(files), [files]);
  const dupCountMap = useMemo(() => duplicateCountMap(duplicateGroups), [duplicateGroups]);

  const folders = useMemo(() => {
    const set = new Set(files.map((f) => f.folder).filter(Boolean));
    return ['all', ...Array.from(set).sort()];
  }, [files]);

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase();
    let list = files.filter((file) => {
      if (folder !== 'all' && file.folder !== folder) return false;
      if (!matchesSizeFilter(file.size, sizeFilter)) return false;
      if (!q) return true;
      return (
        file.name.toLowerCase().includes(q) ||
        file.url.toLowerCase().includes(q) ||
        file.folder.toLowerCase().includes(q)
      );
    });
    if (duplicatesOnly) {
      list = filterDuplicateFiles(list, duplicateGroups);
    }
    return sortMediaFiles(list, sort);
  }, [files, search, folder, sizeFilter, sort, duplicatesOnly, duplicateGroups]);

  const previewFiles = viewMode === 'duplicates' ? filtered : filtered;
  const previewFile = previewIndex !== null ? previewFiles[previewIndex] : null;

  const totalBytes = useMemo(() => files.reduce((sum, f) => sum + f.size, 0), [files]);
  const duplicateFileCount = useMemo(
    () => duplicateGroups.reduce((sum, g) => sum + g.files.length, 0),
    [duplicateGroups]
  );

  const selectedFiles = useMemo(
    () => files.filter((f) => picked.has(f.objectName)),
    [files, picked]
  );

  const allFilteredSelected =
    filtered.length > 0 && filtered.every((f) => picked.has(f.objectName));

  const togglePick = (objectName: string) => {
    setPicked((prev) => {
      const next = new Set(prev);
      if (next.has(objectName)) next.delete(objectName);
      else next.add(objectName);
      return next;
    });
  };

  const selectAllFiltered = () => {
    setPicked((prev) => {
      const next = new Set(prev);
      filtered.forEach((f) => next.add(f.objectName));
      return next;
    });
  };

  const clearSelection = () => setPicked(new Set());

  const openPreview = (file: MediaFile) => {
    const idx = previewFiles.findIndex((f) => f.objectName === file.objectName);
    setPreviewIndex(idx >= 0 ? idx : 0);
  };

  const selectDuplicatesExceptNewest = (groupFiles: MediaFile[]) => {
    const sorted = sortMediaFiles(groupFiles, 'date-desc');
    const toPick = sorted.slice(1).filter((f) => !f.inUse);
    setPicked((prev) => {
      const next = new Set(prev);
      toPick.forEach((f) => next.add(f.objectName));
      return next;
    });
  };

  const toggleGroup = (key: string) => {
    setExpandedGroups((prev) => {
      const next = new Set(prev);
      if (next.has(key)) next.delete(key);
      else next.add(key);
      return next;
    });
  };

  const handleCopy = async (urls: string[]) => {
    await navigator.clipboard.writeText(urls.join('\n'));
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  const handleDeleteOne = async (file: MediaFile) => {
    if (file.inUse) {
      setError('Esta imagem está em uso no site. Troque-a antes de excluir.');
      return;
    }
    if (!confirm(`Excluir "${file.name}"? Esta ação não pode ser desfeita.`)) return;
    setBulkBusy(true);
    setError('');
    try {
      await deleteMediaFile(file.objectName);
      setFiles((prev) => prev.filter((f) => f.objectName !== file.objectName));
      setPicked((prev) => {
        const next = new Set(prev);
        next.delete(file.objectName);
        return next;
      });
      if (previewFile?.objectName === file.objectName) setPreviewIndex(null);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao excluir');
    } finally {
      setBulkBusy(false);
    }
  };

  const handleBulkDelete = async () => {
    const deletable = selectedFiles.filter((f) => !f.inUse);
    const skipped = selectedFiles.length - deletable.length;
    if (deletable.length === 0) {
      setError('Nenhuma das imagens selecionadas pode ser excluída (em uso no site).');
      return;
    }
    const msg =
      skipped > 0
        ? `Excluir ${deletable.length} imagem(ns)? ${skipped} em uso serão ignoradas.`
        : `Excluir ${deletable.length} imagem(ns)? Esta ação não pode ser desfeita.`;
    if (!confirm(msg)) return;

    setBulkBusy(true);
    setError('');
    const failed: string[] = [];
    const removed = new Set<string>();

    for (const file of deletable) {
      try {
        await deleteMediaFile(file.objectName);
        removed.add(file.objectName);
      } catch {
        failed.push(file.name);
      }
    }

    if (removed.size) {
      setFiles((prev) => prev.filter((f) => !removed.has(f.objectName)));
      setPicked((prev) => {
        const next = new Set(prev);
        removed.forEach((id) => next.delete(id));
        return next;
      });
      if (previewFile && removed.has(previewFile.objectName)) setPreviewIndex(null);
    }
    if (failed.length) {
      setError(`Falha ao excluir: ${failed.slice(0, 3).join(', ')}${failed.length > 3 ? '…' : ''}`);
    }
    setBulkBusy(false);
  };

  const handleBulkDownload = async () => {
    setBulkBusy(true);
    setError('');
    try {
      for (const file of selectedFiles) {
        await downloadFile(file);
        await new Promise((r) => setTimeout(r, 200));
      }
    } catch {
      setError('Falha ao baixar uma ou mais imagens.');
    } finally {
      setBulkBusy(false);
    }
  };

  const handleBulkOpen = () => {
    selectedFiles.slice(0, 10).forEach((file) => {
      window.open(file.url, '_blank', 'noopener,noreferrer');
    });
    if (selectedFiles.length > 10) {
      setError('Abertas as 10 primeiras. Use copiar URLs para ver todas.');
    }
  };

  const handleUpload = async (fileList: FileList | null) => {
    const file = fileList?.[0];
    const token = getToken();
    if (!file || !token) return;
    setUploading(true);
    setError('');
    try {
      await uploadFile(file, token);
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha no upload');
    } finally {
      setUploading(false);
      if (inputRef.current) inputRef.current.value = '';
    }
  };

  const renderTile = (file: MediaFile) => {
    const isPicked = picked.has(file.objectName);
    const dupCount = dupCountMap.get(file.objectName);
    return (
      <div
        key={file.objectName}
        className={`group relative aspect-square rounded-xl overflow-hidden border bg-slate-50 transition-all ${
          isPicked
            ? 'border-brand-500 ring-2 ring-brand-500/40 shadow-md'
            : 'border-slate-200 hover:border-brand-300 hover:shadow-md'
        }`}
      >
        <button
          type="button"
          onClick={() => openPreview(file)}
          className="absolute inset-0 w-full h-full text-left"
          aria-label={`Ver ${file.name}`}
        >
          <img src={file.url} alt={file.name} className="w-full h-full object-cover" loading="lazy" />
        </button>

        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            togglePick(file.objectName);
          }}
          className={`absolute top-2 right-2 z-10 w-7 h-7 rounded-md flex items-center justify-center shadow-md transition-colors ${
            isPicked
              ? 'bg-brand-500 text-white'
              : 'bg-white/95 text-slate-600 opacity-0 group-hover:opacity-100 focus:opacity-100'
          }`}
          aria-label={isPicked ? 'Desmarcar' : 'Selecionar'}
          aria-pressed={isPicked}
        >
          {isPicked ? <Check size={16} strokeWidth={3} /> : <Square size={14} />}
        </button>

        <div className="absolute top-2 left-2 z-10 flex flex-col gap-1 items-start">
          {file.inUse && (
            <span className="bg-accent text-white text-[10px] font-bold px-1.5 py-0.5 rounded">Em uso</span>
          )}
          {dupCount && dupCount > 1 && (
            <span className="bg-amber-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">
              ×{dupCount}
            </span>
          )}
        </div>

        <div className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-2 pt-6 pointer-events-none">
          <p className="text-white text-[10px] font-medium truncate">{file.name}</p>
          <p className="text-white/70 text-[9px] truncate">
            {formatBytes(file.size)} · {file.folder}
          </p>
        </div>
      </div>
    );
  };

  const renderListRow = (file: MediaFile) => {
    const isPicked = picked.has(file.objectName);
    const dupCount = dupCountMap.get(file.objectName);
    return (
      <div
        key={file.objectName}
        className={`flex items-center gap-3 p-2 rounded-xl border transition-all ${
          isPicked ? 'border-brand-500 bg-brand-50/50' : 'border-slate-200 hover:border-brand-200 hover:bg-slate-50'
        }`}
      >
        <button
          type="button"
          onClick={() => togglePick(file.objectName)}
          className="shrink-0 p-1 text-slate-500 hover:text-brand-600"
          aria-pressed={isPicked}
        >
          {isPicked ? <CheckSquare size={18} className="text-brand-600" /> : <Square size={18} />}
        </button>
        <button
          type="button"
          onClick={() => openPreview(file)}
          className="shrink-0 w-14 h-14 rounded-lg overflow-hidden border border-slate-200"
        >
          <img src={file.url} alt="" className="w-full h-full object-cover" loading="lazy" />
        </button>
        <button type="button" onClick={() => openPreview(file)} className="flex-1 min-w-0 text-left">
          <p className="font-medium text-sm text-slate-800 truncate">{file.name}</p>
          <p className="text-xs text-slate-500 truncate">
            {file.folder} · {formatBytes(file.size)} · {file.contentType.replace('image/', '').toUpperCase()}
          </p>
        </button>
        <div className="hidden sm:flex items-center gap-2 shrink-0">
          {file.inUse && (
            <span className="text-[10px] font-bold text-accent bg-accent/10 px-2 py-0.5 rounded">Em uso</span>
          )}
          {dupCount && dupCount > 1 && (
            <span className="text-[10px] font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded">
              ×{dupCount}
            </span>
          )}
        </div>
      </div>
    );
  };

  const visibleDuplicateGroups = useMemo(() => {
    const ids = new Set(filtered.map((f) => f.objectName));
    return duplicateGroups
      .map((g) => ({ ...g, files: g.files.filter((f) => ids.has(f.objectName)) }))
      .filter((g) => g.files.length > 1);
  }, [duplicateGroups, filtered]);

  return (
    <>
      <SeoHead title="Mídia | Portal Andrade" description="Biblioteca de imagens do site." noindex />
      <div className="space-y-6 pb-24">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold text-brand-800">Biblioteca de mídia</h1>
            <p className="text-slate-500 mt-1 text-sm">
              {files.length} arquivo{files.length !== 1 ? 's' : ''} · {formatBytes(totalBytes)}
              {duplicateGroups.length > 0 && (
                <span className="text-amber-600">
                  {' '}
                  · {duplicateGroups.length} grupo{duplicateGroups.length !== 1 ? 's' : ''} de duplicata
                  ({duplicateFileCount} arquivos)
                </span>
              )}
              {storage === 'gcs' ? ' · GCS' : ' · local (dev)'}
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={() => inputRef.current?.click()}
              disabled={uploading}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 disabled:opacity-50"
            >
              {uploading ? <Loader2 size={16} className="animate-spin" /> : <Upload size={16} />}
              Enviar imagem
            </button>
            <input
              ref={inputRef}
              type="file"
              accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml"
              className="sr-only"
              onChange={(e) => void handleUpload(e.target.files)}
            />
          </div>
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-start justify-between gap-3">
            <span>{error}</span>
            <button type="button" onClick={() => setError('')} className="text-red-400 hover:text-red-600 shrink-0">
              <X size={16} />
            </button>
          </div>
        )}

        <div className="flex flex-col gap-3">
          <div className="flex flex-col lg:flex-row gap-3">
            <div className="relative flex-1">
              <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="search"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                placeholder="Buscar por nome ou pasta…"
                className="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-sm"
              />
            </div>
            <div className="flex flex-wrap gap-2">
              <select
                value={folder}
                onChange={(e) => setFolder(e.target.value)}
                className="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white min-w-[140px]"
              >
                {folders.map((f) => (
                  <option key={f} value={f}>
                    {f === 'all' ? 'Todas as pastas' : f}
                  </option>
                ))}
              </select>
              <select
                value={sizeFilter}
                onChange={(e) => setSizeFilter(e.target.value as MediaSizeFilter)}
                className="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white min-w-[150px]"
              >
                {SIZE_FILTER_OPTIONS.map((o) => (
                  <option key={o.value} value={o.value}>
                    {o.label}
                  </option>
                ))}
              </select>
              <select
                value={sort}
                onChange={(e) => setSort(e.target.value as MediaSort)}
                className="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white min-w-[150px]"
              >
                {SORT_OPTIONS.map((o) => (
                  <option key={o.value} value={o.value}>
                    {o.label}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="flex flex-wrap items-center gap-2">
            <div className="inline-flex rounded-lg border border-slate-200 p-0.5 bg-white">
              {(
                [
                  { mode: 'grid' as const, icon: Grid3x3, label: 'Grade' },
                  { mode: 'list' as const, icon: List, label: 'Lista' },
                  { mode: 'duplicates' as const, icon: Layers, label: 'Duplicatas' },
                ] as const
              ).map(({ mode, icon: Icon, label }) => (
                <button
                  key={mode}
                  type="button"
                  onClick={() => setViewMode(mode)}
                  className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-colors ${
                    viewMode === mode ? 'bg-brand-500 text-white' : 'text-slate-600 hover:bg-slate-50'
                  }`}
                >
                  <Icon size={14} />
                  {label}
                </button>
              ))}
            </div>

            <label className="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium cursor-pointer hover:bg-slate-50">
              <input
                type="checkbox"
                checked={duplicatesOnly}
                onChange={(e) => setDuplicatesOnly(e.target.checked)}
                className="rounded border-slate-300 text-brand-500"
              />
              Só duplicatas
            </label>

            <span className="text-xs text-slate-500 ml-auto">
              {filtered.length} resultado{filtered.length !== 1 ? 's' : ''}
            </span>
          </div>
        </div>

        {filtered.length > 0 && !loading && viewMode !== 'duplicates' && (
          <div className="flex flex-wrap items-center gap-2 text-sm">
            <button
              type="button"
              onClick={allFilteredSelected ? clearSelection : selectAllFiltered}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 font-medium text-slate-700"
            >
              {allFilteredSelected ? <CheckSquare size={15} /> : <Square size={15} />}
              {allFilteredSelected ? 'Desmarcar filtrados' : `Selecionar filtrados (${filtered.length})`}
            </button>
            {picked.size > 0 && (
              <button
                type="button"
                onClick={clearSelection}
                className="px-3 py-1.5 rounded-lg text-slate-500 hover:text-slate-700 hover:bg-slate-50"
              >
                Limpar seleção
              </button>
            )}
          </div>
        )}

        {loading ? (
          <div className="flex justify-center py-20 text-slate-500">
            <Loader2 className="animate-spin mr-2" size={24} />
            Carregando biblioteca…
          </div>
        ) : filtered.length === 0 ? (
          <div className="text-center py-20 text-slate-500">
            <ImageIcon size={40} className="mx-auto mb-3 opacity-40" />
            <p>Nenhuma imagem encontrada.</p>
          </div>
        ) : viewMode === 'duplicates' ? (
          <div className="space-y-3">
            {visibleDuplicateGroups.length === 0 ? (
              <p className="text-center py-12 text-slate-500">Nenhum grupo de duplicatas nos filtros atuais.</p>
            ) : (
              visibleDuplicateGroups.map((group) => {
                const open = expandedGroups.has(group.key) || visibleDuplicateGroups.length <= 5;
                return (
                  <div key={group.key} className="border border-slate-200 rounded-xl overflow-hidden bg-white">
                    <button
                      type="button"
                      onClick={() => toggleGroup(group.key)}
                      className="w-full flex items-center gap-3 px-4 py-3 hover:bg-slate-50 text-left"
                    >
                      {open ? <ChevronDown size={18} className="text-slate-400" /> : <ChevronRight size={18} className="text-slate-400" />}
                      <div className="flex-1 min-w-0">
                        <p className="font-semibold text-sm text-slate-800 truncate">{group.label}</p>
                        <p className="text-xs text-slate-500">
                          {REASON_LABEL[group.reason]} · {group.files.length} arquivos
                        </p>
                      </div>
                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          selectDuplicatesExceptNewest(group.files);
                        }}
                        className="shrink-0 px-3 py-1.5 rounded-lg border border-amber-200 text-amber-800 text-xs font-semibold hover:bg-amber-50"
                      >
                        Selecionar extras
                      </button>
                    </button>
                    {open && (
                      <div className="px-4 pb-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 border-t border-slate-100 pt-3">
                        {group.files.map((file) => renderTile(file))}
                      </div>
                    )}
                  </div>
                );
              })
            )}
          </div>
        ) : viewMode === 'list' ? (
          <div className="space-y-2">{filtered.map((file) => renderListRow(file))}</div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            {filtered.map((file) => renderTile(file))}
          </div>
        )}
      </div>

      {picked.size > 0 && (
        <div className="fixed bottom-4 left-1/2 -translate-x-1/2 z-40 w-[calc(100%-2rem)] max-w-3xl">
          <div className="bg-brand-900 text-white rounded-xl shadow-2xl px-4 py-3 flex flex-wrap items-center gap-2 sm:gap-3">
            <span className="text-sm font-semibold mr-1">
              {picked.size} selecionada{picked.size !== 1 ? 's' : ''}
            </span>
            <div className="flex flex-wrap gap-2 flex-1">
              <button
                type="button"
                disabled={bulkBusy}
                onClick={() => void handleCopy(selectedFiles.map((f) => f.url))}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold disabled:opacity-50"
              >
                {copied ? <Check size={14} /> : <Copy size={14} />}
                {copied ? 'Copiado!' : 'Copiar URLs'}
              </button>
              <button
                type="button"
                disabled={bulkBusy}
                onClick={handleBulkOpen}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold disabled:opacity-50"
              >
                <ExternalLink size={14} />
                Abrir
              </button>
              <button
                type="button"
                disabled={bulkBusy}
                onClick={() => void handleBulkDownload()}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold disabled:opacity-50"
              >
                {bulkBusy ? <Loader2 size={14} className="animate-spin" /> : <Download size={14} />}
                Baixar
              </button>
              <button
                type="button"
                disabled={bulkBusy || selectedFiles.every((f) => f.inUse)}
                onClick={() => void handleBulkDelete()}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-500/90 hover:bg-red-500 text-xs font-semibold disabled:opacity-40"
              >
                <Trash2 size={14} />
                Excluir
              </button>
            </div>
            <button
              type="button"
              onClick={clearSelection}
              className="p-1.5 rounded-lg hover:bg-white/10 text-white/80"
              aria-label="Limpar seleção"
            >
              <X size={16} />
            </button>
          </div>
        </div>
      )}

      {previewIndex !== null && previewFile && (
        <MediaLightbox
          files={previewFiles}
          index={previewIndex}
          onIndexChange={setPreviewIndex}
          onClose={() => setPreviewIndex(null)}
          picked={picked}
          onTogglePick={togglePick}
          onCopy={(urls) => void handleCopy(urls)}
          copied={copied}
          onDownload={(f) => void downloadFile(f)}
          onDelete={(f) => void handleDeleteOne(f)}
          busy={bulkBusy}
          duplicateCount={dupCountMap.get(previewFile.objectName)}
        />
      )}
    </>
  );
}
