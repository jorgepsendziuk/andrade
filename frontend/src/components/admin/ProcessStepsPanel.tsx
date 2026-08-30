import { useState } from 'react';
import {
  Building2,
  Calendar,
  CheckCircle2,
  ChevronDown,
  ChevronRight,
  Circle,
  Clock,
  Info,
  ListChecks,
  Loader2,
  Lock,
  Save,
  SkipForward,
} from 'lucide-react';
import { updateProcessStep, advanceProcessStep } from '../../lib/portal-api';
import { STEP_META, STEP_STATUS_LABELS } from '../../lib/process-steps';
import { STEP_ICONS } from '../../lib/process-ui-icons';
import type { ProcessRecord, ProcessStep, ProcessStepKey, ProcessStepStatus } from '../../types/process';

const STATUS_STYLES: Record<ProcessStepStatus, string> = {
  pendente: 'bg-slate-100 text-slate-600 border-slate-200',
  em_andamento: 'bg-amber-50 text-amber-800 border-amber-200',
  concluida: 'bg-emerald-50 text-emerald-800 border-emerald-200',
  bloqueada: 'bg-slate-100 text-slate-400 border-slate-200',
};

const STATUS_DOT: Record<ProcessStepStatus, string> = {
  pendente: 'bg-slate-300',
  em_andamento: 'bg-amber-500',
  concluida: 'bg-emerald-500',
  bloqueada: 'bg-slate-300',
};

interface ProcessStepsPanelProps {
  process: ProcessRecord;
  onUpdated: () => void;
}

