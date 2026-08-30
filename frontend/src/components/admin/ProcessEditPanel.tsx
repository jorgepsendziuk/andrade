import { useEffect, useState } from 'react';
import { Car, Loader2, Pencil, Save } from 'lucide-react';
import { updateAdminProcess } from '../../lib/portal-api';
import {
  MODALITY_LABELS,
  STATUS_LABELS,
  type ProcessModality,
  type ProcessRecord,
  type ProcessStatus,
} from '../../types/process';

type VehicleInfo = NonNullable<ProcessRecord['vehicle']>;

interface ProcessEditPanelProps {
  process: ProcessRecord;
  onUpdated: () => void;
}

export function ProcessEditPanel({ process, onUpdated }: ProcessEditPanelProps) {
  const [open, setOpen] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [modality, setModality] = useState<ProcessModality>(process.modality);
  const [status, setStatus] = useState<ProcessStatus>(process.status);
  const [vehicle, setVehicle] = useState<VehicleInfo>(process.vehicle ?? {});

  useEffect(() => {
    setModality(process.modality);
    setStatus(process.status);
    setVehicle(process.vehicle ?? {});
  }, [process.id, process.modality, process.status, process.vehicle, process.updatedAt]);

  const handleSave = async () => {
    setSaving(true);
    setError('');
    try {
      const cleanVehicle = Object.fromEntries(
        Object.entries(vehicle).filter(([, v]) => v?.trim())
      ) as VehicleInfo;
      await updateAdminProcess(process.id, {
        modality,
        status,
        vehicle: Object.keys(cleanVehicle).length ? cleanVehicle : undefined,
      });
      setOpen(false);
      onUpdated();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao salvar');
    } finally {
      setSaving(false);
    }
  };

  const setV = (key: keyof VehicleInfo, value: string) => {
    setVehicle((prev) => ({ ...prev, [key]: value }));
  };

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-5">
      <div className="flex items-center justify-between gap-3 mb-3">
        <div className="flex items-center gap-2">
          <Pencil size={18} className="text-brand-600" />
          <h2 className="font-display font-bold text-brand-800">Características do processo</h2>
        </div>
        <button
          type="button"
          onClick={() => setOpen((v) => !v)}
          className="text-sm font-medium text-brand-600 hover:underline"
        >
          {open ? 'Fechar' : 'Editar'}
        </button>
      </div>

      {!open ? (
        <div className="space-y-3">
          <div className="flex flex-wrap gap-2 text-sm text-slate-600">
            <span className="px-2.5 py-1 rounded-full bg-brand-50 text-brand-700 font-medium">
              {MODALITY_LABELS[process.modality]}
            </span>
            <span className="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-medium">
              {STATUS_LABELS[process.status]}
            </span>
            {process.vehicle?.marca && (
              <span className="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-medium inline-flex items-center gap-1">
                <Car size={13} />
                {process.vehicle.marca} {process.vehicle.modelo} {process.vehicle.ano}
              </span>
            )}
          </div>
          {process.vehicle && (
            <div className="grid sm:grid-cols-2 gap-x-4 gap-y-1 text-xs text-slate-600">
              {process.vehicle.placa && (
                <p><span className="text-slate-400">Placa:</span> {process.vehicle.placa}</p>
              )}
              {process.vehicle.chassi && (
                <p><span className="text-slate-400">Chassi:</span> {process.vehicle.chassi}</p>
              )}
              {process.vehicle.renavam && (
                <p><span className="text-slate-400">RENAVAM:</span> {process.vehicle.renavam}</p>
              )}
              {process.vehicle.potencia && (
                <p><span className="text-slate-400">Potência:</span> {process.vehicle.potencia}</p>
              )}
              {process.vehicle.concessionaria && (
                <p className="sm:col-span-2">
                  <span className="text-slate-400">Concessionária:</span> {process.vehicle.concessionaria}
                  {process.vehicle.concessionariaCnpj ? ` · CNPJ ${process.vehicle.concessionariaCnpj}` : ''}
                </p>
              )}
            </div>
          )}
        </div>
      ) : (
        <div className="space-y-4">
          {error && (
            <div className="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm">
              {error}
            </div>
          )}

          <div className="grid sm:grid-cols-2 gap-3">
            <div>
              <label className="text-xs text-slate-500 block mb-1">Modalidade</label>
              <select
                value={modality}
                onChange={(e) => setModality(e.target.value as ProcessModality)}
                className="input-field text-sm"
              >
                <option value="pcd">PCD</option>
                <option value="taxi">Táxi</option>
              </select>
            </div>
            <div>
              <label className="text-xs text-slate-500 block mb-1">Status</label>
              <select
                value={status}
                onChange={(e) => setStatus(e.target.value as ProcessStatus)}
                className="input-field text-sm"
              >
                {(Object.keys(STATUS_LABELS) as ProcessStatus[]).map((s) => (
                  <option key={s} value={s}>
                    {STATUS_LABELS[s]}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div>
            <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2 flex items-center gap-1.5">
              <Car size={13} /> Veículo
            </p>
            <div className="grid sm:grid-cols-2 gap-3">
              {(
                [
                  ['marca', 'Marca'],
                  ['modelo', 'Modelo'],
                  ['ano', 'Ano'],
                  ['placa', 'Placa'],
                  ['chassi', 'Chassi'],
                  ['renavam', 'RENAVAM'],
                  ['potencia', 'Potência'],
                  ['concessionaria', 'Concessionária'],
                  ['concessionariaCnpj', 'CNPJ concessionária'],
                  ['concessionariaIe', 'IE concessionária'],
                ] as const
              ).map(([key, label]) => (
                <div key={key}>
                  <label className="text-xs text-slate-500 block mb-1">{label}</label>
                  <input
                    className="input-field text-sm"
                    value={vehicle[key] ?? ''}
                    onChange={(e) => setV(key, e.target.value)}
                  />
                </div>
              ))}
            </div>
          </div>

          <button
            type="button"
            disabled={saving}
            onClick={() => void handleSave()}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 disabled:opacity-50"
          >
            {saving ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />}
            Salvar alterações
          </button>
        </div>
      )}
    </div>
  );
}
