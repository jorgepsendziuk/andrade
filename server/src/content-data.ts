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

export { buildSitemapXml, warmSitemapCache } from './sitemap.js';
