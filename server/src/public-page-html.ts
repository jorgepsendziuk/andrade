import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { CANONICAL_ORIGIN } from './seo-public.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const FRONTEND_DIST = path.join(__dirname, '../../frontend/dist');
const SITE_NAME = 'Andrade Isenções';
const DEFAULT_OG_IMAGE = `${CANONICAL_ORIGIN}/assets/logo/logo-full.png`;

export interface PublicFaqItem {
  question: string;
  answer: string;
}

export interface PublicGuiaArticle {
  slug: string;
  title: string;
  metaDescription: string;
  excerpt?: string;
  content: string[];
  relatedConditions?: string[];
  publishedAt?: string;
  updatedAt?: string;
  faq?: PublicFaqItem[];
}

export function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export function normalizeGuiaArticle(raw: Record<string, unknown>): PublicGuiaArticle {
  const content = Array.isArray(raw.content)
    ? raw.content.map((p) => String(p ?? '').trim()).filter(Boolean)
    : [];

  const relatedConditions = Array.isArray(raw.relatedConditions)
    ? raw.relatedConditions.map((s) => String(s)).filter(Boolean)
    : [];

  const faq = Array.isArray(raw.faq)
    ? raw.faq
        .map((item) => {
          if (!item || typeof item !== 'object') return null;
          const row = item as Record<string, unknown>;
          const question = String(row.question ?? '').trim();
          const answer = String(row.answer ?? '').trim();
          if (!question || !answer) return null;
          return { question, answer };
        })
        .filter((item): item is PublicFaqItem => item !== null)
    : [];

  return {
    slug: String(raw.slug ?? ''),
    title: String(raw.title ?? ''),
    metaDescription: String(raw.metaDescription ?? ''),
    excerpt: raw.excerpt ? String(raw.excerpt) : undefined,
    content,
    relatedConditions,
    publishedAt: raw.publishedAt ? String(raw.publishedAt) : undefined,
    updatedAt: raw.updatedAt ? String(raw.updatedAt) : undefined,
    faq,
  };
}

function absoluteUrl(routePath: string): string {
  return `${CANONICAL_ORIGIN}${routePath.startsWith('/') ? routePath : `/${routePath}`}`;
}

function articlePath(slug: string): string {
  return `/guia/${slug}`;
}

function pageTitle(article: PublicGuiaArticle): string {
  return `${article.title} | Guia PCD — ${SITE_NAME}`;
}

export function buildGuiaArticleSchemas(article: PublicGuiaArticle) {
  const url = absoluteUrl(articlePath(article.slug));
  const image = DEFAULT_OG_IMAGE;
  const datePublished = article.publishedAt;
  const dateModified = article.updatedAt || article.publishedAt;

  const schemas: Record<string, unknown>[] = [
    {
      '@context': 'https://schema.org',
      '@type': 'Article',
      headline: article.title,
      description: article.metaDescription,
      url,
      image,
      datePublished,
      dateModified,
      mainEntityOfPage: {
        '@type': 'WebPage',
        '@id': url,
      },
      author: {
        '@type': 'Organization',
        name: SITE_NAME,
        url: CANONICAL_ORIGIN,
      },
      publisher: {
        '@type': 'Organization',
        name: SITE_NAME,
        url: CANONICAL_ORIGIN,
        logo: {
          '@type': 'ImageObject',
          url: DEFAULT_OG_IMAGE,
        },
      },
    },
    {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Início', item: absoluteUrl('/') },
        { '@type': 'ListItem', position: 2, name: 'Guia PCD', item: absoluteUrl('/guia') },
        { '@type': 'ListItem', position: 3, name: article.title },
      ],
    },
  ];

  if (article.faq && article.faq.length > 0) {
    schemas.push({
      '@context': 'https://schema.org',
      '@type': 'FAQPage',
      mainEntity: article.faq.map((item) => ({
        '@type': 'Question',
        name: item.question,
        acceptedAnswer: {
          '@type': 'Answer',
          text: item.answer,
        },
      })),
    });
  }

  return schemas;
}

