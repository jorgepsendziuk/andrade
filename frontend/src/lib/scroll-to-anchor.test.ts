import { describe, expect, it } from 'vitest';
import {
  ANCHOR_ALIAS_TARGETS,
  isAnchorHref,
  normalizeHref,
} from './scroll-to-anchor';

describe('scroll-to-anchor', () => {
  it('detecta links âncora', () => {
    expect(isAnchorHref('#condicoes')).toBe(true);
    expect(isAnchorHref('/#analise-premium')).toBe(true);
    expect(isAnchorHref('/iniciar')).toBe(false);
    expect(isAnchorHref(undefined)).toBe(false);
    expect(isAnchorHref(null)).toBe(false);
  });

  it('normaliza href conforme a rota', () => {
    expect(normalizeHref('#contato', '/')).toBe('#contato');
    expect(normalizeHref('#contato', '/guia')).toBe('/#contato');
    expect(normalizeHref('/iniciar', '/guia')).toBe('/iniciar');
    expect(normalizeHref(undefined, '/')).toBe('#');
  });

  it('mapeia aliases conhecidos do CMS', () => {
    expect(ANCHOR_ALIAS_TARGETS['voce-pode-ter-direito']).toContain('condicoes');
    expect(ANCHOR_ALIAS_TARGETS['analise-pcd-premium']).toContain('como-funciona');
  });
});
