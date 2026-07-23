import { useCms } from '../../context/CmsContext';

export function Footer() {
  const { content } = useCms();
  if (!content) return null;
  const { site } = content;

  return (
    <footer className="bg-brand-800 text-white py-4">
      <div className="max-w-6xl mx-auto px-4 text-center">
        <p className="text-brand-200 text-xs">{site.copyright}</p>
      </div>
    </footer>
  );
}
