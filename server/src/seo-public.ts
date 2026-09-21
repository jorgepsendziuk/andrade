export const OFFICIAL_HOSTS = new Set(['andradeisencoes.com.br', 'www.andradeisencoes.com.br']);
export const CANONICAL_ORIGIN = 'https://andradeisencoes.com.br';

export const LEGACY_REDIRECTS: Record<string, string> = {
  '/consorcio': '/',
  '/consórcio': '/',
  '/consorcio/': '/',
  '/sobre': '/quem-somos',
  '/sobre-nos': '/quem-somos',
  '/sobre-nós': '/quem-somos',
  '/nossa-historia': '/quem-somos',
  '/nossa-história': '/quem-somos',
  '/missao': '/quem-somos',
  '/missão': '/quem-somos',
  '/quemsomos': '/quem-somos',
  '/home': '/',
  '/index.php': '/',
  '/index.html': '/',
  '/blog': '/guia',
  '/blog/': '/guia',
  '/conta': '/entrar',
  '/portfolio': '/',
  '/categoria': '/',
  '/category': '/',
};

/** Páginas /detalhe/ do site WordPress antigo → equivalentes atuais. */
export const DETALHE_REDIRECTS: Record<string, string> = {
  'isencao-de-ipi': '/guia/isencao-ipi-pcd',
  'isencao-de-icms': '/guia/isencao-icms-mato-grosso',
  'isencao-de-ipva': '/guia/isencao-ipva-mato-grosso',
  'junta-medica': '/guia/pericia-pcd-como-funciona',
  'cnh-especial': '/guia/pericia-pcd-como-funciona',
  'primeiro-emplacamento': '/guia/como-funciona-processo-carro-pcd',
  'assessoria-juridica': '/quem-somos',
  'luiz-carlos-ferreira-de-andrade-junior': '/quem-somos',
};

/** Aliases de artigos do guia → URL oficial (preencher quando houver consolidação). */
export const GUIA_REDIRECTS: Record<string, string> = {};

export const PDF_REDIRECTS: Record<string, string> = {
  '/wp-content/uploads/2020/07/certificadocorrespondentebancario.pdf': '/quem-somos',
  '/wp-content/uploads/2020/02/diario_oficial_2019-12-09.pdf': '/quem-somos',
};

const LEGACY_PREFIXES = [
  '/wp-content',
  '/wp-admin',
  '/wp-includes',
  '/wp-json',
  '/uploads',
  '/portfolio',
  '/categoria',
  '/category',
  '/tag',
  '/author',
];

const SPA_PATHS = [
  /^\/$/,
  /^\/guia\/?$/,
  /^\/iniciar\/?$/,
  /^\/privacidade\/?$/,
  /^\/termos\/?$/,
  /^\/quem-somos\/?$/,
  /^\/cookies\/?$/,
  /^\/entrar(\/|$)/,
  /^\/portal(\/|$)/,
  /^\/admin(\/|$)/,
  /^\/conta\/?$/,
];

export function normalizeHost(hostHeader?: string | string[] | null): string {
  const raw = Array.isArray(hostHeader) ? hostHeader[0] : hostHeader || '';
  return raw.split(',')[0].split(':')[0].trim().toLowerCase();
}

export function requestHostname(headers: {
  host?: string | string[];
  'x-forwarded-host'?: string | string[];
  'x-fh-requested-host'?: string | string[];
}): string {
  return (
    normalizeHost(headers['x-fh-requested-host']) ||
    normalizeHost(headers['x-forwarded-host']) ||
    normalizeHost(headers.host)
  );
}

export function isOfficialHost(host: string): boolean {
  return OFFICIAL_HOSTS.has(host);
}

export function isPreviewHost(host: string): boolean {
  if (!host || host === 'localhost' || host === '127.0.0.1') return true;
  return (
    host.endsWith('.run.app') ||
    host.endsWith('.web.app') ||
    host.endsWith('.firebaseapp.com') ||
    host.endsWith('.vercel.app') ||
    host.endsWith('.netlify.app')
  );
}

export function canonicalRedirect(host: string, url: string): string | null {
  if (host === 'www.andradeisencoes.com.br') {
    return `${CANONICAL_ORIGIN}${url}`;
  }
  return null;
}

export function publicRedirectLocation(host: string, targetPath: string): string {
  if (isOfficialHost(host) || isPreviewHost(host)) {
    return `${CANONICAL_ORIGIN}${targetPath}`;
  }
  return targetPath;
}

export function cleanPathname(pathname: string): string {
  return pathname.replace(/\/+$/, '') || '/';
}

export function guiaRedirectPath(slug: string): string | null {
  const clean = slug.replace(/\/+$/, '');
  const target = GUIA_REDIRECTS[clean];
  return target ? (target.startsWith('/guia/') ? target : `/guia/${target.replace(/^\/+/, '')}`) : null;
}

export function legacyRedirectPath(pathname: string): string | null {
  const path = pathname.split('?')[0] || '/';
  const clean = cleanPathname(path);

  const exact = LEGACY_REDIRECTS[path] || LEGACY_REDIRECTS[clean];
  if (exact) return exact;

  const pdfTarget = PDF_REDIRECTS[path.toLowerCase()] || PDF_REDIRECTS[clean.toLowerCase()];
  if (pdfTarget) return pdfTarget;

  if (clean === '/detalhe' || clean.startsWith('/detalhe/')) {
    const slug = clean.replace(/^\/detalhe\/?/, '').split('/')[0] || '';
    return DETALHE_REDIRECTS[slug] || '/';
  }

  for (const prefix of LEGACY_PREFIXES) {
    if (clean === prefix || clean.startsWith(`${prefix}/`)) return '/';
  }

  return null;
}

export function isPublicSpaPath(pathname: string): boolean {
  const clean = cleanPathname(pathname.split('?')[0] || '/');
  return SPA_PATHS.some((re) => re.test(clean));
}

export function buildRobotsTxt(host: string): string {
  if (isPreviewHost(host) || (host && !isOfficialHost(host) && host !== 'localhost' && host !== '127.0.0.1')) {
    return `User-agent: *\nDisallow: /\n`;
  }

  return `User-agent: *
Allow: /
Disallow: /portal
Disallow: /admin
Disallow: /conta
Disallow: /entrar
Disallow: /api/
Disallow: /detalhe
Disallow: /wp-content
Disallow: /wp-admin
Disallow: /uploads

Sitemap: ${CANONICAL_ORIGIN}/sitemap.xml
`;
}

export const NOT_FOUND_HTML = `<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="canonical" href="${CANONICAL_ORIGIN}/" />
  <title>Página não encontrada | Andrade Isenções</title>
</head>
<body style="font-family:Inter,system-ui,sans-serif;max-width:40rem;margin:4rem auto;padding:0 1.25rem;color:#0f1c28;background:#fff">
  <h1>Página não encontrada</h1>
  <p>Este endereço não existe no site atual da Andrade Isenções.</p>
  <p><a href="/">Voltar ao início</a> · <a href="/guia">Guia PCD</a></p>
</body>
</html>
`;