export function renderGuiaArticleBody(article: PublicGuiaArticle): string {
  const breadcrumbs = `
    <nav aria-label="Trilha de navegação" class="text-sm text-text-secondary mb-6">
      <ol class="flex flex-wrap items-center gap-1 list-none p-0 m-0">
        <li><a href="/" class="hover:text-brand-600">Início</a></li>
        <li aria-hidden="true">/</li>
        <li><a href="/guia" class="hover:text-brand-600">Guia PCD</a></li>
        <li aria-hidden="true">/</li>
        <li><span class="text-brand-700" aria-current="page">${escapeHtml(article.title)}</span></li>
      </ol>
    </nav>`;

  const paragraphs = article.content
    .map((p) => `<p class="text-text-secondary text-base leading-relaxed">${escapeHtml(p)}</p>`)
    .join('\n');

  const related =
    article.relatedConditions && article.relatedConditions.length > 0
      ? `<div class="bg-surface rounded-xl p-5 border border-brand-100 mt-8">
          <h2 class="font-display font-bold text-brand-800 text-sm mb-3">Condições relacionadas</h2>
          <ul class="space-y-2">
            ${article.relatedConditions
              .map(
                (slug) =>
                  `<li><a href="/isencao-pcd/${escapeHtml(slug)}" class="text-brand-600 text-xs hover:underline capitalize">${escapeHtml(slug.replace(/-/g, ' '))}</a></li>`
              )
              .join('')}
          </ul>
        </div>`
      : '';

  const faq =
    article.faq && article.faq.length > 0
      ? `<section class="mt-8">
          <h2 class="font-display font-bold text-brand-800 text-lg mb-4">Perguntas frequentes</h2>
          <div class="space-y-4">
            ${article.faq
              .map(
                (item) => `<div>
                  <h3 class="font-bold text-brand-800 text-sm mb-1">${escapeHtml(item.question)}</h3>
                  <p class="text-text-secondary text-sm leading-relaxed">${escapeHtml(item.answer)}</p>
                </div>`
              )
              .join('')}
          </div>
        </section>`
      : '';

  return `<article class="py-10 md:py-14" itemscope itemtype="https://schema.org/Article">
    <div class="max-w-6xl mx-auto px-4 grid lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2">
        ${breadcrumbs}
        <h1 class="font-display text-3xl md:text-4xl font-bold text-brand-800 mb-6" itemprop="headline">${escapeHtml(article.title)}</h1>
        <div class="prose prose-sm max-w-none space-y-4" itemprop="articleBody">
          ${paragraphs}
        </div>
        ${faq}
        ${related}
      </div>
      <aside class="space-y-6">
        <div class="bg-brand-800 rounded-2xl p-6 text-white sticky top-24">
          <h2 class="font-display font-bold text-lg mb-3">Precisa de ajuda?</h2>
          <p class="text-brand-100 text-sm mb-4">Nossa equipe especializada pode analisar seu caso e orientar sobre os benefícios aplicáveis.</p>
          <a href="https://wa.me/5565999844212" class="btn-primary w-full inline-flex justify-center">Falar com especialista</a>
        </div>
      </aside>
    </div>
  </article>`;
}

export interface PublicConditionPage {
  slug: string;
  title: string;
  metaDescription: string;
  whoCan: string;
  benefits: string[];
  documents: string[];
  howItWorks: string[];
  faq: PublicFaqItem[];
  allConditions?: string[];
}

