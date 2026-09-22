import express, { type NextFunction, type Response } from 'express';
import cors from 'cors';
import multer from 'multer';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import {
  getGoogleReviews,
  readConditions,
  readCondition,
  readGuiaArticles,
  readGuiaArticle,
  saveCondition,
  deleteCondition,
  saveGuiaArticle,
  deleteGuiaArticle,
} from './content-data.js';
import { getInstagramFeed } from './instagram.js';
import { sendContactEmail, sendTestEmail } from './mail-service.js';
import { saveContact, listContacts, updateContactStatus, getContactStats } from './contacts-store.js';
import type { ContactStatus } from './types/contact.js';
import { getSiteContent, saveSiteContent } from './content-store.js';
import {
  findUserByEmail,
  findUserById,
  listUsers,
  createUser,
  updateUser,
  updatePassword,
  verifyPassword,
} from './users-store.js';
import type { UserRole } from './types/user.js';
import { toPublicUser } from './types/user.js';
import { authMiddleware, requireRole, signToken, type AuthRequest } from './auth.js';
import { isLoginExempt, recordFailedLogin, clearRateLimit } from './rate-limit.js';
import {
  getAdminSettings,
  updateEmailSettings,
  updateIntegrationSettings,
} from './settings-store.js';
import { getDashboardStats } from './dashboard-stats.js';
import { getGa4DashboardData } from './ga4-analytics.js';
import { getInfraInfo } from './infra-info.js';
import { runDailyBackup, isValidBackupCronSecret } from './backup-service.js';
import { canUploadPersistent, saveUploadedImage, validateImageMime, listMediaFiles, deleteMediaFile, collectImageUrls, isGcsEnabled } from './media-store.js';
import { authenticateUser, getAuthUser } from './auth-login.js';
import { requestPasswordReset, resetPasswordWithToken } from './auth-password-reset.js';
import { normalizeAuthEmail } from './auth-email.js';
import { registerPortalRoutes } from './portal-routes.js';
import { registerAdminProcessRoutes } from './admin-process-routes.js';
import {
  buildRobotsTxt,
  canonicalRedirect,
  isOfficialHost,
  isPreviewHost,
  guiaRedirectPath,
  isPublicSpaPath,
  legacyRedirectPath,
  NOT_FOUND_HTML,
  publicRedirectLocation,
  requestHostname,
} from './seo-public.js';
import {
  normalizeConditionPage,
  normalizeGuiaArticle,
  renderConditionNotFoundHtml,
  renderConditionPage,
  renderGuiaArticlePage,
  renderGuiaNotFoundHtml,
} from './public-page-html.js';
import { getSitemapXmlForRequest } from './sitemap.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const DATA_DIR = path.join(__dirname, '../data');
const CONTENT_FILE = path.join(DATA_DIR, 'site-content.json');
const FRONTEND_DIST = path.join(__dirname, '../../frontend/dist');

function readJson<T>(file: string, fallback: T): T {
  try {
    return JSON.parse(fs.readFileSync(file, 'utf-8'));
  } catch {
    return fallback;
  }
}

const app = express();
app.set('trust proxy', true);
app.use(cors());
app.use(express.json({ limit: '10mb' }));

app.use((req, res, next) => {
  const host = requestHostname(req.headers);
  const location = req.originalUrl || req.url || '/';

  const hostRedirect = canonicalRedirect(host, location);
  if (hostRedirect) {
    return res.redirect(301, hostRedirect);
  }

  const pathRedirect = legacyRedirectPath(req.path);
  if (pathRedirect) {
    res.set('Cache-Control', 'public, max-age=86400, s-maxage=86400');
    return res.redirect(301, publicRedirectLocation(host, pathRedirect));
  }

  const skipRobotsTag =
    req.path === '/sitemap.xml' ||
    req.path === '/api/sitemap.xml' ||
    req.path === '/robots.txt';

  if (
    !skipRobotsTag &&
    (isPreviewHost(host) || (host && !isOfficialHost(host) && host !== 'localhost' && host !== '127.0.0.1'))
  ) {
    res.set('X-Robots-Tag', 'noindex, nofollow');
  }
  next();
});

