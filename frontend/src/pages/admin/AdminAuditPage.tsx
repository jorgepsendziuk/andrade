import { useEffect, useState } from 'react';
import { History, Loader2, RefreshCw, Search } from 'lucide-react';
import { AuditLogsPanel } from '../../components/admin/AuditLogsPanel';
import { SeoHead } from '../../components/seo/SeoHead';
import { fetchAdminAudit } from '../../lib/portal-api';
import type { AuditLogRecord } from '../../types/process';

export function AdminAuditPage() {
  const [logs, setLogs] = useState<AuditLogRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');
  const [action, setAction] = useState('');
  const [resourceType, setResourceType] = useState('');

  const load = () => {
    setLoading(true);
    setError('');
    fetchAdminAudit({
      search: search.trim() || undefined,
      action: action || undefined,
      resourceType: resourceType || undefined,
    })
      .then(setLogs)
      .catch((err) => setError(err instanceof Error ? err.message : 'Erro ao carregar'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, []);

  return (
    <>
      <SeoHead title="Histórico de alterações | Portal Andrade" description="Histórico de alterações do portal." noindex />
      <div className="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
          <h1 className="font-display text-2xl font-extrabold text-brand-800 flex items-center gap-2">
            <History size={24} className="text-brand-600" />
            Histórico de alterações
          </h1>
          <p className="text-sm text-slate-500 mt-1">
            Veja quem mudou o quê no cadastro, no processo ou nos arquivos. Se precisar, dá para desfazer uma alteração.
          </p>
        </div>
        <button type="button" onClick={load} className="btn-secondary text-xs">
          <RefreshCw size={14} /> Atualizar
        </button>
      </div>

      <form
        className="bg-white rounded-xl border border-slate-200 p-4 mb-4 grid sm:grid-cols-4 gap-3"
        onSubmit={(e) => {
          e.preventDefault();
          load();
        }}
      >
        <label className="text-xs text-slate-500 sm:col-span-2">
          Procurar
          <div className="relative mt-1">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              className="input-field pl-9"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Nome, e-mail ou o que foi alterado"
            />
          </div>
        </label>
        <label className="text-xs text-slate-500">
          O que aconteceu
          <select className="input-field mt-1" value={action} onChange={(e) => setAction(e.target.value)}>
            <option value="">Tudo</option>
            <option value="update">Alterou dados</option>
            <option value="create">Criou</option>
            <option value="upload">Enviou arquivo</option>
            <option value="download">Baixou arquivo</option>
            <option value="delete">Excluiu</option>
            <option value="consent">Aceitou os termos</option>
            <option value="password_reset">Redefiniu senha</option>
          </select>
        </label>
        <label className="text-xs text-slate-500">
          Onde
          <select className="input-field mt-1" value={resourceType} onChange={(e) => setResourceType(e.target.value)}>
            <option value="">Tudo</option>
            <option value="client">Cadastro</option>
            <option value="process">Processo</option>
            <option value="process_file">Arquivo</option>
          </select>
        </label>
        <div className="sm:col-span-4">
          <button type="submit" className="btn-primary-sm">Aplicar filtros</button>
        </div>
      </form>

      <div className="bg-white rounded-xl border border-slate-200 p-4">
        {loading ? (
          <div className="flex justify-center py-12">
            <Loader2 className="animate-spin text-brand-500" size={28} />
          </div>
        ) : error ? (
          <p className="text-sm text-red-600">{error}</p>
        ) : (
          <AuditLogsPanel logs={logs} onReverted={load} />
        )}
      </div>
    </>
  );
}
