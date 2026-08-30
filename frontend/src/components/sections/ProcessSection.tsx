import { ArrowRight, ClipboardList, FileCheck, BadgeCheck, CarFront, Sparkles } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableImage } from '../cms/EditableImage';
import { useCms } from '../../context/CmsContext';

const stepIcons = [ClipboardList, FileCheck, BadgeCheck, CarFront, Sparkles];

export function ProcessSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('como-funciona');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    subtitle: string;
    steps: Array<{ id: string; number: string; title: string; description: string }>;
    ctaText: string;
    ctaLink: string;
    backgroundImage: string;
  };

  return (
    <section id="como-funciona" className="relative py-10 md:py-12 overflow-hidden bg-brand-800">
      <EditableImage
        src={data.backgroundImage}
        alt=""
        onChange={(url) => updateSection('como-funciona', { backgroundImage: url })}
        className="absolute inset-0 w-full h-full object-cover opacity-35"
        loading="lazy"
        wrapperClassName="absolute inset-0"
        aria-hidden
      />
      <div className="absolute inset-0 bg-gradient-to-r from-brand-900/92 via-brand-800/88 to-brand-900/80" />

      <div className="relative max-w-6xl mx-auto px-4">
        <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
          <div>
            <EditableText
              value={data.title}
              onChange={(v) => updateSection('como-funciona', { title: v })}
              as="h2"
              className="font-display text-xl md:text-2xl font-extrabold text-white uppercase mb-1"
            />
            <EditableText
              value={data.subtitle}
              onChange={(v) => updateSection('como-funciona', { subtitle: v })}
              as="p"
              className="text-brand-100 text-xs md:text-sm"
            />
          </div>
          <a href={data.ctaLink} className="btn-primary text-xs flex-shrink-0 self-start md:self-auto">
            {data.ctaText}
            <ArrowRight size={16} strokeWidth={2.5} />
          </a>
        </div>

        <div className="grid grid-cols-5 gap-2 md:gap-4">
          {data.steps.map((step, i) => {
            const Icon = stepIcons[i % stepIcons.length];
            return (
              <div key={step.id} className="text-center group relative">
                {i < data.steps.length - 1 && (
                  <div
                    className="hidden md:block absolute top-7 left-[calc(50%+2rem)] right-0 h-px bg-white/25"
                    aria-hidden
                  />
                )}
                <div
                  className="w-14 h-14 md:w-16 md:h-16 rounded-full bg-white flex items-center justify-center mx-auto shadow-lg border border-brand-100 group-hover:scale-105 transition-transform mb-3"
                  aria-hidden
                >
                  <Icon size={26} className="text-brand-600 md:w-7 md:h-7" strokeWidth={2} />
                </div>
                <span className="block font-display font-extrabold text-white/90 text-lg md:text-xl leading-none mb-1.5 tracking-wider">
                  {step.number}
                </span>
                <EditableText
                  value={step.title}
                  onChange={(v) => {
                    const steps = data.steps.map((s) => (s.id === step.id ? { ...s, title: v } : s));
                    updateSection('como-funciona', { steps });
                  }}
                  as="h3"
                  className="text-white font-bold text-xs md:text-sm leading-tight"
                />
                <EditableText
                  value={step.description}
                  onChange={(v) => {
                    const steps = data.steps.map((s) => (s.id === step.id ? { ...s, description: v } : s));
                    updateSection('como-funciona', { steps });
                  }}
                  as="p"
                  className="text-brand-100 text-xs leading-snug mt-1 sr-only"
                />
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