const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 8 * 1024 * 1024 },
  fileFilter: (_req, file, cb) => {
    if (validateImageMime(file.mimetype)) cb(null, true);
    else cb(new Error('Tipo de arquivo não permitido.'));
  },
});

app.get('/api/health', (_req, res) => {
  res.json({ status: 'ok' });
});

app.get('/api/content', async (_req, res) => {
  try {
    const content = await getSiteContent();
    res.json(content);
  } catch (err) {
    console.error('get content error', err);
    res.json(readJson(CONTENT_FILE, {}));
  }
});

app.get('/api/google-reviews', async (_req, res) => {
  try {
    const data = await getGoogleReviews();
    res.json(data);
  } catch {
    res.json({ rating: 5, totalReviews: 300, reviews: [], source: 'fallback' });
  }
});

app.get('/api/instagram', async (_req, res) => {
  try {
    const data = await getInstagramFeed(6);
    res.set('Cache-Control', 'public, max-age=300, s-maxage=3600');
    res.json(data);
  } catch {
    res.json({
      posts: [],
      username: 'andradeconsultoriae',
      profileUrl: 'https://www.instagram.com/andradeconsultoriae',
      source: 'fallback',
    });
  }
});

app.get('/api/conditions', async (_req, res) => {
  try {
    res.json(await readConditions());
  } catch (err) {
    console.error('list conditions error', err);
    res.status(500).json({ error: 'Falha ao carregar condições.' });
  }
});

app.get('/api/conditions/:slug', async (req, res) => {
  try {
    const condition = await readCondition(req.params.slug);
    if (!condition) return res.status(404).json({ error: 'Condição não encontrada' });
    res.json(condition);
  } catch (err) {
    console.error('get condition error', err);
    res.status(500).json({ error: 'Falha ao carregar condição.' });
  }
});

app.get('/api/guia', async (_req, res) => {
  try {
    res.json(await readGuiaArticles());
  } catch (err) {
    console.error('list guia error', err);
    res.status(500).json({ error: 'Falha ao carregar guia.' });
  }
});

app.get('/api/guia/:slug', async (req, res) => {
  try {
    const article = await readGuiaArticle(req.params.slug);
    if (!article) return res.status(404).json({ error: 'Artigo não encontrado' });
    res.json(article);
  } catch (err) {
    console.error('get guia error', err);
    res.status(500).json({ error: 'Falha ao carregar artigo.' });
  }
});

app.get('/robots.txt', (req, res) => {
  const host = requestHostname(req.headers);
  res.set('Content-Type', 'text/plain; charset=utf-8');
  res.set('Cache-Control', 'public, max-age=3600, s-maxage=3600');
  res.removeHeader('X-Robots-Tag');
  res.send(buildRobotsTxt(host));
});

app.get(['/api/sitemap.xml', '/sitemap.xml'], async (_req, res) => {
  res.set('Content-Type', 'application/xml; charset=utf-8');
  res.set('Cache-Control', 'public, max-age=300, s-maxage=300');
  res.removeHeader('X-Robots-Tag');
  const xml = await getSitemapXmlForRequest();
  res.status(200).send(xml);
});

app.get('/isencao-pcd/:slug', async (req, res, next) => {
  const slug = String(req.params.slug || '').trim();
  if (!slug) return next();

  try {
    const raw = await readCondition(slug);
    if (!raw) {
      res.status(404);
      res.set('X-Robots-Tag', 'noindex, nofollow');
      res.set('Cache-Control', 'public, max-age=60');
      return res.type('html').send(renderConditionNotFoundHtml());
    }

    const condition = normalizeConditionPage(raw as Record<string, unknown>);
    res.set('Cache-Control', 'public, max-age=300, s-maxage=300');
    return res.type('html').send(renderConditionPage(condition));
  } catch (err) {
    console.error('condition ssr error', err);
    return next(err);
  }
});

