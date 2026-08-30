import { useEffect, useMemo, useState } from 'react';
import { Loader2, Search, UserPlus, Users, X } from 'lucide-react';
import { createAdminProcess, fetchAdminClients } from '../../lib/portal-api';
import { formatCpf } from '../../lib/process-grid-utils';
import type { ClientPublic, ProcessModality } from '../../types/process';

interface CreateProcessModalProps {
  open: boolean;
  onClose: () => void;
  onCreated: (processId: string, tempPassword?: string) => void;
}

type Tab = 'existing' | 'new';

export function CreateProcessModal({ open, onClose, onCreated }: CreateProcessModalProps) {
  const [tab, setTab] = useState<Tab>('existing');
  const [clients, setClients] = useState<ClientPublic[]>([]);
  const [loadingClients, setLoadingClients] = useState(false);
  const [clientSearch, setClientSearch] = useState('');
  const [selectedClientId, setSelectedClientId] = useState('');
  const [modality, setModality] = useState<ProcessModality>('pcd');
  const [force, setForce] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [newClient, setNewClient] = useState({
    name: '',
    email: '',
    cpf: '',
    phone: '',
    password: '',
  });

  useEffect(() => {
    if (!open) return;
    setError('');
    setLoadingClients(true);
    fetchAdminClients()
      .then(setClients)
      .catch((err) => setError(err instanceof Error ? err.message : 'Erro ao carregar clientes'))
      .finally(() => setLoadingClients(false));
  }, [open]);

  const filteredClients = useMemo(() => {
    const q = clientSearch.trim().toLowerCase();
    if (!q) return clients;
    return clients.filter(
      (c) =>
        c.name.toLowerCase().includes(q) ||
        c.email.toLowerCase().includes(q) ||
        c.cpf.includes(q.replace(/\D/g, ''))
    );
  }, [clients, clientSearch]);

  const reset = () => {
    setTab('existing');
    setClientSearch('');
    setSelectedClientId('');
    setModality('pcd');
    setForce(false);
    setError('');
    setNewClient({ name: '', email: '', cpf: '', phone: '', password: '' });
  };

  const handleClose = () => {
    reset();
    onClose();
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setBusy(true);
    setError('');
    try {
      const result =
        tab === 'existing'
          ? await createAdminProcess({ clientId: selectedClientId, modality, force })
          : await createAdminProcess({
              modality,
              force,
              newClient: {
                name: newClient.name.trim(),
                email: newClient.email.trim(),
                cpf: newClient.cpf.replace(/\D/g, ''),
                phone: newClient.phone.trim() || undefined,
                password: newClient.password.trim() || undefined,
              },
            });
      onCreated(result.process.id, result.tempPassword);
      handleClose();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao criar processo');
    } finally {
      setBusy(false);
    }
  };

  if (!open) return null;

  const canSubmit =
    tab === 'existing'
      ? Boolean(selectedClientId)
      : newClient.name.trim() && newClient.email.trim() && newClient.cpf.replace(/\D/g, '').length === 11;

  return (
    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" onClick={handleClose}>
      <div
        className="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-hidden shadow-2xl flex flex-col"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-center justify-between px-5 py-4 border-b border-slate-100">
          <div>
            <h2 className="font-bold text-brand-800">Novo processo</h2>
            <p className="text-xs text-slate-500 mt-0.5">Vincule a um cliente existente ou cadastre um novo</p>
          </div>
          <button type="button" onClick={handleClose} className="p-2 rounded-lg hover:bg-slate-100 text-slate-500">
            <X size={18} />
          </button>
        </div>

        <div className="flex border-b border-slate-100 px-5 gap-4">
          <button
            type="button"
            onClick={() => setTab('existing')}
            className={`flex items-center gap-1.5 py-3 text-sm font-semibold border-b-2 -mb-px ${
              tab === 'existing' ? 'border-brand-500 text-brand-700' : 'border-transparent text-slate-500'
            }`}
          >
            <Users size={15} />
            Cliente existente
          </button>
          <button
            type="button"
            onClick={() => setTab('new')}
            className={`flex items-center gap-1.5 py-3 text-sm font-semibold border-b-2 -mb-px ${
              tab === 'new' ? 'border-brand-500 text-brand-700' : 'border-transparent text-slate-500'
            }`}
          >
            <UserPlus size={15} />
            Novo cliente
          </button>
        </div>

        <form onSubmit={(e) => void handleSubmit(e)} className="flex flex-col flex-1 min-h-0">
          <div className="p-5 space-y-4 overflow-y-auto flex-1">
            {error && (
              <div className="bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm">{error}</div>
            )}

            <div>
              <label className="block text-xs font-medium text-slate-600 mb-1">Modalidade</label>
              <select
                value={modality}
                onChange={(e) => setModality(e.target.value as ProcessModality)}
                className="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"
              >
                <option value="pcd">PCD — Isenção</option>
                <option value="taxi">Táxi</option>
              </select>
            </div>

            {tab === 'existing' ? (
              <div className="space-y-2">
                <label className="block text-xs font-medium text-slate-600">Cliente</label>
                <div className="relative">
                  <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                  <input
                    type="search"
                    value={clientSearch}
                    onChange={(e) => setClientSearch(e.target.value)}
                    placeholder="Buscar nome, CPF ou e-mail…"
                    className="w-full pl-8 pr-3 py-2 rounded-lg border border-slate-200 text-sm"
                  />
                </div>
                <div className="max-h-48 overflow-y-auto border border-slate-200 rounded-lg divide-y">
                  {loadingClients ? (
                    <div className="flex justify-center py-6 text-slate-400">
                      <Loader2 size={20} className="animate-spin" />
                    </div>
                  ) : filteredClients.length === 0 ? (
                    <p className="text-center py-6 text-sm text-slate-500">Nenhum cliente encontrado.</p>
                  ) : (
                    filteredClients.map((c) => (
                      <label
                        key={c.id}
                        className={`flex items-start gap-3 px-3 py-2.5 cursor-pointer hover:bg-slate-50 ${
                          selectedClientId === c.id ? 'bg-brand-50' : ''
                        }`}
                      >
                        <input
                          type="radio"
                          name="client"
                          checked={selectedClientId === c.id}
                          onChange={() => setSelectedClientId(c.id)}
                          className="mt-1"
                        />
                        <div className="min-w-0">
                          <p className="text-sm font-medium text-slate-800 truncate">{c.name}</p>
                          <p className="text-xs text-slate-500">
                            {formatCpf(c.cpf)} · {c.email}
                          </p>
                        </div>
                      </label>
                    ))
                  )}
                </div>
              </div>
            ) : (
              <div className="grid gap-3 sm:grid-cols-2">
                <div className="sm:col-span-2">
                  <label className="block text-xs font-medium text-slate-600 mb-1">Nome completo *</label>
                  <input
                    required
                    value={newClient.name}
                    onChange={(e) => setNewClient((f) => ({ ...f, name: e.target.value }))}
                    className="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-600 mb-1">E-mail *</label>
                  <input
                    type="email"
                    required
                    value={newClient.email}
                    onChange={(e) => setNewClient((f) => ({ ...f, email: e.target.value }))}
                    className="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-600 mb-1">CPF *</label>
                  <input
                    required
                    value={newClient.cpf}
                    onChange={(e) => setNewClient((f) => ({ ...f, cpf: e.target.value }))}
                    placeholder="000.000.000-00"
                    className="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-600 mb-1">Telefone</label>
                  <input
                    value={newClient.phone}
                    onChange={(e) => setNewClient((f) => ({ ...f, phone: e.target.value }))}
                    className="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-600 mb-1">Senha (opcional)</label>
                  <input
                    type="password"
                    value={newClient.password}
                    onChange={(e) => setNewClient((f) => ({ ...f, password: e.target.value }))}
                    placeholder="Gerada automaticamente se vazio"
                    className="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm"
                  />
                </div>
              </div>
            )}

            <label className="flex items-start gap-2 text-xs text-slate-600 cursor-pointer">
              <input
                type="checkbox"
                checked={force}
                onChange={(e) => setForce(e.target.checked)}
                className="mt-0.5 rounded"
              />
              Permitir novo processo mesmo se o cliente já tiver um ativo
            </label>
          </div>

          <div className="px-5 py-4 border-t border-slate-100 flex justify-end gap-2">
            <button
              type="button"
              onClick={handleClose}
              className="px-4 py-2 rounded-lg border text-sm font-medium hover:bg-slate-50"
            >
              Cancelar
            </button>
            <button
              type="submit"
              disabled={!canSubmit || busy}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 disabled:opacity-50"
            >
              {busy && <Loader2 size={14} className="animate-spin" />}
              Criar processo
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
