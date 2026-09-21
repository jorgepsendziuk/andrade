import { useEffect, useState } from 'react';
import { CalendarDays } from 'lucide-react';
import { formatDateBrInput, parseFlexibleDate } from '../../lib/date-br';

interface DateFieldProps {
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
}

export function DateField({ value, onChange, placeholder = 'dd/mm/aaaa' }: DateFieldProps) {
  const parsed = parseFlexibleDate(value);
  const [text, setText] = useState(() => parsed?.br || formatDateBrInput(value));

  useEffect(() => {
    const next = parseFlexibleDate(value);
    if (next) {
      setText(next.br);
      return;
    }
    if (!value) setText('');
  }, [value]);

  return (
    <div className="mt-1 flex">
      <input
        className="input-field flex-1 rounded-r-none"
        inputMode="numeric"
        autoComplete="off"
        placeholder={placeholder}
        value={text}
        maxLength={10}
        onChange={(e) => {
          const masked = formatDateBrInput(e.target.value);
          setText(masked);
          const ok = parseFlexibleDate(masked);
          onChange(ok ? ok.iso : masked);
        }}
      />
      <div className="relative w-11 shrink-0">
        <span className="pointer-events-none absolute inset-0 flex items-center justify-center rounded-r-lg border border-l-0 border-slate-200 bg-slate-50 text-brand-700">
          <CalendarDays size={18} />
        </span>
        <input
          type="date"
          title="Abrir calendário"
          aria-label="Abrir calendário"
          className="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
          value={parsed?.iso ?? ''}
          min="1900-01-01"
          max={new Date().toISOString().slice(0, 10)}
          onChange={(e) => {
            onChange(e.target.value);
            const ok = parseFlexibleDate(e.target.value);
            if (ok) setText(ok.br);
            if (!e.target.value) setText('');
          }}
        />
      </div>
    </div>
  );
}
