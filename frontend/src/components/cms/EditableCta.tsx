import { EditableText } from './EditableText';
import { useCms } from '../../context/CmsContext';

interface EditableCtaProps {
  text: string;
  href: string;
  onTextChange: (value: string) => void;
  onHrefChange: (value: string) => void;
  className?: string;
  children?: React.ReactNode;
}

export function EditableCta({
  text,
  href,
  onTextChange,
  onHrefChange,
  className = '',
  children,
}: EditableCtaProps) {
  const { isEditing } = useCms();

  if (isEditing) {
    return (
      <div className="cms-editable space-y-1.5 p-2 rounded-lg border border-dashed border-accent/50 bg-white/10">
        <EditableText value={text} onChange={onTextChange} as="span" className={className} />
        <input
          type="text"
          value={href}
          onChange={(e) => onHrefChange(e.target.value)}
          className="w-full text-[10px] bg-white/90 text-slate-800 rounded px-2 py-1 border border-slate-200"
          placeholder="Link (ex: #contato ou /iniciar)"
          onClick={(e) => e.stopPropagation()}
        />
      </div>
    );
  }

  return (
    <a href={href} className={className}>
      {children ?? text}
    </a>
  );
}