export function ProcessStepsPanel({ process, onUpdated }: ProcessStepsPanelProps) {
  const [expanded, setExpanded] = useState<ProcessStepKey | null>(process.currentStep);
  const [busy, setBusy] = useState<string | null>(null);
  const [error, setError] = useState('');
  const [drafts, setDrafts] = useState<Record<string, Partial<ProcessStep>>>({});

  const getDraft = (step: ProcessStep) => ({
    status: drafts[step.key]?.status ?? step.status,
    protocol: drafts[step.key]?.protocol ?? step.protocol ?? '',
    internalNote: drafts[step.key]?.internalNote ?? step.internalNote ?? '',
  });

  const setDraft = (key: ProcessStepKey, patch: Partial<ProcessStep>) => {
    setDrafts((prev) => ({ ...prev, [key]: { ...prev[key], ...patch } }));
  };

  const quickSetStatus = async (stepKey: ProcessStepKey, status: ProcessStepStatus) => {
    const step = process.steps.find((s) => s.key === stepKey);
    if (!step) return;
    const draft = getDraft(step);
    setBusy(stepKey);
    setError('');
    try {
      await updateProcessStep(process.id, stepKey, {
        status,
        protocol: draft.protocol || undefined,
        internalNote: draft.internalNote || undefined,
      });
      onUpdated();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao atualizar etapa');
    } finally {
      setBusy(null);
    }
  };

  const saveStep = async (stepKey: ProcessStepKey) => {
    const step = process.steps.find((s) => s.key === stepKey);
    if (!step) return;
    const draft = getDraft(step);
    setBusy(stepKey);
    setError('');
    try {
      await updateProcessStep(process.id, stepKey, {
        status: draft.status,
        protocol: draft.protocol || undefined,
        internalNote: draft.internalNote || undefined,
      });
      setDrafts((prev) => {
        const next = { ...prev };
        delete next[stepKey];
        return next;
      });
      onUpdated();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao salvar etapa');
    } finally {
      setBusy(null);
    }
  };

  const handleAdvance = async () => {
    setBusy('advance');
    setError('');
    try {
      await advanceProcessStep(process.id);
      onUpdated();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao avançar');
    } finally {
      setBusy(null);
    }
  };

  const visibleSteps = process.steps.filter((s) => s.key !== 'cancelado' || s.status !== 'pendente');

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-5">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div className="flex items-center gap-2">
          <ListChecks size={20} className="text-brand-600" />
          <div>
            <h2 className="font-display font-bold text-brand-800">Etapas do processo</h2>
            <p className="text-xs text-slate-500">Clique na etapa para editar protocolo e observações</p>
          </div>
        </div>
        <button
          type="button"
          onClick={() => void handleAdvance()}
          disabled={busy !== null || process.status !== 'ativo'}
          className="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 disabled:opacity-50"
        >
          {busy === 'advance' ? <Loader2 size={16} className="animate-spin" /> : <SkipForward size={16} />}
          Avançar etapa
        </button>
      </div>

      {error && (
        <div className="mb-4 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm">{error}</div>
      )}

      <div className="space-y-2">
        {visibleSteps.map((step, index) => {
          const meta = STEP_META[step.key];
          const StepIcon = STEP_ICONS[step.key];
          const isOpen = expanded === step.key;
          const draft = getDraft(step);
          const isCurrent = process.currentStep === step.key;
          const hasChanges =
            draft.status !== step.status ||
            draft.protocol !== (step.protocol ?? '') ||
            draft.internalNote !== (step.internalNote ?? '');

          return (
            <div
              key={step.key}
              className={`rounded-xl border transition-all ${
                isCurrent ? 'border-brand-300 ring-1 ring-brand-200' : 'border-slate-200'
              }`}
            >
              <button
                type="button"
                onClick={() => setExpanded(isOpen ? null : step.key)}
                className="w-full flex items-center gap-3 p-3 text-left hover:bg-slate-50/80 rounded-xl"
              >
                <div className="flex flex-col items-center gap-1 shrink-0">
                  <span className={`w-2.5 h-2.5 rounded-full ${STATUS_DOT[step.status]}`} />
                  {index < visibleSteps.length - 1 && <span className="w-px h-4 bg-slate-200" />}
                </div>
                <StepIcon size={18} className={`shrink-0 ${isCurrent ? 'text-brand-600' : 'text-slate-400'}`} />
                <div className="flex-1 min-w-0">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="font-medium text-sm text-slate-800">{step.label}</span>
                    {isCurrent && (
                      <span className="text-[10px] font-bold uppercase tracking-wide text-brand-600 bg-brand-50 px-1.5 py-0.5 rounded">
                        Atual
                      </span>
                    )}
                  </div>
                  {step.protocol && (
                    <p className="text-xs text-slate-500 truncate mt-0.5">Protocolo: {step.protocol}</p>
                  )}
                </div>
                <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full border ${STATUS_STYLES[step.status]}`}>
                  {STEP_STATUS_LABELS[step.status]}
                </span>
                {isOpen ? <ChevronDown size={16} className="text-slate-400" /> : <ChevronRight size={16} className="text-slate-400" />}
              </button>

              {isOpen && (
                <div className="px-3 pb-3 pt-0 border-t border-slate-100 mx-3 mb-3 space-y-3">
                  <div className="flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-lg p-3 text-xs text-blue-900">
                    <Info size={14} className="shrink-0 mt-0.5" />
                    <p>{meta.hint}</p>
                  </div>

                  <div className="grid sm:grid-cols-2 gap-3 text-xs">
                    {meta.agency && (
                      <div className="flex items-center gap-1.5 text-slate-600">
                        <Building2 size={13} />
                        <span>{meta.agency}</span>
                      </div>
                    )}
                    {meta.sla && (
                      <div className="flex items-center gap-1.5 text-slate-600">
                        <Clock size={13} />
                        <span>Prazo típico: {meta.sla}</span>
                      </div>
                    )}
                    {step.startedAt && (
                      <div className="flex items-center gap-1.5 text-slate-600">
                        <Calendar size={13} />
                        <span>Início: {new Date(step.startedAt).toLocaleDateString('pt-BR')}</span>
                      </div>
                    )}
                    {step.completedAt && (
                      <div className="flex items-center gap-1.5 text-slate-600">
                        <CheckCircle2 size={13} />
                        <span>Concluída: {new Date(step.completedAt).toLocaleDateString('pt-BR')}</span>
                      </div>
                    )}
                  </div>

                  {meta.checklist.length > 0 && (
                    <ul className="text-xs text-slate-600 space-y-1">
                      {meta.checklist.map((item) => (
                        <li key={item} className="flex items-start gap-2">
                          <Circle size={8} className="shrink-0 mt-1 text-slate-300" fill="currentColor" />
                          {item}
                        </li>
                      ))}
                    </ul>
                  )}

                  <div className="grid sm:grid-cols-2 gap-3">
                    <div>
                      <label className="block text-xs font-medium text-slate-600 mb-1">Status</label>
                      <select
                        value={draft.status}
                        onChange={(e) => setDraft(step.key, { status: e.target.value as ProcessStepStatus })}
                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-sm"
                      >
                        {(Object.keys(STEP_STATUS_LABELS) as ProcessStepStatus[]).map((s) => (
                          <option key={s} value={s}>
                            {STEP_STATUS_LABELS[s]}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block text-xs font-medium text-slate-600 mb-1">Protocolo</label>
                      <input
                        value={draft.protocol}
                        onChange={(e) => setDraft(step.key, { protocol: e.target.value })}
                        placeholder="Ex: SEFAZ-MT-2026-12345"
                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-sm"
                      />
                    </div>
                    <div className="sm:col-span-2">
                      <label className="block text-xs font-medium text-slate-600 mb-1">Observação interna</label>
                      <textarea
                        value={draft.internalNote}
                        onChange={(e) => setDraft(step.key, { internalNote: e.target.value })}
                        rows={2}
                        placeholder="Notas visíveis apenas para a equipe…"
                        className="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-sm resize-y"
                      />
                    </div>
                  </div>

                  <div className="flex flex-wrap gap-2">
                    <button
                      type="button"
                      disabled={busy === step.key || !hasChanges}
                      onClick={() => void saveStep(step.key)}
                      className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-500 text-white text-xs font-semibold hover:bg-brand-600 disabled:opacity-40"
                    >
                      {busy === step.key ? <Loader2 size={13} className="animate-spin" /> : <Save size={13} />}
                      Salvar etapa
                    </button>
                    {step.status === 'pendente' && (
                      <button
                        type="button"
                        disabled={busy === step.key}
                        onClick={() => void quickSetStatus(step.key, 'em_andamento')}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium hover:bg-slate-50"
                      >
                        <Clock size={13} /> Iniciar
                      </button>
                    )}
                    {step.status !== 'concluida' && step.key !== 'cancelado' && (
                      <button
                        type="button"
                        disabled={busy === step.key}
                        onClick={() => void quickSetStatus(step.key, 'concluida')}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-emerald-200 text-emerald-700 text-xs font-medium hover:bg-emerald-50"
                      >
                        <CheckCircle2 size={13} /> Marcar concluída
                      </button>
                    )}
                    {step.status !== 'bloqueada' && step.status !== 'concluida' && (
                      <button
                        type="button"
                        disabled={busy === step.key}
                        onClick={() => void quickSetStatus(step.key, 'bloqueada')}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-medium text-slate-500 hover:bg-slate-50"
                      >
                        <Lock size={13} /> Bloquear
                      </button>
                    )}
                  </div>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}