app.get('/guia/:slug', async (req, res, next) => {
  const host = requestHostname(req.headers);
  const slug = String(req.params.slug || '').trim();
  if (!slug) return next();

  const alias = guiaRedirectPath(slug);
  if (alias) {
    res.set('Cache-Control', 'public, max-age=86400, s-maxage=86400');
    return res.redirect(301, publicRedirectLocation(host, alias));
  }

  try {
    const raw = await readGuiaArticle(slug);
    if (!raw) {
      res.status(404);
      res.set('X-Robots-Tag', 'noindex, nofollow');
      res.set('Cache-Control', 'public, max-age=60');
      return res.type('html').send(renderGuiaNotFoundHtml());
    }

    const article = normalizeGuiaArticle(raw as Record<string, unknown>);
    res.set('Cache-Control', 'public, max-age=300, s-maxage=300');
    return res.type('html').send(renderGuiaArticlePage(article));
  } catch (err) {
    console.error('guia ssr error', err);
    return next(err);
  }
});

app.post('/api/contact', async (req, res) => {
  const { name, email, phone, message } = req.body as {
    name?: string;
    email?: string;
    phone?: string;
    message?: string;
  };

  if (!name?.trim() || !email?.trim() || !message?.trim()) {
    return res.status(400).json({ error: 'Preencha nome, e-mail e mensagem.' });
  }

  const payload = {
    name: name.trim(),
    email: email.trim(),
    phone: (phone || '').trim(),
    message: message.trim(),
  };

  const { sent: emailSent, error: emailError } = await sendContactEmail(payload);

  try {
    const submission = await saveContact({ ...payload, emailSent });
    if (emailSent) {
      return res.json({ success: true, emailSent: true, id: submission.id });
    }
    return res.status(207).json({
      success: true,
      emailSent: false,
      id: submission.id,
      warning: emailError || 'E-mail não enviado.',
    });
  } catch (err) {
    console.error('contact save error', err);
    if (emailSent) {
      return res.json({
        success: true,
        emailSent: true,
        warning: 'E-mail enviado, mas não registrado no painel.',
      });
    }
    return res.status(503).json({
      error: emailError || 'Falha ao processar contato.',
      code: 'contact_failed',
    });
  }
});

app.get('/api/admin/contacts', authMiddleware, async (_req: AuthRequest, res) => {
  try {
    const contacts = await listContacts(500);
    res.json(contacts);
  } catch (err) {
    console.error('list contacts error', err);
    res.status(500).json({ error: 'Falha ao carregar contatos.' });
  }
});

app.get('/api/admin/stats', authMiddleware, async (req: AuthRequest, res) => {
  try {
    const includeUsers = req.user?.role === 'admin';
    const stats = await getDashboardStats(includeUsers);
    res.json(stats);
  } catch (err) {
    console.error('stats error', err);
    res.status(500).json({ error: 'Falha ao carregar estatísticas.' });
  }
});

app.get('/api/admin/analytics/ga4', authMiddleware, requireRole('admin', 'editor'), async (req: AuthRequest, res) => {
  try {
    const force = req.query.refresh === '1';
    const data = await getGa4DashboardData(force);
    res.json(data);
  } catch (err) {
    console.error('ga4 analytics error', err);
    res.status(500).json({ error: 'Falha ao carregar analytics.' });
  }
});

app.get('/api/admin/infra', authMiddleware, requireRole('admin'), async (req: AuthRequest, res) => {
  try {
    const force = req.query.refresh === '1';
    const data = await getInfraInfo(force);
    res.json(data);
  } catch (err) {
    console.error('infra info error', err);
    res.status(500).json({ error: 'Falha ao carregar infraestrutura.' });
  }
});

