/** Rotas públicas e autenticadas do Portal Andrade. */
export const PORTAL_LOGIN = '/entrar';
export const PORTAL_CLIENT_PROCESS = '/portal/meu-processo';
export const PORTAL_STAFF_HOME = '/portal/inicio';

export const PORTAL_STAFF = {
  home: PORTAL_STAFF_HOME,
  contacts: '/portal/contatos',
  processes: '/portal/processos',
  process: (id: string) => `/portal/processos/${id}`,
  files: '/portal/arquivos',
  site: '/portal/site',
  media: '/portal/midia',
  settings: '/portal/configuracoes',
  users: '/portal/usuarios',
  analytics: '/portal/analytics',
  infra: '/portal/infraestrutura',
} as const;

/** Mapeia rotas legadas /admin/* para /portal/*. */
export function mapLegacyAdminPath(pathname: string): string | null {
  if (!pathname.startsWith('/admin')) return null;
  const suffix = pathname.slice('/admin'.length) || '/';
  if (suffix === '/' || suffix === '/dashboard') return PORTAL_STAFF_HOME;
  return `/portal${suffix}`;
}
