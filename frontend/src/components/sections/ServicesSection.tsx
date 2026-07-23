import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';

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
    <section id="servicos" className="py-12 md:py-16 bg-brand-50">
      <div className="max-w-6xl mx-auto px-4">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('servicos', { title: v })}
          as="h2"
          className="section-title"
        />

        {/* Grid 2x2 de serviços — estilo original */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 mb-10 max-w-4xl mx-auto">
          {data.items.map((item) => (
            <div
              key={item.id}
              className="bg-brand-100 border border-brand-200 rounded-lg py-5 px-3 text-center hover:bg-brand-200 transition-colors"
            >
              <EditableText
                value={item.title}
                onChange={(v) => updateItem(item.id, v)}
                as="span"
                className="text-brand-700 font-bold text-xs md:text-sm uppercase leading-tight block"
              />
            </div>
          ))}
        </div>

        <div className="max-w-4xl mx-auto space-y-4">
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
              className="text-slate-600 text-sm leading-relaxed text-justify"
              multiline
            />
          ))}
          <EditableText
            value={data.highlight}
            onChange={(v) => updateSection('servicos', { highlight: v })}
            as="p"
            className="text-brand-600 font-bold text-base text-center pt-4"
          />
        </div>
      </div>
    </section>
  );
}
