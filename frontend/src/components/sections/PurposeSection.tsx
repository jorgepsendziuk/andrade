import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';

export function PurposeSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('proposito');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    paragraphs: string[];
  };

  return (
    <section id="proposito" className="py-12 md:py-16 bg-white">
      <div className="max-w-3xl mx-auto px-4 text-center">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('proposito', { title: v })}
          as="h2"
          className="section-title"
        />
        <div className="space-y-4">
          {data.paragraphs.map((p, i) => (
            <EditableText
              key={i}
              value={p}
              onChange={(v) => {
                const paragraphs = [...data.paragraphs];
                paragraphs[i] = v;
                updateSection('proposito', { paragraphs });
              }}
              as="p"
              className="text-slate-600 text-sm leading-relaxed text-justify md:text-center"
              multiline
            />
          ))}
        </div>
      </div>
    </section>
  );
}
