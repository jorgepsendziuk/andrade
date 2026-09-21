import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
  ArrowLeft,
  ClipboardList,
  KeyRound,
  Loader2,
  Mail,
  Phone,
  Plus,
  User,
} from 'lucide-react';
import { AuditLogsPanel } from '../../components/admin/AuditLogsPanel';
import { ClientEditAlertBanner } from '../../components/admin/ClientEditAlertBanner';
import { CreateProcessModal } from '../../components/admin/CreateProcessModal';
import { ClientProfileForm } from '../../components/forms/ClientProfileForm';
import { SeoHead } from '../../components/seo/SeoHead';
import {
  acknowledgeAdminAlert,
  fetchAdminAlerts,
  fetchAdminAudit,
  fetchAdminClientProcesses,
  sendAdminClientPasswordReset,
  updateAdminClient,
} from '../../lib/portal-api';
import { formatCpf, formatDateShort } from '../../lib/process-grid-utils';
import { PORTAL_STAFF } from '../../lib/portal-routes';
import type { AuditLogRecord, ClientPublic, ProcessRecord, StaffAlert } from '../../types/process';
import { MODALITY_LABELS, STATUS_LABELS, STEP_LABELS } from '../../types/process';

export function AdminClientDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [client, setClient] = useState<ClientPublic | null>(null);
  const [processes, setProcesses] = useState<ProcessRecord[]>([]);
  const [activeProcessId, setActiveProcessId] = useState<string | undefined>();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [saving, setSaving] = useState(false);
  const [resetting, setResetting] = useState(false);
  const [showCreate, setShowCreate] = useState(false);
  const [logs, setLogs] = useState<AuditLogRecord[]>([]);
  const [editAlert, setEditAlert] = useState<StaffAlert | null>(null);
  const [acking, setAcking] = useState(false);

  const load = () => {
    if (!id) return;
    setLoading(true);
    setError('');
    Promise.all([
      fetchAdminClientProcesses(id),
      fetchAdminAudit({ resourceId: id }),
      fetchAdminAlerts({ clientId: id, unread: true }),
    ])
      .then(([data, audit, alerts]) => {
        setClient(data.client);
        setProcesses(data.processes);
        setActiveProcessId(data.activeProcessId);
        setLogs(audit);
        setEditAlert(alerts[0] ?? null);
      })
      .catch((err) => setError(err instanceof Error ? err.message : 'Erro ao carregar'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, [id]);

  const handleSave = async (patch: Record<string, unknown>) => {
    if (!client) return;
    setSaving(true);
    setError('');
    setSuccess('');
    try {
      await updateAdminClient(client.id, patch);
      setSuccess('Dados salvos com sucesso.');
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao salvar');
    } finally {
      setSaving(false);
    }
  };

  const handlePasswordReset = async () => {
    if (!client) return;
    if (!window.confirm(`Enviar e-mail de redefinição de senha para ${client.email}?`)) return;
    setResetting(true);
    setError('');
    setSuccess('');
    try {
      await sendAdminClientPasswordReset(client.id);
      setSuccess('E-mail de redefinição enviado.');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao enviar e-mail');
    } finally {
      setResetting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-16">
        <Loader2 className="animate-spin text-brand-500" size={32} />
      </div>
    );
  }

  if (!client) {
    return <div className="text-center py-16 text-slate-500">Cliente não encontrado.</div>;
  }

  return (
    <>
      <SeoHead title={`${client.name} | Clientes`} description="Detalhe do cliente." noindex />

      <Link
        to={PORTAL_STAFF.clients}
        className="inline-flex items-center gap-1.5 text-sm text-brand-600 hover:underline mb-4"
      >
        <ArrowLeft size={16} /> Voltar à lista
      </Link>

      {error && (
        <div className="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{error}</div>
      )}
      {success && (
        <div className="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm">
          {success}
        </div>
      )}

      <div className="grid lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-6">
          <div className="bg-white rounded-xl border border-slate-200 p-5">
            <div className="flex items-start gap-3 mb-5">
              <div className="w-11 h-11 rounded-xl bg-brand-100 flex items-center justify-center shrink-0">
                <User size={22} className="text-brand-600" />
              </div>
              <div>
                <h1 className="font-display text-xl font-extrabold text-brand-800">{client.name}</h1>
                <p className="text-sm text-slate-500">CPF {formatCpf(client.cpf)}</p>
              </div>
            </div>

            <ClientEditAlertBanner
              alert={editAlert}
              fields={client.lastSelfEditFields}
              editedAt={client.lastSelfEditAt}
              acknowledging={acking}
              onAcknowledge={
                editAlert
                  ? () => {
                      setAcking(true);
                      acknowledgeAdminAlert(editAlert.id)
                        .then(() => load())
                        .catch((err) => setError(err instanceof Error ? err.message : 'Falha ao marcar alerta'))
                        .finally(() => setAcking(false));
                    }
                  : undefined
              }
            />
            <ClientProfileForm
              client={client}
              includeIdentity
              includeActive
              highlightFields={client.lastSelfEditFields}
              saving={saving}
              onSave={handleSave}
            />
            <div className="mt-3">
              <button
                type="button"
                onClick={() => void handlePasswordReset()}
                disabled={resetting || client.active === false}
                className="btn-secondary text-xs"
              >
                {resetting ? <Loader2 size={14} className="animate-spin" /> : <KeyRound size={14} />}
                Redefinir senha por e-mail
              </button>
            </div>

            <div className="mt-4 pt-4 border-t border-slate-100 flex flex-wrap gap-4 text-sm text-slate-600">
              <a href={`mailto:${client.email}`} className="inline-flex items-center gap-2 hover:text-brand-600">
                <Mail size={14} />
                {client.email}
              </a>
              {client.phone && (
                <a href={`tel:${client.phone}`} className="inline-flex items-center gap-2 hover:text-brand-600">
                  <Phone size={14} />
                  {client.phone}
                </a>
              )}
            </div>
          </div>

          <div className="bg-white rounded-xl border border-slate-200 p-5">
            <div className="flex items-center justify-between gap-3 mb-4">
              <h2 className="font-display font-bold text-brand-800 flex items-center gap-2">
                <ClipboardList size={18} />
                Processos ({processes.length})
              </h2>
              <button type="button" onClick={() => setShowCreate(true)} className="btn-primary-sm">
                <Plus size={14} />
                Novo processo
              </button>
            </div>

            {processes.length === 0 ? (
              <p className="text-sm text-slate-500">Nenhum processo vinculado a este cliente.</p>
            ) : (
              <div className="space-y-2">
                {processes.map((process) => (
                  <Link
                    key={process.id}
                    to={PORTAL_STAFF.process(process.id)}
                    className="block rounded-lg border border-slate-200 px-4 py-3 hover:border-brand-300 hover:bg-brand-50/50 transition-colors"
                  >
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <div>
                        <p className="font-semibold text-brand-800 text-sm">
                          {MODALITY_LABELS[process.modality]}
                          {process.id === activeProcessId && (
                            <span className="ml-2 text-[10px] uppercase tracking-wide text-emerald-700 bg-emerald-100 px-1.5 py-0.5 rounded">
                              Ativo
                            </span>
                          )}
                        </p>
                        <p className="text-xs text-slate-500 mt-0.5">
                          {STEP_LABELS[process.currentStep]} · {formatDateShort(process.createdAt)}
                        </p>
                      </div>
                      <span
                        className={`text-xs font-semibold px-2 py-0.5 rounded-full ${
                          process.status === 'ativo'
                            ? 'bg-emerald-100 text-emerald-800'
                            : process.status === 'concluido'
                              ? 'bg-blue-100 text-blue-800'
                              : 'bg-red-100 text-red-800'
                        }`}
                      >
                        {STATUS_LABELS[process.status]}
                      </span>
                    </div>
                  </Link>
                ))}
              </div>
            )}
          </div>

          <div className="bg-white rounded-xl border border-slate-200 p-5">
            <h2 className="font-display font-bold text-brand-800 mb-3">Histórico deste cadastro</h2>
            <AuditLogsPanel logs={logs} onReverted={load} />
          </div>
        </div>

        <aside className="space-y-4">
          <div className="bg-slate-50 rounded-xl border border-slate-200 p-4 text-xs text-slate-500 space-y-2">
            <p>
              <span className="font-medium text-slate-600">ID:</span> {client.id}
            </p>
            <p>
              <span className="font-medium text-slate-600">Cadastro:</span>{' '}
              {new Date(client.createdAt).toLocaleString('pt-BR')}
            </p>
            <p>
              <span className="font-medium text-slate-600">Atualizado:</span>{' '}
              {new Date(client.updatedAt).toLocaleString('pt-BR')}
            </p>
            {client.storageSlug && (
              <p>
                <span className="font-medium text-slate-600">Pasta:</span>{' '}
                <code className="text-[10px] bg-white px-1 rounded border">clientes/{client.storageSlug}/</code>
              </p>
            )}
          </div>
        </aside>
      </div>

      <CreateProcessModal
        open={showCreate}
        onClose={() => setShowCreate(false)}
        initialClientId={client.id}
        onCreated={(processId) => {
          load();
          navigate(PORTAL_STAFF.process(processId));
        }}
      />
    </>
  );
}
