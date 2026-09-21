import { describe, expect, it, beforeAll } from 'vitest';
import request from 'supertest';
import app from '../../app.js';
import { signToken } from '../../auth.js';

const fakePdf = Buffer.from('%PDF-1.4\n% Andrade test\n');

describe('API health', () => {
  it('GET /api/health retorna ok', async () => {
    const res = await request(app).get('/api/health');
    expect(res.status).toBe(200);
    expect(res.body.status).toBe('ok');
  });
});

describe('API auth', () => {
  it('POST /api/auth/login autentica admin seed', async () => {
    const res = await request(app)
      .post('/api/auth/login')
      .send({ email: 'admin@test.com', password: 'Test@1234' });
    expect(res.status).toBe(200);
    expect(res.body.token).toBeTruthy();
    expect(res.body.user.role).toBe('admin');
  });

  it('GET /api/admin/users exige autenticação', async () => {
    const res = await request(app).get('/api/admin/users');
    expect(res.status).toBe(401);
  });

  it('GET /api/admin/users retorna lista com token admin', async () => {
    const token = signToken({
      id: 'admin-test-id',
      email: 'admin@test.com',
      name: 'Admin Test',
      role: 'admin',
    });
    const res = await request(app)
      .get('/api/admin/users')
      .set('Authorization', `Bearer ${token}`);
    expect(res.status).toBe(200);
    expect(Array.isArray(res.body)).toBe(true);
  });
});

describe('API portal register', () => {
  it('POST /api/portal/register cria cliente sem RG', async () => {
    const suffix = Date.now();
    const res = await request(app)
      .post('/api/portal/register')
      .field('email', `portal-${suffix}@test.com`)
      .field('password', 'Senha@1234')
      .field('name', 'Portal Teste')
      .field('cpf', `${suffix}`.slice(-11).padStart(11, '3'))
      .field('modality', 'pcd')
      .field('lgpdConsent', 'true')
      .field('termsConsent', 'true')
      .attach('cnh', fakePdf, { filename: 'cnh.pdf', contentType: 'application/pdf' })
      .attach('laudo', fakePdf, { filename: 'laudo.pdf', contentType: 'application/pdf' })
      .attach('comprovante_residencia', fakePdf, {
        filename: 'comprovante.pdf',
        contentType: 'application/pdf',
      });

    expect(res.status).toBe(201);
    expect(res.body.client.email).toBe(`portal-${suffix}@test.com`);
    expect(res.body.processId).toBeTruthy();
  });
});

describe('API password reset', () => {
  it('POST /api/auth/forgot-password retorna mensagem genérica', async () => {
    const res = await request(app)
      .post('/api/auth/forgot-password')
      .send({ email: 'naoexiste@test.com' });
    expect(res.status).toBe(200);
    expect(res.body.success).toBe(true);
    expect(res.body.message).toMatch(/cadastrado/i);
  });

  it('redefine senha com token válido', async () => {
    const suffix = Date.now();
    const email = `reset-${suffix}@test.com`;
    const register = await request(app)
      .post('/api/portal/register')
      .field('email', email)
      .field('password', 'Senha@1234')
      .field('name', 'Reset Teste')
      .field('cpf', `${suffix}`.slice(-11).padStart(11, '7'))
      .field('modality', 'pcd')
      .field('lgpdConsent', 'true')
      .field('termsConsent', 'true')
      .attach('cnh', fakePdf, { filename: 'cnh.pdf', contentType: 'application/pdf' })
      .attach('laudo', fakePdf, { filename: 'laudo.pdf', contentType: 'application/pdf' })
      .attach('comprovante_residencia', fakePdf, {
        filename: 'comprovante.pdf',
        contentType: 'application/pdf',
      });
    expect(register.status).toBe(201);

    const forgot = await request(app).post('/api/auth/forgot-password').send({ email });
    expect(forgot.status).toBe(200);

    const { createPasswordResetToken } = await import('../../password-reset-store.js');
    const { token } = await createPasswordResetToken({
      email,
      userId: register.body.client.id,
      accountType: 'cliente',
    });

    const reset = await request(app)
      .post('/api/auth/reset-password')
      .send({ token, password: 'NovaSenha@99' });
    expect(reset.status).toBe(200);

    const loginOld = await request(app).post('/api/auth/login').send({ email, password: 'Senha@1234' });
    expect(loginOld.status).toBe(401);

    const loginNew = await request(app).post('/api/auth/login').send({ email, password: 'NovaSenha@99' });
    expect(loginNew.status).toBe(200);
    expect(loginNew.body.user.role).toBe('cliente');
  });
});

describe('API admin processos', () => {
  const adminToken = signToken({
    id: 'admin-test-id',
    email: 'admin@test.com',
    name: 'Admin Test',
    role: 'admin',
  });

  it('GET /api/admin/processes lista processos', async () => {
    const res = await request(app)
      .get('/api/admin/processes')
      .set('Authorization', `Bearer ${adminToken}`);
    expect(res.status).toBe(200);
    expect(Array.isArray(res.body)).toBe(true);
  });

  it('GET /api/admin/docs/browse na raiz', async () => {
    const res = await request(app)
      .get('/api/admin/docs/browse')
      .set('Authorization', `Bearer ${adminToken}`);
    expect(res.status).toBe(200);
    expect(res.body).toHaveProperty('folders');
    expect(res.body).toHaveProperty('files');
  });
});

