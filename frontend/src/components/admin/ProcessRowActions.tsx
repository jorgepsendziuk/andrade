import { useEffect, useRef, useState, type ReactNode } from 'react';
import { createPortal } from 'react-dom';
import { Link } from 'react-router-dom';
import {
  Ban,
  CheckCircle2,
  Copy,
  Eye,
  FolderOpen,
  Loader2,
  Mail,
  MoreVertical,
  Pencil,
  Printer,
  RotateCcw,
  SkipForward,
  Trash2,
} from 'lucide-react';
import { buildClientStoragePrefix } from '../../lib/storage-paths';
import type { ProcessListItem } from '../../types/process';

interface ProcessRowActionsProps {
  process: ProcessListItem;
  busy: boolean;
  onAction: (action: string, p: ProcessListItem) => void;
}

function ActionBtn({
  label,
  onClick,
  disabled,
  danger,
  children,
}: {
  label: string;
  onClick?: () => void;
  disabled?: boolean;
  danger?: boolean;
  children: ReactNode;
}) {
  return (
    <button
      type="button"
      title={label}
      aria-label={label}
      disabled={disabled}
      onClick={onClick}
      className={`p-1.5 rounded-lg border bg-white transition-colors disabled:opacity-40 ${
        danger
          ? 'border-red-200 text-red-600 hover:bg-red-50'
          : 'border-slate-200 text-slate-600 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200'
      }`}
    >
      {children}
    </button>
  );
}

export function ProcessRowActions({ process, busy, onAction }: ProcessRowActionsProps) {
  const [menuOpen, setMenuOpen] = useState(false);
  const [menuPos, setMenuPos] = useState({ top: 0, left: 0 });
  const triggerRef = useRef<HTMLButtonElement>(null);
  const menuRef = useRef<HTMLDivElement>(null);

  const galleryPrefix = buildClientStoragePrefix(
    process.clientName,
    process.clientCpf,
    process.clientStorageSlug
  );

  useEffect(() => {
    if (!menuOpen) return;
    const close = (e: MouseEvent) => {
      const t = e.target as Node;
      if (triggerRef.current?.contains(t) || menuRef.current?.contains(t)) return;
      setMenuOpen(false);
    };
    const onScroll = () => setMenuOpen(false);
    document.addEventListener('mousedown', close);
    window.addEventListener('scroll', onScroll, true);
    return () => {
      document.removeEventListener('mousedown', close);
      window.removeEventListener('scroll', onScroll, true);
    };
  }, [menuOpen]);

  const openMenu = () => {
    const el = triggerRef.current;
    if (!el) return;
    const rect = el.getBoundingClientRect();
    const menuWidth = 220;
    let left = rect.left;
    if (left + menuWidth > window.innerWidth - 8) {
      left = Math.max(8, rect.right - menuWidth);
    }
    setMenuPos({ top: rect.bottom + 6, left });
    setMenuOpen(true);
  };

  const run = (action: string) => {
    setMenuOpen(false);
    onAction(action, process);
  };

  const menu = menuOpen
    ? createPortal(
        <div
          ref={menuRef}
          className="fixed z-[200] w-[220px] bg-white border border-slate-200 rounded-xl shadow-xl py-1 text-sm"
          style={{ top: menuPos.top, left: menuPos.left }}
          role="menu"
        >
          {process.status === 'ativo' && (
            <button
              type="button"
              role="menuitem"
              className="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 text-left text-slate-700"
              onClick={() => run('advance')}
            >
              <SkipForward size={15} className="text-brand-600 shrink-0" />
              Avançar etapa
            </button>
          )}
          <button
            type="button"
            role="menuitem"
            className="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 text-left text-slate-700"
            onClick={() => run('copyId')}
          >
            <Copy size={15} className="text-slate-500 shrink-0" />
            Copiar ID
          </button>
          <button
            type="button"
            role="menuitem"
            className="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 text-left text-slate-700"
            onClick={() => run('email')}
          >
            <Mail size={15} className="text-slate-500 shrink-0" />
            E-mail cliente
          </button>
          <Link
            to={`/portal/processos/${process.id}#documentos`}
            role="menuitem"
            className="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 text-left text-slate-700"
            onClick={() => setMenuOpen(false)}
          >
            <Printer size={15} className="text-slate-500 shrink-0" />
            Gerar documentos
          </Link>
          <hr className="my-1 border-slate-100" />
          {process.status !== 'concluido' && (
            <button
              type="button"
              role="menuitem"
              className="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-blue-50 text-left text-blue-700"
              onClick={() => run('status:concluido')}
            >
              <CheckCircle2 size={15} className="shrink-0" />
              Marcar concluído
            </button>
          )}
          {process.status !== 'cancelado' && (
            <button
              type="button"
              role="menuitem"
              className="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-red-50 text-left text-red-700"
              onClick={() => run('status:cancelado')}
            >
              <Ban size={15} className="shrink-0" />
              Cancelar processo
            </button>
          )}
          {process.status !== 'ativo' && (
            <button
              type="button"
              role="menuitem"
              className="w-full flex items-center gap-2.5 px-3 py-2 hover:bg-emerald-50 text-left text-emerald-700"
              onClick={() => run('status:ativo')}
            >
              <RotateCcw size={15} className="shrink-0" />
              Reativar
            </button>
          )}
        </div>,
        document.body
      )
    : null;

  return (
    <>
      <div className="flex items-center gap-1 shrink-0">
        <ActionBtn label="Abrir processo" disabled={busy} onClick={() => onAction('view', process)}>
          {busy ? <Loader2 size={15} className="animate-spin" /> : <Eye size={15} />}
        </ActionBtn>
        <Link
          to={`/portal/processos/${process.id}`}
          title="Editar processo"
          aria-label="Editar processo"
          className="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 transition-colors inline-flex"
        >
          <Pencil size={15} />
        </Link>
        <Link
          to={`/portal/arquivos?prefix=${encodeURIComponent(galleryPrefix)}`}
          title="Galeria de arquivos"
          aria-label="Galeria de arquivos"
          className="p-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 transition-colors inline-flex"
        >
          <FolderOpen size={15} />
        </Link>
        <ActionBtn
          label="Cancelar processo"
          danger
          disabled={busy || process.status === 'cancelado'}
          onClick={() => onAction('status:cancelado', process)}
        >
          <Trash2 size={15} />
        </ActionBtn>
        <button
          ref={triggerRef}
          type="button"
          title="Mais ações"
          aria-label="Mais ações"
          aria-expanded={menuOpen}
          aria-haspopup="menu"
          disabled={busy}
          onClick={() => (menuOpen ? setMenuOpen(false) : openMenu())}
          className={`p-1.5 rounded-lg border transition-colors disabled:opacity-40 ${
            menuOpen
              ? 'border-brand-300 bg-brand-50 text-brand-700'
              : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
          }`}
        >
          <MoreVertical size={15} />
        </button>
      </div>
      {menu}
    </>
  );
}
