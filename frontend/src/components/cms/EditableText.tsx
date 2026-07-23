import { useState, useRef, useEffect } from 'react';
import { useCms } from '../../context/CmsContext';

interface EditableTextProps {
  value: string;
  onChange: (value: string) => void;
  as?: 'h1' | 'h2' | 'h3' | 'h4' | 'p' | 'span';
  className?: string;
  multiline?: boolean;
}

export function EditableText({
  value,
  onChange,
  as: Tag = 'p',
  className = '',
  multiline = false,
}: EditableTextProps) {
  const { isEditing } = useCms();
  const [editing, setEditing] = useState(false);
  const ref = useRef<HTMLElement>(null);

  useEffect(() => {
    if (editing && ref.current) {
      ref.current.focus();
      const range = document.createRange();
      range.selectNodeContents(ref.current);
      range.collapse(false);
      const sel = window.getSelection();
      sel?.removeAllRanges();
      sel?.addRange(range);
    }
  }, [editing]);

  if (!isEditing) {
    return <Tag className={className}>{value}</Tag>;
  }

  const handleBlur = () => {
    setEditing(false);
    if (ref.current) {
      const newVal = ref.current.innerText.trim();
      if (newVal !== value) onChange(newVal);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (!multiline && e.key === 'Enter') {
      e.preventDefault();
      ref.current?.blur();
    }
    if (e.key === 'Escape') {
      if (ref.current) ref.current.innerText = value;
      setEditing(false);
    }
  };

  return (
    <Tag
      ref={ref as never}
      className={`cms-editable cursor-text ${className}`}
      data-editing={editing}
      contentEditable={editing}
      suppressContentEditableWarning
      onClick={() => !editing && setEditing(true)}
      onBlur={handleBlur}
      onKeyDown={handleKeyDown}
    >
      {value}
    </Tag>
  );
}
