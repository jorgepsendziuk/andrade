import {
  listConditionsForSitemap,
  listGuiaArticlesForSitemap,
} from './cms-content-store.js';

const SITE_URL = (process.env.SITE_URL || 'https://andradeisencoes.com.br').replace(/\/$/, '');
const SITEMAP_CACHE_TTL_MS = 5 * 60 * 1000;
const SITEMAP_BUILD_TIMEOUT_MS = 12_000;

let sitemapCache: { xml: string; expiresAt: number } | null = null;
let lastGoodSitemapXml: string | null = null;
let refreshInFlight: Promise<string> | null = null;

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

export function buildMinimalSitemapXml(): string {
  const today = new Date().toISOString().split('T')[0];
  const urls = [
    { loc: `${SITE_URL}/`, priority: '1.0', changefreq: 'weekly', lastmod: today },
    { loc: `${SITE_URL}/guia`, priority: '0.9', changefreq: 'weekly', lastmod: today },
    { loc: `${SITE_URL}/quem-somos`, priority: '0.8', changefreq: 'monthly', lastmod: today },
  ];
  return serializeSitemap(urls);
}

function serializeSitemap(
  urls: Array<{ loc: string; priority: string; changefreq: string; lastmod: string }>
): string {
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

  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${body}
</urlset>`;
}

async function buildSitemapXmlInternal(): Promise<string> {
  const today = new Date().toISOString().split('T')[0];

  const urls: Array<{ loc: string; priority: string; changefreq: string; lastmod: string }> = [
    { loc: `${SITE_URL}/`, priority: '1.0', changefreq: 'weekly', lastmod: today },
    { loc: `${SITE_URL}/quem-somos`, priority: '0.8', changefreq: 'monthly', lastmod: today },
    { loc: `${SITE_URL}/guia`, priority: '0.9', changefreq: 'weekly', lastmod: today },
    { loc: `${SITE_URL}/iniciar`, priority: '0.7', changefreq: 'monthly', lastmod: today },
    { loc: `${SITE_URL}/privacidade`, priority: '0.3', changefreq: 'yearly', lastmod: today },
    { loc: `${SITE_URL}/termos`, priority: '0.3', changefreq: 'yearly', lastmod: today },
    { loc: `${SITE_URL}/cookies`, priority: '0.3', changefreq: 'yearly', lastmod: today },
  ];

  const conditions = await listConditionsForSitemap();
  for (const condition of conditions) {
    const slug = String((condition as { slug?: string }).slug ?? '');
    if (!slug) continue;
    const row = condition as { updatedAt?: string; publishedAt?: string };
    urls.push({
      loc: `${SITE_URL}/isencao-pcd/${slug}`,
      priority: '0.8',
      changefreq: 'monthly',
      lastmod: sitemapLastmod(row.updatedAt || row.publishedAt, today),
    });
  }

  const guiaArticles = await listGuiaArticlesForSitemap();
  for (const article of guiaArticles) {
    const slug = String((article as { slug?: string }).slug ?? '');
    if (!slug) continue;
    const row = article as { updatedAt?: string; publishedAt?: string };
    urls.push({
      loc: `${SITE_URL}/guia/${slug}`,
      priority: '0.7',
      changefreq: 'monthly',
      lastmod: sitemapLastmod(row.updatedAt || row.publishedAt, today),
    });
  }

  const xml = serializeSitemap(urls);
  lastGoodSitemapXml = xml;
  sitemapCache = { xml, expiresAt: Date.now() + SITEMAP_CACHE_TTL_MS };
  return xml;
}

function withTimeout<T>(promise: Promise<T>, ms: number, fallback: T): Promise<T> {
  return Promise.race([
    promise,
    new Promise<T>((resolve) => {
      setTimeout(() => resolve(fallback), ms);
    }),
  ]);
}

async function refreshSitemapCache(): Promise<string> {
  if (refreshInFlight) return refreshInFlight;
  refreshInFlight = buildSitemapXmlInternal()
    .catch((err) => {
      console.error('sitemap build error', err);
      return lastGoodSitemapXml || buildMinimalSitemapXml();
    })
    .finally(() => {
      refreshInFlight = null;
    });
  return refreshInFlight;
}

export function getCachedSitemapXml(): string | null {
  if (sitemapCache && sitemapCache.expiresAt > Date.now()) {
    return sitemapCache.xml;
  }
  return lastGoodSitemapXml;
}

/** Resposta do endpoint: nunca deve lançar erro nem resultar em 500. */
export async function getSitemapXmlForRequest(): Promise<string> {
  const cached = getCachedSitemapXml();
  if (cached) {
    void refreshSitemapCache();
    return cached;
  }

  const built = await withTimeout(
    refreshSitemapCache(),
    SITEMAP_BUILD_TIMEOUT_MS,
    lastGoodSitemapXml || buildMinimalSitemapXml()
  );
  return built;
}

export async function warmSitemapCache(): Promise<void> {
  try {
    await refreshSitemapCache();
  } catch (err) {
    console.error('sitemap warm error', err);
    lastGoodSitemapXml = lastGoodSitemapXml || buildMinimalSitemapXml();
  }
}

/** Compatibilidade com testes e imports antigos. */
export async function buildSitemapXml(): Promise<string> {
  const cached = getCachedSitemapXml();
  if (cached) return cached;
  return refreshSitemapCache();
}
