import type { ReactNode } from 'react';

export function AdminCard({
  title,
  description,
  children,
  className = '',
}: {
  title: string;
  description?: string;
  children: ReactNode;
  className?: string;
}) {
  return (
    <section className={`bg-white rounded-xl border border-slate-200 shadow-sm ${className}`}>
      <div className="px-5 py-4 border-b border-slate-100">
        <h2 className="font-semibold text-brand-800">{title}</h2>
        {description && <p className="text-sm text-slate-500 mt-0.5">{description}</p>}
      </div>
      <div className="p-5 space-y-4">{children}</div>
    </section>
  );
}

export function AdminField({
  label,
  hint,
  children,
  className = '',
}: {
  label: string;
  hint?: string;
  children: ReactNode;
  className?: string;
}) {
  return (
    <label className={`block ${className}`}>
      <span className="block text-sm font-medium text-slate-700 mb-1">{label}</span>
      {children}
      {hint && <span className="block text-xs text-slate-400 mt-1">{hint}</span>}
    </label>
  );
}

const inputClass =
  'w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-brand-400';

export function AdminInput(props: React.InputHTMLAttributes<HTMLInputElement>) {
  return <input {...props} className={`${inputClass} ${props.className || ''}`} />;
}

export function AdminTextarea(props: React.TextareaHTMLAttributes<HTMLTextAreaElement>) {
  return (
    <textarea
      {...props}
      className={`${inputClass} min-h-[88px] resize-y ${props.className || ''}`}
    />
  );
}

export function AdminTabs({
  tabs,
  active,
  onChange,
}: {
  tabs: { id: string; label: string; adminOnly?: boolean }[];
  active: string;
  onChange: (id: string) => void;
}) {
  return (
    <div className="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
      {tabs.map((tab) => (
        <button
          key={tab.id}
          type="button"
          onClick={() => onChange(tab.id)}
          className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
            active === tab.id
              ? 'bg-brand-600 text-white shadow-sm'
              : 'text-slate-600 hover:bg-slate-100'
          }`}
        >
          {tab.label}
        </button>
      ))}
    </div>
  );
}

export function SaveBar({
  saving,
  saved,
  error,
  onSave,
  disabled,
}: {
  saving: boolean;
  saved: boolean;
  error: string | null;
  onSave: () => void;
  disabled?: boolean;
}) {
  return (
    <div className="sticky bottom-0 -mx-4 md:-mx-6 px-4 md:px-6 py-3 bg-white/95 backdrop-blur border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
      <div className="text-sm">
        {error && <span className="text-red-600">{error}</span>}
        {!error && saved && <span className="text-accent">Alterações salvas com sucesso.</span>}
        {!error && !saved && <span className="text-slate-400">Alterações não salvas</span>}
      </div>
      <button
        type="button"
        onClick={onSave}
        disabled={saving || disabled}
        className="px-5 py-2.5 rounded-lg bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700 disabled:opacity-50 transition-colors"
      >
        {saving ? 'Salvando…' : 'Salvar alterações'}
      </button>
    </div>
  );
}

export function StatusBadge({ ok, label }: { ok: boolean; label: string }) {
  return (
    <span
      className={`inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full ${
        ok ? 'bg-accent-light text-accent-dark' : 'bg-amber-50 text-amber-800'
      }`}
    >
      <span className={`w-1.5 h-1.5 rounded-full ${ok ? 'bg-accent' : 'bg-amber-500'}`} />
      {label}
    </span>
  );
}
