import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import {
  ArrowLeft,
  Car,
  ClipboardList,
  Loader2,
  Mail,
  MapPin,
  Pencil,
  Phone,
  Printer,
  Save,
  User,
} from 'lucide-react';
import { ProcessDocumentsPanel } from '../../components/admin/ProcessDocumentsPanel';
import { HonorariosPanel } from '../../components/admin/HonorariosPanel';
import { ProcessEditPanel } from '../../components/admin/ProcessEditPanel';
import { ProcessStepsPanel } from '../../components/admin/ProcessStepsPanel';
import { SeoHead } from '../../components/seo/SeoHead';
import { fetchAdminProcess, updateAdminClient } from '../../lib/portal-api';
import { TEMPLATE_ICONS } from '../../lib/process-ui-icons';
import { MODALITY_LABELS, STATUS_LABELS, STEP_LABELS } from '../../types/process';
import { getToken } from '../../lib/api';
import { formatCpf, formatCep, isValidCpf } from '../../lib/process-grid-utils';
import { PORTAL_STAFF } from '../../lib/portal-routes';
import type { DocumentTemplateCode } from '../../types/process';

export function AdminProcessDetailPage() {
  const { id } = useParams<{ id: string }>();
  const [data, setData] = useState<Awaited<ReturnType<typeof fetchAdminProcess>> | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [editingClient, setEditingClient] = useState(false);
  const [clientForm, setClientForm] = useState({ cpf: '', cep: '' });
  const [savingClient, setSavingClient] = useState(false);

  const load = () => {
    if (!id) return;
    setLoading(true);
    fetchAdminProcess(id)
      .then(setData)
      .catch((err) => setError(err instanceof Error ? err.message : 'Erro'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, [id]);

  const handlePrint = async (template: string) => {
    if (!id) return;
    const token = getToken();
    let url = `/api/admin/processes/${id}/print/${template}`;
    if (template === 'recibo' && data?.process) {
      const pagamentos = data.process.pagamentos ?? [];
      const paid = pagamentos.filter((p) => p.status === 'pago');
      const target =
        paid.length === 1
          ? paid[0]
          : paid.length > 1
            ? [...paid].sort((a, b) => b.data.localeCompare(a.data))[0]
            : pagamentos.length === 1
              ? pagamentos[0]
              : null;
      if (target) url += `?pagamentoId=${encodeURIComponent(target.id)}`;
    }
    const res = await fetch(url, {
      headers: { Authorization: `Bearer ${token}` },
    });
    if (!res.ok) {
      setError('Falha ao gerar documento');
      return;
    }
    const html = await res.text();
    const w = window.open('', '_blank');
    if (w) {
      w.document.write(html);
      w.document.close();
    }
  };

  const client = data?.client;
  const process = data?.process;
  const files = data?.files ?? [];
  const documentTemplates = data?.documentTemplates ?? [];
  const storagePrefix = data?.storagePrefix;
  const currentStepLabel = process ? STEP_LABELS[process.currentStep] : '';
  const cpfLooksLikeCep = client?.cpf && client.cpf.replace(/\D/g, '').length === 8;

  const startEditClient = () => {
    if (!client) return;
    setClientForm({ cpf: client.cpf || '', cep: client.cep || '' });
    setEditingClient(true);
  };

  const saveClientData = async () => {
    if (!client) return;
    if (!isValidCpf(clientForm.cpf)) {
      setError('CPF inválido. Informe os 11 dígitos.');
      return;
    }
    setSavingClient(true);
    setError('');
    try {
      await updateAdminClient(client.id, { cpf: clientForm.cpf, cep: clientForm.cep });
      setEditingClient(false);
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao salvar cliente');
    } finally {
      setSavingClient(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-16">
        <Loader2 className="animate-spin text-brand-500" size={32} />
      </div>
    );
  }

  if (!process) {
    return <div className="text-center py-16 text-slate-500">Processo não encontrado.</div>;
  }

  return (
    <>
      <SeoHead title={`Processo — ${client?.name ?? ''} | Portal Andrade`} description="Detalhe do processo PCD." noindex />

      <Link
        to={PORTAL_STAFF.processes}
        className="inline-flex items-center gap-1.5 text-sm text-brand-600 hover:underline mb-4"
      >
        <ArrowLeft size={16} /> Voltar à lista
      </Link>

      {error && (
        <div className="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{error}</div>
      )}

      <div className="flex flex-col lg:flex-row lg:items-start gap-6">
        <div className="flex-1 space-y-6 min-w-0">
          <div className="bg-white rounded-xl border border-slate-200 p-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
              <div className="flex items-start gap-3">
                <div className="w-11 h-11 rounded-xl bg-brand-100 flex items-center justify-center shrink-0">
                  <User size={22} className="text-brand-600" />
                </div>
                <div className="flex-1">
                  <h1 className="font-display text-xl font-extrabold text-brand-800">{client?.name}</h1>
                  {editingClient ? (
                    <div className="mt-2 grid sm:grid-cols-2 gap-2 max-w-md">
                      <label className="text-xs text-slate-500">
                        CPF
                        <input
                          className="input-field mt-1 text-sm"
                          value={clientForm.cpf}
                          onChange={(e) => setClientForm((f) => ({ ...f, cpf: e.target.value }))}
                          placeholder="000.000.000-00"
                        />
                      </label>
                      <label className="text-xs text-slate-500">
                        CEP
                        <input
                          className="input-field mt-1 text-sm"
                          value={clientForm.cep}
                          onChange={(e) => setClientForm((f) => ({ ...f, cep: e.target.value }))}
                          placeholder="00000-000"
                        />
                      </label>
                      <div className="sm:col-span-2 flex gap-2">
                        <button
                          type="button"
                          onClick={() => void saveClientData()}
                          disabled={savingClient}
                          className="inline-flex items-center gap-1 text-xs font-semibold text-white bg-brand-500 px-3 py-1.5 rounded-lg"
                        >
                          {savingClient ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
                          Salvar
                        </button>
                        <button
                          type="button"
                          onClick={() => setEditingClient(false)}
                          className="text-xs text-slate-500 px-3 py-1.5"
                        >
                          Cancelar
                        </button>
                      </div>
                    </div>
                  ) : (
                    <div className="mt-0.5">
                      <p className="text-sm text-slate-500">
                        CPF {client?.cpf ? formatCpf(client.cpf) : '—'}
                        {client?.cep && (
                          <span className="text-slate-400"> · CEP {formatCep(client.cep)}</span>
                        )}
                      </p>
                      {cpfLooksLikeCep && (
                        <p className="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-2 py-1 mt-1 inline-block">
                          Parece que o CEP foi salvo no campo CPF. Clique em Editar para corrigir.
                        </p>
                      )}
                    </div>
                  )}
                </div>
              </div>
              <div className="flex flex-wrap items-center gap-2">
                {client && !editingClient && (
                  <button
                    type="button"
                    onClick={startEditClient}
                    className="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:bg-brand-50 px-2 py-1 rounded-lg"
                  >
                    <Pencil size={14} />
                    Editar CPF/CEP
                  </button>
                )}
                <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-brand-100 text-brand-700">
                  {MODALITY_LABELS[process.modality]}
                </span>
                <span
                  className={`text-xs font-semibold px-2.5 py-1 rounded-full ${
                    process.status === 'ativo'
                      ? 'bg-emerald-100 text-emerald-800'
                      : process.status === 'concluido'
                        ? 'bg-blue-100 text-blue-800'
                        : 'bg-red-100 text-red-800'
                  }`}
                >
                  {STATUS_LABELS[process.status]}
                </span>
                <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800">
                  {currentStepLabel}
                </span>
              </div>
            </div>

            <div className="mt-4 grid sm:grid-cols-2 gap-2 text-sm text-slate-600">
              {client?.email && (
                <a href={`mailto:${client.email}`} className="flex items-center gap-2 hover:text-brand-600">
                  <Mail size={14} className="text-slate-400" />
                  {client.email}
                </a>
              )}
              {client?.phone && (
                <a href={`tel:${client.phone}`} className="flex items-center gap-2 hover:text-brand-600">
                  <Phone size={14} className="text-slate-400" />
                  {client.phone}
                </a>
              )}
              {client?.endereco && (
                <p className="sm:col-span-2 flex items-start gap-2">
                  <MapPin size={14} className="text-slate-400 shrink-0 mt-0.5" />
                  <span>
                    {client.endereco}, {client.numero}
                    {client.complemento ? ` — ${client.complemento}` : ''} — {client.bairro}, {client.cidade}/
                    {client.uf}
                  </span>
                </p>
              )}
            </div>

            {client?.representante && (
              <div className="mt-4 pt-4 border-t border-slate-100 text-sm">
                <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Representante legal</p>
                <p className="font-medium text-slate-800">{client.representante.nome}</p>
                <p className="text-slate-500 text-xs">CPF {formatCpf(client.representante.cpf)}</p>
              </div>
            )}

            {process.vehicle && (
              <div className="mt-4 pt-4 border-t border-slate-100 text-sm">
                <p className="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1 flex items-center gap-1.5">
                  <Car size={13} /> Veículo
                </p>
                <p className="font-medium text-slate-800">
                  {process.vehicle.marca} {process.vehicle.modelo} {process.vehicle.ano}
                </p>
                {process.vehicle.concessionaria && (
                  <p className="text-slate-500 text-xs">{process.vehicle.concessionaria}</p>
                )}
              </div>
            )}
          </div>

          <ProcessEditPanel process={process} onUpdated={load} />
          <ProcessStepsPanel process={process} onUpdated={load} />
          <ProcessDocumentsPanel
            processId={process.id}
            storagePrefix={storagePrefix ?? `clientes/${client?.storageSlug ?? process.clientId}/`}
            files={files}
            onUpdated={load}
          />
        </div>

        <aside className="lg:w-80 space-y-4 shrink-0">
          <div className="bg-white rounded-xl border border-slate-200 p-5">
            <h3 className="font-display font-bold text-brand-800 mb-3 flex items-center gap-2">
              <Printer size={18} className="text-brand-600" />
              Gerar documentos
            </h3>
            <p className="text-xs text-slate-500 mb-3">Abre versão HTML para impressão ou PDF</p>
            <div className="space-y-1.5">
              {documentTemplates.map((t) => {
                const Icon = TEMPLATE_ICONS[t.code as DocumentTemplateCode] ?? ClipboardList;
                return (
                  <button
                    key={t.code}
                    type="button"
                    onClick={() => void handlePrint(t.code)}
                    className="w-full flex items-center gap-2.5 text-left text-sm px-3 py-2.5 rounded-lg hover:bg-brand-50 border border-brand-100 transition-colors"
                  >
                    <Icon size={16} className="text-brand-600 shrink-0" />
                    <span>{t.label}</span>
                  </button>
                );
              })}
            </div>
          </div>

          <HonorariosPanel process={process} processId={process.id} onUpdated={load} />

          <div className="bg-slate-50 rounded-xl border border-slate-200 p-4 text-xs text-slate-500 space-y-1">
            <p>
              <span className="font-medium text-slate-600">Pasta:</span>{' '}
              <code className="text-[10px] bg-white px-1.5 py-0.5 rounded border">
                {storagePrefix ?? `clientes/${client?.storageSlug ?? process.clientId}/`}
              </code>
            </p>
            {process.storageSlug && (
              <p>
                <span className="font-medium text-slate-600">Processo:</span> {process.storageSlug}
              </p>
            )}
            <p>
              <span className="font-medium text-slate-600">ID:</span> {process.id}
            </p>
            <p>
              <span className="font-medium text-slate-600">Criado:</span>{' '}
              {new Date(process.createdAt).toLocaleString('pt-BR')}
            </p>
            <p>
              <span className="font-medium text-slate-600">Atualizado:</span>{' '}
              {new Date(process.updatedAt).toLocaleString('pt-BR')}
            </p>
          </div>
        </aside>
      </div>
    </>
  );
}