app.post('/api/admin/backup/run', (req: AuthRequest, res: Response, next: NextFunction) => {
  if (isValidBackupCronSecret(req.get('X-Backup-Secret') || undefined)) {
    return next();
  }
  authMiddleware(req, res, () => requireRole('admin')(req, res, next));
}, async (req: AuthRequest, res) => {
  try {
    const isCron = isValidBackupCronSecret(req.get('X-Backup-Secret') || undefined);
    const body = (req.body ?? {}) as { force?: boolean };
    const manifest = await runDailyBackup(isCron ? 'scheduler' : 'manual', {
      force: body.force === true,
    });
    res.json(manifest);
  } catch (err) {
    console.error('backup run error', err);
    const message = err instanceof Error ? err.message : 'Falha no backup.';
    res.status(500).json({ error: message });
  }
});

app.patch('/api/admin/contacts/:id', authMiddleware, async (req: AuthRequest, res) => {
  const { status } = req.body as { status?: ContactStatus };
  if (!status || !['new', 'read', 'archived'].includes(status)) {
    return res.status(400).json({ error: 'Status inválido.' });
  }
  try {
    const updated = await updateContactStatus(String(req.params.id), status);
    if (!updated) return res.status(404).json({ error: 'Contato não encontrado.' });
    res.json(updated);
  } catch (err) {
    console.error('update contact error', err);
    res.status(500).json({ error: 'Falha ao atualizar contato.' });
  }
});

app.put('/api/content', authMiddleware, requireRole('admin', 'editor'), async (req: AuthRequest, res) => {
  try {
    await saveSiteContent(req.body);
    res.json({ success: true });
  } catch (err) {
    console.error('put content error', err);
    const message = err instanceof Error ? err.message : 'Falha ao salvar';
    res.status(503).json({ error: message });
  }
});

app.put('/api/admin/conditions/:slug', authMiddleware, requireRole('admin', 'editor'), async (req: AuthRequest, res) => {
  try {
    const saved = await saveCondition(req.body as Record<string, unknown>, String(req.params.slug));
    res.json(saved);
  } catch (err) {
    console.error('save condition error', err);
    const message = err instanceof Error ? err.message : 'Falha ao salvar condição.';
    res.status(400).json({ error: message });
  }
});

app.post('/api/admin/conditions', authMiddleware, requireRole('admin', 'editor'), async (req: AuthRequest, res) => {
  try {
    const saved = await saveCondition(req.body as Record<string, unknown>);
    res.status(201).json(saved);
  } catch (err) {
    console.error('create condition error', err);
    const message = err instanceof Error ? err.message : 'Falha ao criar condição.';
    res.status(400).json({ error: message });
  }
});

app.delete('/api/admin/conditions/:slug', authMiddleware, requireRole('admin', 'editor'), async (req: AuthRequest, res) => {
  try {
    await deleteCondition(String(req.params.slug));
    res.json({ success: true });
  } catch (err) {
    console.error('delete condition error', err);
    res.status(500).json({ error: 'Falha ao excluir condição.' });
  }
});

app.put('/api/admin/guia/:slug', authMiddleware, requireRole('admin', 'editor'), async (req: AuthRequest, res) => {
  try {
    const saved = await saveGuiaArticle(req.body as Record<string, unknown>, String(req.params.slug));
    res.json(saved);
  } catch (err) {
    console.error('save guia error', err);
    const message = err instanceof Error ? err.message : 'Falha ao salvar artigo.';
    res.status(400).json({ error: message });
  }
});

app.post('/api/admin/guia', authMiddleware, requireRole('admin', 'editor'), async (req: AuthRequest, res) => {
  try {
    const saved = await saveGuiaArticle(req.body as Record<string, unknown>);
    res.status(201).json(saved);
  } catch (err) {
    console.error('create guia error', err);
    const message = err instanceof Error ? err.message : 'Falha ao criar artigo.';
    res.status(400).json({ error: message });
  }
});

