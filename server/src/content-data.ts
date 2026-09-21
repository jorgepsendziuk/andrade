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

export {
  listConditions as readConditions,
  getCondition as readCondition,
  listGuiaArticles as readGuiaArticles,
  getGuiaArticle as readGuiaArticle,
  listConditionSlugs as readConditionSlugs,
  listGuiaSlugs as readGuiaSlugs,
  saveCondition,
  deleteCondition,
  saveGuiaArticle,
  deleteGuiaArticle,
  safeSlug,
} from './cms-content-store.js';

import { listConditions, listGuiaArticles } from './cms-content-store.js';

function sitemapLastmod(value: unknown, fallback: string): string {
  const raw = String(value ?? '').trim();
  if (!raw) return fallback;
  const date = raw.split('T')[0];
  return /^\d{4}-\d{2}-\d{2}$/.test(date) ? date : fallback;
}

function escapeXmlText(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

const SITEMAP_CACHE_TTL_MS = 5 * 60 * 1000;
let sitemapCache: { xml: string; expiresAt: number } | null = null;

export async function buildSitemapXml(): Promise<string> {
  const now = Date.now();
  if (sitemapCache && sitemapCache.expiresAt > now) {
    return sitemapCache.xml;
  }
  const siteUrl = (process.env.SITE_URL || 'https://andradeisencoes.com.br').replace(/\/$/, '');
  const today = new Date().toISOString().split('T')[0];

  const urls: Array<{ loc: string; priority: string; changefreq: string; lastmod: string }> = [
    { loc: `${siteUrl}/`, priority: '1.0', changefreq: 'weekly', lastmod: today },
    { loc: `${siteUrl}/quem-somos`, priority: '0.8', changefreq: 'monthly', lastmod: today },
    { loc: `${siteUrl}/guia`, priority: '0.9', changefreq: 'weekly', lastmod: today },
    { loc: `${siteUrl}/iniciar`, priority: '0.7', changefreq: 'monthly', lastmod: today },
    { loc: `${siteUrl}/privacidade`, priority: '0.3', changefreq: 'yearly', lastmod: today },
    { loc: `${siteUrl}/termos`, priority: '0.3', changefreq: 'yearly', lastmod: today },
    { loc: `${siteUrl}/cookies`, priority: '0.3', changefreq: 'yearly', lastmod: today },
  ];

  try {
    const conditions = await listConditions();
    for (const condition of conditions) {
      const slug = String((condition as { slug?: string }).slug ?? '');
      if (!slug) continue;
      const row = condition as { updatedAt?: string; publishedAt?: string };
      urls.push({
        loc: `${siteUrl}/isencao-pcd/${slug}`,
        priority: '0.8',
        changefreq: 'monthly',
        lastmod: sitemapLastmod(row.updatedAt || row.publishedAt, today),
      });
    }
  } catch (err) {
    console.error('sitemap conditions error', err);
  }

  try {
    const guiaArticles = await listGuiaArticles();
    for (const article of guiaArticles) {
      const slug = String((article as { slug?: string }).slug ?? '');
      if (!slug) continue;
      const row = article as { updatedAt?: string; publishedAt?: string };
      urls.push({
        loc: `${siteUrl}/guia/${slug}`,
        priority: '0.7',
        changefreq: 'monthly',
        lastmod: sitemapLastmod(row.updatedAt || row.publishedAt, today),
      });
    }
  } catch (err) {
    console.error('sitemap guia error', err);
  }

  const body = urls
    .map(
      (u) => `  <url>
    <loc>${escapeXmlText(u.loc)}</loc>
    <lastmod>${escapeXmlText(u.lastmod)}</lastmod>
    <changefreq>${escapeXmlText(u.changefreq)}</changefreq>
    <priority>${escapeXmlText(u.priority)}</priority>
  </url>`
    )
    .join('\n');

  const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${body}
</urlset>`;
  sitemapCache = { xml, expiresAt: now + SITEMAP_CACHE_TTL_MS };
  return xml;
}
