import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  ArrowDown,
  ArrowUp,
  ArrowUpDown,
  ChevronLeft,
  ChevronRight,
  Download,
  Loader2,
  Plus,
  RefreshCw,
  Search,
} from 'lucide-react';
import { CreateProcessModal } from '../../components/admin/CreateProcessModal';
import { ProcessRowActions } from '../../components/admin/ProcessRowActions';
import { SeoHead } from '../../components/seo/SeoHead';
import {
  advanceProcessStep,
  fetchAdminProcesses,
  updateAdminProcessStatus,
} from '../../lib/portal-api';
import {
  exportProcessesCsv,
  filterProcesses,
  formatCpf,
  formatDate,
  formatDateShort,
  sortProcesses,
  type ProcessGridFilters,
  type ProcessSortDir,
  type ProcessSortKey,
} from '../../lib/process-grid-utils';
import type { ProcessListItem, ProcessModality, ProcessStatus, ProcessStepKey } from '../../types/process';
import { MODALITY_LABELS, STATUS_LABELS, STEP_LABELS } from '../../types/process';

const PAGE_SIZES = [10, 25, 50, 100] as const;

const STATUS_STYLES: Record<ProcessStatus, string> = {
  ativo: 'bg-emerald-100 text-emerald-800',
  concluido: 'bg-blue-100 text-blue-800',
  cancelado: 'bg-red-100 text-red-800',
};

const STEP_OPTIONS: ProcessStepKey[] = [
  'documentacao',
  'analise',
  'pericia',
  'ipi',
  'veiculo',
  'sefaz_mt',
  'sefaz_sp',
  'concluido',
  'cancelado',
];

function SortIcon({ active, dir }: { active: boolean; dir: ProcessSortDir }) {
  if (!active) return <ArrowUpDown size={13} className="opacity-40" />;
  return dir === 'asc' ? <ArrowUp size={13} /> : <ArrowDown size={13} />;
}

