import { ArrowRight, Check } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableCta } from '../cms/EditableCta';
import { useCms } from '../../context/CmsContext';

export function PremiumAnalysisSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('analise-premium');
  if (!section?.enabled) return null;

  const data = section.data as {
    badge?: string;
    title: string;
    subtitle: string;
    intro: string;
    checklist: string[];
    ctaText: string;
    ctaLink: string;
  };

  return (
    <section id="analise-premium" className="py-10 md:py-14 bg-gradient-to-b from-brand-50 to-white">
      <div className="max-w-6xl mx-auto px-4">
        <div className="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
          <div>
            {data.badge && (
              <span className="inline-block bg-accent/15 text-accent-dark font-bold text-sm uppercase tracking-widest px-2.5 py-1 rounded-full mb-3">
                <EditableText
                  value={data.badge}
                  onChange={(v) => updateSection('analise-premium', { badge: v })}
                  as="span"
                />
              </span>
            )}
            <EditableText
              value={data.title}
              onChange={(v) => updateSection('analise-premium', { title: v })}
              as="h2"
              className="font-display text-2xl md:text-3xl font-extrabold text-brand-800 leading-snug mb-4"
            />
            <EditableText
              value={data.subtitle}
              onChange={(v) => updateSection('analise-premium', { subtitle: v })}
              as="p"
              className="text-text-secondary text-sm md:text-base leading-relaxed mb-4"
              multiline
            />
            <EditableText
              value={data.intro}
              onChange={(v) => updateSection('analise-premium', { intro: v })}
              as="p"
              className="text-text-secondary text-sm leading-relaxed"
              multiline
            />
          </div>

          <div className="bg-white rounded-2xl p-6 md:p-8 shadow-[var(--shadow-card)] border border-brand-100">
            <p className="font-semibold text-brand-800 text-sm mb-4">Na análise avaliamos:</p>
            <ul className="space-y-2.5 mb-6">
              {data.checklist.map((item, i) => (
                <li key={i} className="flex items-start gap-2.5 text-sm text-text-secondary">
                  <Check size={18} className="text-accent flex-shrink-0 mt-0.5" strokeWidth={2.5} />
                  <EditableText
                    value={item}
                    onChange={(v) => {
                      const checklist = [...data.checklist];
                      checklist[i] = v;
                      updateSection('analise-premium', { checklist });
                    }}
                    as="span"
                    className="flex-1"
                  />
                </li>
              ))}
            </ul>
            <EditableCta
              text={data.ctaText}
              href={data.ctaLink}
              onTextChange={(v) => updateSection('analise-premium', { ctaText: v })}
              onHrefChange={(v) => updateSection('analise-premium', { ctaLink: v })}
              className="btn-primary w-full justify-center text-sm inline-flex items-center gap-2"
            >
              {data.ctaText}
              <ArrowRight size={18} />
            </EditableCta>
          </div>
        </div>
      </div>
    </section>
  );
}
