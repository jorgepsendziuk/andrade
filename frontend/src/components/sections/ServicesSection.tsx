import { Receipt, Percent, Car, CreditCard } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';

const serviceIcons: Record<string, React.ComponentType<{ size?: number; className?: string }>> = {
  ipi: Receipt,
  icms: Percent,
  ipva: Car,
  rodizio: CreditCard,
};

const serviceColors: Record<string, string> = {
  ipi: 'from-brand-600 to-brand-700',
  icms: 'from-brand-500 to-brand-600',
  ipva: 'from-accent to-accent-dark',
  rodizio: 'from-brand-700 to-brand-800',
};

export function ServicesSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('servicos');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    items: { id: string; title: string }[];
    description: string;
    highlight: string;
  };

  const updateItem = (itemId: string, title: string) => {
    const items = data.items.map((item) => (item.id === itemId ? { ...item, title } : item));
    updateSection('servicos', { items });
  };

  const paragraphs = data.description.split('\n\n');

  return (
    <section id="servicos" className="py-10 md:py-12 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('servicos', { title: v })}
          as="h2"
          className="section-title"
        />

        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8 max-w-4xl mx-auto">
          {data.items.map((item) => {
            const Icon = serviceIcons[item.id] || Receipt;
            const gradient = serviceColors[item.id] || 'from-brand-600 to-brand-700';
            return (
              <div
                key={item.id}
                className={`bg-gradient-to-br ${gradient} rounded-xl py-5 px-3 text-center shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all`}
              >
                <Icon size={22} className="text-white/90 mx-auto mb-2" />
                <EditableText
                  value={item.title}
                  onChange={(v) => updateItem(item.id, v)}
                  as="span"
                  className="text-white font-bold text-[10px] md:text-xs uppercase leading-tight block"
                />
              </div>
            );
          })}
        </div>

        <div className="max-w-4xl mx-auto space-y-4 bg-surface rounded-2xl p-5 md:p-6 border border-brand-100">
          {paragraphs.map((p, i) => (
            <EditableText
              key={i}
              value={p}
              onChange={(v) => {
                const newParagraphs = [...paragraphs];
                newParagraphs[i] = v;
                updateSection('servicos', { description: newParagraphs.join('\n\n') });
              }}
              as="p"
              className="text-text-secondary text-sm leading-relaxed text-justify"
              multiline
            />
          ))}
          <EditableText
            value={data.highlight}
            onChange={(v) => updateSection('servicos', { highlight: v })}
            as="p"
            className="text-accent-dark font-extrabold text-base text-center pt-2"
          />
        </div>
      </div>
    </section>
  );
}
