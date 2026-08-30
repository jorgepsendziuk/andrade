import { useEffect, useMemo, useState } from 'react';
import {
  Archive,
  Check,
  Download,
  Loader2,
  Mail,
  MailCheck,
  MailX,
  Phone,
  Search,
  X,
} from 'lucide-react';
import { fetchContacts, updateContactStatus } from '../../lib/api';
import { convertContactToProcess } from '../../lib/portal-api';
import type { ContactSubmission, ContactStatus } from '../../types/contact';
import { SeoHead } from '../../components/seo/SeoHead';

const STATUS_LABEL: Record<ContactStatus, string> = {
  new: 'Novo',
  read: 'Lido',
  archived: 'Arquivado',
};

function formatDate(iso: string) {
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function exportCsv(contacts: ContactSubmission[]) {
  const header = 'Data,Nome,E-mail,Telefone,Status,E-mail enviado,Mensagem\n';
  const rows = contacts
    .map((c) =>
      [
        formatDate(c.createdAt),
        c.name,
        c.email,
        c.phone,
        STATUS_LABEL[c.status],
        c.emailSent ? 'Sim' : 'Não',
        `"${c.message.replace(/"/g, '""')}"`,
      ].join(',')
    )
    .join('\n');
  const blob = new Blob([header + rows], { type: 'text/csv;charset=utf-8' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `contatos-andrade-${new Date().toISOString().slice(0, 10)}.csv`;
  a.click();
  URL.revokeObjectURL(url);
}

export function AdminContactsPage() {
  const [contacts, setContacts] = useState<ContactSubmission[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [filter, setFilter] = useState<ContactStatus | 'all'>('all');
  const [search, setSearch] = useState('');
  const [selected, setSelected] = useState<ContactSubmission | null>(null);
  const [convertingId, setConvertingId] = useState<string | null>(null);
  const [updatingId, setUpdatingId] = useState<string | null>(null);

  const load = () => {
    setLoading(true);
    fetchContacts()
      .then(setContacts)
      .catch((err) => setError(err instanceof Error ? err.message : 'Erro ao carregar'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, []);

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase();
    return contacts.filter((c) => {
      if (filter !== 'all' && c.status !== filter) return false;
      if (!q) return true;
      return (
        c.name.toLowerCase().includes(q) ||
        c.email.toLowerCase().includes(q) ||
        c.message.toLowerCase().includes(q) ||
        c.phone.includes(q)
      );
    });
  }, [contacts, filter, search]);

  const today = new Date().toISOString().slice(0, 10);
  const stats = {
    new: contacts.filter((c) => c.status === 'new').length,
    today: contacts.filter((c) => c.createdAt.startsWith(today)).length,
    total: contacts.length,
    emailRate: contacts.length
      ? Math.round((contacts.filter((c) => c.emailSent).length / contacts.length) * 100)
      : 0,
  };

  const handleConvert = async (id: string) => {
    setConvertingId(id);
    try {
      const result = await convertContactToProcess(id);
      alert(`Processo criado! Senha temporária: ${result.tempPassword}`);
      window.location.href = `/portal/processos/${result.process.id}`;
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Erro ao criar processo');
    } finally {
      setConvertingId(null);
    }
  };

  const handleStatus = async (id: string, status: ContactStatus) => {
    setUpdatingId(id);
    try {
      const updated = await updateContactStatus(id, status);
      setContacts((prev) => prev.map((c) => (c.id === id ? updated : c)));
      if (selected?.id === id) setSelected(updated);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao atualizar');
    } finally {
      setUpdatingId(null);
    }
  };

  return (
    <>
      <SeoHead title="Contatos | Portal Andrade" description="Painel administrativo de contatos." noindex />
      <div className="space-y-6">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-2xl font-bold text-brand-800">Contatos do site</h1>
            <p className="text-slate-500 text-sm mt-1">Mensagens enviadas pelo formulário</p>
          </div>
          <button
            type="button"
            onClick={() => exportCsv(filtered)}
            disabled={filtered.length === 0}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg border bg-white text-sm font-medium hover:bg-slate-50 disabled:opacity-50"
          >
            <Download size={16} />
            Exportar CSV
          </button>
        </div>

        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          {[
            { label: 'Novos', value: stats.new, color: 'text-accent' },
            { label: 'Hoje', value: stats.today, color: 'text-brand-700' },
            { label: 'Total', value: stats.total, color: 'text-brand-800' },
            { label: 'E-mail OK', value: `${stats.emailRate}%`, color: 'text-green-600' },
          ].map((s) => (
            <div key={s.label} className="bg-white rounded-xl border p-4">
              <p className="text-xs text-slate-500">{s.label}</p>
              <p className={`text-2xl font-bold mt-1 ${s.color}`}>{s.value}</p>
            </div>
          ))}
        </div>

        <div className="flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
            <input
              type="search"
              placeholder="Buscar por nome, e-mail ou mensagem..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-200 text-sm"
            />
          </div>
          <div className="flex gap-2 flex-wrap">
            {(['all', 'new', 'read', 'archived'] as const).map((key) => (
              <button
                key={key}
                type="button"
                onClick={() => setFilter(key)}
                className={`px-3 py-2 rounded-lg text-sm font-medium ${
                  filter === key ? 'bg-brand-500 text-white' : 'bg-white border text-slate-600'
                }`}
              >
                {key === 'all' ? 'Todos' : STATUS_LABEL[key]}
              </button>
            ))}
          </div>
        </div>

        {error && <div className="bg-red-50 text-red-700 px-4 py-3 rounded-lg text-sm">{error}</div>}

        {loading ? (
          <div className="flex justify-center py-16">
            <Loader2 className="animate-spin text-brand-500" size={32} />
          </div>
        ) : filtered.length === 0 ? (
          <div className="text-center py-16 text-slate-500 bg-white rounded-xl border">Nenhum contato encontrado.</div>
        ) : (
          <div className="bg-white rounded-xl border overflow-hidden">
            <div className="hidden md:block overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="bg-slate-50 text-slate-500">
                  <tr>
                    <th className="text-left px-4 py-3 font-medium">Data</th>
                    <th className="text-left px-4 py-3 font-medium">Nome</th>
                    <th className="text-left px-4 py-3 font-medium">E-mail</th>
                    <th className="text-left px-4 py-3 font-medium">Status</th>
                    <th className="text-left px-4 py-3 font-medium">E-mail</th>
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {filtered.map((c) => (
                    <tr
                      key={c.id}
                      onClick={() => setSelected(c)}
                      className={`cursor-pointer hover:bg-slate-50 ${
                        c.status === 'new' ? 'bg-brand-50/50' : ''
                      } ${selected?.id === c.id ? 'ring-2 ring-inset ring-brand-300' : ''}`}
                    >
                      <td className="px-4 py-3 text-slate-500 whitespace-nowrap">{formatDate(c.createdAt)}</td>
                      <td className="px-4 py-3 font-medium">{c.name}</td>
                      <td className="px-4 py-3 text-slate-600">{c.email}</td>
                      <td className="px-4 py-3">
                        <span className="text-xs px-2 py-0.5 rounded-full bg-slate-100">{STATUS_LABEL[c.status]}</span>
                      </td>
                      <td className="px-4 py-3">
                        {c.emailSent ? (
                          <MailCheck size={16} className="text-green-600" />
                        ) : (
                          <MailX size={16} className="text-amber-500" />
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <div className="md:hidden divide-y">
              {filtered.map((c) => (
                <button
                  key={c.id}
                  type="button"
                  onClick={() => setSelected(c)}
                  className="w-full text-left px-4 py-3 hover:bg-slate-50"
                >
                  <p className="font-medium">{c.name}</p>
                  <p className="text-xs text-slate-500 truncate">{c.message}</p>
                  <p className="text-xs text-slate-400 mt-1">{formatDate(c.createdAt)}</p>
                </button>
              ))}
            </div>
          </div>
        )}
      </div>

      {selected && (
        <>
          <button
            type="button"
            className="fixed inset-0 bg-black/30 z-40"
            onClick={() => setSelected(null)}
            aria-label="Fechar"
          />
          <aside className="fixed top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl z-50 flex flex-col">
            <div className="flex items-center justify-between px-5 py-4 border-b">
              <h2 className="font-semibold text-brand-800">{selected.name}</h2>
              <button type="button" onClick={() => setSelected(null)} className="p-2 hover:bg-slate-100 rounded-lg">
                <X size={20} />
              </button>
            </div>
            <div className="flex-1 overflow-y-auto p-5 space-y-4 text-sm">
              <p className="text-slate-500">{formatDate(selected.createdAt)}</p>
              <div className="space-y-2">
                <a href={`mailto:${selected.email}`} className="flex items-center gap-2 text-brand-600">
                  <Mail size={16} /> {selected.email}
                </a>
                {selected.phone && (
                  <a href={`tel:${selected.phone}`} className="flex items-center gap-2 text-brand-600">
                    <Phone size={16} /> {selected.phone}
                  </a>
                )}
              </div>
              <p className="text-slate-700 whitespace-pre-wrap bg-slate-50 rounded-lg p-4">{selected.message}</p>
              {selected.emailSent ? (
                <p className="text-green-600 flex items-center gap-1 text-xs">
                  <MailCheck size={14} /> E-mail enviado ao comercial
                </p>
              ) : (
                <p className="text-amber-600 flex items-center gap-1 text-xs">
                  <MailX size={14} /> Registrado apenas no painel
                </p>
              )}
            </div>
            <div className="p-4 border-t flex flex-wrap gap-2">
              {selected.status !== 'read' && (
                <button
                  type="button"
                  disabled={updatingId === selected.id}
                  onClick={() => handleStatus(selected.id, 'read')}
                  className="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-slate-100 text-sm"
                >
                  <Check size={14} /> Lido
                </button>
              )}
              {selected.status !== 'archived' && (
                <button
                  type="button"
                  disabled={updatingId === selected.id}
                  onClick={() => handleStatus(selected.id, 'archived')}
                  className="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-slate-100 text-sm"
                >
                  <Archive size={14} /> Arquivar
                </button>
              )}
              <button
                type="button"
                disabled={convertingId === selected.id}
                onClick={() => handleConvert(selected.id)}
                className="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-brand-600 text-white text-sm ml-auto"
              >
                {convertingId === selected.id ? 'Criando…' : 'Criar processo'}
              </button>
            </div>
          </aside>
        </>
      )}
    </>
  );
}