export function normalizeConditionPage(raw: Record<string, unknown>): PublicConditionPage {
  const faq = Array.isArray(raw.faq)
    ? raw.faq
        .map((item) => {
          if (!item || typeof item !== 'object') return null;
          const row = item as Record<string, unknown>;
          const question = String(row.question ?? '').trim();
          const answer = String(row.answer ?? '').trim();
          if (!question || !answer) return null;
          return { question, answer };
        })
        .filter((item): item is PublicFaqItem => item !== null)
    : [];

  return {
    slug: String(raw.slug ?? ''),
    title: String(raw.title ?? ''),
    metaDescription: String(raw.metaDescription ?? ''),
    whoCan: String(raw.whoCan ?? ''),
    benefits: Array.isArray(raw.benefits) ? raw.benefits.map((b) => String(b)) : [],
    documents: Array.isArray(raw.documents) ? raw.documents.map((d) => String(d)) : [],
    howItWorks: Array.isArray(raw.howItWorks) ? raw.howItWorks.map((s) => String(s)) : [],
    faq,
    allConditions: Array.isArray(raw.allConditions) ? raw.allConditions.map((s) => String(s)) : undefined,
  };
}

type PageHeadOptions = {
  title: string;
  description: string;
  canonicalPath: string;
  ogType: 'article' | 'website';
  schemas: Record<string, unknown>[];
  indexable?: boolean;
};

function injectPublicPageHead(html: string, opts: PageHeadOptions): string {
  const title = opts.title;
  const description = opts.description;
  const canonical = absoluteUrl(opts.canonicalPath);
  const robots = opts.indexable === false ? 'noindex, nofollow' : 'index, follow, max-image-preview:large';
  const schemas = opts.schemas
    .map((schema) => `<script type="application/ld+json">${JSON.stringify(schema)}</script>`)
    .join('\n    ');

  let next = html;
  next = next.replace(/<title>[\s\S]*?<\/title>/i, `<title>${escapeHtml(title)}</title>`);
  next = next.replace(
    /<meta\s+name="description"[^>]*>/i,
    `<meta name="description" content="${escapeHtml(description)}" />`
  );
  next = next.replace(/<meta\s+name="robots"[^>]*>/i, `<meta name="robots" content="${robots}" />`);
  next = next.replace(
    /<link\s+rel="canonical"[^>]*>/i,
    `<link rel="canonical" href="${escapeHtml(canonical)}" />`
  );
  next = next.replace(
    /<meta\s+property="og:type"[^>]*>/i,
    `<meta property="og:type" content="${opts.ogType}" />`
  );
  next = next.replace(
    /<meta\s+property="og:title"[^>]*>/i,
    `<meta property="og:title" content="${escapeHtml(title)}" />`
  );
  next = next.replace(
    /<meta\s+property="og:description"[^>]*>/i,
    `<meta property="og:description" content="${escapeHtml(description)}" />`
  );
  next = next.replace(
    /<meta\s+property="og:url"[^>]*>/i,
    `<meta property="og:url" content="${escapeHtml(canonical)}" />`
  );
  next = next.replace(
    /<meta\s+property="og:image"[^>]*>/i,
    `<meta property="og:image" content="${DEFAULT_OG_IMAGE}" />`
  );
  next = next.replace(
    /<meta\s+name="twitter:title"[^>]*>/i,
    `<meta name="twitter:title" content="${escapeHtml(title)}" />`
  );
  next = next.replace(
    /<meta\s+name="twitter:description"[^>]*>/i,
    `<meta name="twitter:description" content="${escapeHtml(description)}" />`
  );
  next = next.replace(
    /<meta\s+name="twitter:image"[^>]*>/i,
    `<meta name="twitter:image" content="${DEFAULT_OG_IMAGE}" />`
  );

  return next.replace('</head>', `    ${schemas}\n  </head>`);
}

function injectHead(html: string, article: PublicGuiaArticle): string {
  return injectPublicPageHead(html, {
    title: pageTitle(article),
    description: article.metaDescription,
    canonicalPath: articlePath(article.slug),
    ogType: 'article',
    schemas: buildGuiaArticleSchemas(article),
  });
}