app.delete('/api/admin/guia/:slug', authMiddleware, requireRole('admin', 'editor'), async (req: AuthRequest, res) => {
  try {
    await deleteGuiaArticle(String(req.params.slug));
    res.json({ success: true });
  } catch (err) {
    console.error('delete guia error', err);
    res.status(500).json({ error: 'Falha ao excluir artigo.' });
  }
});

app.post('/api/auth/forgot-password', async (req, res) => {
  const { email } = req.body as { email?: string };
  try {
    const result = await requestPasswordReset(email || '');
    res.json({ success: true, message: result.message });
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Falha ao solicitar recuperação.';
    res.status(400).json({ error: message });
  }
});

app.post('/api/auth/reset-password', async (req, res) => {
  const { token, password } = req.body as { token?: string; password?: string };
  try {
    await resetPasswordWithToken(token || '', password || '');
    res.json({ success: true, message: 'Senha redefinida com sucesso. Você já pode entrar com a nova senha.' });
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Falha ao redefinir senha.';
    res.status(400).json({ error: message });
  }
});

app.post('/api/auth/login', async (req, res) => {
  const { email, username, password } = req.body as {
    email?: string;
    username?: string;
    password?: string;
  };
  const loginEmail = normalizeAuthEmail(email || username || '');
  if (!loginEmail || !password?.trim()) {
    return res.status(400).json({ error: 'Informe e-mail e senha.' });
  }

  const ip = req.ip || req.socket.remoteAddress || 'unknown';
  const rateKey = `login:${loginEmail}:${ip}`;

  try {
    const auth = await authenticateUser(loginEmail, password);
    if (!auth) {
      if (!isLoginExempt(loginEmail)) {
        const rate = recordFailedLogin(rateKey);
        if (rate.blocked) {
          return res.status(429).json({
            error: `Muitas tentativas incorretas. Aguarde ${rate.retryAfterSec}s.`,
          });
        }
      }
      return res.status(401).json({ error: 'Credenciais inválidas' });
    }
    clearRateLimit(rateKey);
    res.json(auth);
  } catch (err) {
    console.error('login error', err);
    res.status(500).json({ error: 'Falha no login.' });
  }
});

app.get('/api/auth/me', authMiddleware, async (req: AuthRequest, res) => {
  try {
    const user = await getAuthUser(req.user!.id, req.user!.role);
    if (!user) return res.status(401).json({ error: 'Sessão inválida. Faça login novamente.' });
    res.json(user);
  } catch (err) {
    console.error('auth me error', err);
    res.status(500).json({ error: 'Falha ao carregar usuário.' });
  }
});

app.patch('/api/auth/password', authMiddleware, async (req: AuthRequest, res) => {
  const { currentPassword, newPassword } = req.body as {
    currentPassword?: string;
    newPassword?: string;
  };
  if (!currentPassword || !newPassword || newPassword.length < 8) {
    return res.status(400).json({ error: 'Senha atual e nova senha (mín. 8 caracteres) são obrigatórias.' });
  }
  try {
    if (req.user?.role === 'cliente') {
      const { findClientById, verifyClientPassword, updateClientPassword } = await import('./clients-store.js');
      const client = await findClientById(req.user.id);
      if (!client || !(await verifyClientPassword(client, currentPassword))) {
        return res.status(401).json({ error: 'Senha atual incorreta.' });
      }
      await updateClientPassword(client.id, newPassword);
      return res.json({ success: true });
    }

    const user = await findUserById(req.user!.id);
    if (!user || !(await verifyPassword(user, currentPassword))) {
      return res.status(401).json({ error: 'Senha atual incorreta.' });
    }
    await updatePassword(user.id, newPassword);
    res.json({ success: true });
  } catch (err) {
    console.error('password change error', err);
    res.status(500).json({ error: 'Falha ao alterar senha.' });
  }
});

