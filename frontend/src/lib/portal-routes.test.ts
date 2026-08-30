import { describe, expect, it } from 'vitest';
import { mapLegacyAdminPath } from './portal-routes';

describe('mapLegacyAdminPath', () => {
  it('mapeia /admin/dashboard para /portal/inicio', () => {
    expect(mapLegacyAdminPath('/admin/dashboard')).toBe('/portal/inicio');
  });

  it('mapeia /admin/processos para /portal/processos', () => {
    expect(mapLegacyAdminPath('/admin/processos')).toBe('/portal/processos');
  });

  it('preserva subrotas com parâmetros', () => {
    expect(mapLegacyAdminPath('/admin/processos/abc-123')).toBe('/portal/processos/abc-123');
  });

  it('retorna null para rotas não legadas', () => {
    expect(mapLegacyAdminPath('/entrar')).toBeNull();
  });
});