function mountSsrRoot(html: string, body: string, bootstrapScript?: string): string {
  const replacement = bootstrapScript
    ? `<div id="root">${body}</div>\n    ${bootstrapScript}`
    : `<div id="root">${body}</div>`;
  if (/<div id="root">[\s\S]*?<\/div>/i.test(html)) {
    return html.replace(/<div id="root">[\s\S]*?<\/div>/i, replacement);
  }
  return html;
}

export function buildConditionSchemas(condition: PublicConditionPage) {
  const url = absoluteUrl(`/isencao-pcd/${condition.slug}`);
  const schemas: Record<string, unknown>[] = [
    {
      '@context': 'https://schema.org',
      '@type': 'WebPage',
      name: condition.title,
      description: condition.metaDescription,
      url,
      mainEntityOfPage: { '@type': 'WebPage', '@id': url },
      isPartOf: { '@type': 'WebSite', name: SITE_NAME, url: CANONICAL_ORIGIN },
    },
    {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Início', item: absoluteUrl('/') },
        { '@type': 'ListItem', position: 2, name: 'Isenção PCD', item: absoluteUrl('/#tenho-direito') },
        { '@type': 'ListItem', position: 3, name: condition.title },
      ],
    },
  ];
  if (condition.faq.length > 0) {
    schemas.push({
      '@context': 'https://schema.org',
      '@type': 'FAQPage',
      mainEntity: condition.faq.map((item) => ({
        '@type': 'Question',
        name: item.question,
        acceptedAnswer: { '@type': 'Answer', text: item.answer },
      })),
    });
  }
  return schemas;
}

export function renderConditionBody(condition: PublicConditionPage): string {
  const benefits = condition.benefits
    .map((b) => `<li class="text-sm text-text-secondary"><span class="text-accent font-bold" aria-hidden>✓</span> ${escapeHtml(b)}</li>`)
    .join('');
  const documents = condition.documents
    .map((d) => `<li class="text-sm text-text-secondary">• ${escapeHtml(d)}</li>`)
    .join('');
  const steps = condition.howItWorks
    .map(
      (step, i) =>
        `<li class="flex gap-3 text-sm text-text-secondary"><span class="font-display font-bold text-accent flex-shrink-0">${String(i + 1).padStart(2, '0')}</span>${escapeHtml(step)}</li>`
    )
    .join('');
  const faq = condition.faq
    .map(
      (item) =>
        `<details class="bg-surface rounded-xl border border-brand-100 group mb-3"><summary class="font-semibold text-brand-800 text-sm p-4 cursor-pointer">${escapeHtml(item.question)}</summary><p class="text-text-secondary text-sm leading-relaxed px-4 pb-4">${escapeHtml(item.answer)}</p></details>`
    )
    .join('');

  return `<article class="py-10 md:py-14" itemscope itemtype="https://schema.org/WebPage">
    <div class="max-w-3xl mx-auto px-4">
      <nav aria-label="Trilha de navegação" class="text-sm text-text-secondary mb-6">
        <a href="/">Início</a> / <a href="/#tenho-direito">Isenção PCD</a> / <span class="text-brand-700">${escapeHtml(condition.title)}</span>
      </nav>
      <h1 class="font-display text-3xl md:text-4xl font-bold text-brand-800 mb-6" itemprop="name">${escapeHtml(condition.title)}</h1>
      <section class="mb-8"><h2 class="font-display font-bold text-brand-700 text-lg mb-3">Quem pode ter direito?</h2><p class="text-text-secondary text-sm leading-relaxed" itemprop="description">${escapeHtml(condition.whoCan)}</p></section>
      <section class="mb-8"><h2 class="font-display font-bold text-brand-700 text-lg mb-3">Quais benefícios podem existir?</h2><ul class="space-y-2">${benefits}</ul></section>
      <section class="mb-8"><h2 class="font-display font-bold text-brand-700 text-lg mb-3">Documentos necessários</h2><ul class="space-y-2">${documents}</ul></section>
      <section class="mb-8"><h2 class="font-display font-bold text-brand-700 text-lg mb-3">Como funciona?</h2><ol class="space-y-3">${steps}</ol></section>
      <section class="mb-10"><h2 class="font-display font-bold text-brand-700 text-lg mb-4">Perguntas frequentes</h2>${faq}</section>
      <div class="bg-brand-800 rounded-2xl p-6 md:p-8 text-center text-white">
        <h2 class="font-display font-bold text-xl mb-3">Fale com um especialista</h2>
        <p class="text-brand-100 text-sm mb-6">Cada caso possui características específicas. Nossa equipe analisa sua situação e orienta você.</p>
        <a href="https://wa.me/5565999844212" class="btn-primary inline-flex justify-center">Analisar meu caso</a>
      </div>
    </div>
  </article>`;
}

