import { ArrowRight, ClipboardList, FileCheck, BadgeCheck, CarFront, Sparkles, Stethoscope, Landmark, Truck } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableImage } from '../cms/EditableImage';
import { EditableCta } from '../cms/EditableCta';
import { useCms } from '../../context/CmsContext';

const stepIcons = [ClipboardList, FileCheck, BadgeCheck, Stethoscope, Landmark, CarFront, Truck, Sparkles];

function gridClass(count: number): string {
  if (count <= 4) return 'grid-cols-2 md:grid-cols-4';
  if (count <= 6) return 'grid-cols-2 md:grid-cols-3 lg:grid-cols-6';
  return 'grid-cols-2 md:grid-cols-4';
}

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
          <EditableCta
            text={data.ctaText}
            href={data.ctaLink}
            onTextChange={(v) => updateSection('como-funciona', { ctaText: v })}
            onHrefChange={(v) => updateSection('como-funciona', { ctaLink: v })}
            className="btn-primary text-xs flex-shrink-0 self-start md:self-auto inline-flex items-center gap-2"
          >
            {data.ctaText}
            <ArrowRight size={16} strokeWidth={2.5} />
          </EditableCta>
        </div>

        <div className={`grid ${gridClass(data.steps.length)} gap-3 md:gap-4`}>
          {data.steps.map((step, i) => {
            const Icon = stepIcons[i % stepIcons.length];
            return (
              <div key={step.id} className="text-center group relative">
                <div
                  className="w-12 h-12 md:w-14 md:h-14 rounded-full bg-white flex items-center justify-center mx-auto shadow-lg border border-brand-100 group-hover:scale-105 transition-transform mb-2"
                  aria-hidden
                >
                  <Icon size={22} className="text-brand-600" strokeWidth={2} />
                </div>
                <span className="block font-display font-extrabold text-white/90 text-base md:text-lg leading-none mb-1 tracking-wider">
                  {step.number}
                </span>
                <EditableText
                  value={step.title}
                  onChange={(v) => {
                    const steps = data.steps.map((s) => (s.id === step.id ? { ...s, title: v } : s));
                    updateSection('como-funciona', { steps });
                  }}
                  as="h3"
                  className="text-white font-bold text-[10px] md:text-xs leading-tight px-1"
                />
                <EditableText
                  value={step.description}
                  onChange={(v) => {
                    const steps = data.steps.map((s) => (s.id === step.id ? { ...s, description: v } : s));
                    updateSection('como-funciona', { steps });
                  }}
                  as="p"
                  className="text-brand-100 text-[10px] leading-snug mt-1 hidden md:block px-1"
                />
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
