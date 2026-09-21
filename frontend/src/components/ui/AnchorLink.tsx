import type { AnchorHTMLAttributes, ReactNode } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import {
  isAnchorHref,
  normalizeHref,
  parseAnchorHref,
  scrollToAnchor,
} from '../../lib/scroll-to-anchor';

type AnchorLinkProps = Omit<AnchorHTMLAttributes<HTMLAnchorElement>, 'href'> & {
  href: string;
  children: ReactNode;
};

export function AnchorLink({ href, children, onClick, ...rest }: AnchorLinkProps) {
  const location = useLocation();
  const navigate = useNavigate();

  const handleClick = (event: React.MouseEvent<HTMLAnchorElement>) => {
    onClick?.(event);
    if (event.defaultPrevented) return;

    const internal = href?.trim() || '';
    if (internal.startsWith('/') && !internal.startsWith('//') && !isAnchorHref(internal)) {
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
        return;
      }
      event.preventDefault();
      navigate(internal);
      return;
    }

    if (!isAnchorHref(href)) return;

    event.preventDefault();
    const hash = parseAnchorHref(href)!;

    if (location.pathname !== '/') {
      navigate({ pathname: '/', hash: hash.slice(1) });
      return;
    }

    window.history.pushState(null, '', hash);
    if (!scrollToAnchor(hash)) {
      window.setTimeout(() => scrollToAnchor(hash), 120);
    }
  };

  return (
    <a href={normalizeHref(href, location.pathname)} onClick={handleClick} {...rest}>
      {children}
    </a>
  );
}
