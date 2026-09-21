import { ArrowRight, Info } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableCta } from '../cms/EditableCta';
import { useCms } from '../../context/CmsContext';

export function EligibilityIntroSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('quem-pode-direito');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    subtitle: string;
    body: string;
    disclaimer: string;
    ctaText: string;
    ctaLink: string;
  };

  return (
    <section id="quem-pode-direito" className="py-10 md:py-12 bg-white">
      <div className="max-w-4xl mx-auto px-4 text-center">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('quem-pode-direito', { title: v })}
          as="h2"
          className="section-title mb-4"
        />
        <EditableText
          value={data.subtitle}
          onChange={(v) => updateSection('quem-pode-direito', { subtitle: v })}
          as="p"
          className="text-text-secondary text-sm md:text-base leading-relaxed mb-6 max-w-2xl mx-auto"
          multiline
        />
        <EditableText
          value={data.body}
          onChange={(v) => updateSection('quem-pode-direito', { body: v })}
          as="p"
          className="text-text-secondary text-sm leading-relaxed mb-6 text-left bg-surface rounded-xl p-5 border border-brand-100"
          multiline
        />
        <div className="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 text-left">
          <Info size={20} className="text-amber-700 flex-shrink-0 mt-0.5" />
          <EditableText
            value={data.disclaimer}
            onChange={(v) => updateSection('quem-pode-direito', { disclaimer: v })}
            as="p"
            className="text-amber-900 text-sm md:text-base leading-relaxed"
            multiline
          />
        </div>
        <EditableCta
          text={data.ctaText}
          href={data.ctaLink}
          onTextChange={(v) => updateSection('quem-pode-direito', { ctaText: v })}
          onHrefChange={(v) => updateSection('quem-pode-direito', { ctaLink: v })}
          className="btn-secondary text-sm inline-flex items-center gap-2"
        >
          {data.ctaText}
          <ArrowRight size={16} />
        </EditableCta>
      </div>
    </section>
  );
}
