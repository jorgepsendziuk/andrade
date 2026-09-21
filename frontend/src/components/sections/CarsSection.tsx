import { ArrowRight } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableImage } from '../cms/EditableImage';
import { useCms } from '../../context/CmsContext';
import { WhatsAppLink } from '../ui/WhatsAppLink';
import { getBrandLogo } from '../../lib/brandLogos';

export function CarsSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('carros');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    subtitle: string;
    brands: Array<{ id: string; name: string; logo?: string }>;
    promoTitle: string;
    promoText: string;
    promoImage: string;
    ctaText: string;
    promoBadge?: string;
  };

  return (
    <section id="carros" className="py-10 md:py-12 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2 mb-6">
          <EditableText
            value={data.title}
            onChange={(v) => updateSection('carros', { title: v })}
            as="h2"
            className="font-display text-xl md:text-2xl font-extrabold text-brand-800"
          />
          <EditableText
            value={data.subtitle}
            onChange={(v) => updateSection('carros', { subtitle: v })}
            as="p"
            className="text-text-secondary text-sm md:text-base font-medium"
          />
        </div>

        <div className="grid lg:grid-cols-3 gap-5 lg:gap-6">
          <div className="lg:col-span-2 grid grid-cols-2 sm:grid-cols-4 gap-3 md:gap-4">
            {data.brands.map((brand) => {
              const logo = brand.logo || getBrandLogo(brand.name);
              return (
                <div
                  key={brand.id}
                  className="card-vivid px-3 py-4 md:px-4 md:py-5 flex flex-col items-center justify-center gap-2 md:gap-2.5 aspect-[5/4] sm:aspect-auto sm:min-h-[128px] md:min-h-[140px] hover:border-brand-300 hover:-translate-y-0.5 bg-gradient-to-b from-white to-brand-50/40"
                >
                  {logo ? (
                    <div className="flex-1 flex items-center justify-center w-full min-h-[52px] md:min-h-[60px]">
                      <img
                        src={logo}
                        alt=""
                        aria-hidden
                        className="h-11 sm:h-12 md:h-14 w-full max-w-[120px] object-contain"
                        loading="lazy"
                      />
                    </div>
                  ) : null}
                  <EditableText
                    value={brand.name}
                    onChange={(v) => {
                      const brands = data.brands.map((b) =>
                        b.id === brand.id ? { ...b, name: v } : b
                      );
                      updateSection('carros', { brands });
                    }}
                    as="p"
                    className="font-display font-bold text-brand-800 text-sm md:text-base text-center leading-tight shrink-0"
                  />
                </div>
              );
            })}
          </div>

          <div className="card-vivid overflow-hidden flex flex-col border-2 border-brand-100">
            <div className="relative">
              <EditableImage
                src={data.promoImage}
                alt="Veículo PCD"
                onChange={(url) => updateSection('carros', { promoImage: url })}
                className="w-full h-40 md:h-48 object-cover"
                loading="lazy"
                wrapperClassName="w-full"
              />
              <div className="absolute top-3 left-3 bg-brand-600 text-white text-[11px] font-bold uppercase px-3 py-1.5 rounded shadow-md">
                <EditableText
                  value={data.promoBadge || 'Condições especiais'}
                  onChange={(v) => updateSection('carros', { promoBadge: v })}
                  as="span"
                />
              </div>
            </div>
            <div className="p-5 md:p-6 flex-1 flex flex-col bg-gradient-to-b from-white to-brand-50">
              <EditableText
                value={data.promoTitle}
                onChange={(v) => updateSection('carros', { promoTitle: v })}
                as="h3"
                className="font-display font-extrabold text-brand-800 text-base md:text-lg mb-2"
              />
              <EditableText
                value={data.promoText}
                onChange={(v) => updateSection('carros', { promoText: v })}
                as="p"
                className="text-text-secondary text-sm md:text-base mb-5 flex-1 leading-relaxed"
              />
              <WhatsAppLink
                message="Olá! Quero solicitar minha cotação personalizada de veículo PCD."
                className="btn-primary-sm w-full py-2.5"
              >
                <EditableText
                  value={data.ctaText}
                  onChange={(v) => updateSection('carros', { ctaText: v })}
                  as="span"
                />
                <ArrowRight size={16} />
              </WhatsAppLink>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
