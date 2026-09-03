import { useState } from 'react';
import { ChevronDown } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';
import { JsonLd } from '../seo/JsonLd';
import { buildFaqSchema } from '../../lib/seo';

export function FaqSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('faq');
  const [openId, setOpenId] = useState<string | null>(null);

  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    subtitle?: string;
    items: Array<{ id: string; question: string; answer: string }>;
  };

  const updateItem = (id: string, patch: Partial<{ question: string; answer: string }>) => {
    const items = data.items.map((item) => (item.id === id ? { ...item, ...patch } : item));
    updateSection('faq', { items });
  };

  return (
    <section id="faq" className="py-10 md:py-12 bg-surface">
      <JsonLd data={buildFaqSchema(data.items.map((i) => ({ question: i.question, answer: i.answer })))} />
      <div className="max-w-3xl mx-auto px-4">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('faq', { title: v })}
          as="h2"
          className="section-title text-center mb-2"
        />
        {data.subtitle && (
          <EditableText
            value={data.subtitle}
            onChange={(v) => updateSection('faq', { subtitle: v })}
            as="p"
            className="text-text-secondary text-sm text-center mb-8"
          />
        )}

        <div className="space-y-2">
          {data.items.map((item) => {
            const isOpen = openId === item.id;
            return (
              <div key={item.id} className="bg-white rounded-xl border border-brand-100 overflow-hidden">
                <button
                  type="button"
                  onClick={() => setOpenId(isOpen ? null : item.id)}
                  className="w-full flex items-center justify-between gap-4 p-4 text-left hover:bg-brand-50/50 transition-colors"
                  aria-expanded={isOpen}
                >
                  <EditableText
                    value={item.question}
                    onChange={(v) => updateItem(item.id, { question: v })}
                    as="span"
                    className="font-semibold text-brand-800 text-sm"
                  />
                  <ChevronDown
                    size={18}
                    className={`text-brand-500 flex-shrink-0 transition-transform ${isOpen ? 'rotate-180' : ''}`}
                  />
                </button>
                {isOpen && (
                  <div className="px-4 pb-4">
                    <EditableText
                      value={item.answer}
                      onChange={(v) => updateItem(item.id, { answer: v })}
                      as="p"
                      className="text-text-secondary text-sm leading-relaxed"
                      multiline
                    />
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}
