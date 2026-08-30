interface RegisteredMarkBadgeProps {
  className?: string;
}

export function RegisteredMarkBadge({ className = '' }: RegisteredMarkBadgeProps) {
  return (
    <div
      className={`inline-flex items-center gap-2 rounded-full border border-brand-500/40 bg-brand-900/40 px-2.5 py-1 ${className}`}
      title="Marca registrada"
    >
      <span
        className="flex items-center justify-center w-5 h-5 rounded-full bg-brand-100 text-brand-800 text-[10px] font-bold leading-none flex-shrink-0"
        aria-hidden
      >
        ®
      </span>
      <span className="text-[10px] font-medium text-brand-100 tracking-wide uppercase">
        Marca registrada
      </span>
    </div>
  );
}
