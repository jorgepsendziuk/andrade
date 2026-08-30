import { useEffect, useState } from 'react';
import { ExternalLink, Play } from 'lucide-react';
import { InstagramIcon } from '../ui/InstagramIcon';
import { useCms } from '../../context/CmsContext';
import { fetchInstagramFeed, type InstagramFeedData } from '../../lib/api';

function truncateCaption(text: string, max = 90) {
  const clean = text.replace(/\s+/g, ' ').trim();
  if (clean.length <= max) return clean;
  return `${clean.slice(0, max).trim()}…`;
}

function InstagramFallback({ profileUrl, username }: { profileUrl: string; username: string }) {
  const tiles = [
    { label: 'Dicas PCD', gradient: 'from-[#833ab4] via-[#fd1d1d] to-[#fcb045]' },
    { label: 'Bastidores', gradient: 'from-brand-600 to-brand-800' },
    { label: 'Histórias reais', gradient: 'from-accent to-brand-600' },
    { label: 'Novidades', gradient: 'from-[#405de6] to-[#5851db]' },
    { label: 'Entregas', gradient: 'from-brand-500 to-accent' },
    { label: 'Equipe Andrade', gradient: 'from-[#fd1d1d] to-[#fcb045]' },
  ];

  return (
    <div className="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4">
      {tiles.map((tile) => (
        <a
          key={tile.label}
          href={profileUrl}
          target="_blank"
          rel="noopener noreferrer"
          className={`group relative aspect-square rounded-2xl overflow-hidden bg-gradient-to-br ${tile.gradient} shadow-[var(--shadow-card)] hover:shadow-[var(--shadow-card-hover)] hover:-translate-y-0.5 transition-all`}
        >
          <div className="absolute inset-0 bg-black/10 group-hover:bg-black/5 transition-colors" />
          <div className="absolute inset-0 flex flex-col items-center justify-center p-4 text-center">
            <InstagramIcon size={28} className="text-white/90 mb-2" />
            <span className="text-white font-display font-bold text-sm">{tile.label}</span>
          </div>
        </a>
      ))}
      <p className="col-span-full text-center text-text-secondary text-xs mt-1">
        Siga{' '}
        <a href={profileUrl} target="_blank" rel="noopener noreferrer" className="text-brand-600 font-semibold hover:underline">
          @{username}
        </a>{' '}
        para ver nossos últimos posts
      </p>
    </div>
  );
}

export function InstagramSection() {
  const { getSection } = useCms();
  const section = getSection('instagram');
  const [feed, setFeed] = useState<InstagramFeedData | null>(null);

  const data = section?.data as {
    title?: string;
    subtitle?: string;
    profileUrl?: string;
    username?: string;
  } | undefined;

  useEffect(() => {
    fetchInstagramFeed()
      .then(setFeed)
      .catch(() => setFeed(null));
  }, []);

  if (!section?.enabled) return null;

  const profileUrl = data?.profileUrl || feed?.profileUrl || 'https://www.instagram.com/andradeconsultoriae';
  const username = data?.username || feed?.username || 'andradeconsultoriae';
  const posts = feed?.posts ?? [];
  const hasApiPosts = posts.length > 0;

  return (
    <section id="instagram" className="py-10 md:py-12 bg-white" aria-labelledby="instagram-heading">
      <div className="max-w-6xl mx-auto px-4">
        <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6 md:mb-8">
          <div className="text-center md:text-left">
            <div className="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-[#833ab4]/10 via-[#fd1d1d]/10 to-[#fcb045]/10 px-3 py-1 mb-3">
              <InstagramIcon size={16} className="text-[#E1306C]" />
              <span className="text-xs font-semibold text-brand-700 uppercase tracking-wider">Instagram</span>
            </div>
            <h2 id="instagram-heading" className="section-title md:text-left mb-2">
              {data?.title ?? 'Acompanhe nosso dia a dia'}
            </h2>
            <p className="section-subtitle md:mx-0 md:text-left mb-0">
              {data?.subtitle ?? 'Histórias, dicas e novidades sobre assessoria PCD em Mato Grosso.'}
            </p>
          </div>

          <a
            href={profileUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center justify-center gap-2 self-center md:self-auto rounded-full px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-[#833ab4] via-[#fd1d1d] to-[#fcb045] shadow-md hover:shadow-lg hover:-translate-y-0.5 transition-all"
          >
            <InstagramIcon size={18} />
            @{username}
            <ExternalLink size={14} className="opacity-80" />
          </a>
        </div>

        {hasApiPosts ? (
          <div className="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4">
            {posts.map((post) => (
              <a
                key={post.id}
                href={post.permalink}
                target="_blank"
                rel="noopener noreferrer"
                className="group relative aspect-square rounded-2xl overflow-hidden bg-brand-100 shadow-[var(--shadow-card)] hover:shadow-[var(--shadow-card-hover)] hover:-translate-y-0.5 transition-all"
              >
                <img
                  src={post.mediaUrl}
                  alt={truncateCaption(post.caption, 120) || 'Post no Instagram'}
                  className="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                  loading="lazy"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
                {post.mediaType === 'VIDEO' && (
                  <div className="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/50 flex items-center justify-center">
                    <Play size={14} className="text-white ml-0.5" fill="currentColor" />
                  </div>
                )}
                {post.caption && (
                  <p className="absolute bottom-0 left-0 right-0 p-3 text-white text-xs leading-snug opacity-0 group-hover:opacity-100 transition-opacity">
                    {truncateCaption(post.caption)}
                  </p>
                )}
              </a>
            ))}
          </div>
        ) : (
          <InstagramFallback profileUrl={profileUrl} username={username} />
        )}
      </div>
    </section>
  );
}
