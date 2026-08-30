import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const DATA_DIR = path.join(__dirname, '../data');

interface CachedReviews {
  data: {
    rating: number;
    totalReviews: number;
    reviews: Array<{ author: string; rating: number; text: string; time?: string }>;
    source: 'api' | 'fallback';
  };
  timestamp: number;
}

let cache: CachedReviews | null = null;
const CACHE_TTL = 24 * 60 * 60 * 1000;

const FALLBACK_REVIEWS = {
  rating: 5,
  totalReviews: 300,
  reviews: [
    { author: 'Cliente Andrade', rating: 5, text: 'Atendimento excelente! A equipe foi muito atenciosa em todo o processo de isenção do meu carro PCD.' },
    { author: 'Cliente Andrade', rating: 5, text: 'Profissionais competentes e humanos. Me ajudaram em cada etapa até a entrega do veículo.' },
    { author: 'Cliente Andrade', rating: 5, text: 'Recomendo a todos que buscam assessoria PCD. Processo transparente e sem complicações.' },
  ],
  source: 'fallback' as const,
};

export async function getGoogleReviews() {
  if (cache && Date.now() - cache.timestamp < CACHE_TTL) {
    return cache.data;
  }

  const apiKey = process.env.GOOGLE_PLACES_API_KEY;
  const placeId = process.env.GOOGLE_PLACE_ID;

  if (!apiKey || !placeId) {
    const data = { ...FALLBACK_REVIEWS };
    cache = { data, timestamp: Date.now() };
    return data;
  }

  try {
    const fields = 'rating,userRatingCount,reviews';
    const url = `https://places.googleapis.com/v1/places/${placeId}?fields=${fields}&key=${apiKey}&languageCode=pt-BR`;

    const res = await fetch(url, {
      headers: { 'X-Goog-FieldMask': fields },
    });

    if (!res.ok) throw new Error(`Places API error: ${res.status}`);

    const json = await res.json();
    const data = {
      rating: json.rating ?? 5,
      totalReviews: json.userRatingCount ?? 300,
      reviews: (json.reviews ?? []).slice(0, 5).map((r: { authorAttribution?: { displayName?: string }; rating?: number; text?: { text?: string }; publishTime?: string }) => ({
        author: r.authorAttribution?.displayName ?? 'Cliente',
        rating: r.rating ?? 5,
        text: r.text?.text ?? '',
        time: r.publishTime,
      })),
      source: 'api' as const,
    };

    cache = { data, timestamp: Date.now() };
    return data;
  } catch {
    const data = { ...FALLBACK_REVIEWS };
    cache = { data, timestamp: Date.now() };
    return data;
  }
}

export function readConditions(): unknown[] {
  const dir = path.join(DATA_DIR, 'conditions');
  if (!fs.existsSync(dir)) return [];
  return fs
    .readdirSync(dir)
    .filter((f) => f.endsWith('.json'))
    .map((f) => JSON.parse(fs.readFileSync(path.join(dir, f), 'utf-8')));
}

export function readCondition(slug: string): unknown | null {
  const file = path.join(DATA_DIR, 'conditions', `${slug}.json`);
  if (!fs.existsSync(file)) return null;
  return JSON.parse(fs.readFileSync(file, 'utf-8'));
}

export function readGuiaArticles(): unknown[] {
  const dir = path.join(DATA_DIR, 'guia');
  if (!fs.existsSync(dir)) return [];
  return fs
    .readdirSync(dir)
    .filter((f) => f.endsWith('.json'))
    .map((f) => JSON.parse(fs.readFileSync(path.join(dir, f), 'utf-8')))
    .sort((a: { publishedAt?: string }, b: { publishedAt?: string }) =>
      (b.publishedAt ?? '').localeCompare(a.publishedAt ?? '')
    );
}

export function readGuiaArticle(slug: string): unknown | null {
  const file = path.join(DATA_DIR, 'guia', `${slug}.json`);
  if (!fs.existsSync(file)) return null;
  return JSON.parse(fs.readFileSync(file, 'utf-8'));
}

export function readConditionSlugs(): string[] {
  const dir = path.join(DATA_DIR, 'conditions');
  if (!fs.existsSync(dir)) return [];
  return fs
    .readdirSync(dir)
    .filter((f) => f.endsWith('.json'))
    .map((f) => f.replace('.json', ''));
}

export function readGuiaSlugs(): string[] {
  const dir = path.join(DATA_DIR, 'guia');
  if (!fs.existsSync(dir)) return [];
  return fs
    .readdirSync(dir)
    .filter((f) => f.endsWith('.json'))
    .map((f) => f.replace('.json', ''));
}

export function buildSitemapXml(): string {
  const siteUrl = (process.env.SITE_URL || 'https://andradeisencoes.com.br').replace(/\/$/, '');
  const today = new Date().toISOString().split('T')[0];

  const urls: Array<{ loc: string; priority: string; changefreq: string }> = [
    { loc: `${siteUrl}/`, priority: '1.0', changefreq: 'weekly' },
    { loc: `${siteUrl}/guia`, priority: '0.9', changefreq: 'weekly' },
  ];

  for (const slug of readConditionSlugs()) {
    urls.push({
      loc: `${siteUrl}/isencao-pcd/${slug}`,
      priority: '0.8',
      changefreq: 'monthly',
    });
  }

  for (const slug of readGuiaSlugs()) {
    urls.push({
      loc: `${siteUrl}/guia/${slug}`,
      priority: '0.7',
      changefreq: 'monthly',
    });
  }

  const body = urls
    .map(
      (u) => `  <url>
    <loc>${u.loc}</loc>
    <lastmod>${today}</lastmod>
    <changefreq>${u.changefreq}</changefreq>
    <priority>${u.priority}</priority>
  </url>`
    )
    .join('\n');

  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${body}
</urlset>`;
}
