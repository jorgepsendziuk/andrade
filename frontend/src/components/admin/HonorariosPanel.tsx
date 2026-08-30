import { useState } from 'react';
import { Loader2, Plus, Printer, Trash2, Wallet } from 'lucide-react';
import { updateAdminProcess } from '../../lib/portal-api';
import { getToken } from '../../lib/api';
import type { PagamentoHonorario, PagamentoTipo, ProcessRecord } from '../../types/process';

interface HonorariosPanelProps {
  process: ProcessRecord;
  processId: string;
  onUpdated: () => void;
}

const TIPOS: PagamentoTipo[] = ['PIX', 'boleto', 'dinheiro', 'transferencia'];

function fmtMoney(value: number): string {
  return value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function sumPagos(pagamentos: PagamentoHonorario[]): number {
  return pagamentos.filter((p) => p.status === 'pago').reduce((s, p) => s + p.valor, 0);
}

function nextReciboNumero(pagamentos: PagamentoHonorario[]): string {
  const year = new Date().getFullYear();
  const nums = pagamentos
    .map((p) => p.numero.match(/REC-\d{4}-(\d+)/))
    .filter(Boolean)
    .map((m) => Number(m![1]));
  const next = (nums.length ? Math.max(...nums) : 0) + 1;
  return `REC-${year}-${String(next).padStart(4, '0')}`;
}

export function HonorariosPanel({ process, processId, onUpdated }: HonorariosPanelProps) {
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const pagamentos = process.pagamentos ?? [];
  const total = process.honorarios ?? 0;
  const pago = sumPagos(pagamentos);
  const pct = total > 0 ? Math.min(100, Math.round((pago / total) * 100)) : 0;

  const save = async (patch: Partial<ProcessRecord>) => {
    setSaving(true);
    setError('');
    try {
      await updateAdminProcess(processId, patch);
      onUpdated();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao salvar');
    } finally {
      setSaving(false);
    }
  };

  const savePagamentos = (next: PagamentoHonorario[]) => save({ pagamentos: next });

  const handlePrintRecibo = async (pagamentoId: string) => {
    const token = getToken();
    const res = await fetch(
      `/api/admin/processes/${processId}/print/recibo?pagamentoId=${encodeURIComponent(pagamentoId)}`,
      { headers: { Authorization: `Bearer ${token}` } }
    );
    if (!res.ok) {
      setError('Falha ao gerar recibo');
      return;
    }
    const html = await res.text();
    const w = window.open('', '_blank');
    if (w) {
      w.document.write(html);
      w.document.close();
    }
  };

  const addPagamento = () => {
    const next: PagamentoHonorario = {
      id: crypto.randomUUID(),
      numero: nextReciboNumero(pagamentos),
      valor: 0,
      tipo: 'PIX',
      status: 'pendente',
      data: new Date().toISOString().slice(0, 10),
      descricao: '',
    };
    void savePagamentos([...pagamentos, next]);
  };

  const updatePagamento = (id: string, patch: Partial<PagamentoHonorario>) => {
    void savePagamentos(pagamentos.map((p) => (p.id === id ? { ...p, ...patch } : p)));
  };

  const removePagamento = (id: string) => {
    void savePagamentos(pagamentos.filter((p) => p.id !== id));
  };

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-5">
      <h3 className="font-display font-bold text-brand-800 mb-3 flex items-center gap-2">
        <Wallet size={18} className="text-brand-600" />
        Honorários
        {saving && <Loader2 size={14} className="animate-spin text-brand-500 ml-1" />}
      </h3>

      {error && (
        <div className="mb-3 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-xs">
          {error}
        </div>
      )}

      <label className="text-xs text-slate-500 block mb-1">Valor total contratado (R$)</label>
      <input
        type="number"
        className="input-field text-sm mb-3"
        defaultValue={total || ''}
        onBlur={(e) => save({ honorarios: Number(e.target.value) || undefined })}
      />

      {total > 0 && (
        <div className="mb-4">
          <div className="flex justify-between text-xs text-slate-600 mb-1">
            <span>R$ {fmtMoney(pago)} pago</span>
            <span>de R$ {fmtMoney(total)}</span>
          </div>
          <div className="h-2 rounded-full bg-slate-100 overflow-hidden">
            <div
              className="h-full bg-emerald-500 transition-all"
              style={{ width: `${pct}%` }}
            />
          </div>
          {process.pagamentoStatus && (
            <p className="text-xs text-slate-500 mt-1">{process.pagamentoStatus}</p>
          )}
        </div>
      )}

      <div className="flex items-center justify-between mb-2">
        <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide">Pagamentos / Recibos</p>
        <button
          type="button"
          onClick={addPagamento}
          className="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-700"
        >
          <Plus size={14} /> Adicionar
        </button>
      </div>

      {pagamentos.length === 0 ? (
        <p className="text-xs text-slate-400 italic">Nenhum pagamento cadastrado.</p>
      ) : (
        <div className="space-y-3">
          {pagamentos.map((p) => (
            <div key={p.id} className="border border-slate-100 rounded-lg p-3 bg-slate-50/50 space-y-2">
              <div className="flex items-center justify-between gap-2">
                <span className="text-xs font-bold text-brand-700">{p.numero}</span>
                <div className="flex items-center gap-1">
                  {p.status === 'pago' && (
                    <button
                      type="button"
                      title="Imprimir recibo"
                      onClick={() => void handlePrintRecibo(p.id)}
                      className="p-1.5 rounded-md text-brand-600 hover:bg-brand-50 border border-brand-100"
                    >
                      <Printer size={14} />
                    </button>
                  )}
                  <button
                    type="button"
                    title="Remover"
                    onClick={() => removePagamento(p.id)}
                    className="p-1.5 rounded-md text-red-500 hover:bg-red-50"
                  >
                    <Trash2 size={14} />
                  </button>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-2">
                <div>
                  <label className="text-[10px] text-slate-400 block">Valor (R$)</label>
                  <input
                    type="number"
                    className="input-field text-xs py-1.5"
                    value={p.valor || ''}
                    onChange={(e) => updatePagamento(p.id, { valor: Number(e.target.value) || 0 })}
                  />
                </div>
                <div>
                  <label className="text-[10px] text-slate-400 block">Data</label>
                  <input
                    type="date"
                    className="input-field text-xs py-1.5"
                    value={p.data.slice(0, 10)}
                    onChange={(e) => updatePagamento(p.id, { data: e.target.value })}
                  />
                </div>
                <div>
                  <label className="text-[10px] text-slate-400 block">Forma</label>
                  <select
                    className="input-field text-xs py-1.5"
                    value={p.tipo}
                    onChange={(e) => updatePagamento(p.id, { tipo: e.target.value as PagamentoTipo })}
                  >
                    {TIPOS.map((t) => (
                      <option key={t} value={t}>
                        {t.toUpperCase()}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="text-[10px] text-slate-400 block">Status</label>
                  <select
                    className="input-field text-xs py-1.5"
                    value={p.status}
                    onChange={(e) =>
                      updatePagamento(p.id, { status: e.target.value as PagamentoHonorario['status'] })
                    }
                  >
                    <option value="pendente">Pendente</option>
                    <option value="pago">Pago</option>
                  </select>
                </div>
              </div>
              <div>
                <label className="text-[10px] text-slate-400 block">Descrição</label>
                <input
                  className="input-field text-xs py-1.5"
                  value={p.descricao ?? ''}
                  placeholder="Entrada, perícia, saldo…"
                  onChange={(e) => updatePagamento(p.id, { descricao: e.target.value })}
                />
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