describe('API edição e auditoria', () => {
  const adminToken = signToken({
    id: 'admin-test-id',
    email: 'admin@test.com',
    name: 'Admin Test',
    role: 'admin',
  });

  it('equipe edita representante legal e o log aparece na auditoria', async () => {
    const suffix = Date.now();
    const email = `audit-${suffix}@test.com`;
    const register = await request(app)
      .post('/api/portal/register')
      .field('email', email)
      .field('password', 'Senha@1234')
      .field('name', 'Cliente Auditoria')
      .field('cpf', `${suffix}`.slice(-11).padStart(11, '4'))
      .field('modality', 'pcd')
      .field('lgpdConsent', 'true')
      .field('termsConsent', 'true')
      .attach('cnh', fakePdf, { filename: 'cnh.pdf', contentType: 'application/pdf' })
      .attach('laudo', fakePdf, { filename: 'laudo.pdf', contentType: 'application/pdf' })
      .attach('comprovante_residencia', fakePdf, {
        filename: 'comprovante.pdf',
        contentType: 'application/pdf',
      });
    expect(register.status).toBe(201);
    const clientId = register.body.client.id as string;
    const clientToken = register.body.token as string;

    const patch = await request(app)
      .patch(`/api/admin/clients/${clientId}`)
      .set('Authorization', `Bearer ${adminToken}`)
      .send({
        name: 'Cliente Auditoria Editado',
        representante: {
          nome: 'Tutor Legal',
          cpf: '529.982.247-25',
          rg: 'MG123',
          telefone: '65988887777',
        },
      });
    expect(patch.status).toBe(200);
    expect(patch.body.representante.nome).toBe('Tutor Legal');
    expect(patch.body.representante.cpf).toBe('52998224725');

    const clientPatch = await request(app)
      .patch('/api/portal/me')
      .set('Authorization', `Bearer ${clientToken}`)
      .send({ phone: '65911112222', email: 'nao-pode@test.com' });
    expect(clientPatch.status).toBe(200);
    expect(clientPatch.body.phone).toBe('65911112222');
    expect(clientPatch.body.email).toBe(email);

    const audit = await request(app)
      .get(`/api/admin/audit?resourceId=${clientId}`)
      .set('Authorization', `Bearer ${adminToken}`);
    expect(audit.status).toBe(200);
    expect(Array.isArray(audit.body)).toBe(true);
    expect(audit.body.some((log: { action: string; summary?: string }) => log.action === 'update')).toBe(true);

    const portalAudit = await request(app)
      .get('/api/portal/audit')
      .set('Authorization', `Bearer ${clientToken}`);
    expect(portalAudit.status).toBe(200);
    expect(portalAudit.body.length).toBeGreaterThan(0);

    const clientAfter = await request(app)
      .get(`/api/admin/clients/${clientId}`)
      .set('Authorization', `Bearer ${adminToken}`);
    expect(clientAfter.status).toBe(200);
    expect(clientAfter.body.lastSelfEditFields).toContain('phone');

    const alerts = await request(app)
      .get(`/api/admin/alerts?unread=1&clientId=${clientId}`)
      .set('Authorization', `Bearer ${adminToken}`);
    expect(alerts.status).toBe(200);
    expect(alerts.body.length).toBeGreaterThan(0);
    expect(alerts.body[0].unread).toBe(true);

    const ack = await request(app)
      .patch(`/api/admin/alerts/${alerts.body[0].id}/ack`)
      .set('Authorization', `Bearer ${adminToken}`);
    expect(ack.status).toBe(200);
    expect(ack.body.unread).toBe(false);

    const count = await request(app)
      .get('/api/admin/alerts/count')
      .set('Authorization', `Bearer ${adminToken}`);
    expect(count.status).toBe(200);
    expect(count.body.unread).toBe(0);

    const phoneLog = (audit.body as Array<{ id: string; changes?: { field: string }[] }>).find((item) =>
      item.changes?.some((change) => change.field === 'phone')
    );
    expect(phoneLog).toBeTruthy();
    const revert = await request(app)
      .post(`/api/admin/audit/${phoneLog!.id}/revert`)
      .set('Authorization', `Bearer ${adminToken}`);
    expect(revert.status).toBe(200);
    expect(revert.body.success).toBe(true);

    const afterRevert = await request(app)
      .get(`/api/admin/clients/${clientId}`)
      .set('Authorization', `Bearer ${adminToken}`);
    expect(afterRevert.status).toBe(200);
    expect(afterRevert.body.phone || '').toBe('');
  });
});

describe('API backup', () => {
  it('POST /api/admin/backup/run com secret de cron', async () => {
    const res = await request(app)
      .post('/api/admin/backup/run')
      .set('X-Backup-Secret', process.env.BACKUP_CRON_SECRET!)
      .send({ force: true });
    expect(res.status).toBe(200);
    expect(res.body.status).toBe('success');
  });
});

