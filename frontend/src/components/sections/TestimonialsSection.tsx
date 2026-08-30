import { useState, useEffect } from 'react';
import { ExternalLink, ArrowRight, BookOpen } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableImage } from '../cms/EditableImage';
import { useCms } from '../../context/CmsContext';
import { StarRating } from '../ui/StarRating';
import { GoogleLogo } from '../ui/GoogleLogo';
import { fetchGoogleReviews, type GoogleReviewsData } from '../../lib/api';

interface FeaturedReview {
  id: string;
  name: string;
  location: string;
  text: string;
  rating: number;
  photo?: string;
}

export function TestimonialsSection() {
  const { getSection, updateSection, content } = useCms();
  const section = getSection('clientes');
  const [googleData, setGoogleData] = useState<GoogleReviewsData | null>(null);

  const data = section?.data as {
    title: string;
    description: string;
    reviewsUrl: string;
    reviewCount: string;
    featuredReviews: FeaturedReview[];
    guiaTitle: string;
    guiaText: string;
    guiaLink: string;
  } | undefined;

  useEffect(() => {
    fetchGoogleReviews()
      .then(setGoogleData)
      .catch(() => setGoogleData(null));
  }, []);

  if (!section?.enabled || !data) return null;

  const displayRating = googleData?.rating ?? 5;
  const displayCount = googleData?.totalReviews
    ? `+${googleData.totalReviews}`
    : data.reviewCount;

  const reviews: FeaturedReview[] =
    data.featuredReviews?.length > 0
      ? data.featuredReviews
      : (googleData?.reviews ?? []).map((r, i) => ({
          id: `g-${i}`,
          name: r.author,
          location: '',
          text: r.text,
          rating: r.rating,
        }));

  return (
    <section id="clientes" className="py-10 md:py-12 bg-surface">
      <div className="max-w-6xl mx-auto px-4">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('clientes', { title: v })}
          as="h2"
          className="section-title whitespace-nowrap"
        />

        <div className="grid lg:grid-cols-12 gap-5 lg:gap-6 items-start">
          <div className="lg:col-span-3">
            <div className="card-vivid p-4 mb-4 bg-white">
              <div className="flex items-center gap-2 mb-2">
                <GoogleLogo size="lg" showText />
              </div>
              <div className="flex items-center gap-2 mb-1">
                <StarRating rating={Math.round(displayRating)} size={16} />
                <span className="text-lg font-extrabold text-brand-800">{displayRating.toFixed(1)}</span>
              </div>
              <p className="text-brand-600 font-bold text-sm mb-2">{displayCount} avaliações</p>
              <EditableText
                value={data.description}
                onChange={(v) => updateSection('clientes', { description: v })}
                as="p"
                className="text-text-secondary text-[11px] mb-3 leading-relaxed"
                multiline
              />
              <a
                href={data.reviewsUrl || content?.site.googleReviewsUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center gap-1.5 text-brand-600 hover:text-accent font-bold text-[10px] uppercase tracking-wide transition-colors"
              >
                Ver todas as avaliações
                <ExternalLink size={12} />
              </a>
            </div>
          </div>

          <div className="lg:col-span-6 grid sm:grid-cols-3 gap-3">
            {reviews.slice(0, 3).map((review) => (
              <div
                key={review.id}
                className="card-vivid p-4 flex flex-col border-l-4 border-l-accent hover:-translate-y-0.5"
              >
                <div className="flex items-center gap-2 mb-2">
                  {review.photo ? (
                    <EditableImage
                      src={review.photo}
                      alt={review.name}
                      onChange={(url) => {
                        const featuredReviews = data.featuredReviews.map((r) =>
                          r.id === review.id ? { ...r, photo: url } : r
                        );
                        updateSection('clientes', { featuredReviews });
                      }}
                      className="w-10 h-10 rounded-full object-cover flex-shrink-0 ring-2 ring-brand-100"
                      loading="lazy"
                      wrapperClassName="flex-shrink-0 rounded-full overflow-hidden"
                    />
                  ) : (
                    <div className="w-10 h-10 rounded-full bg-brand-600 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                      {review.name.charAt(0)}
                    </div>
                  )}
                  <div className="min-w-0">
                    <p className="font-bold text-brand-800 text-xs truncate">{review.name}</p>
                    {review.location && (
                      <p className="text-text-secondary text-[10px] truncate">{review.location}</p>
                    )}
                  </div>
                </div>
                <StarRating rating={review.rating} size={12} />
                <p className="text-text-secondary text-[11px] leading-relaxed mt-2 flex-1 line-clamp-5">
                  &ldquo;{review.text}&rdquo;
                </p>
              </div>
            ))}
          </div>

          <div className="lg:col-span-3">
            <div className="rounded-xl p-5 text-white h-full flex flex-col justify-center min-h-[200px] bg-gradient-to-br from-brand-800 via-brand-700 to-brand-600 shadow-[var(--shadow-card)] border border-brand-600/30">
              <div className="w-10 h-10 rounded-full bg-white/15 flex items-center justify-center mb-3">
                <BookOpen size={20} className="text-accent" />
              </div>
              <h3 className="font-display font-extrabold text-base mb-2">{data.guiaTitle}</h3>
              <p className="text-brand-100 text-xs mb-4 leading-relaxed">{data.guiaText}</p>
              <a href={data.guiaLink} className="btn-primary-sm self-start text-[10px]">
                Acessar Guia
                <ArrowRight size={14} />
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
