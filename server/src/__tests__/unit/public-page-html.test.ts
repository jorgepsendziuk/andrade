import { describe, expect, it } from 'vitest';
import {
  buildGuiaArticleSchemas,
  normalizeGuiaArticle,
  renderGuiaArticlePage,
} from '../../public-page-html.js';

const sample = normalizeGuiaArticle({
  slug: 'isencao-icms-mato-grosso',
  title: 'Isenção de ICMS em Mato Grosso para PCD',
  metaDescription: 'Guia sobre isenção de ICMS para PCD em MT.',
  content: ['Primeiro parágrafo.', 'Segundo parágrafo.'],
  relatedConditions: ['hernia-de-disco'],
  publishedAt: '2026-03-01',
});

describe('public-page-html', () => {
  it('normaliza artigo do CMS', () => {
    expect(sample.slug).toBe('isencao-icms-mato-grosso');
    expect(sample.content).toHaveLength(2);
    expect(sample.relatedConditions).toEqual(['hernia-de-disco']);
  });

  it('gera HTML com title, canonical, H1 e conteúdo', () => {
    const html = renderGuiaArticlePage(sample);
    expect(html).toContain('<title>Isenção de ICMS em Mato Grosso para PCD | Guia PCD — Andrade Isenções</title>');
    expect(html).toContain('rel="canonical" href="https://andradeisencoes.com.br/guia/isencao-icms-mato-grosso"');
    expect(html).toContain('<meta name="description" content="Guia sobre isenção de ICMS para PCD em MT."');
    expect(html).toContain('<h1');
    expect(html).toContain('Isenção de ICMS em Mato Grosso para PCD');
    expect(html).toContain('Primeiro parágrafo.');
    expect(html).toContain('window.__GUIA_ARTICLE__');
    expect(html).toContain('application/ld+json');
  });

  it('inclui Article e Breadcrumb no schema', () => {
    const schemas = buildGuiaArticleSchemas(sample);
    expect(schemas.some((s) => s['@type'] === 'Article')).toBe(true);
    expect(schemas.some((s) => s['@type'] === 'BreadcrumbList')).toBe(true);
  });
});
