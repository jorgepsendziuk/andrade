import { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  ArrowDown,
  ArrowUp,
  ArrowUpDown,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  Copy,
  RotateCcw,
  Wrench,
} from 'lucide-react';
import {
  AUDIT_ACTION_STYLES,
  auditActionLabel,
  auditFieldLabel,
  auditFriendlySummary,
  auditRecordLink,
  auditResourceLabel,
  auditRoleLabel,
  canRevertAuditLog,
  formatAuditValue,
  formatAuditWhen,
  sortAuditLogs,
  type AuditSortDir,
  type AuditSortKey,
} from '../../lib/audit-ui';
import { revertAdminAudit } from '../../lib/portal-api';
import type { AuditLogRecord } from '../../types/process';

const PAGE_SIZES = [10, 25, 50] as const;

function SortIcon({ active, dir }: { active: boolean; dir: AuditSortDir }) {
  if (!active) return <ArrowUpDown size={13} className="opacity-40" />;
  return dir === 'asc' ? <ArrowUp size={13} /> : <ArrowDown size={13} />;
}

interface AuditLogsPanelProps {
  logs: AuditLogRecord[];
  variant?: 'staff' | 'client';
  onReverted?: () => void;
}

export function AuditLogsPanel({ logs, variant = 'staff', onReverted }: AuditLogsPanelProps) {
  const [openId, setOpenId] = useState<string | null>(null);
  const [techId, setTechId] = useState<string | null>(null);
  const [sortKey, setSortKey] = useState<AuditSortKey>('createdAt');
  const [sortDir, setSortDir] = useState<AuditSortDir>('desc');
  const [page, setPage] = useState(0);
  const [pageSize, setPageSize] = useState<(typeof PAGE_SIZES)[number]>(25);
  const [reverting, setReverting] = useState<string | null>(null);
  const [error, setError] = useState('');
  const [copied, setCopied] = useState('');

  const sorted = useMemo(() => sortAuditLogs(logs, sortKey, sortDir), [logs, sortKey, sortDir]);
  const pageCount = Math.max(1, Math.ceil(sorted.length / pageSize));
  const safePage = Math.min(page, pageCount - 1);
  const items = sorted.slice(safePage * pageSize, (safePage + 1) * pageSize);

  const toggleSort = (key: AuditSortKey) => {
    if (sortKey === key) setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'));
    else {
      setSortKey(key);
      setSortDir(key === 'who' || key === 'action' || key === 'where' ? 'asc' : 'desc');
    }
    setPage(0);
  };

  const copy = async (value: string, label: string) => {
    await navigator.clipboard.writeText(value);
    setCopied(label);
    window.setTimeout(() => setCopied(''), 1500);
  };

  const revert = async (log: AuditLogRecord) => {
    const fields = (log.changes ?? []).map((change) => auditFieldLabel(change.field)).join(', ');
    if (
      !window.confirm(
        `Desfazer esta alteração?\n\nOs campos voltam ao valor de antes: ${fields}.\nSe alguém mudou esses dados depois, a mudança mais nova pode ser perdida.`
      )
    ) {
      return;
    }
    setReverting(log.id);
    setError('');
    try {
      await revertAdminAudit(log.id);
      onReverted?.();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Não foi possível desfazer');
    } finally {
      setReverting(null);
    }
  };

  const Th = ({ label, sort, className = '' }: { label: string; sort: AuditSortKey; className?: string }) => (
    <th className={`text-left px-3 py-3 font-medium text-slate-600 ${className}`}>
      <button type="button" onClick={() => toggleSort(sort)} className="inline-flex items-center gap-1 hover:text-brand-700">
        {label}
        <SortIcon active={sortKey === sort} dir={sortDir} />
      </button>
    </th>
  );

  if (!logs.length) {
    return <p className="text-sm text-slate-500">Nenhuma alteração registrada ainda.</p>;
  }

  return (
    <div>
      {error && (
        <div className="mb-3 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm">{error}</div>
      )}
      {copied && <p className="mb-2 text-xs text-emerald-700">{copied} copiado.</p>}

      <div className="overflow-x-auto">
        <table className="w-full text-sm min-w-[720px]">
          <thead className="bg-slate-50 border-b border-slate-200">
            <tr>
              <Th label="Quando" sort="createdAt" />
              <Th label="Quem fez" sort="who" />
              <Th label="O que aconteceu" sort="action" />
              <Th label="Onde" sort="where" className="hidden md:table-cell" />
              <th className="text-left px-3 py-3 font-medium text-slate-600">O que mudou</th>
              {variant === 'staff' && (
                <th className="text-right px-3 py-3 font-medium text-slate-600 w-[1%]">Ações</th>
              )}
            </tr>
          </thead>
          <tbody>
            {items.map((log) => {
              const open = openId === log.id;
              const tech = techId === log.id;
              const when = formatAuditWhen(log.createdAt);
              const link = variant === 'staff' ? auditRecordLink(log) : null;
              const canRevert = variant === 'staff' && canRevertAuditLog(log);
              return (
                <tr key={log.id} className="border-b border-slate-100 align-top hover:bg-brand-50/30">
                  <td className="px-3 py-3 whitespace-nowrap">
                    <p className="font-medium text-slate-800" title={when.exact}>
                      {when.relative}
                    </p>
                    <p className="text-[11px] text-slate-400">{when.exact}</p>
                  </td>
                  <td className="px-3 py-3">
                    <p className="font-medium text-slate-800">{log.userEmail || 'Equipe ou cliente'}</p>
                    <p className="text-xs text-slate-500">{auditRoleLabel(log.userRole)}</p>
                  </td>
                  <td className="px-3 py-3">
                    <span
                      className={`inline-flex px-2 py-0.5 rounded-full text-xs font-semibold ${
                        AUDIT_ACTION_STYLES[log.action] || 'bg-slate-100 text-slate-700'
                      }`}
                    >
                      {auditActionLabel(log.action)}
                    </span>
                  </td>
                  <td className="px-3 py-3 hidden md:table-cell">
                    <p className="text-slate-700">{auditResourceLabel(log.resourceType)}</p>
                    {link && (
                      <Link to={link.to} className="text-xs text-brand-600 hover:underline">
                        {link.label}
                      </Link>
                    )}
                  </td>
                  <td className="px-3 py-3">
                    <button
                      type="button"
                      onClick={() => setOpenId(open ? null : log.id)}
                      className="inline-flex items-start gap-1 text-left text-slate-700 hover:text-brand-700"
                    >
                      {log.changes?.length ? (
                        open ? <ChevronDown size={14} className="mt-0.5 shrink-0" /> : <ChevronRight size={14} className="mt-0.5 shrink-0" />
                      ) : null}
                      <span>{auditFriendlySummary(log)}</span>
                    </button>
                    {open && log.changes?.length ? (
                      <ul className="mt-2 space-y-1 text-xs bg-slate-50 rounded-lg p-2">
                        {log.changes.map((change) => (
                          <li key={change.field}>
                            <span className="font-semibold text-slate-600">{auditFieldLabel(change.field)}:</span>{' '}
                            <span className="text-red-600">{formatAuditValue(change.from)}</span>
                            {' → '}
                            <span className="text-emerald-700">{formatAuditValue(change.to)}</span>
                          </li>
                        ))}
                      </ul>
                    ) : null}
                    {tech && (
                      <div className="mt-2 text-[11px] text-slate-500 bg-slate-50 border border-slate-200 rounded-lg p-2 space-y-1">
                        <p>
                          Código do registro:{' '}
                          <button type="button" className="underline" onClick={() => void copy(log.id, 'Código')}>
                            {log.id}
                          </button>
                        </p>
                        {log.resourceId && (
                          <p>
                            Código do item:{' '}
                            <button
                              type="button"
                              className="underline"
                              onClick={() => void copy(log.resourceId!, 'Código do item')}
                            >
                              {log.resourceId}
                            </button>
                          </p>
                        )}
                        {log.ip && <p>Endereço de origem: {log.ip}</p>}
                        {log.objectName && <p>Arquivo: {log.objectName}</p>}
                      </div>
                    )}
                  </td>
                  {variant === 'staff' && (
                    <td className="px-3 py-3">
                      <div className="flex flex-wrap justify-end gap-1">
                        {canRevert && (
                          <button
                            type="button"
                            onClick={() => void revert(log)}
                            disabled={reverting === log.id}
                            className="inline-flex items-center gap-1 text-xs font-semibold text-amber-800 hover:bg-amber-100 px-2 py-1 rounded-lg disabled:opacity-50"
                          >
                            <RotateCcw size={13} />
                            Desfazer
                          </button>
                        )}
                        <button
                          type="button"
                          onClick={() => setTechId(tech ? null : log.id)}
                          className="inline-flex items-center gap-1 text-xs text-slate-500 hover:bg-slate-100 px-2 py-1 rounded-lg"
                        >
                          {tech ? <Copy size={13} /> : <Wrench size={13} />}
                          {tech ? 'Ocultar' : 'Técnico'}
                        </button>
                      </div>
                    </td>
                  )}
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <div className="flex flex-wrap items-center justify-between gap-3 mt-3 text-xs text-slate-500">
        <p>
          {sorted.length} registro{sorted.length !== 1 ? 's' : ''}
        </p>
        <div className="flex items-center gap-2">
          <select
            value={pageSize}
            onChange={(e) => {
              setPageSize(Number(e.target.value) as (typeof PAGE_SIZES)[number]);
              setPage(0);
            }}
            className="px-2 py-1 rounded-lg border border-slate-200 bg-white"
          >
            {PAGE_SIZES.map((n) => (
              <option key={n} value={n}>
                {n} por página
              </option>
            ))}
          </select>
          <button
            type="button"
            disabled={safePage === 0}
            onClick={() => setPage((p) => Math.max(0, p - 1))}
            className="p-1 rounded-lg hover:bg-slate-100 disabled:opacity-30"
          >
            <ChevronLeft size={16} />
          </button>
          <span>
            {safePage + 1} / {pageCount}
          </span>
          <button
            type="button"
            disabled={safePage >= pageCount - 1}
            onClick={() => setPage((p) => p + 1)}
            className="p-1 rounded-lg hover:bg-slate-100 disabled:opacity-30"
          >
            <ChevronRight size={16} />
          </button>
        </div>
      </div>
    </div>
  );
}
