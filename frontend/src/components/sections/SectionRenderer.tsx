import { HeroSection } from './HeroSection';
import { AboutSection } from './AboutSection';
import { ServicesSection } from './ServicesSection';
import { DeficienciesSection } from './DeficienciesSection';
import { PurposeSection } from './PurposeSection';
import { TestimonialsSection } from './TestimonialsSection';
import { ContactSection } from './ContactSection';
import { useCms } from '../../context/CmsContext';

const sectionComponents: Record<string, React.ComponentType> = {
  hero: HeroSection,
  about: AboutSection,
  services: ServicesSection,
  deficiencies: DeficienciesSection,
  purpose: PurposeSection,
  testimonials: TestimonialsSection,
  contact: ContactSection,
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