describe('SEO público', () => {
  it('GET /sitemap.xml responde XML com a home oficial', async () => {
    const res = await request(app)
      .get('/sitemap.xml')
      .set('Host', 'andrade-isencoes-xyz.run.app')
      .set('X-Forwarded-Host', 'andradeisencoes.com.br');
    expect(res.status).toBe(200);
    expect(res.text).toContain('https://andradeisencoes.com.br/');
    expect(res.text).toContain('/quem-somos');
    expect(res.headers['x-robots-tag']).toBeUndefined();
  });

  it('GET /robots.txt no domínio oficial aponta o sitemap', async () => {
    const res = await request(app)
      .get('/robots.txt')
      .set('Host', 'andrade-isencoes-xyz.run.app')
      .set('X-Forwarded-Host', 'andradeisencoes.com.br')
      .set('X-FH-Requested-Host', 'andradeisencoes.com.br');
    expect(res.status).toBe(200);
    expect(res.text).toContain('Sitemap: https://andradeisencoes.com.br/sitemap.xml');
    expect(res.text).not.toContain('Disallow: /\n');
  });

  it('GET /consorcio redireciona 301 para a home', async () => {
    const res = await request(app).get('/consorcio').set('Host', 'andradeisencoes.com.br');
    expect(res.status).toBe(301);
    expect(res.headers.location).toBe('https://andradeisencoes.com.br/');
  });

  it('GET /detalhe/* redireciona 301 para a página equivalente', async () => {
    const ipi = await request(app).get('/detalhe/isencao-de-ipi/').set('Host', 'andradeisencoes.com.br');
    expect(ipi.status).toBe(301);
    expect(ipi.headers.location).toBe('https://andradeisencoes.com.br/guia/isencao-ipi-pcd');

    const car = await request(app).get('/detalhe/fiat-argo-2021/').set('Host', 'andradeisencoes.com.br');
    expect(car.status).toBe(301);
    expect(car.headers.location).toBe('https://andradeisencoes.com.br/');

    const pdf = await request(app)
      .get('/wp-content/uploads/2020/07/CERTIFICADOCORRESPONDENTEBANCARIO.pdf')
      .set('Host', 'andradeisencoes.com.br');
    expect(pdf.status).toBe(301);
    expect(pdf.headers.location).toBe('https://andradeisencoes.com.br/quem-somos');
  });

  it('GET URL desconhecida responde 404 sem indexar', async () => {
    const res = await request(app).get('/pagina-antiga-que-nao-existe').set('Host', 'andradeisencoes.com.br');
    expect(res.status).toBe(404);
    expect(res.headers['x-robots-tag']).toMatch(/noindex/);
  });

  it('GET /guia/:slug entrega HTML SSR com SEO individual', async () => {
    const res = await request(app)
      .get('/guia/isencao-ipi-pcd')
      .set('Host', 'andradeisencoes.com.br');
    expect(res.status).toBe(200);
    expect(res.text).toContain('<h1');
    expect(res.text).toContain('isencao-ipi-pcd');
    expect(res.text).toContain('rel="canonical" href="https://andradeisencoes.com.br/guia/isencao-ipi-pcd"');
    expect(res.text).toContain('window.__GUIA_ARTICLE__');
    expect(res.text).not.toContain('Assessoria PCD para Carro Zero km | Cuiabá-MT</title>');
  });

  it('GET /guia/:slug inexistente responde 404', async () => {
    const res = await request(app)
      .get('/guia/artigo-que-nao-existe-xyz')
      .set('Host', 'andradeisencoes.com.br');
    expect(res.status).toBe(404);
    expect(res.headers['x-robots-tag']).toMatch(/noindex/);
    expect(res.text).toContain('Artigo não encontrado');
  });

  it('GET /isencao-pcd/:slug entrega HTML SSR com canonical próprio', async () => {
    const res = await request(app)
      .get('/isencao-pcd/hernia-de-disco')
      .set('Host', 'andradeisencoes.com.br');
    expect(res.status).toBe(200);
    expect(res.text).toContain('rel="canonical" href="https://andradeisencoes.com.br/isencao-pcd/hernia-de-disco"');
    expect(res.text).toContain('<h1');
    expect(res.text).toContain('window.__CONDITION_PAGE__');
  });

  it('host de preview recebe noindex', async () => {
    const res = await request(app).get('/robots.txt').set('Host', 'andrade-isencoes-xyz.run.app');
    expect(res.status).toBe(200);
    expect(res.text).toContain('Disallow: /');
  });
});

describe('API contato', () => {
  it('POST /api/contact salva submissão (e-mail pode falhar em teste)', async () => {
    const res = await request(app).post('/api/contact').send({
      name: 'Contato Teste',
      email: 'contato@test.com',
      phone: '65999999999',
      message: 'Mensagem de teste automatizado',
    });
    expect([200, 207]).toContain(res.status);
    expect(res.body.success).toBe(true);
    expect(res.body.id).toBeTruthy();
  });
});
