import { useCms } from '../../context/CmsContext';
import { ImageSlider } from '../ui/ImageSlider';

export function InfoPcdSection() {
  const { getSection } = useCms();
  const section = getSection('info-pcd');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    subtitle: string;
    slides: string[];
  };

  if (!data.slides?.length) return null;

  return (
    <section id="info-pcd" className="py-10 md:py-14 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <h2 className="section-title">{data.title}</h2>
        <p className="section-subtitle">{data.subtitle}</p>

        <div className="max-w-lg mx-auto">
          <ImageSlider
            slides={data.slides}
            title={data.title}
            subtitle=""
            altPrefix="Informação PCD"
            variant="contain"
            autoPlayMs={5500}
            showHeader={false}
          />
        </div>
      </div>
    </section>
  );
}
