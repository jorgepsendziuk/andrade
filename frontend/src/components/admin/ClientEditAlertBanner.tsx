import { Bell, Check } from 'lucide-react';
import { CLIENT_FIELD_LABELS } from '../../types/process';
import type { StaffAlert } from '../../types/process';

function formatValue(value: unknown): string {
  if (value === null || value === undefined || value === '') return '—';
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
}

interface ClientEditAlertBannerProps {
  alert?: StaffAlert | null;
  fields?: string[];
  editedAt?: string;
  acknowledging?: boolean;
  onAcknowledge?: () => void;
}

export function ClientEditAlertBanner({
  alert,
  fields,
  editedAt,
  acknowledging,
  onAcknowledge,
}: ClientEditAlertBannerProps) {
  const changed = alert?.changes?.map((c) => c.field) ?? fields ?? [];
  if (!changed.length && !alert) return null;
  const when = alert?.createdAt || editedAt;

  return (
    <div className="mb-4 rounded-xl border-2 border-amber-400 bg-amber-50 px-4 py-3">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p className="inline-flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide text-amber-900">
            <Bell size={16} />
            Cliente alterou o cadastro
          </p>
          {when && (
            <p className="text-xs text-amber-800 mt-1">
              {new Date(when).toLocaleString('pt-BR')}
              {alert?.summary ? ` · ${alert.summary}` : ''}
            </p>
          )}
          {changed.length > 0 && (
            <p className="text-sm text-amber-900 mt-2">
              Campos destacados:{' '}
              <strong>{changed.map((f) => CLIENT_FIELD_LABELS[f] || f).join(', ')}</strong>
            </p>
          )}
          {alert?.changes?.length ? (
            <ul className="mt-2 space-y-1 text-xs text-amber-900">
              {alert.changes.map((change) => (
                <li key={change.field}>
                  <span className="font-semibold">{CLIENT_FIELD_LABELS[change.field] || change.field}:</span>{' '}
                  <span className="text-red-700">{formatValue(change.from)}</span>
                  {' → '}
                  <span className="text-emerald-800">{formatValue(change.to)}</span>
                </li>
              ))}
            </ul>
          ) : null}
        </div>
        {onAcknowledge && alert?.unread && (
          <button
            type="button"
            onClick={onAcknowledge}
            disabled={acknowledging}
            className="inline-flex items-center gap-1.5 text-xs font-semibold bg-amber-600 text-white px-3 py-1.5 rounded-lg hover:bg-amber-700 disabled:opacity-50"
          >
            <Check size={14} />
            Marcar como visto
          </button>
        )}
      </div>
    </div>
  );
}