app.get('/api/admin/users', authMiddleware, requireRole('admin'), async (_req, res) => {
  try {
    res.json(await listUsers());
  } catch (err) {
    console.error('list users error', err);
    res.status(500).json({ error: 'Falha ao carregar usuários.' });
  }
});

app.post('/api/admin/users', authMiddleware, requireRole('admin'), async (req, res) => {
  const { email, name, password, role } = req.body as {
    email?: string;
    name?: string;
    password?: string;
    role?: UserRole;
  };
  if (!email?.trim() || !name?.trim() || !password || !role) {
    return res.status(400).json({ error: 'Preencha e-mail, nome, senha e perfil.' });
  }
  if (!['admin', 'editor', 'comercial'].includes(role)) {
    return res.status(400).json({ error: 'Perfil inválido.' });
  }
  try {
    const user = await createUser({ email, name, password, role });
    res.status(201).json(user);
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Falha ao criar usuário';
    res.status(400).json({ error: message });
  }
});

app.patch('/api/admin/users/:id', authMiddleware, requireRole('admin'), async (req, res) => {
  const { name, role, active } = req.body as {
    name?: string;
    role?: UserRole;
    active?: boolean;
  };
  try {
    const updated = await updateUser(String(req.params.id), { name, role, active });
    if (!updated) return res.status(404).json({ error: 'Usuário não encontrado.' });
    res.json(updated);
  } catch (err) {
    console.error('update user error', err);
    res.status(500).json({ error: 'Falha ao atualizar usuário.' });
  }
});

app.delete('/api/admin/users/:id', authMiddleware, requireRole('admin'), async (req: AuthRequest, res) => {
  if (req.params.id === req.user?.id) {
    return res.status(400).json({ error: 'Você não pode desativar sua própria conta.' });
  }
  try {
    const updated = await updateUser(String(req.params.id), { active: false });
    if (!updated) return res.status(404).json({ error: 'Usuário não encontrado.' });
    res.json(updated);
  } catch (err) {
    console.error('deactivate user error', err);
    res.status(500).json({ error: 'Falha ao desativar usuário.' });
  }
});

app.post('/api/upload', authMiddleware, requireRole('admin', 'editor'), upload.single('file'), async (req, res) => {
  if (!req.file) return res.status(400).json({ error: 'Arquivo não enviado' });
  if (!canUploadPersistent()) {
    return res.status(503).json({
      error: 'Upload persistente indisponível. Configure GCS_BUCKET ou rode localmente.',
    });
  }
  try {
    const url = await saveUploadedImage(req.file.buffer, req.file.mimetype, req.file.originalname);
    res.json({ url });
  } catch (err) {
    console.error('upload error', err);
    const message = err instanceof Error ? err.message : 'Falha no upload';
    res.status(400).json({ error: message });
  }
});

app.get('/api/admin/media', authMiddleware, requireRole('admin', 'editor'), async (_req, res) => {
  try {
    const [files, content] = await Promise.all([listMediaFiles(), getSiteContent()]);
    const inUse = collectImageUrls(content);
    const enriched = files.map((file) => ({
      ...file,
      inUse: inUse.has(file.url),
    }));
    res.json({
      files: enriched,
      total: enriched.length,
      storage: isGcsEnabled() ? 'gcs' : 'local',
    });
  } catch (err) {
    console.error('list media error', err);
    res.status(500).json({ error: 'Falha ao carregar biblioteca de mídia.' });
  }
});

app.delete('/api/admin/media', authMiddleware, requireRole('admin', 'editor'), async (req, res) => {
  const { objectName } = req.body as { objectName?: string };
  if (!objectName?.trim()) {
    return res.status(400).json({ error: 'Informe o arquivo a excluir.' });
  }
  try {
    const content = await getSiteContent();
    const files = await listMediaFiles();
    const target = files.find((f) => f.objectName === objectName);
    if (!target) return res.status(404).json({ error: 'Arquivo não encontrado.' });
    if (collectImageUrls(content).has(target.url)) {
      return res.status(409).json({ error: 'Esta imagem está em uso no site. Troque-a antes de excluir.' });
    }
    await deleteMediaFile(objectName);
    res.json({ success: true });
  } catch (err) {
    console.error('delete media error', err);
    const message = err instanceof Error ? err.message : 'Falha ao excluir arquivo.';
    res.status(400).json({ error: message });
  }
});

