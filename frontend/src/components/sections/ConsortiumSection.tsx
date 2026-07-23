import { useCms } from '../../context/CmsContext';

export function ConsortiumSection() {
  const { getSection } = useCms();
  const section = getSection('consorcio');
  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    bannerImage: string;
    carImage: string;
    certificate: string;
    ctaLink: string;
  };

  return (
    <section id="consorcio" className="py-12 md:py-16 bg-brand-50">
      <div className="max-w-6xl mx-auto px-4">
        <h2 className="section-title">{data.title}</h2>
        <div className="space-y-4 max-w-4xl mx-auto">
          <img
            src={data.bannerImage}
            alt="Consórcio Nacional Volkswagen"
            className="w-full rounded-lg shadow-md"
          />
          <img
            src={data.carImage}
            alt="Volkswagen"
            className="w-full max-w-2xl mx-auto rounded-lg shadow-md"
          />
          <p className="text-center text-slate-500 text-sm">{data.certificate}</p>
          <div className="text-center pt-2">
            <a href={data.ctaLink} className="btn-green">
              Simule seu consórcio
            </a>
          </div>
        </div>
      </div>
    </section>
  );
}
