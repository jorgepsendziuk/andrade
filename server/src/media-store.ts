import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export const GCS_BUCKET = process.env.GCS_BUCKET || '';
export const IS_EPHEMERAL_FS = Boolean(process.env.VERCEL || process.env.K_SERVICE);

const LOCAL_UPLOADS_DIR = path.join(__dirname, '../../frontend/public/assets/uploads');

const ALLOWED_TYPES = new Set([
  'image/jpeg',
  'image/png',
  'image/webp',
  'image/gif',
  'image/svg+xml',
]);

const MIME_EXT: Record<string, string> = {
  'image/jpeg': '.jpg',
  'image/png': '.png',
  'image/webp': '.webp',
  'image/gif': '.gif',
  'image/svg+xml': '.svg',
};

export function isGcsEnabled(): boolean {
  return Boolean(GCS_BUCKET);
}

export function canUploadPersistent(): boolean {
  return isGcsEnabled() || !IS_EPHEMERAL_FS;
}

export function validateImageMime(mimetype: string): boolean {
  return ALLOWED_TYPES.has(mimetype);
}

function buildFilename(originalName: string, mimetype: string): string {
  const ext = path.extname(originalName) || MIME_EXT[mimetype] || '.bin';
  return `${Date.now()}-${Math.random().toString(36).slice(2)}${ext}`;
}

export function buildGcsObjectName(originalName: string, mimetype: string): string {
  return `uploads/${buildFilename(originalName, mimetype)}`;
}

export function gcsPublicUrl(objectName: string): string {
  return `https://storage.googleapis.com/${GCS_BUCKET}/${objectName}`;
}

export async function saveUploadedImage(
  buffer: Buffer,
  mimetype: string,
  originalName: string
): Promise<string> {
  if (!validateImageMime(mimetype)) {
    throw new Error('Tipo de arquivo não permitido. Use JPEG, PNG, WebP, GIF ou SVG.');
  }

  if (isGcsEnabled()) {
    const { Storage } = await import('@google-cloud/storage');
    const storage = new Storage({
      projectId: process.env.GOOGLE_CLOUD_PROJECT || process.env.GCP_PROJECT,
    });
    const objectName = buildGcsObjectName(originalName, mimetype);
    const file = storage.bucket(GCS_BUCKET).file(objectName);
    await file.save(buffer, {
      metadata: {
        contentType: mimetype,
        cacheControl: 'public, max-age=31536000, immutable',
      },
      resumable: false,
    });
    return gcsPublicUrl(objectName);
  }

  if (IS_EPHEMERAL_FS) {
    throw new Error('Upload persistente indisponível neste ambiente. Configure GCS_BUCKET.');
  }

  if (!fs.existsSync(LOCAL_UPLOADS_DIR)) {
    fs.mkdirSync(LOCAL_UPLOADS_DIR, { recursive: true });
  }

  const filename = buildFilename(originalName, mimetype);
  fs.writeFileSync(path.join(LOCAL_UPLOADS_DIR, filename), buffer);
  return `/assets/uploads/${filename}`;
}

/** Paths que permanecem no build (logo, marcas). */
export function isBuildAssetPath(url: string): boolean {
  return (
    url.startsWith('/assets/logo/') ||
    url.startsWith('/assets/brands/') ||
    url.startsWith('/favicon') ||
    url.startsWith('/apple-touch-icon')
  );
}

const IMAGE_EXT = /\.(jpe?g|png|webp|gif|svg)$/i;

export interface MediaFileRecord {
  objectName: string;
  url: string;
  name: string;
  folder: string;
  size: number;
  contentType: string;
  updatedAt: string;
}

function mimeFromFilename(filename: string): string {
  const ext = path.extname(filename).toLowerCase();
  const map: Record<string, string> = {
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.png': 'image/png',
    '.webp': 'image/webp',
    '.gif': 'image/gif',
    '.svg': 'image/svg+xml',
  };
  return map[ext] || 'application/octet-stream';
}

function folderFromObjectName(objectName: string): string {
  const dir = path.posix.dirname(objectName);
  return dir === '.' ? '' : dir;
}

export function isAllowedMediaObjectName(objectName: string): boolean {
  if (!objectName || objectName.includes('..')) return false;
  return (
    (objectName.startsWith('media/') || objectName.startsWith('uploads/')) &&
    IMAGE_EXT.test(objectName)
  );
}

export async function listMediaFiles(): Promise<MediaFileRecord[]> {
  if (isGcsEnabled()) {
    const { Storage } = await import('@google-cloud/storage');
    const storage = new Storage({
      projectId: process.env.GOOGLE_CLOUD_PROJECT || process.env.GCP_PROJECT,
    });
    const bucket = storage.bucket(GCS_BUCKET);
    const [files] = await bucket.getFiles();
    return files
      .filter((file) => IMAGE_EXT.test(file.name))
      .map((file) => ({
        objectName: file.name,
        url: gcsPublicUrl(file.name),
        name: path.posix.basename(file.name),
        folder: folderFromObjectName(file.name),
        size: Number(file.metadata.size) || 0,
        contentType: file.metadata.contentType || mimeFromFilename(file.name),
        updatedAt: file.metadata.updated || file.metadata.timeCreated || new Date(0).toISOString(),
      }))
      .sort((a, b) => b.updatedAt.localeCompare(a.updatedAt));
  }

  if (!fs.existsSync(LOCAL_UPLOADS_DIR)) return [];
  return fs
    .readdirSync(LOCAL_UPLOADS_DIR)
    .filter((name) => IMAGE_EXT.test(name))
    .map((name) => {
      const full = path.join(LOCAL_UPLOADS_DIR, name);
      const stat = fs.statSync(full);
      const objectName = `uploads/${name}`;
      return {
        objectName,
        url: `/assets/uploads/${name}`,
        name,
        folder: 'uploads',
        size: stat.size,
        contentType: mimeFromFilename(name),
        updatedAt: stat.mtime.toISOString(),
      };
    })
    .sort((a, b) => b.updatedAt.localeCompare(a.updatedAt));
}

export async function deleteMediaFile(objectName: string): Promise<void> {
  if (!isAllowedMediaObjectName(objectName)) {
    throw new Error('Arquivo inválido.');
  }

  if (isGcsEnabled()) {
    const { Storage } = await import('@google-cloud/storage');
    const storage = new Storage({
      projectId: process.env.GOOGLE_CLOUD_PROJECT || process.env.GCP_PROJECT,
    });
    await storage.bucket(GCS_BUCKET).file(objectName).delete({ ignoreNotFound: false });
    return;
  }

  if (objectName.startsWith('uploads/')) {
    const localPath = path.join(LOCAL_UPLOADS_DIR, path.basename(objectName));
    if (fs.existsSync(localPath)) fs.unlinkSync(localPath);
    return;
  }

  throw new Error('Exclusão disponível apenas com GCS_BUCKET ou arquivos em uploads/.');
}

export function collectImageUrls(value: unknown, urls = new Set<string>()): Set<string> {
  if (typeof value === 'string') {
    if (
      /^https?:\/\//i.test(value) ||
      (value.startsWith('/assets/') && !isBuildAssetPath(value))
    ) {
      if (IMAGE_EXT.test(value)) urls.add(value);
    }
  } else if (Array.isArray(value)) {
    value.forEach((item) => collectImageUrls(item, urls));
  } else if (value && typeof value === 'object') {
    Object.values(value).forEach((item) => collectImageUrls(item, urls));
  }
  return urls;
}
