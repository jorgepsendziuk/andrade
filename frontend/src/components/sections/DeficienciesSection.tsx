import { CheckCircle } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';

export function DeficienciesSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('deficiencias');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    backgroundImage: string;
    columns: string[][];
  };

  const updateItem = (colIndex: number, itemIndex: number, value: string) => {
    const columns = data.columns.map((col, ci) =>
      ci === colIndex ? col.map((item, ii) => (ii === itemIndex ? value : item)) : col
    );
    updateSection('deficiencias', { columns });
  };

  return (
    <section id="deficiencias" className="relative py-16 md:py-20">
      <img
        src={data.backgroundImage}
        alt=""
        className="absolute inset-0 w-full h-full object-cover"
      />
      <div className="absolute inset-0 bg-black/20" />

      <div className="relative max-w-5xl mx-auto px-4">
        <div className="bg-white/95 backdrop-blur-sm rounded-lg shadow-xl p-6 md:p-10">
          <EditableText
            value={data.title}
            onChange={(v) => updateSection('deficiencias', { title: v })}
            as="h2"
            className="section-title mb-8"
          />

          <div className="grid md:grid-cols-3 gap-6">
            {data.columns.map((column, colIndex) => (
              <ul key={colIndex} className="space-y-2">
                {column.map((item, itemIndex) => (
                  <li key={itemIndex} className="flex items-start gap-2">
                    <CheckCircle className="text-brand-500 flex-shrink-0 mt-0.5" size={16} />
                    <EditableText
                      value={item}
                      onChange={(v) => updateItem(colIndex, itemIndex, v)}
                      as="span"
                      className="text-slate-700 text-sm"
                    />
                  </li>
                ))}
              </ul>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
