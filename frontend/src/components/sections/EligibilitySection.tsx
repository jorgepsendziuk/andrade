import { ArrowRight } from 'lucide-react';
import {
  Brain, Accessibility, Heart, Eye, Ear, Activity, Bone, HelpCircle,
} from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableCta } from '../cms/EditableCta';
import { useCms } from '../../context/CmsContext';

const iconMap: Record<string, React.ComponentType<{ size?: number; className?: string }>> = {
  autismo: Brain,
  amputacao: Accessibility,
  avc: Heart,
  'visao-monocular': Eye,
  'deficiencia-visual': Eye,
  'deficiencia-auditiva': Ear,
  'esclerose-multipla': Activity,
  mastectomia: Heart,
  'doencas-neurologicas': Brain,
  artrodese: Bone,
  'deficiencia-fisica': Accessibility,
  'hernia-de-disco': Bone,
  fibromialgia: Activity,
  'artrite-reumatoide': Bone,
  'tunel-do-carpo': Bone,
  'condromalacia-patelar': Bone,
  outras: HelpCircle,
};

export function EligibilitySection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('tenho-direito');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    subtitle: string;
    ctaText: string;
    ctaLink: string;
    cards: Array<{ id: string; label: string; slug: string; icon: string }>;
  };

  return (
    <section id="condicoes" className="py-10 md:py-12 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <div className="text-center mb-8 max-w-2xl mx-auto">
          <EditableText
            value={data.title}
            onChange={(v) => updateSection('tenho-direito', { title: v })}
            as="h2"
            className="section-title mb-3"
          />
          <EditableText
            value={data.subtitle}
            onChange={(v) => updateSection('tenho-direito', { subtitle: v })}
            as="p"
            className="text-text-secondary text-sm leading-relaxed"
            multiline
          />
        </div>

        <div className="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2 md:gap-3 mb-8">
          {data.cards.map((card) => {
            const Icon = iconMap[card.icon] || HelpCircle;
            return (
              <a
                key={card.id}
                href={`/isencao-pcd/${card.slug}`}
                title={`Isenção PCD para ${card.label} — Andrade Isenções`}
                className="card-vivid p-3 text-center group hover:border-accent/40 hover:-translate-y-0.5 bg-surface"
              >
                <div className="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center mx-auto mb-2 group-hover:bg-accent transition-colors shadow-md">
                  <Icon size={18} className="text-white" />
                </div>
                <span className="text-sm font-bold text-brand-800 leading-tight block">
                  <EditableText
                    value={card.label}
                    onChange={(v) => {
                      const cards = data.cards.map((c) =>
                        c.id === card.id ? { ...c, label: v } : c
                      );
                      updateSection('tenho-direito', { cards });
                    }}
                    as="span"
                  />
                </span>
              </a>
            );
          })}
        </div>

        <div className="text-center">
          <EditableCta
            text={data.ctaText}
            href={data.ctaLink}
            onTextChange={(v) => updateSection('tenho-direito', { ctaText: v })}
            onHrefChange={(v) => updateSection('tenho-direito', { ctaLink: v })}
            className="btn-secondary text-sm inline-flex items-center gap-2"
          >
            {data.ctaText}
            <ArrowRight size={16} />
          </EditableCta>
        </div>
      </div>
    </section>
  );
}
