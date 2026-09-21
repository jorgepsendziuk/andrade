import type { ProcessListItem, ProcessModality, ProcessStatus, ProcessStepKey } from '../types/process';

export type ProcessSortKey =
  | 'clientName'
  | 'clientCpf'
  | 'currentStep'
  | 'status'
  | 'modality'
  | 'progressPercent'
  | 'createdAt'
  | 'updatedAt';

export type ProcessSortDir = 'asc' | 'desc';

export interface ProcessGridFilters {
  search: string;
  status: ProcessStatus | 'all';
  step: ProcessStepKey | 'all';
  modality: ProcessModality | 'all';
}

export function formatCpf(cpf: string): string {
  const d = (cpf ?? '').replace(/\D/g, '');
  if (d.length !== 11) return cpf;
  return d.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

export function formatCep(cep: string): string {
  const d = (cep ?? '').replace(/\D/g, '');
  if (d.length !== 8) return cep;
  return d.replace(/(\d{5})(\d{3})/, '$1-$2');
}

export function normalizeDigits(value: string): string {
  return value.replace(/\D/g, '');
}

export function isValidCpf(value: string): boolean {
  return normalizeDigits(value).length === 11;
}

export function formatDate(iso: string): string {
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function formatDateShort(iso: string): string {
  return new Date(iso).toLocaleDateString('pt-BR');
}

export function filterProcesses(items: ProcessListItem[], filters: ProcessGridFilters): ProcessListItem[] {
  const q = (filters.search ?? '').trim().toLowerCase();
  const qDigits = q.replace(/\D/g, '');
  return items.filter((p) => {
    if (filters.status !== 'all' && p.status !== filters.status) return false;
    if (filters.step !== 'all' && p.currentStep !== filters.step) return false;
    if (filters.modality !== 'all' && p.modality !== filters.modality) return false;
    if (!q) return true;

    const name = (p.clientName ?? '').toLowerCase();
    const email = (p.clientEmail ?? '').toLowerCase();
    const phoneDigits = (p.clientPhone ?? '').replace(/\D/g, '');

    return (
      name.includes(q) ||
      email.includes(q) ||
      p.id.toLowerCase().includes(q) ||
      (qDigits.length > 0 && p.clientCpf.includes(qDigits)) ||
      (qDigits.length > 0 && phoneDigits.includes(qDigits)) ||
      (qDigits.length === 0 && (p.clientPhone ?? '').toLowerCase().includes(q))
    );
  });
}

export function sortProcesses(
  items: ProcessListItem[],
  key: ProcessSortKey,
  dir: ProcessSortDir
): ProcessListItem[] {
  const copy = [...items];
  const mul = dir === 'asc' ? 1 : -1;
  copy.sort((a, b) => {
    let cmp = 0;
    switch (key) {
      case 'clientName':
        cmp = a.clientName.localeCompare(b.clientName, 'pt-BR');
        break;
      case 'clientCpf':
        cmp = a.clientCpf.localeCompare(b.clientCpf);
        break;
      case 'currentStep':
        cmp = a.currentStep.localeCompare(b.currentStep);
        break;
      case 'status':
        cmp = a.status.localeCompare(b.status);
        break;
      case 'modality':
        cmp = a.modality.localeCompare(b.modality);
        break;
      case 'progressPercent':
        cmp = (a.progressPercent ?? 0) - (b.progressPercent ?? 0);
        break;
      case 'updatedAt':
        cmp = a.updatedAt.localeCompare(b.updatedAt);
        break;
      case 'createdAt':
      default:
        cmp = a.createdAt.localeCompare(b.createdAt);
    }
    return cmp * mul;
  });
  return copy;
}

export function exportProcessesCsv(items: ProcessListItem[]) {
  const header = 'ID,Cliente,CPF,E-mail,Telefone,Modalidade,Etapa,Status,Progresso,Criado,Atualizado\n';
  const rows = items
    .map((p) =>
      [
        p.id,
        `"${p.clientName.replace(/"/g, '""')}"`,
        formatCpf(p.clientCpf),
        p.clientEmail,
        p.clientPhone ?? '',
        p.modality.toUpperCase(),
        p.currentStep,
        p.status,
        `${p.progressPercent ?? 0}%`,
        formatDate(p.createdAt),
        formatDate(p.updatedAt),
      ].join(',')
    )
    .join('\n');
  const blob = new Blob([header + rows], { type: 'text/csv;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `processos-andrade-${new Date().toISOString().slice(0, 10)}.csv`;
  a.click();
  URL.revokeObjectURL(url);
}
