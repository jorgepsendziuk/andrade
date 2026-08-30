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
