import { useEffect, useState } from 'react';
import { CheckCircle, Clock, FileUp, Loader2, ScrollText, Upload, User } from 'lucide-react';
import { AuditLogsPanel } from '../components/admin/AuditLogsPanel';
import { ClientProfileForm } from '../components/forms/ClientProfileForm';
import { VehicleProfileForm } from '../components/forms/VehicleProfileForm';
import { SeoHead } from '../components/seo/SeoHead';
import {
  fetchPortalAudit,
  fetchPortalMe,
  fetchPortalProcess,
  openPortalFile,
  updatePortalMe,
  updatePortalProcess,
  uploadPortalFile,
} from '../lib/portal-api';
import {
  FILE_TYPE_LABELS,
  type AuditLogRecord,
  type ClientPublic,
  type FileTypeCode,
  type ProcessFileRecord,
  type ProcessRecord,
} from '../types/process';

const STATUS_ICON = {
  pendente: Clock,
  em_andamento: Loader2,
  concluida: CheckCircle,
  bloqueada: Clock,
};

const STATUS_COLOR = {
  pendente: 'text-slate-400 bg-slate-100',
  em_andamento: 'text-amber-700 bg-amber-50',
  concluida: 'text-green-700 bg-green-50',
  bloqueada: 'text-slate-400 bg-slate-100',
};

const REQUIRED: FileTypeCode[] = ['cnh', 'laudo', 'comprovante_residencia'];

