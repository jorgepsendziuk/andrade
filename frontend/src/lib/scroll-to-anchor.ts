/** Mapeia âncoras usadas no CMS para ids reais das seções na página. */
export const ANCHOR_ALIAS_TARGETS: Record<string, string[]> = {
  'voce-pode-ter-direito': ['quem-pode-direito', 'condicoes', 'tenho-direito'],
  'tenho-direito': ['quem-pode-direito', 'condicoes', 'tenho-direito'],
  'analise-pcd-premium': ['analise-premium', 'como-funciona'],
};

const ANCHOR_ALIASES = ANCHOR_ALIAS_TARGETS;

function headerOffsetPx(): number {
  if (typeof document === 'undefined') return 72;
  const header = document.querySelector('header');
  const height = header?.getBoundingClientRect().height;
  return height ? Math.round(height) + 8 : 72;
}

export function parseAnchorHref(href?: string | null): string | null {
  const trimmed = href?.trim();
  if (!trimmed) return null;
  if (trimmed.startsWith('#')) return trimmed;
  if (trimmed.startsWith('/#')) return trimmed.slice(1);
  return null;
}

export function isAnchorHref(href?: string | null): boolean {
  return parseAnchorHref(href) !== null;
}

export function resolveAnchorId(hash: string): string {
  const id = hash.replace(/^#/, '');
  if (!id) return '';
  if (typeof document !== 'undefined' && document.getElementById(id)) return id;
  for (const alias of ANCHOR_ALIASES[id] ?? []) {
    if (typeof document !== 'undefined' && document.getElementById(alias)) return alias;
  }
  return id;
}

export function resolveAnchorElement(hash: string): HTMLElement | null {
  const id = resolveAnchorId(hash);
  if (!id || typeof document === 'undefined') return null;
  return document.getElementById(id);
}

export function scrollToAnchor(hash: string, behavior: ScrollBehavior = 'smooth'): boolean {
  const el = resolveAnchorElement(hash);
  if (!el) return false;

  const top = el.getBoundingClientRect().top + window.scrollY - headerOffsetPx();
  window.scrollTo({ top: Math.max(0, top), behavior });
  return true;
}

export function normalizeHref(href?: string | null, pathname = '/'): string {
  const anchor = parseAnchorHref(href);
  if (anchor) return pathname === '/' ? anchor : `/${anchor}`;
  return href?.trim() || '#';
}