export function renderConditionPage(condition: PublicConditionPage, shellHtml = loadFrontendShell()): string {
  const body = renderConditionBody(condition);
  const bootstrap = `<script>window.__CONDITION_PAGE__=${JSON.stringify(condition).replace(/</g, '\\u003c')};</script>`;
  let html = injectPublicPageHead(shellHtml, {
    title: `${condition.title} | ${SITE_NAME}`,
    description: condition.metaDescription,
    canonicalPath: `/isencao-pcd/${condition.slug}`,
    ogType: 'article',
    schemas: buildConditionSchemas(condition),
  });
  return mountSsrRoot(html, body, bootstrap);
}

export function renderConditionNotFoundHtml(shellHtml = loadFrontendShell()): string {
  let html = injectPublicPageHead(shellHtml, {
    title: 'Página não encontrada | Andrade Isenções',
    description: 'Página não encontrada.',
    canonicalPath: '/',
    ogType: 'website',
    schemas: [],
    indexable: false,
  });
  return mountSsrRoot(
    html,
    '<div class="py-20 text-center px-4"><h1 class="font-display text-2xl font-bold text-brand-800 mb-4">Página não encontrada</h1><p><a href="/">Voltar ao início</a></p></div>'
  );
}

export function loadFrontendShell(): string {
  const indexPath = path.join(FRONTEND_DIST, 'index.html');
  if (fs.existsSync(indexPath)) {
    return fs.readFileSync(indexPath, 'utf-8');
  }

  return `<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="" />
    <meta name="robots" content="index, follow, max-image-preview:large" />
    <link rel="canonical" href="${CANONICAL_ORIGIN}/" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="" />
    <meta property="og:description" content="" />
    <meta property="og:url" content="${CANONICAL_ORIGIN}/" />
    <meta property="og:image" content="${DEFAULT_OG_IMAGE}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="" />
    <meta name="twitter:description" content="" />
    <meta name="twitter:image" content="${DEFAULT_OG_IMAGE}" />
    <title>Andrade Isenções</title>
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>`;
}

export function renderGuiaArticlePage(article: PublicGuiaArticle, shellHtml = loadFrontendShell()): string {
  const body = renderGuiaArticleBody(article);
  const bootstrap = `<script>window.__GUIA_ARTICLE__=${JSON.stringify(article).replace(/</g, '\\u003c')};</script>`;

  const html = injectHead(shellHtml, article);
  return mountSsrRoot(html, body, bootstrap);
}

export function renderGuiaNotFoundHtml(shellHtml = loadFrontendShell()): string {
  let html = injectPublicPageHead(shellHtml, {
    title: 'Artigo não encontrado | Andrade Isenções',
    description: 'Artigo não encontrado.',
    canonicalPath: '/guia',
    ogType: 'website',
    schemas: [],
    indexable: false,
  });
  return mountSsrRoot(
    html,
    '<div class="py-20 text-center px-4"><h1 class="font-display text-2xl font-bold text-brand-800 mb-4">Artigo não encontrado</h1><p><a href="/guia">Voltar ao Guia PCD</a></p></div>'
  );
}
