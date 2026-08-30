import { ArrowRight } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

interface CtaButtonProps {
  href?: string;
  onClick?: () => void;
  children: React.ReactNode;
  icon?: LucideIcon;
  variant?: 'primary' | 'secondary';
  className?: string;
}

export function CtaButton({
  href,
  onClick,
  children,
  icon: Icon,
  variant = 'primary',
  className = '',
}: CtaButtonProps) {
  const base =
    variant === 'primary'
      ? 'btn-primary'
      : 'btn-secondary';

  const content = (
    <>
      {Icon && (
        <span className="flex items-center justify-center w-7 h-7 rounded-md bg-white/20">
          <Icon size={16} strokeWidth={2.5} />
        </span>
      )}
      <span>{children}</span>
      {variant === 'primary' && <ArrowRight size={16} strokeWidth={2.5} className="opacity-90" />}
    </>
  );

  const cls = `${base} ${className}`.trim();

  if (href) {
    return (
      <a href={href} className={cls}>
        {content}
      </a>
    );
  }

  return (
    <button type="button" onClick={onClick} className={cls}>
      {content}
    </button>
  );
}
