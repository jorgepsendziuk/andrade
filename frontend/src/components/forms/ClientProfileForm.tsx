import { useEffect, useState, type ReactNode } from 'react';
import { Loader2, Save } from 'lucide-react';
import { toStoredDate } from '../../lib/date-br';
import { isValidCpf } from '../../lib/process-grid-utils';
import { DateField } from './DateField';
import type { ClientGenero, ClientPublic, LegalRepresentative } from '../../types/process';

export interface ClientProfileValues {
  name: string;
  email: string;
  cpf: string;
  rg: string;
  rgEstado: string;
  rgOrgaoEmissor: string;
  rgDataEmissao: string;
  phone: string;
  cep: string;
  endereco: string;
  numero: string;
  complemento: string;
  bairro: string;
  cidade: string;
  uf: string;
  genero: ClientGenero | '';
  active: boolean;
  hasRepresentante: boolean;
  representante: LegalRepresentative;
}

export function clientToForm(client: ClientPublic): ClientProfileValues {
  return {
    name: client.name ?? '',
    email: client.email ?? '',
    cpf: client.cpf ?? '',
    rg: client.rg ?? '',
    rgEstado: client.rgEstado ?? '',
    rgOrgaoEmissor: client.rgOrgaoEmissor ?? '',
    rgDataEmissao: client.rgDataEmissao ?? '',
    phone: client.phone ?? '',
    cep: client.cep ?? '',
    endereco: client.endereco ?? '',
    numero: client.numero ?? '',
    complemento: client.complemento ?? '',
    bairro: client.bairro ?? '',
    cidade: client.cidade ?? '',
    uf: client.uf ?? '',
    genero: client.genero ?? '',
    active: client.active !== false,
    hasRepresentante: Boolean(client.representante?.nome || client.representante?.cpf),
    representante: {
      nome: client.representante?.nome ?? '',
      cpf: client.representante?.cpf ?? '',
      rg: client.representante?.rg ?? '',
      rgOrgaoEmissor: client.representante?.rgOrgaoEmissor ?? '',
      rgEstado: client.representante?.rgEstado ?? '',
      telefone: client.representante?.telefone ?? '',
    },
  };
}

export function formToClientPatch(
  form: ClientProfileValues,
  options: { includeIdentity: boolean; includeActive?: boolean }
) {
  const patch: Record<string, unknown> = {
    name: form.name.trim(),
    rg: form.rg.trim(),
    rgEstado: form.rgEstado.trim(),
    rgOrgaoEmissor: form.rgOrgaoEmissor.trim(),
    rgDataEmissao: toStoredDate(form.rgDataEmissao),
    phone: form.phone.trim(),
    cep: form.cep,
    endereco: form.endereco.trim(),
    numero: form.numero.trim(),
    complemento: form.complemento.trim(),
    bairro: form.bairro.trim(),
    cidade: form.cidade.trim(),
    uf: form.uf.trim(),
    genero: form.genero || undefined,
    representante: form.hasRepresentante
      ? {
          nome: form.representante.nome.trim(),
          cpf: form.representante.cpf,
          rg: form.representante.rg.trim(),
          rgOrgaoEmissor: form.representante.rgOrgaoEmissor?.trim(),
          rgEstado: form.representante.rgEstado?.trim(),
          telefone: form.representante.telefone?.trim(),
        }
      : null,
  };
  if (options.includeIdentity) {
    patch.email = form.email.trim();
    patch.cpf = form.cpf;
  }
  if (options.includeActive) {
    patch.active = form.active;
  }
  return patch;
}

function Field({
  field,
  highlightFields,
  className = '',
  as: Tag = 'label',
  children,
}: {
  field: string;
  highlightFields?: string[];
  className?: string;
  as?: 'label' | 'div';
  children: ReactNode;
}) {
  const on = highlightFields?.includes(field);
  return (
    <Tag
      className={`text-xs ${
        on ? 'text-amber-900 rounded-lg bg-amber-50 ring-2 ring-amber-400 p-2' : 'text-slate-500'
      } ${className}`}
    >
      {on && (
        <span className="block text-[10px] font-extrabold uppercase tracking-wide text-amber-700 mb-0.5">
          Alterado pelo cliente
        </span>
      )}
      {children}
    </Tag>
  );
}