export function AdminProcessesPage() {
  const navigate = useNavigate();
  const [processes, setProcesses] = useState<ProcessListItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [actionBusyId, setActionBusyId] = useState<string | null>(null);
  const [showCreate, setShowCreate] = useState(false);
  const [sortKey, setSortKey] = useState<ProcessSortKey>('createdAt');
  const [sortDir, setSortDir] = useState<ProcessSortDir>('desc');
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState<(typeof PAGE_SIZES)[number]>(25);
  const [filters, setFilters] = useState<ProcessGridFilters>({
    search: '',
    status: 'all',
    step: 'all',
    modality: 'all',
  });

  const load = useCallback(() => {
    setLoading(true);
    setError('');
    fetchAdminProcesses()
      .then(setProcesses)
      .catch((err) => setError(err instanceof Error ? err.message : 'Erro ao carregar'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    setPage(0);
  }, [filters, sortKey, sortDir, pageSize]);

  const filtered = useMemo(
    () => sortProcesses(filterProcesses(processes, filters), sortKey, sortDir),
    [processes, filters, sortKey, sortDir]
  );

  const pageCount = Math.max(1, Math.ceil(filtered.length / pageSize));
  const pageItems = filtered.slice(page * pageSize, (page + 1) * pageSize);

  const stats = useMemo(
    () => ({
      total: processes.length,
      ativos: processes.filter((p) => p.status === 'ativo').length,
      concluidos: processes.filter((p) => p.status === 'concluido').length,
      cancelados: processes.filter((p) => p.status === 'cancelado').length,
    }),
    [processes]
  );

  const toggleSort = (key: ProcessSortKey) => {
    if (sortKey === key) setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    else {
      setSortKey(key);
      setSortDir(key === 'clientName' ? 'asc' : 'desc');
    }
  };

  const handleAction = async (action: string, p: ProcessListItem) => {
    if (action === 'view') {
      navigate(`/portal/processos/${p.id}`);
      return;
    }
    if (action === 'copyId') {
      await navigator.clipboard.writeText(p.id);
      return;
    }
    if (action === 'email') {
      window.location.href = `mailto:${p.clientEmail}`;
      return;
    }

    setActionBusyId(p.id);
    setError('');
    try {
      if (action === 'advance') {
        await advanceProcessStep(p.id);
      } else if (action.startsWith('status:')) {
        const status = action.replace('status:', '') as ProcessStatus;
        if (status === 'cancelado' && !confirm(`Cancelar o processo de ${p.clientName}?`)) return;
        await updateAdminProcessStatus(p.id, status);
      }
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha na ação');
    } finally {
      setActionBusyId(null);
    }
  };

  const handleCreated = (processId: string, tempPassword?: string) => {
    if (tempPassword) {
      alert(`Processo criado!\nSenha temporária do cliente: ${tempPassword}`);
    }
    load();
    navigate(`/portal/processos/${processId}`);
  };

  const Th = ({
    label,
    sort,
    className = '',
  }: {
    label: string;
    sort?: ProcessSortKey;
    className?: string;
  }) => (
    <th className={`text-left px-3 py-3 font-medium text-slate-600 whitespace-nowrap ${className}`}>
      {sort ? (
        <button
          type="button"
          onClick={() => toggleSort(sort)}
          className="inline-flex items-center gap-1 hover:text-brand-700"
        >
          {label}
          <SortIcon active={sortKey === sort} dir={sortDir} />
        </button>
      ) : (
        label
      )}
    </th>
  );

  return (
    <>
      <SeoHead title="Processos PCD | Portal Andrade" description="Gestão de processos de isenção PCD." noindex />
      <CreateProcessModal open={showCreate} onClose={() => setShowCreate(false)} onCreated={handleCreated} />

      <div className="space-y-6">
        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl font-display font-extrabold text-brand-800">Processos PCD</h1>
            <p className="text-slate-500 text-sm mt-1">Gestão completa dos processos de isenção</p>
          </div>
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={load}
              disabled={loading}
              className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border bg-white text-sm font-medium hover:bg-slate-50 disabled:opacity-50"
            >
              <RefreshCw size={15} className={loading ? 'animate-spin' : ''} />
              Atualizar
            </button>
            <button
              type="button"
              onClick={() => exportProcessesCsv(filtered)}
              disabled={filtered.length === 0}
              className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border bg-white text-sm font-medium hover:bg-slate-50 disabled:opacity-50"
            >
              <Download size={15} />
              Exportar CSV
            </button>
            <button
              type="button"
              onClick={() => setShowCreate(true)}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600"
            >
              <Plus size={16} />
              Novo processo
            </button>
          </div>
        </div>

        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          {[
            { label: 'Total', value: stats.total, color: 'text-brand-800' },
            { label: 'Ativos', value: stats.ativos, color: 'text-emerald-700' },
            { label: 'Concluídos', value: stats.concluidos, color: 'text-blue-700' },
            { label: 'Cancelados', value: stats.cancelados, color: 'text-red-600' },
          ].map((s) => (
            <div key={s.label} className="bg-white rounded-xl border border-slate-200 px-4 py-3">
              <p className="text-xs text-slate-500">{s.label}</p>
              <p className={`text-2xl font-bold ${s.color}`}>{s.value}</p>
            </div>
          ))}
        </div>

        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{error}</div>
        )}

        <div className="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
          <div className="flex flex-col xl:flex-row gap-3">
            <div className="relative flex-1 min-w-[200px]">
              <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="search"
                placeholder="Buscar nome, CPF, e-mail, ID…"
                value={filters.search}
                onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value }))}
                className="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-sm"
              />
            </div>
            <div className="flex flex-wrap gap-2">
              <select
                value={filters.status}
                onChange={(e) =>
                  setFilters((f) => ({ ...f, status: e.target.value as ProcessStatus | 'all' }))
                }
                className="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white min-w-[130px]"
              >
                <option value="all">Todos os status</option>
                {(Object.keys(STATUS_LABELS) as ProcessStatus[]).map((s) => (
                  <option key={s} value={s}>
                    {STATUS_LABELS[s]}
                  </option>
                ))}
              </select>
              <select
                value={filters.step}
                onChange={(e) =>
                  setFilters((f) => ({ ...f, step: e.target.value as ProcessStepKey | 'all' }))
                }
                className="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white min-w-[160px]"
              >
                <option value="all">Todas as etapas</option>
                {STEP_OPTIONS.map((s) => (
                  <option key={s} value={s}>
                    {STEP_LABELS[s]}
                  </option>
                ))}
              </select>
              <select
                value={filters.modality}
                onChange={(e) =>
                  setFilters((f) => ({ ...f, modality: e.target.value as ProcessModality | 'all' }))
                }
                className="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white min-w-[120px]"
              >
                <option value="all">Modalidade</option>
                <option value="pcd">PCD</option>
                <option value="taxi">Táxi</option>
              </select>
              <select
                value={pageSize}
                onChange={(e) => setPageSize(Number(e.target.value) as (typeof PAGE_SIZES)[number])}
                className="px-3 py-2 rounded-lg border border-slate-200 text-sm bg-white"
              >
                {PAGE_SIZES.map((n) => (
                  <option key={n} value={n}>
                    {n} / página
                  </option>
                ))}
              </select>
            </div>
          </div>

          <p className="text-xs text-slate-500">
            {filtered.length} resultado{filtered.length !== 1 ? 's' : ''}
            {filtered.length !== processes.length && ` de ${processes.length}`}
          </p>
        </div>

        {loading ? (
          <div className="flex justify-center py-16">
            <Loader2 className="animate-spin text-brand-500" size={32} />
          </div>
        ) : filtered.length === 0 ? (
          <div className="text-center py-16 text-slate-500 bg-white rounded-xl border">
            <p className="mb-4">Nenhum processo encontrado.</p>
            <button
              type="button"
              onClick={() => setShowCreate(true)}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-semibold"
            >
              <Plus size={16} />
              Criar primeiro processo
            </button>
          </div>
        ) : (
          <div className="bg-white rounded-xl border border-slate-200">
            <div className="overflow-x-auto">
              <table className="w-full text-sm min-w-[980px]">
                <thead className="bg-slate-50 border-b border-slate-200">
                  <tr>
                    <th className="text-left px-3 py-3 font-medium text-slate-600 w-[168px] sticky left-0 z-10 bg-slate-50">
                      Ações
                    </th>
                    <Th label="Cliente" sort="clientName" />
                    <Th label="CPF" sort="clientCpf" className="hidden md:table-cell" />
                    <Th label="Modalidade" sort="modality" className="hidden lg:table-cell" />
                    <Th label="Etapa" sort="currentStep" />
                    <Th label="Progresso" sort="progressPercent" className="hidden sm:table-cell" />
                    <Th label="Status" sort="status" />
                    <Th label="Criado" sort="createdAt" className="hidden xl:table-cell" />
                    <Th label="Atualizado" sort="updatedAt" className="hidden 2xl:table-cell" />
                  </tr>
                </thead>
                <tbody>
                  {pageItems.map((p) => (
                    <tr
                      key={p.id}
                      className="group border-b border-slate-100 last:border-0 hover:bg-brand-50/40 transition-colors"
                    >
                      <td className="px-3 py-3 sticky left-0 z-10 bg-white group-hover:bg-brand-50/40">
                        <ProcessRowActions
                          process={p}
                          onAction={(a, proc) => void handleAction(a, proc)}
                          busy={actionBusyId === p.id}
                        />
                      </td>
                      <td className="px-3 py-3">
                        <Link
                          to={`/portal/processos/${p.id}`}
                          className="font-medium text-brand-700 hover:underline block truncate max-w-[200px]"
                        >
                          {p.clientName}
                        </Link>
                        {p.clientLastSelfEditAt && (
                          <span className="inline-flex mt-1 text-[10px] font-extrabold uppercase tracking-wide text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded">
                            Editou cadastro
                          </span>
                        )}
                        <p className="text-xs text-slate-400 truncate max-w-[200px]">{p.clientEmail}</p>
                      </td>
                      <td className="px-3 py-3 hidden md:table-cell text-slate-600 whitespace-nowrap">
                        {formatCpf(p.clientCpf)}
                      </td>
                      <td className="px-3 py-3 hidden lg:table-cell">
                        <span className="text-xs font-medium bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full">
                          {MODALITY_LABELS[p.modality]}
                        </span>
                      </td>
                      <td className="px-3 py-3">
                        <span className="text-xs bg-brand-100 text-brand-700 px-2 py-0.5 rounded-full whitespace-nowrap">
                          {STEP_LABELS[p.currentStep]}
                        </span>
                      </td>
                      <td className="px-3 py-3 hidden sm:table-cell">
                        <div className="flex items-center gap-2 min-w-[100px]">
                          <div className="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div
                              className="h-full bg-brand-500 rounded-full transition-all"
                              style={{ width: `${p.progressPercent ?? 0}%` }}
                            />
                          </div>
                          <span className="text-xs text-slate-500 w-8 text-right">{p.progressPercent ?? 0}%</span>
                        </div>
                      </td>
                      <td className="px-3 py-3">
                        <span
                          className={`text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap ${STATUS_STYLES[p.status]}`}
                        >
                          {STATUS_LABELS[p.status]}
                        </span>
                      </td>
                      <td className="px-3 py-3 hidden xl:table-cell text-slate-500 whitespace-nowrap text-xs">
                        {formatDateShort(p.createdAt)}
                      </td>
                      <td className="px-3 py-3 hidden 2xl:table-cell text-slate-500 whitespace-nowrap text-xs">
                        {formatDate(p.updatedAt)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {pageCount > 1 && (
              <div className="flex items-center justify-between px-4 py-3 border-t border-slate-100 bg-slate-50/50">
                <p className="text-xs text-slate-500">
                  Página {page + 1} de {pageCount}
                </p>
                <div className="flex items-center gap-1">
                  <button
                    type="button"
                    disabled={page === 0}
                    onClick={() => setPage((p) => p - 1)}
                    className="p-2 rounded-lg border bg-white hover:bg-slate-50 disabled:opacity-40"
                    aria-label="Página anterior"
                  >
                    <ChevronLeft size={16} />
                  </button>
                  <button
                    type="button"
                    disabled={page >= pageCount - 1}
                    onClick={() => setPage((p) => p + 1)}
                    className="p-2 rounded-lg border bg-white hover:bg-slate-50 disabled:opacity-40"
                    aria-label="Próxima página"
                  >
                    <ChevronRight size={16} />
                  </button>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </>
  );
}
