import {
  HeartHandshake,
  ShieldCheck,
  Eye,
  Route,
  MonitorSmartphone,
  BookOpen,
} from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';

const iconMap: Record<string, React.ComponentType<{ size?: number; className?: string }>> = {
  personalizado: HeartHandshake,
  experiencia: ShieldCheck,
  transparencia: Eye,
  acompanhamento: Route,
  portal: MonitorSmartphone,
  informacao: BookOpen,
};

export function WhyAndradeSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('por-que-andrade');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    subtitle?: string;
    items: Array<{ id: string; title: string; description: string; icon: string }>;
    authorityText?: string;
    authorityLinkText?: string;
    authorityLink?: string;
  };

  const updateItem = (id: string, patch: Partial<{ title: string; description: string }>) => {
    const items = data.items.map((item) => (item.id === id ? { ...item, ...patch } : item));
    updateSection('por-que-andrade', { items });
  };

  return (
    <section id="por-que-andrade" className="py-10 md:py-12 bg-surface">
      <div className="max-w-6xl mx-auto px-4">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('por-que-andrade', { title: v })}
          as="h2"
          className="section-title text-center mb-2"
        />
        {data.subtitle && (
          <EditableText
            value={data.subtitle}
            onChange={(v) => updateSection('por-que-andrade', { subtitle: v })}
            as="p"
            className="text-text-secondary text-sm text-center mb-8 max-w-2xl mx-auto"
          />
        )}

        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
          {data.items.map((item) => {
            const Icon = iconMap[item.icon] || ShieldCheck;
            return (
              <div
                key={item.id}
                className="bg-white rounded-xl p-5 border border-brand-100 shadow-sm hover:shadow-md transition-shadow"
              >
                <div className="w-10 h-10 rounded-lg bg-brand-100 flex items-center justify-center mb-3">
                  <Icon size={20} className="text-brand-600" />
                </div>
                <EditableText
                  value={item.title}
                  onChange={(v) => updateItem(item.id, { title: v })}
                  as="h3"
                  className="font-bold text-brand-800 text-sm mb-2"
                />
                <EditableText
                  value={item.description}
                  onChange={(v) => updateItem(item.id, { description: v })}
                  as="p"
                  className="text-text-secondary text-xs leading-relaxed"
                  multiline
                />
              </div>
            );
          })}
        </div>

        {data.authorityText && (
          <div className="max-w-3xl mx-auto text-center bg-white rounded-xl p-6 border border-brand-100">
            <EditableText
              value={data.authorityText}
              onChange={(v) => updateSection('por-que-andrade', { authorityText: v })}
              as="p"
              className="text-text-secondary text-sm leading-relaxed mb-3"
              multiline
            />
            {data.authorityLinkText && data.authorityLink && (
              <a
                href={data.authorityLink}
                className="text-brand-600 hover:text-accent font-semibold text-sm inline-flex items-center gap-1"
              >
                {data.authorityLinkText} →
              </a>
            )}
          </div>
        )}
      </div>
    </section>
  );
}
