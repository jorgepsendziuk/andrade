import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';
import { ASSESSORIA_ICONS, IconAnaliseDocumental } from './AssessoriaIcons';

type AssessoriaItem = {
  id: string;
  title: string;
  description?: string;
  highlight?: boolean;
};

export function ServicesSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('servicos');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    items: AssessoriaItem[];
  };

  const updateItem = (itemId: string, patch: Partial<AssessoriaItem>) => {
    const items = data.items.map((item) => (item.id === itemId ? { ...item, ...patch } : item));
    updateSection('servicos', { items });
  };

  const top = data.items.slice(0, 4);
  const bottom = data.items.slice(4);

  const renderCard = (item: AssessoriaItem) => {
    const Icon = ASSESSORIA_ICONS[item.id] || IconAnaliseDocumental;
    return (
      <article
        key={item.id}
        className={`bg-white rounded-2xl px-4 py-5 text-center shadow-[var(--shadow-card)] border h-full flex flex-col items-center ${
          item.highlight ? 'border-accent/70' : 'border-brand-100'
        }`}
      >
        <Icon className="w-20 h-16 mb-3" />
        <EditableText
          value={item.title}
          onChange={(v) => updateItem(item.id, { title: v })}
          as="h3"
          className="font-display font-bold text-brand-800 text-sm md:text-base leading-snug mb-2"
        />
        {item.description && (
          <EditableText
            value={item.description}
            onChange={(v) => updateItem(item.id, { description: v })}
            as="p"
            className="text-text-secondary text-sm leading-relaxed"
            multiline
          />
        )}
      </article>
    );
  };

  return (
    <section id="servicos" className="py-10 md:py-12 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('servicos', { title: v })}
          as="h2"
          className="section-title text-center mb-8"
        />

        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {top.map(renderCard)}
        </div>
        {bottom.length > 0 && (
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4 lg:max-w-4xl lg:mx-auto">
            {bottom.map(renderCard)}
          </div>
        )}
      </div>
    </section>
  );
}
