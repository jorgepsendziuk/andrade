import { ArrowRight } from 'lucide-react';
import {
  Brain, Accessibility, Heart, Eye, Ear, Activity, Bone, HelpCircle,
} from 'lucide-react';
import { EditableText } from '../cms/EditableText';
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
    <section id="tenho-direito" className="py-10 md:py-12 bg-surface">
      <div className="max-w-6xl mx-auto px-4">
        <div className="grid lg:grid-cols-[38%_1fr] gap-6 lg:gap-10 items-start">
          <div className="bg-white rounded-2xl p-5 md:p-6 shadow-[var(--shadow-card)] border border-brand-100">
            <span className="inline-block bg-accent/15 text-accent-dark font-bold text-[10px] uppercase tracking-widest px-2.5 py-1 rounded-full mb-3">
              Tenho direito?
            </span>
            <EditableText
              value={data.title}
              onChange={(v) => updateSection('tenho-direito', { title: v })}
              as="h2"
              className="font-display text-xl md:text-2xl font-extrabold text-brand-800 leading-snug mb-3"
            />
            <EditableText
              value={data.subtitle}
              onChange={(v) => updateSection('tenho-direito', { subtitle: v })}
              as="p"
              className="text-text-secondary text-xs md:text-sm mb-5 leading-relaxed"
              multiline
            />
            <a href={data.ctaLink} className="btn-primary text-xs">
              {data.ctaText}
              <ArrowRight size={16} />
            </a>
          </div>

          <div>
            <div className="grid grid-cols-4 gap-2 md:gap-2.5">
              {data.cards.map((card) => {
                const Icon = iconMap[card.icon] || HelpCircle;
                return (
                  <a
                    key={card.id}
                    href={`/isencao-pcd/${card.slug}`}
                    title={`Isenção PCD para ${card.label} — Andrade Isenções`}
                    className="card-vivid p-3 text-center group hover:border-accent/40 hover:-translate-y-0.5"
                  >
                    <div className="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center mx-auto mb-2 group-hover:bg-accent transition-colors shadow-md">
                      <Icon size={18} className="text-white" />
                    </div>
                    <span className="text-[10px] font-bold text-brand-800 leading-tight block">
                      {card.label}
                    </span>
                  </a>
                );
              })}
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
