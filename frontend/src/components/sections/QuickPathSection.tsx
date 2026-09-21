import { ArrowRight } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';

export function QuickPathSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('quick-path');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    steps: Array<{ id: string; number: string; title: string; description: string }>;
    ctaText: string;
    ctaLink: string;
  };

  return (
    <section className="py-10 md:py-14 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('quick-path', { title: v })}
          as="h2"
          className="section-title"
        />

        <div className="grid md:grid-cols-3 gap-4 md:gap-6 mb-8">
          {data.steps.map((step) => (
            <div key={step.id} className="section-box p-5 md:p-6 text-center">
              <span className="font-display text-3xl font-extrabold text-accent">{step.number}</span>
              <EditableText
                value={step.title}
                onChange={(v) => {
                  const steps = data.steps.map((s) => (s.id === step.id ? { ...s, title: v } : s));
                  updateSection('quick-path', { steps });
                }}
                as="h3"
                className="font-display font-bold text-brand-800 mt-2 mb-2 text-sm md:text-base"
              />
              <EditableText
                value={step.description}
                onChange={(v) => {
                  const steps = data.steps.map((s) => (s.id === step.id ? { ...s, description: v } : s));
                  updateSection('quick-path', { steps });
                }}
                as="p"
                className="text-text-secondary text-sm md:text-base"
              />
            </div>
          ))}
        </div>

        <div className="text-center">
          <a href={data.ctaLink} className="btn-primary">
            {data.ctaText}
            <ArrowRight size={18} />
          </a>
        </div>
      </div>
    </section>
  );
}
