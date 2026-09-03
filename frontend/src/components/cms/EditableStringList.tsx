import { Plus, Trash2 } from 'lucide-react';
import { useCms } from '../../context/CmsContext';
import { EditableText } from './EditableText';

interface EditableStringListProps {
  items: string[];
  onChange: (items: string[]) => void;
  className?: string;
  itemClassName?: string;
  addLabel?: string;
  bullet?: boolean;
}

export function EditableStringList({
  items,
  onChange,
  className = '',
  itemClassName = '',
  addLabel = 'Adicionar item',
  bullet = false,
}: EditableStringListProps) {
  const { isEditing } = useCms();

  if (!isEditing) {
    return (
      <ul className={className}>
        {items.map((item, i) => (
          <li key={i} className={itemClassName}>
            {bullet ? `• ${item}` : item}
          </li>
        ))}
      </ul>
    );
  }

  return (
    <div className={`space-y-2 ${className}`}>
      {items.map((item, i) => (
        <div key={i} className="flex items-start gap-2 cms-editable p-2 rounded-lg border border-dashed border-brand-200 bg-brand-50/50">
          {bullet && <span className="text-accent font-bold mt-1">•</span>}
          <EditableText
            value={item}
            onChange={(v) => {
              const next = [...items];
              next[i] = v;
              onChange(next);
            }}
            as="span"
            className={`flex-1 text-sm ${itemClassName}`}
            multiline
          />
          <button
            type="button"
            onClick={() => onChange(items.filter((_, idx) => idx !== i))}
            className="p-1 text-red-500 hover:bg-red-50 rounded"
            aria-label="Remover item"
          >
            <Trash2 size={14} />
          </button>
        </div>
      ))}
      <button
        type="button"
        onClick={() => onChange([...items, 'Novo item'])}
        className="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-800"
      >
        <Plus size={14} />
        {addLabel}
      </button>
    </div>
  );
}
