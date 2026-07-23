import express from 'express';
import cors from 'cors';
import jwt from 'jsonwebtoken';
import bcrypt from 'bcryptjs';
import multer from 'multer';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const DATA_DIR = path.join(__dirname, '../data');
const CONTENT_FILE = path.join(DATA_DIR, 'site-content.json');
const USERS_FILE = path.join(DATA_DIR, 'users.json');
const UPLOADS_DIR = process.env.VERCEL
  ? path.join('/tmp', 'andrade-uploads')
  : path.join(__dirname, '../../frontend/public/assets/uploads');

const JWT_SECRET = process.env.JWT_SECRET || 'andrade-cms-secret-2026';

if (!fs.existsSync(UPLOADS_DIR)) {
  fs.mkdirSync(UPLOADS_DIR, { recursive: true });
}

function readJson<T>(file: string, fallback: T): T {
  try {
    return JSON.parse(fs.readFileSync(file, 'utf-8'));
  } catch {
    return fallback;
  }
}

function writeJson(file: string, data: unknown) {
  if (process.env.VERCEL) {
    throw new Error('Persistência de conteúdo indisponível neste ambiente. Configure armazenamento externo.');
  }
  fs.writeFileSync(file, JSON.stringify(data, null, 2), 'utf-8');
}

if (!fs.existsSync(USERS_FILE) && !process.env.VERCEL) {
  const hash = bcrypt.hashSync('admin123', 10);
  fs.writeFileSync(USERS_FILE, JSON.stringify([{ id: '1', username: 'admin', passwordHash: hash, name: 'Administrador' }], null, 2), 'utf-8');
}

const app = express();
app.use(cors());
app.use(express.json({ limit: '10mb' }));

const storage = multer.diskStorage({
  destination: (_req, _file, cb) => cb(null, UPLOADS_DIR),
  filename: (_req, file, cb) => {
    const ext = path.extname(file.originalname);
    cb(null, `${Date.now()}-${Math.random().toString(36).slice(2)}${ext}`);
  },
});
const upload = multer({ storage, limits: { fileSize: 10 * 1024 * 1024 } });

interface AuthRequest extends express.Request {
  user?: { id: string; username: string };
}

function authMiddleware(req: AuthRequest, res: express.Response, next: express.NextFunction) {
  const header = req.headers.authorization;
  if (!header?.startsWith('Bearer ')) {
    return res.status(401).json({ error: 'Não autorizado' });
  }
  try {
    req.user = jwt.verify(header.slice(7), JWT_SECRET) as { id: string; username: string };
    next();
  } catch {
    return res.status(401).json({ error: 'Token inválido' });
  }
}

app.get('/api/health', (_req, res) => {
  res.json({ status: 'ok' });
});

app.get('/api/content', (_req, res) => {
  const content = readJson(CONTENT_FILE, {});
  res.json(content);
});

app.put('/api/content', authMiddleware, (req: AuthRequest, res) => {
  try {
    writeJson(CONTENT_FILE, req.body);
    res.json({ success: true });
  } catch (err) {
    const message = err instanceof Error ? err.message : 'Falha ao salvar';
    res.status(503).json({ error: message });
  }
});

app.post('/api/auth/login', (req, res) => {
  const { username, password } = req.body;
  const users = readJson<Array<{ id: string; username: string; passwordHash: string; name: string }>>(USERS_FILE, []);
  const user = users.find((u) => u.username === username);
  if (!user || !bcrypt.compareSync(password, user.passwordHash)) {
    return res.status(401).json({ error: 'Credenciais inválidas' });
  }
  const token = jwt.sign({ id: user.id, username: user.username }, JWT_SECRET, { expiresIn: '7d' });
  res.json({ token, user: { id: user.id, username: user.username, name: user.name } });
});

app.get('/api/auth/me', authMiddleware, (req: AuthRequest, res) => {
  const users = readJson<Array<{ id: string; username: string; name: string }>>(USERS_FILE, []);
  const user = users.find((u) => u.id === req.user?.id);
  if (!user) return res.status(404).json({ error: 'Usuário não encontrado' });
  res.json({ id: user.id, username: user.username, name: user.name });
});

app.post('/api/upload', authMiddleware, upload.single('file'), (req, res) => {
  if (!req.file) return res.status(400).json({ error: 'Arquivo não enviado' });
  if (process.env.VERCEL) {
    return res.status(503).json({
      error: 'Upload persistente indisponível na Vercel. Use assets em frontend/public ou armazenamento externo.',
    });
  }
  res.json({ url: `/assets/uploads/${req.file.filename}` });
});

export default app;
