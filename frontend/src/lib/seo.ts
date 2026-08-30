export const SITE_NAME = 'Andrade Isenções';
export const SITE_URL = import.meta.env.VITE_SITE_URL || 'https://andradeisencoes.com.br';
export const DEFAULT_OG_IMAGE = `${SITE_URL}/assets/logo/logo-full.png`;
export const DEFAULT_DESCRIPTION =
  'Assessoria especializada em isenção PCD para compra de carro zero km em Mato Grosso. Isenção de IPI, ICMS e IPVA com acompanhamento completo.';

export function absoluteUrl(path = '/') {
  if (path.startsWith('http')) return path;
  return `${SITE_URL}${path.startsWith('/') ? path : `/${path}`}`;
}

export interface BreadcrumbItem {
  name: string;
  path?: string;
}

export function buildBreadcrumbSchema(items: BreadcrumbItem[]) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: item.name,
      ...(item.path ? { item: absoluteUrl(item.path) } : {}),
    })),
  };
}

export function buildFaqSchema(faq: Array<{ question: string; answer: string }>) {
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faq.map((item) => ({
      '@type': 'Question',
      name: item.question,
      acceptedAnswer: {
        '@type': 'Answer',
        text: item.answer,
      },
    })),
  };
}

export function buildArticleSchema(opts: {
  title: string;
  description: string;
  path: string;
  publishedAt?: string;
}) {
  return {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: opts.title,
    description: opts.description,
    url: absoluteUrl(opts.path),
    datePublished: opts.publishedAt,
    author: {
      '@type': 'Organization',
      name: SITE_NAME,
      url: SITE_URL,
    },
    publisher: {
      '@type': 'Organization',
      name: SITE_NAME,
      url: SITE_URL,
      logo: {
        '@type': 'ImageObject',
        url: absoluteUrl('/assets/logo/logo-full.png'),
      },
    },
  };
}

export function buildWebPageSchema(opts: {
  title: string;
  description: string;
  path: string;
}) {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebPage',
    name: opts.title,
    description: opts.description,
    url: absoluteUrl(opts.path),
    isPartOf: {
      '@type': 'WebSite',
      name: SITE_NAME,
      url: SITE_URL,
    },
  };
}

export function buildLocalBusinessSchema(site: {
  title: string;
  description: string;
  phone: string;
  email: string;
  address: string;
  hours: string;
}) {
  return {
    '@context': 'https://schema.org',
    '@type': 'LocalBusiness',
    '@id': `${SITE_URL}/#organization`,
    name: site.title,
    description: site.description,
    url: SITE_URL,
    telephone: site.phone.replace(/\s/g, ''),
    email: site.email,
    image: absoluteUrl('/assets/logo/logo-full.png'),
    address: {
      '@type': 'PostalAddress',
      streetAddress: 'Av. Fernando Corrêa da Costa, 1899, Galeria Itália Center',
      addressLocality: 'Cuiabá',
      addressRegion: 'MT',
      postalCode: '78060-600',
      addressCountry: 'BR',
    },
    openingHours: 'Mo-Fr 09:00-18:00',
    areaServed: {
      '@type': 'State',
      name: 'Mato Grosso',
    },
    priceRange: '$$',
    sameAs: ['https://www.instagram.com/andradeconsultoriae'],
  };
}

export function buildWebSiteSchema() {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: SITE_NAME,
    url: SITE_URL,
    description: DEFAULT_DESCRIPTION,
    inLanguage: 'pt-BR',
    publisher: {
      '@id': `${SITE_URL}/#organization`,
    },
  };
}
