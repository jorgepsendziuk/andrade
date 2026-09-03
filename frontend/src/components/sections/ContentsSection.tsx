import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, BookOpen } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableCta } from '../cms/EditableCta';
import { useCms } from '../../context/CmsContext';
import { fetchGuiaArticles } from '../../lib/api';
import type { GuiaArticle } from '../../types/site';

export function ContentsSection() {
  const { getSection, updateSection } = useCms();
  const section = getSection('conteudos');
  const [articles, setArticles] = useState<GuiaArticle[]>([]);

  useEffect(() => {
    fetchGuiaArticles()
      .then(setArticles)
      .catch(() => setArticles([]));
  }, []);

  if (!section?.enabled) return null;

  const data = section.data as {
    title: string;
    subtitle: string;
    ctaText: string;
    ctaLink: string;
    featuredSlugs?: string[];
  };

  const featured =
    data.featuredSlugs?.length
      ? data.featuredSlugs
          .map((slug) => articles.find((a) => a.slug === slug))
          .filter((a): a is GuiaArticle => Boolean(a))
      : articles.slice(0, 10);

  return (
    <section id="conteudos" className="py-10 md:py-12 bg-white">
      <div className="max-w-6xl mx-auto px-4">
        <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-8">
          <div>
            <EditableText
              value={data.title}
              onChange={(v) => updateSection('conteudos', { title: v })}
              as="h2"
              className="section-title mb-2"
            />
            <EditableText
              value={data.subtitle}
              onChange={(v) => updateSection('conteudos', { subtitle: v })}
              as="p"
              className="text-text-secondary text-sm max-w-xl"
              multiline
            />
          </div>
          <EditableCta
            text={data.ctaText}
            href={data.ctaLink}
            onTextChange={(v) => updateSection('conteudos', { ctaText: v })}
            onHrefChange={(v) => updateSection('conteudos', { ctaLink: v })}
            className="btn-secondary text-sm inline-flex items-center gap-2 flex-shrink-0"
          >
            {data.ctaText}
            <ArrowRight size={16} />
          </EditableCta>
        </div>

        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
          {featured.map((article) => (
            <Link
              key={article.slug}
              to={`/guia/${article.slug}`}
              className="group bg-surface rounded-xl p-5 border border-brand-100 hover:border-brand-300 hover:shadow-md transition-all"
            >
              <div className="w-9 h-9 rounded-lg bg-brand-100 flex items-center justify-center mb-3 group-hover:bg-brand-200 transition-colors">
                <BookOpen size={18} className="text-brand-600" />
              </div>
              <h3 className="font-bold text-brand-800 text-sm leading-snug mb-2 group-hover:text-brand-600">
                {article.title}
              </h3>
              <p className="text-text-secondary text-xs leading-relaxed line-clamp-2">
                {article.excerpt}
              </p>
            </Link>
          ))}
        </div>
      </div>
    </section>
  );
}
