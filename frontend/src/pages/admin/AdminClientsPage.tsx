import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { Loader2, RefreshCw, Search, UserCircle } from 'lucide-react';
import { SeoHead } from '../../components/seo/SeoHead';
import { fetchAdminClients } from '../../lib/portal-api';
import { formatCpf } from '../../lib/process-grid-utils';
import { PORTAL_STAFF } from '../../lib/portal-routes';
import type { ClientListItem } from '../../types/process';

export function AdminClientsPage() {
  const [clients, setClients] = useState<ClientListItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [search, setSearch] = useState('');

  const load = useCallback((query?: string) => {
    setLoading(true);
    setError('');
    fetchAdminClients(query)
      .then(setClients)
      .catch((err) => setError(err instanceof Error ? err.message : 'Erro ao carregar'))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    load(search.trim() || undefined);
  };

  const sorted = useMemo(
    () => [...clients].sort((a, b) => b.updatedAt.localeCompare(a.updatedAt)),
    [clients]
  );

  return (
    <>
      <SeoHead title="Clientes | Portal Andrade" description="Gestão de clientes do portal." noindex />

      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
          <h1 className="font-display text-2xl font-extrabold text-brand-800 flex items-center gap-2">
            <UserCircle size={26} className="text-brand-600" />
            Clientes
          </h1>
          <p className="text-sm text-slate-500 mt-1">
            Gerencie cadastros, redefina senhas e veja todos os processos por CPF.
          </p>
        </div>
        <button
          type="button"
          onClick={() => load(search.trim() || undefined)}
          className="inline-flex items-center gap-2 text-sm font-medium text-brand-600 hover:bg-brand-50 px-3 py-2 rounded-lg"
        >
          <RefreshCw size={16} />
          Atualizar
        </button>
      </div>

      <form onSubmit={handleSearch} className="mb-4 flex gap-2">
        <div className="relative flex-1 max-w-md">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input
            type="search"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Buscar por nome, e-mail ou CPF…"
            className="input-field pl-9"
          />
        </div>
        <button type="submit" className="btn-primary-sm">
          Buscar
        </button>
      </form>

      {error && (
        <div className="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{error}</div>
      )}

      {loading ? (
        <div className="flex justify-center py-16">
          <Loader2 className="animate-spin text-brand-500" size={32} />
        </div>
      ) : (
        <div className="bg-white rounded-xl border border-slate-200 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                  <th className="px-4 py-3 font-semibold">Cliente</th>
                  <th className="px-4 py-3 font-semibold">CPF</th>
                  <th className="px-4 py-3 font-semibold hidden md:table-cell">E-mail</th>
                  <th className="px-4 py-3 font-semibold hidden lg:table-cell">Telefone</th>
                  <th className="px-4 py-3 font-semibold text-center">Processos</th>
                  <th className="px-4 py-3 font-semibold">Status</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {sorted.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-4 py-10 text-center text-slate-500">
                      Nenhum cliente encontrado.
                    </td>
                  </tr>
                ) : (
                  sorted.map((client) => (
                    <tr key={client.id} className="hover:bg-brand-50/40">
                      <td className="px-4 py-3">
                        <Link
                          to={PORTAL_STAFF.client(client.id)}
                          className="font-semibold text-brand-700 hover:text-accent hover:underline"
                        >
                          {client.name}
                        </Link>
                        {client.lastSelfEditAt && (
                          <span className="ml-2 inline-flex text-[10px] font-extrabold uppercase tracking-wide text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded">
                            Editou cadastro
                          </span>
                        )}
                      </td>
                      <td className="px-4 py-3 font-mono text-xs text-slate-600">
                        {client.cpf ? formatCpf(client.cpf) : '—'}
                      </td>
                      <td className="px-4 py-3 hidden md:table-cell text-slate-600">{client.email}</td>
                      <td className="px-4 py-3 hidden lg:table-cell text-slate-600">{client.phone || '—'}</td>
                      <td className="px-4 py-3 text-center">
                        <span className="inline-flex min-w-[1.75rem] justify-center rounded-full bg-brand-100 text-brand-800 text-xs font-bold px-2 py-0.5">
                          {client.processCount}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <span
                          className={`text-xs font-semibold px-2 py-0.5 rounded-full ${
                            client.active !== false
                              ? 'bg-emerald-100 text-emerald-800'
                              : 'bg-slate-100 text-slate-600'
                          }`}
                        >
                          {client.active !== false ? 'Ativo' : 'Inativo'}
                        </span>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </>
  );
}