interface ClientProfileFormProps {
  client: ClientPublic;
  includeIdentity?: boolean;
  includeActive?: boolean;
  highlightFields?: string[];
  saving?: boolean;
  onSave: (patch: Record<string, unknown>) => Promise<void>;
}

export function ClientProfileForm({
  client,
  includeIdentity = true,
  includeActive = false,
  highlightFields,
  saving = false,
  onSave,
}: ClientProfileFormProps) {
  const [form, setForm] = useState(() => clientToForm(client));
  const [error, setError] = useState('');

  useEffect(() => {
    setForm(clientToForm(client));
  }, [client.id, client.updatedAt]);

  const set = (key: keyof ClientProfileValues, value: string | boolean) => {
    setForm((f) => ({ ...f, [key]: value }));
  };

  const setRep = (key: keyof LegalRepresentative, value: string) => {
    setForm((f) => ({ ...f, representante: { ...f.representante, [key]: value } }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (includeIdentity && !isValidCpf(form.cpf)) {
      setError('CPF inválido. Informe os 11 dígitos.');
      return;
    }
    if (form.hasRepresentante) {
      if (!form.representante.nome.trim()) {
        setError('Informe o nome do representante legal.');
        return;
      }
      if (form.representante.cpf && !isValidCpf(form.representante.cpf)) {
        setError('CPF do representante inválido.');
        return;
      }
    }
    await onSave(formToClientPatch(form, { includeIdentity, includeActive }));
  };

  return (
    <form onSubmit={(e) => void handleSubmit(e)} className="space-y-5">
      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm">{error}</div>
      )}

      <div className="grid sm:grid-cols-2 gap-3">
        <Field field="name" highlightFields={highlightFields} className="sm:col-span-2">
          Nome
          <input className="input-field mt-1" value={form.name} onChange={(e) => set('name', e.target.value)} required />
        </Field>
        {includeIdentity ? (
          <>
            <Field field="email" highlightFields={highlightFields}>
              E-mail
              <input type="email" className="input-field mt-1" value={form.email} onChange={(e) => set('email', e.target.value)} required />
            </Field>
            <Field field="cpf" highlightFields={highlightFields}>
              CPF
              <input className="input-field mt-1" value={form.cpf} onChange={(e) => set('cpf', e.target.value)} required />
            </Field>
          </>
        ) : (
          <>
            <Field field="email" highlightFields={highlightFields}>
              E-mail
              <input type="email" className="input-field mt-1 bg-slate-50" value={form.email} disabled />
            </Field>
            <Field field="cpf" highlightFields={highlightFields}>
              CPF
              <input className="input-field mt-1 bg-slate-50" value={form.cpf} disabled />
            </Field>
          </>
        )}
        <Field field="phone" highlightFields={highlightFields}>
          Telefone
          <input className="input-field mt-1" value={form.phone} onChange={(e) => set('phone', e.target.value)} />
        </Field>
        <Field field="genero" highlightFields={highlightFields}>
          Gênero
          <select className="input-field mt-1" value={form.genero} onChange={(e) => set('genero', e.target.value)}>
            <option value="">Não informado</option>
            <option value="F">Feminino</option>
            <option value="M">Masculino</option>
          </select>
        </Field>
        <Field field="rg" highlightFields={highlightFields}>
          RG
          <input className="input-field mt-1" value={form.rg} onChange={(e) => set('rg', e.target.value)} />
        </Field>
        <Field field="rgOrgaoEmissor" highlightFields={highlightFields}>
          Órgão emissor
          <input className="input-field mt-1" value={form.rgOrgaoEmissor} onChange={(e) => set('rgOrgaoEmissor', e.target.value)} />
        </Field>
        <Field field="rgEstado" highlightFields={highlightFields}>
          UF do RG
          <input className="input-field mt-1" value={form.rgEstado} onChange={(e) => set('rgEstado', e.target.value)} maxLength={2} />
        </Field>
        <Field field="rgDataEmissao" highlightFields={highlightFields} as="div">
          Data de emissão
          <DateField value={form.rgDataEmissao} onChange={(value) => set('rgDataEmissao', value)} />
        </Field>
        <Field field="cep" highlightFields={highlightFields}>
          CEP
          <input className="input-field mt-1" value={form.cep} onChange={(e) => set('cep', e.target.value)} />
        </Field>
        <Field field="endereco" highlightFields={highlightFields} className="sm:col-span-2">
          Endereço
          <input className="input-field mt-1" value={form.endereco} onChange={(e) => set('endereco', e.target.value)} />
        </Field>
        <Field field="numero" highlightFields={highlightFields}>
          Número
          <input className="input-field mt-1" value={form.numero} onChange={(e) => set('numero', e.target.value)} />
        </Field>
        <Field field="complemento" highlightFields={highlightFields}>
          Complemento
          <input className="input-field mt-1" value={form.complemento} onChange={(e) => set('complemento', e.target.value)} />
        </Field>
        <Field field="bairro" highlightFields={highlightFields}>
          Bairro
          <input className="input-field mt-1" value={form.bairro} onChange={(e) => set('bairro', e.target.value)} />
        </Field>
        <Field field="cidade" highlightFields={highlightFields}>
          Cidade
          <input className="input-field mt-1" value={form.cidade} onChange={(e) => set('cidade', e.target.value)} />
        </Field>
        <Field field="uf" highlightFields={highlightFields}>
          UF
          <input className="input-field mt-1" value={form.uf} onChange={(e) => set('uf', e.target.value)} maxLength={2} />
        </Field>
      </div>

      <div className={`border-t border-slate-100 pt-4 ${highlightFields?.includes('representante') ? 'rounded-xl bg-amber-50 ring-2 ring-amber-400 p-3' : ''}`}>
        {highlightFields?.includes('representante') && (
          <p className="text-[10px] font-extrabold uppercase tracking-wide text-amber-700 mb-2">
            Alterado pelo cliente
          </p>
        )}
        <label className="flex items-center gap-2 text-sm font-medium text-brand-800 mb-3">
          <input
            type="checkbox"
            checked={form.hasRepresentante}
            onChange={(e) => set('hasRepresentante', e.target.checked)}
          />
          Possui representante legal / tutor / curador
        </label>
        {form.hasRepresentante && (
          <div className="grid sm:grid-cols-2 gap-3">
            <label className="text-xs text-slate-500 sm:col-span-2">
              Nome do representante
              <input className="input-field mt-1" value={form.representante.nome} onChange={(e) => setRep('nome', e.target.value)} />
            </label>
            <label className="text-xs text-slate-500">
              CPF
              <input className="input-field mt-1" value={form.representante.cpf} onChange={(e) => setRep('cpf', e.target.value)} />
            </label>
            <label className="text-xs text-slate-500">
              Telefone
              <input className="input-field mt-1" value={form.representante.telefone ?? ''} onChange={(e) => setRep('telefone', e.target.value)} />
            </label>
            <label className="text-xs text-slate-500">
              RG
              <input className="input-field mt-1" value={form.representante.rg} onChange={(e) => setRep('rg', e.target.value)} />
            </label>
            <label className="text-xs text-slate-500">
              Órgão emissor
              <input className="input-field mt-1" value={form.representante.rgOrgaoEmissor ?? ''} onChange={(e) => setRep('rgOrgaoEmissor', e.target.value)} />
            </label>
            <label className="text-xs text-slate-500">
              UF do RG
              <input className="input-field mt-1" value={form.representante.rgEstado ?? ''} onChange={(e) => setRep('rgEstado', e.target.value)} maxLength={2} />
            </label>
          </div>
        )}
      </div>

      {includeActive && (
        <label className="flex items-center gap-2 text-sm text-slate-700">
          <input type="checkbox" checked={form.active} onChange={(e) => set('active', e.target.checked)} />
          Cadastro ativo
        </label>
      )}

      <button type="submit" disabled={saving} className="btn-primary-sm">
        {saving ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
        Salvar dados
      </button>
    </form>
  );
}
