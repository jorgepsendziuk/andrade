import { Users, Star, Shield, Handshake } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';

const icons = [Users, Star, Shield, Handshake];

export function TrustBarSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('trust-bar');
  if (!section?.enabled) return null;

  const data = section.data as {
    items: Array<{ id: string; value: string; label: string }>;
  };

  return (
    <section className="bg-white border-b border-brand-100 py-6 md:py-8 shadow-sm">
      <div className="max-w-6xl mx-auto px-4">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
          {data.items.map((item, i) => {
            const Icon = icons[i % icons.length];
            return (
              <div
                key={item.id}
                className="flex flex-col items-center text-center gap-2 p-3 rounded-xl hover:bg-surface transition-colors"
              >
                <div className="w-12 h-12 rounded-full bg-brand-50 flex items-center justify-center">
                  <Icon size={24} className="text-brand-600" />
                </div>
                <EditableText
                  value={item.value}
                  onChange={(v) => {
                    const items = data.items.map((it) => (it.id === item.id ? { ...it, value: v } : it));
                    updateSection('trust-bar', { items });
                  }}
                  as="p"
                  className="font-display font-bold text-lg md:text-xl text-brand-800"
                />
                <EditableText
                  value={item.label}
                  onChange={(v) => {
                    const items = data.items.map((it) => (it.id === item.id ? { ...it, label: v } : it));
                    updateSection('trust-bar', { items });
                  }}
                  as="p"
                  className="text-text-secondary text-xs md:text-sm"
                />
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
