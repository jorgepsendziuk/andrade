import { ShieldCheck, Users, Shield, Handshake } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableImage } from '../cms/EditableImage';
import { useCms } from '../../context/CmsContext';
import { WhatsAppLink } from '../ui/WhatsAppLink';
import { GoogleLogo } from '../ui/GoogleLogo';
import { HeroBackgroundSlider } from '../ui/HeroBackgroundSlider';
import { CtaButton } from '../ui/CtaButton';

const trustLucideIcons = [Users, Shield, Handshake];
const trustLucideIndex = [0, 2, 3];

const trustIconStyles = ['icon-circle-blue', '', 'icon-circle-green', 'icon-circle-blue'];

function TrustTextLabel({ text, className }: { text: string; className?: string }) {
  const lines = text.split('\n');
  return (
    <p className={className}>
      {lines.map((line, i) => (
        <span key={i}>
          {i > 0 && <br />}
          {line}
        </span>
      ))}
    </p>
  );
}

export function HeroCommercialSection() {
  const { getSection, updateSection, isEditing } = useCms();
  const section = getSection('hero-commercial');
  const trustSection = getSection('trust-bar');
  if (!section?.enabled) return null;

  const data = section.data as {
    eyebrow: string;
    headline: string;
    subheadline: string;
    tagline: string;
    insert?: string;
    image: string;
    backgroundSlides?: string[];
    primaryCta: string;
    primaryCtaLink: string;
    secondaryCta: string;
    secondaryCtaLink: string;
  };

  const trustItems =
    (trustSection?.data as { items?: Array<{ id: string; value: string; label: string }> })?.items ?? [];

  const slides =
    data.backgroundSlides?.length ? data.backgroundSlides : data.image ? [data.image] : [];

  const updateSlide = (index: number, url: string) => {
    const next = [...slides];
    next[index] = url;
    updateSection('hero-commercial', {
      backgroundSlides: next,
      image: next[0] ?? data.image,
    });
  };

  return (
    <section id="inicio" className="relative">
      <div className="relative min-h-[440px] md:min-h-[500px] flex flex-col">
        <HeroBackgroundSlider slides={slides} />

        {isEditing && slides.length > 0 && (
          <div className="absolute bottom-28 md:bottom-32 left-4 right-4 z-20 flex gap-2 overflow-x-auto pb-1">
            {slides.map((slide, i) => (
              <EditableImage
                key={`${slide}-${i}`}
                src={slide}
                alt={`Slide ${i + 1}`}
                onChange={(url) => updateSlide(i, url)}
                className="w-20 h-14 object-cover rounded border-2 border-white/80"
                wrapperClassName="flex-shrink-0"
              />
            ))}
          </div>
        )}

        <div className="absolute inset-0 bg-gradient-to-r from-black/75 via-black/35 to-black/10 pointer-events-none" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none" />

        {/* Insert cursivo — estilo mockup */}
        {data.insert && (
          <div className="absolute right-4 md:right-10 lg:right-16 top-[38%] md:top-[40%] z-10 hidden sm:block pointer-events-none">
            <div className="bg-white/95 backdrop-blur-sm rounded-xl px-5 py-3.5 shadow-2xl rotate-[-4deg] border border-white/80 max-w-[210px]">
              <EditableText
                value={data.insert}
                onChange={(v) => updateSection('hero-commercial', { insert: v })}
                as="p"
                className="font-script text-brand-800 text-xl md:text-2xl leading-snug text-center"
                multiline
              />
            </div>
          </div>
        )}

        <div className="relative flex-1 max-w-6xl mx-auto px-4 pt-4 pb-2 w-full flex items-center">
          <div className="max-w-xl text-white py-5 md:py-6 [text-shadow:0_1px_12px_rgba(0,0,0,0.45)]">
            <EditableText
              value={data.eyebrow}
              onChange={(v) => updateSection('hero-commercial', { eyebrow: v })}
              as="p"
              className="text-accent font-bold text-[10px] md:text-xs uppercase tracking-[0.25em] mb-3"
            />
            <EditableText
              value={data.headline}
              onChange={(v) => updateSection('hero-commercial', { headline: v })}
              as="h1"
              className="font-display text-3xl sm:text-4xl lg:text-[2.75rem] font-extrabold leading-[1.08] mb-4 uppercase"
            />
            <EditableText
              value={data.subheadline}
              onChange={(v) => updateSection('hero-commercial', { subheadline: v })}
              as="p"
              className="text-white/95 text-sm md:text-base leading-relaxed mb-2"
              multiline
            />
            <EditableText
              value={data.tagline}
              onChange={(v) => updateSection('hero-commercial', { tagline: v })}
              as="p"
              className="text-white/80 text-xs mb-4"
            />
            <div className="flex flex-col sm:flex-row gap-2.5 [text-shadow:none]">
              <CtaButton href={data.primaryCtaLink} icon={ShieldCheck} className="justify-center text-xs">
                {data.primaryCta}
              </CtaButton>
              <WhatsAppLink className="btn-secondary !border-white !text-white hover:!bg-white/15 justify-center text-xs backdrop-blur-sm">
                {data.secondaryCta}
              </WhatsAppLink>
            </div>
          </div>
        </div>

        {trustItems.length > 0 && (
          <div className="trust-bar relative z-10">
            <div className="max-w-6xl mx-auto px-4 py-4 md:py-5">
              <div className="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                {trustItems.map((item, i) => {
                  const isGoogle = i === 1;
                  const isTextOnly = !item.value?.trim();
                  const lucideIdx = trustLucideIndex.indexOf(i);
                  const LucideIcon = lucideIdx >= 0 ? trustLucideIcons[lucideIdx] : Users;
                  return (
                    <div key={item.id} className="flex items-center gap-3">
                      {isGoogle ? (
                        <div className="w-11 h-11 rounded-full bg-white border-2 border-brand-100 flex items-center justify-center flex-shrink-0 shadow-md">
                          <GoogleLogo size="md" />
                        </div>
                      ) : (
                        <div className={trustIconStyles[i % trustIconStyles.length]}>
                          <LucideIcon size={20} />
                        </div>
                      )}
                      <div className="min-w-0">
                        {isTextOnly ? (
                          <TrustTextLabel
                            text={item.label}
                            className="font-display font-bold text-xs md:text-sm text-brand-800 leading-snug"
                          />
                        ) : (
                          <>
                            <p className="font-display font-extrabold text-base md:text-lg text-brand-800 leading-tight">
                              {item.value}
                            </p>
                            <p className="text-text-secondary text-[10px] md:text-xs font-medium leading-snug">
                              {item.label}
                            </p>
                          </>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        )}
      </div>
    </section>
  );
}
