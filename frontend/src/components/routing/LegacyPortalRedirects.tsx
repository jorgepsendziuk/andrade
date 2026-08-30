import { Navigate, useLocation } from 'react-router-dom';
import { mapLegacyAdminPath } from '../../lib/portal-routes';

export function LegacyAdminRedirect() {
  const location = useLocation();
  const target = mapLegacyAdminPath(location.pathname);
  if (!target) return <Navigate to="/entrar" replace />;
  return <Navigate to={`${target}${location.search}${location.hash}`} replace />;
}

export function LegacyContaRedirect() {
  const location = useLocation();
  return <Navigate to={`/portal/meu-processo${location.search}${location.hash}`} replace />;
}
