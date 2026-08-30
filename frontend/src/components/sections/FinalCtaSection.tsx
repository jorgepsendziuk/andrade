import { EditableText } from '../cms/EditableText';
import { EditableImage } from '../cms/EditableImage';
import { useCms } from '../../context/CmsContext';
import { WhatsAppLink } from '../ui/WhatsAppLink';
import { ArrowRight } from 'lucide-react';

export function FinalCtaSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('final-cta');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    subtitle: string;
    ctaText: string;
    ctaSubtext: string;
    backgroundImage: string;
  };

  return (
    <section className="relative py-10 md:py-12 overflow-hidden bg-brand-800">
      <EditableImage
        src={data.backgroundImage}
        alt=""
        onChange={(url) => updateSection('final-cta', { backgroundImage: url })}
        className="absolute inset-0 w-full h-full object-cover opacity-30"
        loading="lazy"
        wrapperClassName="absolute inset-0"
        aria-hidden
      />
      <div className="absolute inset-0 bg-gradient-to-r from-brand-900/90 via-brand-800/85 to-brand-900/90" />

      <div className="relative max-w-6xl mx-auto px-4">
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-5">
          <div className="max-w-lg">
            <EditableText
              value={data.title}
              onChange={(v) => updateSection('final-cta', { title: v })}
              as="h2"
              className="font-display text-xl md:text-2xl font-bold text-white uppercase leading-snug mb-2"
            />
            <EditableText
              value={data.subtitle}
              onChange={(v) => updateSection('final-cta', { subtitle: v })}
              as="p"
              className="text-brand-100 text-xs md:text-sm"
              multiline
            />
          </div>
          <div className="flex-shrink-0 text-center md:text-right">
            <WhatsAppLink className="btn-primary text-xs md:text-sm w-full md:w-auto justify-center">
              {data.ctaText}
              <ArrowRight size={18} />
            </WhatsAppLink>
            <EditableText
              value={data.ctaSubtext}
              onChange={(v) => updateSection('final-cta', { ctaSubtext: v })}
              as="p"
              className="text-brand-200 text-[10px] mt-2"
            />
          </div>
        </div>
      </div>
    </section>
  );
}
