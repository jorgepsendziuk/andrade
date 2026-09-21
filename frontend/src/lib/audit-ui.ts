import { CLIENT_FIELD_LABELS, type AuditLogRecord } from '../types/process';

export const AUDIT_ACTION_LABELS: Record<string, string> = {
  view: 'Visualizou',
  download: 'Baixou arquivo',
  upload: 'Enviou arquivo',
  delete: 'Excluiu',
  login: 'Entrou no sistema',
  consent: 'Aceitou os termos',
  create: 'Criou',
  update: 'Alterou dados',
  password_reset: 'Redefiniu senha',
};

export const AUDIT_ACTION_STYLES: Record<string, string> = {
  view: 'bg-slate-100 text-slate-700',
  download: 'bg-sky-100 text-sky-800',
  upload: 'bg-blue-100 text-blue-800',
  delete: 'bg-red-100 text-red-800',
  login: 'bg-slate-100 text-slate-700',
  consent: 'bg-violet-100 text-violet-800',
  create: 'bg-emerald-100 text-emerald-800',
  update: 'bg-amber-100 text-amber-900',
  password_reset: 'bg-orange-100 text-orange-800',
};

export const AUDIT_RESOURCE_LABELS: Record<string, string> = {
  client: 'Cadastro',
  process: 'Processo',
  process_file: 'Arquivo',
  file: 'Arquivo',
};

export const AUDIT_ROLE_LABELS: Record<string, string> = {
  admin: 'Administração',
  comercial: 'Comercial',
  cliente: 'Cliente',
  editor: 'Conteúdo do site',
};

const EXTRA_FIELD_LABELS: Record<string, string> = {
  modality: 'Modalidade',
  status: 'Situação',
  vehicle: 'Veículo',
  honorarios: 'Honorários',
  pagamentoTipo: 'Forma de pagamento',
  pagamentoStatus: 'Situação do pagamento',
  pagamentos: 'Pagamentos',
  currentStep: 'Etapa atual',
  steps: 'Etapas',
  step: 'Etapa',
  conductors: 'Condutores',
};

export type AuditSortKey = 'createdAt' | 'who' | 'action' | 'where';
export type AuditSortDir = 'asc' | 'desc';

export function auditFieldLabel(field: string): string {
  return CLIENT_FIELD_LABELS[field] || EXTRA_FIELD_LABELS[field] || field;
}

export function auditActionLabel(action: string): string {
  return AUDIT_ACTION_LABELS[action] || action;
}

export function auditResourceLabel(resourceType: string): string {
  return AUDIT_RESOURCE_LABELS[resourceType] || 'Registro';
}

export function auditRoleLabel(role: string): string {
  return AUDIT_ROLE_LABELS[role] || role;
}

export function formatAuditValue(value: unknown): string {
  if (value === null || value === undefined || value === '') return 'vazio';
  if (value === true) return 'Sim';
  if (value === false) return 'Não';
  if (Array.isArray(value)) {
    if (!value.length) return 'vazio';
    return `${value.length} ${value.length === 1 ? 'item' : 'itens'}`;
  }
  if (typeof value === 'object') {
    const obj = value as Record<string, unknown>;
    if (typeof obj.nome === 'string' && obj.nome.trim()) return obj.nome;
    const vehicle = [obj.marca, obj.modelo, obj.ano].filter(Boolean).join(' ');
    if (vehicle) return vehicle;
    const parts = Object.entries(obj)
      .filter(([, next]) => next !== null && next !== undefined && next !== '')
      .map(([key, next]) => `${auditFieldLabel(key)}: ${String(next)}`);
    return parts.length ? parts.join(', ') : 'vazio';
  }
  return String(value);
}

export function auditChangedFields(log: AuditLogRecord): string[] {
  return (log.changes ?? []).map((change) => auditFieldLabel(change.field));
}

export function auditFriendlySummary(log: AuditLogRecord): string {
  const fields = auditChangedFields(log);
  if (fields.length) {
    const verb = log.action === 'create' ? 'Criou' : log.action === 'delete' ? 'Excluiu' : 'Alterou';
    return `${verb} ${fields.join(', ')}`;
  }
  if (log.objectName) {
    const fileName = log.objectName.split('/').pop() || log.objectName;
    if (log.action === 'upload') return `Enviou ${fileName}`;
    if (log.action === 'download') return `Baixou ${fileName}`;
    if (log.action === 'delete') return `Excluiu ${fileName}`;
    if (log.action === 'view') return `Abriu ${fileName}`;
    return fileName;
  }
  if (log.summary) {
    return log.summary
      .replace(/\bclient\b/gi, 'cadastro')
      .replace(/\bprocess\b/gi, 'processo')
      .replace(/\bupdate\b/gi, 'alteração')
      .replace(/\bcreate\b/gi, 'criação');
  }
  return auditActionLabel(log.action);
}

export function formatAuditWhen(iso: string): { relative: string; exact: string } {
  const date = new Date(iso);
  const exact = date.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
  const diff = Date.now() - date.getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return { relative: 'agora', exact };
  if (mins < 60) return { relative: `há ${mins} min`, exact };
  const hours = Math.floor(mins / 60);
  if (hours < 24) return { relative: `há ${hours}h`, exact };
  const days = Math.floor(hours / 24);
  if (days < 7) return { relative: `há ${days} dia${days > 1 ? 's' : ''}`, exact };
  return { relative: exact, exact };
}

export function sortAuditLogs(
  logs: AuditLogRecord[],
  key: AuditSortKey,
  dir: AuditSortDir
): AuditLogRecord[] {
  const factor = dir === 'asc' ? 1 : -1;
  return [...logs].sort((a, b) => {
    const av =
      key === 'createdAt'
        ? a.createdAt
        : key === 'who'
          ? (a.userEmail || a.userId || '').toLowerCase()
          : key === 'action'
            ? auditActionLabel(a.action).toLowerCase()
            : auditResourceLabel(a.resourceType).toLowerCase();
    const bv =
      key === 'createdAt'
        ? b.createdAt
        : key === 'who'
          ? (b.userEmail || b.userId || '').toLowerCase()
          : key === 'action'
            ? auditActionLabel(b.action).toLowerCase()
            : auditResourceLabel(b.resourceType).toLowerCase();
    if (av < bv) return -1 * factor;
    if (av > bv) return 1 * factor;
    return 0;
  });
}

const REVERTABLE_CLIENT_FIELDS = new Set(Object.keys(CLIENT_FIELD_LABELS));
const REVERTABLE_PROCESS_FIELDS = new Set([
  'modality',
  'status',
  'vehicle',
  'honorarios',
  'pagamentoTipo',
  'pagamentoStatus',
  'pagamentos',
  'currentStep',
  'steps',
  'step',
]);

export function canRevertAuditLog(log: AuditLogRecord): boolean {
  if (log.action !== 'update' || !log.resourceId || !log.changes?.length) return false;
  if (log.resourceType === 'client') {
    return log.changes.every((change) => REVERTABLE_CLIENT_FIELDS.has(change.field));
  }
  if (log.resourceType === 'process') {
    return log.changes.every((change) => REVERTABLE_PROCESS_FIELDS.has(change.field));
  }
  return false;
}

export function auditRecordLink(
  log: AuditLogRecord
): { to: string; label: string } | null {
  if (!log.resourceId) return null;
  if (log.resourceType === 'client') {
    return { to: `/portal/clientes/${log.resourceId}`, label: 'Abrir cadastro' };
  }
  if (log.resourceType === 'process') {
    return { to: `/portal/processos/${log.resourceId}`, label: 'Abrir processo' };
  }
  return null;
}
