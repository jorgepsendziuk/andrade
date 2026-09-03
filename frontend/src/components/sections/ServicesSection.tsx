import { Link } from 'react-router-dom';
import { Receipt, Percent, Car } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';

const serviceIcons: Record<string, React.ComponentType<{ size?: number; className?: string }>> = {
  ipi: Receipt,
  icms: Percent,
  ipva: Car,
};

const serviceColors: Record<string, string> = {
  ipi: 'from-brand-600 to-brand-700',
  icms: 'from-brand-500 to-brand-600',
  ipva: 'from-accent to-accent-dark',
};

export function ServicesSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('servicos');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    items: { id: string; title: string; description: string; link?: string }[];
    disclaimer: string;
  };

  const updateItem = (itemId: string, patch: Partial<{ title: string; description: string }>) => {
    const items = data.items.map((item) => (item.id === itemId ? { ...item, ...patch } : item));
    updateSection('servicos', { items });
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

        <div className="grid md:grid-cols-3 gap-4 mb-8">
          {data.items.map((item) => {
            const Icon = serviceIcons[item.id] || Receipt;
            const gradient = serviceColors[item.id] || 'from-brand-600 to-brand-700';
            const card = (
              <div
                className={`bg-gradient-to-br ${gradient} rounded-xl p-5 text-white shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all h-full flex flex-col`}
              >
                <Icon size={28} className="text-white/90 mb-3" />
                <EditableText
                  value={item.title}
                  onChange={(v) => updateItem(item.id, { title: v })}
                  as="h3"
                  className="font-bold text-base mb-2"
                />
                <EditableText
                  value={item.description}
                  onChange={(v) => updateItem(item.id, { description: v })}
                  as="p"
                  className="text-white/90 text-xs leading-relaxed flex-1"
                  multiline
                />
              </div>
            );
            return item.link ? (
              <Link key={item.id} to={item.link} className="block h-full">
                {card}
              </Link>
            ) : (
              <div key={item.id}>{card}</div>
            );
          })}
        </div>

        <div className="max-w-3xl mx-auto bg-amber-50 border border-amber-200 rounded-xl p-4 md:p-5">
          <EditableText
            value={data.disclaimer}
            onChange={(v) => updateSection('servicos', { disclaimer: v })}
            as="p"
            className="text-amber-900 text-xs md:text-sm leading-relaxed text-center"
            multiline
          />
        </div>
      </div>
    </section>
  );
}
