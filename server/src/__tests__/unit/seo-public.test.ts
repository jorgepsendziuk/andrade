import { describe, expect, it } from 'vitest';
import {
  buildRobotsTxt,
  canonicalRedirect,
  isPreviewHost,
  isPublicSpaPath,
  legacyRedirectPath,
  requestHostname,
} from '../../seo-public.js';

describe('seo-public', () => {
  it('redireciona www para o domínio canônico', () => {
    expect(canonicalRedirect('www.andradeisencoes.com.br', '/guia?x=1')).toBe(
      'https://andradeisencoes.com.br/guia?x=1'
    );
    expect(canonicalRedirect('andradeisencoes.com.br', '/')).toBeNull();
  });

  it('mapeia URLs antigas para as oficiais', () => {
    expect(legacyRedirectPath('/consorcio')).toBe('/');
    expect(legacyRedirectPath('/sobre-nos')).toBe('/quem-somos');
    expect(legacyRedirectPath('/blog/')).toBe('/guia');
    expect(legacyRedirectPath('/detalhe/isencao-de-ipi')).toBe('/guia/isencao-ipi-pcd');
    expect(legacyRedirectPath('/detalhe/adriane-pisoni/')).toBe('/');
    expect(legacyRedirectPath('/detalhe/junta-medica/?portfolioCats=21')).toBe(
      '/guia/pericia-pcd-como-funciona'
    );
    expect(legacyRedirectPath('/wp-content/uploads/2020/07/CERTIFICADOCORRESPONDENTEBANCARIO.pdf')).toBe(
      '/quem-somos'
    );
    expect(legacyRedirectPath('/wp-content/plugins/pwnd/pwnd.php')).toBe('/');
  });

  it('usa o host público do Firebase, não o do Cloud Run', () => {
    expect(
      requestHostname({
        host: 'andrade-isencoes-ezpnz7bdpa-rj.a.run.app',
        'x-forwarded-host': 'andradeisencoes.com.br',
        'x-fh-requested-host': 'andradeisencoes.com.br',
      })
    ).toBe('andradeisencoes.com.br');
  });

  it('bloqueia indexação em hosts de preview', () => {
    expect(isPreviewHost('andrade-isencoes-xyz.run.app')).toBe(true);
    expect(buildRobotsTxt('andrade-isencoes-xyz.run.app')).toContain('Disallow: /');
    expect(buildRobotsTxt('andradeisencoes.com.br')).toContain('Sitemap:');
  });

  it('só entrega o SPA em rotas públicas atuais', () => {
    expect(isPublicSpaPath('/')).toBe(true);
    expect(isPublicSpaPath('/guia')).toBe(true);
    expect(isPublicSpaPath('/guia/isencao-ipi-pcd')).toBe(false);
    expect(isPublicSpaPath('/isencao-pcd/hernia-de-disco')).toBe(false);
    expect(isPublicSpaPath('/admin/dashboard')).toBe(true);
    expect(isPublicSpaPath('/detalhe/isencao-de-ipi')).toBe(false);
    expect(isPublicSpaPath('/wp-content/uploads/x.pdf')).toBe(false);
  });
});
