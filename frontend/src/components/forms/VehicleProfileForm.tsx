import { useEffect, useState } from 'react';
import { Car, Loader2, Save } from 'lucide-react';
import type { ProcessRecord } from '../../types/process';

type VehicleInfo = NonNullable<ProcessRecord['vehicle']>;

const FIELDS = [
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
] as const;

export function vehicleToPatch(vehicle: VehicleInfo): VehicleInfo | undefined {
  const clean = Object.fromEntries(
    Object.entries(vehicle).filter(([, v]) => String(v ?? '').trim())
  ) as VehicleInfo;
  return Object.keys(clean).length ? clean : undefined;
}

interface VehicleProfileFormProps {
  vehicle?: ProcessRecord['vehicle'];
  saving?: boolean;
  onSave: (vehicle: VehicleInfo | undefined) => Promise<void>;
}

export function VehicleProfileForm({ vehicle, saving = false, onSave }: VehicleProfileFormProps) {
  const [form, setForm] = useState<VehicleInfo>(vehicle ?? {});

  useEffect(() => {
    setForm(vehicle ?? {});
  }, [vehicle]);

  return (
    <form
      className="space-y-4"
      onSubmit={(e) => {
        e.preventDefault();
        void onSave(vehicleToPatch(form));
      }}
    >
      <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide flex items-center gap-1.5">
        <Car size={13} /> Veículo do processo
      </p>
      <div className="grid sm:grid-cols-2 gap-3">
        {FIELDS.map(([key, label]) => (
          <label key={key} className="text-xs text-slate-500">
            {label}
            <input
              className="input-field mt-1"
              value={form[key] ?? ''}
              onChange={(e) => setForm((f) => ({ ...f, [key]: e.target.value }))}
            />
          </label>
        ))}
      </div>
      <button type="submit" disabled={saving} className="btn-primary-sm">
        {saving ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
        Salvar veículo
      </button>
    </form>
  );
}
