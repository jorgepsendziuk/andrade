import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Bell, Check, Loader2, RefreshCw } from 'lucide-react';
import { SeoHead } from '../../components/seo/SeoHead';
import { acknowledgeAdminAlert, fetchAdminAlerts } from '../../lib/portal-api';
import { PORTAL_STAFF } from '../../lib/portal-routes';
import { CLIENT_FIELD_LABELS, type StaffAlert } from '../../types/process';

function formatValue(value: unknown): string {
  if (value === null || value === undefined || value === '') return '—';
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
}

export function AdminAlertsPage() {
  const [alerts, setAlerts] = useState<StaffAlert[]>([]);
  const [loading, setLoading] = useState(true);
  const [unreadOnly, setUnreadOnly] = useState(true);
  const [error, setError] = useState('');
  const [acking, setAcking] = useState<string | null>(null);

  const load = () => {
    setLoading(true);
    setError('');
    fetchAdminAlerts({ unread: unreadOnly })
      .then(setAlerts)
      .catch((err) => setError(err instanceof Error ? err.message : 'Erro ao carregar'))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    load();
  }, [unreadOnly]);

  const ack = async (id: string) => {
    setAcking(id);
    try {
      await acknowledgeAdminAlert(id);
      load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao marcar');
    } finally {
      setAcking(null);
    }
  };

  return (
    <>
      <SeoHead title="Alertas | Portal Andrade" description="Alertas de edição feitos pelo cliente." noindex />
      <div className="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
          <h1 className="font-display text-2xl font-extrabold text-brand-800 flex items-center gap-2">
            <Bell size={24} className="text-amber-600" />
            Alertas do sistema
          </h1>
          <p className="text-sm text-slate-500 mt-1">
            A equipe é avisada quando o cliente altera o próprio cadastro ou o veículo do processo.
          </p>
        </div>
        <div className="flex flex-wrap gap-2">
          <button
            type="button"
            onClick={() => setUnreadOnly((v) => !v)}
            className={`text-xs font-semibold px-3 py-1.5 rounded-lg ${
              unreadOnly ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600'
            }`}
          >
            {unreadOnly ? 'Somente não lidos' : 'Todos'}
          </button>
          <button type="button" onClick={load} className="btn-secondary text-xs">
            <RefreshCw size={14} /> Atualizar
          </button>
        </div>
      </div>

      {error && (
        <div className="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{error}</div>
      )}

      {loading ? (
        <div className="flex justify-center py-16">
          <Loader2 className="animate-spin text-brand-500" size={28} />
        </div>
      ) : alerts.length === 0 ? (
        <div className="bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-500 text-sm">
          Nenhum alerta {unreadOnly ? 'pendente' : 'registrado'}.
        </div>
      ) : (
        <div className="space-y-3">
          {alerts.map((alert) => (
            <article
              key={alert.id}
              className={`rounded-xl border p-4 ${
                alert.unread ? 'border-amber-400 bg-amber-50' : 'border-slate-200 bg-white'
              }`}
            >
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <p className="font-semibold text-brand-800">{alert.title}</p>
                  <p className="text-xs text-slate-500 mt-0.5">
                    {new Date(alert.createdAt).toLocaleString('pt-BR')} · {alert.summary}
                  </p>
                  {alert.changes?.length ? (
                    <ul className="mt-2 space-y-1 text-xs">
                      {alert.changes.map((change) => (
                        <li key={change.field}>
                          <span className="font-semibold">{CLIENT_FIELD_LABELS[change.field] || change.field}:</span>{' '}
                          <span className="text-red-600">{formatValue(change.from)}</span>
                          {' → '}
                          <span className="text-emerald-700">{formatValue(change.to)}</span>
                        </li>
                      ))}
                    </ul>
                  ) : null}
                  <div className="mt-3 flex flex-wrap gap-2">
                    <Link to={PORTAL_STAFF.client(alert.clientId)} className="text-xs font-semibold text-brand-600 hover:underline">
                      Abrir cadastro
                    </Link>
                    {alert.processId && (
                      <Link to={PORTAL_STAFF.process(alert.processId)} className="text-xs font-semibold text-brand-600 hover:underline">
                        Abrir processo
                      </Link>
                    )}
                  </div>
                </div>
                {alert.unread ? (
                  <button
                    type="button"
                    onClick={() => void ack(alert.id)}
                    disabled={acking === alert.id}
                    className="inline-flex items-center gap-1.5 text-xs font-semibold bg-amber-600 text-white px-3 py-1.5 rounded-lg hover:bg-amber-700 disabled:opacity-50"
                  >
                    {acking === alert.id ? <Loader2 size={14} className="animate-spin" /> : <Check size={14} />}
                    Marcar como visto
                  </button>
                ) : (
                  <span className="text-xs text-slate-400">Visto {alert.readByEmail ? `por ${alert.readByEmail}` : ''}</span>
                )}
              </div>
            </article>
          ))}
        </div>
      )}
    </>
  );
}
