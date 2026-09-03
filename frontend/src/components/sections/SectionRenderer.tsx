import { HeroCommercialSection } from './HeroCommercialSection';
import { TrustBarSection } from './TrustBarSection';
import { QuickPathSection } from './QuickPathSection';
import { EligibilitySection } from './EligibilitySection';
import { ProcessSection } from './ProcessSection';
import { CarsSection } from './CarsSection';
import { SimulatorSection } from './SimulatorSection';
import { MediaSection } from './MediaSection';
import { InstagramSection } from './InstagramSection';
import { TestimonialsSection } from './TestimonialsSection';
import { AboutSection } from './AboutSection';
import { ServicesSection } from './ServicesSection';
import { FinalCtaSection } from './FinalCtaSection';
import { ContactSection } from './ContactSection';
import { HeroSection } from './HeroSection';
import { DeficienciesSection } from './DeficienciesSection';
import { PurposeSection } from './PurposeSection';
import { PremiumAnalysisSection } from './PremiumAnalysisSection';
import { EligibilityIntroSection } from './EligibilityIntroSection';
import { WhyAndradeSection } from './WhyAndradeSection';
import { PortalPromoSection } from './PortalPromoSection';
import { ContentsSection } from './ContentsSection';
import { FaqSection } from './FaqSection';
import { useCms } from '../../context/CmsContext';

const sectionComponents: Record<string, React.ComponentType> = {
  'hero-commercial': HeroCommercialSection,
  'trust-bar': TrustBarSection,
  'quick-path': QuickPathSection,
  'analise-premium': PremiumAnalysisSection,
  'quem-pode-direito': EligibilityIntroSection,
  'tenho-direito': EligibilitySection,
  'como-funciona': ProcessSection,
  carros: CarsSection,
  simulador: SimulatorSection,
  media: MediaSection,
  instagram: InstagramSection,
  testimonials: TestimonialsSection,
  about: AboutSection,
  'por-que-andrade': WhyAndradeSection,
  'portal-promo': PortalPromoSection,
  conteudos: ContentsSection,
  faq: FaqSection,
  services: ServicesSection,
  'final-cta': FinalCtaSection,
  contact: ContactSection,
  hero: HeroSection,
  deficiencies: DeficienciesSection,
  purpose: PurposeSection,
};

export function SectionRenderer() {
  const { content } = useCms();
  if (!content) return null;

  const sections = [...content.sections]
    .filter((s) => s.enabled)
    .sort((a, b) => a.order - b.order);

  return (
    <>
      {sections.map((section) => {
        const Component = sectionComponents[section.type];
        if (!Component) return null;
        return <Component key={section.id} />;
      })}
    </>
  );
}