app.get('/api/admin/settings', authMiddleware, requireRole('admin'), async (_req, res) => {
  try {
    res.json(await getAdminSettings());
  } catch (err) {
    console.error('get settings error', err);
    res.status(500).json({ error: 'Falha ao carregar configurações.' });
  }
});

app.patch('/api/admin/settings/email', authMiddleware, requireRole('admin'), async (req, res) => {
  const {
    smtpHost,
    smtpPort,
    smtpSecure,
    smtpUser,
    smtpFrom,
    contactEmail,
    smtpPass,
  } = req.body as {
    smtpHost?: string;
    smtpPort?: number;
    smtpSecure?: boolean;
    smtpUser?: string;
    smtpFrom?: string;
    contactEmail?: string;
    smtpPass?: string | null;
  };

  try {
    const settings = await updateEmailSettings({
      smtpHost: smtpHost?.trim(),
      smtpPort: smtpPort !== undefined ? Number(smtpPort) : undefined,
      smtpSecure,
      smtpUser: smtpUser?.trim(),
      smtpFrom: smtpFrom?.trim(),
      contactEmail: contactEmail?.trim(),
      smtpPass: smtpPass === '' ? undefined : smtpPass ?? undefined,
    });
    res.json(settings);
  } catch (err) {
    console.error('update email settings error', err);
    res.status(500).json({ error: 'Falha ao salvar configurações de e-mail.' });
  }
});

app.patch('/api/admin/settings/integrations', authMiddleware, requireRole('admin'), async (req, res) => {
  const { googlePlaceId, instagramUsername, instagramProfileUrl, siteUrl } = req.body as {
    googlePlaceId?: string;
    instagramUsername?: string;
    instagramProfileUrl?: string;
    siteUrl?: string;
  };

  try {
    const settings = await updateIntegrationSettings({
      googlePlaceId: googlePlaceId?.trim(),
      instagramUsername: instagramUsername?.trim(),
      instagramProfileUrl: instagramProfileUrl?.trim(),
      siteUrl: siteUrl?.trim(),
    });
    res.json(settings);
  } catch (err) {
    console.error('update integrations error', err);
    res.status(500).json({ error: 'Falha ao salvar integrações.' });
  }
});

registerPortalRoutes(app);
registerAdminProcessRoutes(app);

app.post('/api/admin/settings/email/test', authMiddleware, requireRole('admin'), async (req, res) => {
  const { to } = req.body as { to?: string };
  if (!to?.trim()) {
    return res.status(400).json({ error: 'Informe o e-mail de destino do teste.' });
  }

  const { sent, error } = await sendTestEmail(to.trim());
  if (!sent) {
    return res.status(502).json({ error: error || 'Falha ao enviar e-mail de teste.' });
  }
  res.json({ success: true });
});

// Frontend estático (Cloud Run / produção local)
if (fs.existsSync(FRONTEND_DIST)) {
  app.use(express.static(FRONTEND_DIST, { index: false, maxAge: '1d' }));
}

app.get(/^(?!\/api).*/, (req, res, next) => {
  if (isPublicSpaPath(req.path) && fs.existsSync(FRONTEND_DIST)) {
    return res.sendFile(path.join(FRONTEND_DIST, 'index.html'), (err) => {
      if (err) next(err);
    });
  }
  if (isPublicSpaPath(req.path)) return next();

  res.status(404);
  res.set('X-Robots-Tag', 'noindex, nofollow');
  res.set('Cache-Control', 'public, max-age=60');
  res.type('html').send(NOT_FOUND_HTML);
});

export default app;
