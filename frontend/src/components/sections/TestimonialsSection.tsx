import { useState, useEffect } from 'react';
import { ExternalLink } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { EditableImage } from '../cms/EditableImage';
import { EditableCta } from '../cms/EditableCta';
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
    reviewsLinkText?: string;
  } | undefined;

  useEffect(() => {
    fetchGoogleReviews()
      .then(setGoogleData)
      .catch(() => setGoogleData(null));
  }, []);

  if (!section?.enabled || !data) return null;

  const displayRating = googleData?.rating ?? 4.6;
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
          className="section-title text-center mb-8"
        />

        <div className="flex flex-col md:flex-row items-center justify-center gap-6 mb-8">
          <div className="card-vivid p-5 bg-white flex items-center gap-4">
            <GoogleLogo size="lg" showText />
            <div>
              <div className="flex items-center gap-2 mb-1">
                <StarRating rating={Math.round(displayRating)} size={18} />
                <span className="text-2xl font-extrabold text-brand-800">{displayRating.toFixed(1)}/5</span>
              </div>
              <p className="text-brand-600 font-bold text-sm">{displayCount} avaliações</p>
            </div>
          </div>
          <EditableText
            value={data.description}
            onChange={(v) => updateSection('clientes', { description: v })}
            as="p"
            className="text-text-secondary text-sm max-w-md text-center md:text-left"
            multiline
          />
        </div>

        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
          {reviews.slice(0, 9).map((review) => (
            <div
              key={review.id}
              className="card-vivid p-4 flex flex-col border-l-4 border-l-accent hover:-translate-y-0.5 bg-white"
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
                  <EditableText
                    value={review.name}
                    onChange={(v) => {
                      const featuredReviews = (data.featuredReviews || []).map((r) =>
                        r.id === review.id ? { ...r, name: v } : r
                      );
                      updateSection('clientes', { featuredReviews });
                    }}
                    as="p"
                    className="font-bold text-brand-800 text-sm truncate"
                  />
                  {review.location && (
                    <EditableText
                      value={review.location}
                      onChange={(v) => {
                        const featuredReviews = (data.featuredReviews || []).map((r) =>
                          r.id === review.id ? { ...r, location: v } : r
                        );
                        updateSection('clientes', { featuredReviews });
                      }}
                      as="p"
                      className="text-text-secondary text-sm truncate"
                    />
                  )}
                </div>
              </div>
              <StarRating rating={review.rating} size={12} />
              <EditableText
                value={review.text}
                onChange={(v) => {
                  const featuredReviews = (data.featuredReviews || reviews).map((r) =>
                    r.id === review.id ? { ...r, text: v } : r
                  );
                  updateSection('clientes', { featuredReviews });
                }}
                as="p"
                className="text-text-secondary text-sm leading-relaxed mt-2 flex-1 line-clamp-6"
                multiline
              />
            </div>
          ))}
        </div>

        <div className="text-center">
          <EditableCta
            text={data.reviewsLinkText || 'Ver todas as avaliações no Google'}
            href={data.reviewsUrl || content?.site.googleReviewsUrl || '#'}
            onTextChange={(v) => updateSection('clientes', { reviewsLinkText: v })}
            onHrefChange={(v) => updateSection('clientes', { reviewsUrl: v })}
            className="inline-flex items-center gap-1.5 text-brand-600 hover:text-accent font-bold text-sm transition-colors"
          >
            {data.reviewsLinkText || 'Ver todas as avaliações no Google'}
            <ExternalLink size={14} />
          </EditableCta>
        </div>
      </div>
    </section>
  );
}
