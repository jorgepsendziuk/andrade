import type { PagamentoHonorario, ProcessRecord } from './types/process.js';

export function fmtMoney(value: number): string {
  return value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function sumPagamentosPagos(pagamentos: PagamentoHonorario[] = []): number {
  return pagamentos.filter((p) => p.status === 'pago').reduce((sum, p) => sum + p.valor, 0);
}

export function derivePagamentoMeta(process: Pick<ProcessRecord, 'honorarios' | 'pagamentos'>): {
  pagamentoTipo?: string;
  pagamentoStatus?: string;
} {
  const pagamentos = process.pagamentos ?? [];
  const honorarios = process.honorarios ?? 0;
  const totalPago = sumPagamentosPagos(pagamentos);

  const paidSorted = [...pagamentos]
    .filter((p) => p.status === 'pago')
    .sort((a, b) => b.data.localeCompare(a.data));
  const lastPaid = paidSorted[0];

  let pagamentoStatus: string | undefined;
  if (pagamentos.length === 0) {
    pagamentoStatus = honorarios > 0 ? 'Pendente' : undefined;
  } else if (honorarios > 0 && totalPago >= honorarios) {
    pagamentoStatus = 'Pago';
  } else if (totalPago > 0) {
    pagamentoStatus = `Parcial — R$ ${fmtMoney(totalPago)} de R$ ${fmtMoney(honorarios)}`;
  } else {
    pagamentoStatus = 'Pendente';
  }

  return {
    pagamentoTipo: lastPaid?.tipo,
    pagamentoStatus,
  };
}

export function resolveReciboPagamento(
  process: ProcessRecord,
  pagamentoId?: string
): PagamentoHonorario | null {
  const pagamentos = process.pagamentos ?? [];
  if (pagamentos.length === 0) return null;

  if (pagamentoId) {
    const found = pagamentos.find((p) => p.id === pagamentoId);
    return found ?? null;
  }

  const paid = pagamentos.filter((p) => p.status === 'pago');
  if (paid.length === 1) return paid[0];
  if (paid.length > 1) {
    return [...paid].sort((a, b) => b.data.localeCompare(a.data))[0];
  }

  return pagamentos.length === 1 ? pagamentos[0] : null;
}

export function defaultReciboPagamentoId(process: ProcessRecord): string | undefined {
  return resolveReciboPagamento(process)?.id;
}
