import { ArrowRight, LogIn, FileText, Bell, FolderOpen } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableCta } from '../cms/EditableCta';
import { useCms } from '../../context/CmsContext';

const featureIcons = [FileText, Bell, FolderOpen];

export function PortalPromoSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('portal-promo');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    subtitle: string;
    features: string[];
    loginCtaText: string;
    loginCtaLink: string;
    startCtaText: string;
    startCtaLink: string;
  };

  return (
    <section id="portal" className="py-10 md:py-12 bg-brand-800 text-white">
      <div className="max-w-6xl mx-auto px-4">
        <div className="grid lg:grid-cols-2 gap-8 items-center">
          <div>
            <EditableText
              value={data.title}
              onChange={(v) => updateSection('portal-promo', { title: v })}
              as="h2"
              className="font-display text-2xl md:text-3xl font-extrabold mb-3"
            />
            <EditableText
              value={data.subtitle}
              onChange={(v) => updateSection('portal-promo', { subtitle: v })}
              as="p"
              className="text-brand-100 text-sm leading-relaxed mb-6"
              multiline
            />
            <ul className="space-y-3 mb-6">
              {data.features.map((feature, i) => {
                const Icon = featureIcons[i % featureIcons.length];
                return (
                  <li key={i} className="flex items-center gap-3 text-sm text-brand-50">
                    <Icon size={18} className="text-accent flex-shrink-0" />
                    <EditableText
                      value={feature}
                      onChange={(v) => {
                        const features = [...data.features];
                        features[i] = v;
                        updateSection('portal-promo', { features });
                      }}
                      as="span"
                    />
                  </li>
                );
              })}
            </ul>
            <div className="flex flex-col sm:flex-row gap-3">
              <EditableCta
                text={data.loginCtaText}
                href={data.loginCtaLink}
                onTextChange={(v) => updateSection('portal-promo', { loginCtaText: v })}
                onHrefChange={(v) => updateSection('portal-promo', { loginCtaLink: v })}
                className="btn-secondary !border-white !text-white hover:!bg-white/10 text-sm inline-flex items-center justify-center gap-2"
              >
                <LogIn size={16} />
                {data.loginCtaText}
              </EditableCta>
              <EditableCta
                text={data.startCtaText}
                href={data.startCtaLink}
                onTextChange={(v) => updateSection('portal-promo', { startCtaText: v })}
                onHrefChange={(v) => updateSection('portal-promo', { startCtaLink: v })}
                className="btn-primary text-sm inline-flex items-center justify-center gap-2"
              >
                {data.startCtaText}
                <ArrowRight size={16} />
              </EditableCta>
            </div>
          </div>
          <div className="hidden lg:flex justify-center">
            <div className="bg-white/10 backdrop-blur rounded-2xl p-8 border border-white/20 max-w-sm w-full">
              <MonitorMockup />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

function MonitorMockup() {
  return (
    <div className="space-y-3">
      <div className="h-3 w-24 bg-white/30 rounded" />
      <div className="h-2 w-full bg-white/20 rounded" />
      <div className="h-2 w-4/5 bg-white/20 rounded" />
      <div className="grid grid-cols-3 gap-2 mt-4">
        {[1, 2, 3].map((n) => (
          <div key={n} className="h-16 bg-white/15 rounded-lg border border-white/10" />
        ))}
      </div>
      <div className="h-24 bg-white/10 rounded-lg border border-white/10 mt-2" />
    </div>
  );
}