export function PortalAccountPage() {
  const [process, setProcess] = useState<ProcessRecord | null>(null);
  const [files, setFiles] = useState<ProcessFileRecord[]>([]);
  const [client, setClient] = useState<ClientPublic | null>(null);
  const [logs, setLogs] = useState<AuditLogRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState<FileTypeCode | null>(null);
  const [savingProfile, setSavingProfile] = useState(false);
  const [savingVehicle, setSavingVehicle] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const load = () => {
    setLoading(true);
    Promise.allSettled([fetchPortalMe(), fetchPortalProcess(), fetchPortalAudit()])
      .then(([me, data, audit]) => {
        if (me.status === 'fulfilled') setClient(me.value);
        else setError(me.reason instanceof Error ? me.reason.message : 'Erro ao carregar');
        if (data.status === 'fulfilled') {
          setProcess(data.value.process);
          setFiles(data.value.files);
        }
        if (audit.status === 'fulfilled') setLogs(audit.value);
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, []);

  const handleUpload = async (type: FileTypeCode, file: File) => {
    setUploading(type);
    setError('');
    try {
      await uploadPortalFile(file, type);
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha no upload');
    } finally {
      setUploading(null);
    }
  };

  const openFile = async (fileId: string) => {
    try {
      await openPortalFile(fileId);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao abrir arquivo');
    }
  };

  const handleSaveProfile = async (patch: Record<string, unknown>) => {
    setSavingProfile(true);
    setError('');
    setSuccess('');
    try {
      const updated = await updatePortalMe(patch);
      setClient(updated);
      setSuccess('Seus dados foram atualizados.');
      const audit = await fetchPortalAudit();
      setLogs(audit);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao salvar dados');
    } finally {
      setSavingProfile(false);
    }
  };

  const handleSaveVehicle = async (vehicle: ProcessRecord['vehicle']) => {
    setSavingVehicle(true);
    setError('');
    setSuccess('');
    try {
      const updated = await updatePortalProcess({ vehicle: vehicle ?? {} });
      setProcess(updated);
      setSuccess('Dados do veículo atualizados.');
      const audit = await fetchPortalAudit();
      setLogs(audit);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao salvar veículo');
    } finally {
      setSavingVehicle(false);
    }
  };

  const uploadedTypes = new Set(files.map((f) => f.fileType));
  const missing = REQUIRED.filter((t) => !uploadedTypes.has(t));

  if (loading) {
    return (
      <div className="flex justify-center py-16">
        <Loader2 className="animate-spin text-brand-500" size={32} />
      </div>
    );
  }

  return (
    <>
      <SeoHead title="Meu processo | Portal Andrade" description="Acompanhe seu processo de isenção PCD." noindex path="/portal/meu-processo" />
      <h1 className="font-display text-2xl font-extrabold text-brand-800 mb-1">
        Olá, {client?.name.split(' ')[0] || 'cliente'}!
      </h1>
      <p className="text-slate-600 text-sm mb-6">Acompanhe as etapas e mantenha seus dados atualizados.</p>

      {error && <div className="mb-4 bg-red-50 text-red-600 px-4 py-3 rounded-xl text-sm">{error}</div>}
      {success && <div className="mb-4 bg-emerald-50 text-emerald-800 px-4 py-3 rounded-xl text-sm">{success}</div>}

      {client && (
        <section className="bg-white rounded-2xl p-5 shadow-card border border-brand-100 mb-6">
          <h2 className="font-display font-bold text-brand-800 mb-4 flex items-center gap-2">
            <User size={20} /> Meus dados e representante legal
          </h2>
          <p className="text-xs text-slate-500 mb-4">
            E-mail e CPF não podem ser alterados por aqui. Se precisar corrigir, fale com a equipe.
          </p>
          <ClientProfileForm client={client} includeIdentity={false} saving={savingProfile} onSave={handleSaveProfile} />
        </section>
      )}

      {process && (
        <>
          <section className="bg-white rounded-2xl p-5 shadow-card border border-brand-100 mb-6">
            <VehicleProfileForm vehicle={process.vehicle} saving={savingVehicle} onSave={handleSaveVehicle} />
          </section>

          <section className="bg-white rounded-2xl p-5 shadow-card border border-brand-100 mb-6">
            <h2 className="font-display font-bold text-brand-800 mb-4">Etapas do processo</h2>
            <div className="space-y-3">
              {process.steps.filter((s) => s.key !== 'cancelado').map((step) => {
                const Icon = STATUS_ICON[step.status];
                return (
                  <div key={step.key} className={`flex items-center gap-3 p-3 rounded-xl ${STATUS_COLOR[step.status]}`}>
                    <Icon size={20} className={step.status === 'em_andamento' ? 'animate-spin' : ''} />
                    <div className="flex-1">
                      <p className="font-medium text-sm">{step.label}</p>
                      {step.status === 'concluida' && step.completedAt && (
                        <p className="text-xs opacity-70">Concluída em {new Date(step.completedAt).toLocaleDateString('pt-BR')}</p>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </section>

          <section className="bg-white rounded-2xl p-5 shadow-card border border-brand-100 mb-6">
            <h2 className="font-display font-bold text-brand-800 mb-2 flex items-center gap-2">
              <FileUp size={20} /> Documentos
            </h2>
            {missing.length > 0 && (
              <p className="text-amber-700 text-sm mb-3">Faltam: {missing.map((m) => FILE_TYPE_LABELS[m]).join(', ')}</p>
            )}
            <ul className="space-y-2 mb-4">
              {files.map((f) => (
                <li key={f.id} className="flex items-center justify-between text-sm p-2 bg-brand-50 rounded-lg">
                  <span>{FILE_TYPE_LABELS[f.fileType]} — {f.originalName}</span>
                  <button type="button" onClick={() => openFile(f.id)} className="text-brand-600 hover:underline text-xs">Abrir</button>
                </li>
              ))}
            </ul>
            <div className="space-y-2">
              {REQUIRED.map((type) => (
                <label key={type} className="flex items-center gap-3 text-sm cursor-pointer p-2 border border-dashed border-brand-200 rounded-lg hover:bg-brand-50">
                  <Upload size={16} className="text-brand-500" />
                  <span className="flex-1">{uploadedTypes.has(type) ? `Substituir ${FILE_TYPE_LABELS[type]}` : `Enviar ${FILE_TYPE_LABELS[type]}`}</span>
                  <input
                    type="file"
                    accept=".pdf,image/jpeg,image/png"
                    className="hidden"
                    disabled={uploading === type}
                    onChange={(e) => {
                      const file = e.target.files?.[0];
                      if (file) handleUpload(type, file);
                    }}
                  />
                  {uploading === type && <Loader2 size={14} className="animate-spin" />}
                </label>
              ))}
            </div>
          </section>
        </>
      )}

      <section className="bg-white rounded-2xl p-5 shadow-card border border-brand-100 mb-6">
        <h2 className="font-display font-bold text-brand-800 mb-4 flex items-center gap-2">
          <ScrollText size={20} /> Histórico de alterações
        </h2>
        <AuditLogsPanel logs={logs} variant="client" />
      </section>
    </>
  );
}
