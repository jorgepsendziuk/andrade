import fs from 'fs';
import path from 'path';
import type { Response } from 'express';
import { privateDocsDir } from './data-paths.js';

export const GCS_DOCS_BUCKET = process.env.GCS_DOCS_BUCKET || '';
const LOCAL_DOCS_DIR = privateDocsDir();
const SIGNED_URL_TTL_MS = 15 * 60 * 1000;

const ALLOWED_DOC_TYPES = new Set([
  'application/pdf',
  'image/jpeg',
  'image/png',
  'image/webp',
]);

const MIME_EXT: Record<string, string> = {
  'application/pdf': '.pdf',
  'image/jpeg': '.jpg',
  'image/png': '.png',
  'image/webp': '.webp',
};

export interface DocsListResult {
  prefix: string;
  folders: { name: string; prefix: string }[];
  files: {
    name: string;
    objectName: string;
    size: number;
    updatedAt: string;
    mimeType: string;
  }[];
}

export function isDocsGcsEnabled(): boolean {
  return Boolean(GCS_DOCS_BUCKET);
}

export function validateDocMime(mimetype: string): boolean {
  return ALLOWED_DOC_TYPES.has(mimetype);
}

function getStorage() {
  return import('@google-cloud/storage').then(({ Storage }) => {
    return new Storage({
      projectId: process.env.GOOGLE_CLOUD_PROJECT || process.env.GCP_PROJECT,
    });
  });
}

function normalizePrefix(prefix: string): string {
  if (!prefix) return '';
  return prefix.endsWith('/') ? prefix : `${prefix}/`;
}

/** Caminho seguro dentro do bucket (sem traversal nem paths absolutos). */
export function isSafeStoragePath(objectName: string): boolean {
  if (!objectName || objectName.includes('..')) return false;
  if (objectName.startsWith('/') || objectName.includes('\\')) return false;
  if (/[\x00-\x1f]/.test(objectName)) return false;
  return true;
}

function isSafeObjectName(name: string): boolean {
  return isSafeStoragePath(name);
}

export async function savePrivateDoc(
  buffer: Buffer,
  mimetype: string,
  objectName: string
): Promise<string> {
  if (!validateDocMime(mimetype)) {
    throw new Error('Tipo não permitido. Use PDF, JPEG ou PNG.');
  }
  return savePrivateDocRaw(buffer, mimetype, objectName);
}

/** Gravação interna (seed, relatórios HTML) — não exposta em upload de usuário. */
export async function savePrivateDocRaw(
  buffer: Buffer,
  mimetype: string,
  objectName: string
): Promise<string> {
  if (!isSafeObjectName(objectName)) {
    throw new Error('Caminho de arquivo inválido.');
  }

  if (isDocsGcsEnabled()) {
    const storage = await getStorage();
    const file = storage.bucket(GCS_DOCS_BUCKET).file(objectName);
    await file.save(buffer, {
      metadata: { contentType: mimetype },
      resumable: false,
    });
    return objectName;
  }

  const localPath = path.join(LOCAL_DOCS_DIR, objectName);
  fs.mkdirSync(path.dirname(localPath), { recursive: true });
  fs.writeFileSync(localPath, buffer);
  return objectName;
}

export async function getSignedDocUrl(objectName: string): Promise<string> {
  if (!isSafeObjectName(objectName)) {
    throw new Error('Caminho inválido.');
  }

  if (isDocsGcsEnabled()) {
    const storage = await getStorage();
    const [url] = await storage.bucket(GCS_DOCS_BUCKET).file(objectName).getSignedUrl({
      version: 'v4',
      action: 'read',
      expires: Date.now() + SIGNED_URL_TTL_MS,
    });
    return url;
  }

  const localPath = path.join(LOCAL_DOCS_DIR, objectName);
  if (!fs.existsSync(localPath)) throw new Error('Arquivo não encontrado.');
  return `/api/docs/local?path=${encodeURIComponent(objectName)}`;
}

export async function listDocsPrefix(prefix: string): Promise<DocsListResult> {
  const normalized = normalizePrefix(prefix);

  if (isDocsGcsEnabled()) {
    const storage = await getStorage();
    const bucket = storage.bucket(GCS_DOCS_BUCKET);
    const [files, , apiResponse] = await bucket.getFiles({
      prefix: normalized,
      delimiter: '/',
      autoPaginate: false,
      maxResults: 500,
    });

    const prefixes = (apiResponse as { prefixes?: string[] })?.prefixes ?? [];
    const folders = prefixes.map((p) => ({
      name: p.slice(normalized.length).replace(/\/$/, ''),
      prefix: p,
    }));

    const directFiles = files
      .filter((f) => f.name !== normalized && !f.name.endsWith('/'))
      .map((f) => ({
        name: path.posix.basename(f.name),
        objectName: f.name,
        size: Number(f.metadata.size) || 0,
        updatedAt: f.metadata.updated || f.metadata.timeCreated || '',
        mimeType: f.metadata.contentType || 'application/octet-stream',
      }));

    return { prefix: normalized, folders, files: directFiles };
  }

  const baseDir = path.join(LOCAL_DOCS_DIR, normalized);
  const folders: { name: string; prefix: string }[] = [];
  const directFiles: DocsListResult['files'] = [];

  if (fs.existsSync(baseDir) && fs.statSync(baseDir).isDirectory()) {
    for (const entry of fs.readdirSync(baseDir)) {
      const full = path.join(baseDir, entry);
      const objectName = `${normalized}${entry}`;
      if (fs.statSync(full).isDirectory()) {
        folders.push({ name: entry, prefix: `${objectName}/` });
      } else {
        const stat = fs.statSync(full);
        directFiles.push({
          name: entry,
          objectName,
          size: stat.size,
          updatedAt: stat.mtime.toISOString(),
          mimeType: 'application/octet-stream',
        });
      }
    }
  }

  return { prefix: normalized, folders, files: directFiles };
}

export async function deletePrivateDoc(objectName: string): Promise<void> {
  if (!isSafeObjectName(objectName)) throw new Error('Caminho inválido.');

  if (isDocsGcsEnabled()) {
    const storage = await getStorage();
    await storage.bucket(GCS_DOCS_BUCKET).file(objectName).delete({ ignoreNotFound: true });
    return;
  }

  const localPath = path.join(LOCAL_DOCS_DIR, objectName);
  if (fs.existsSync(localPath)) fs.unlinkSync(localPath);
}

export function getLocalDocPath(objectName: string): string | null {
  if (!isSafeObjectName(objectName)) return null;
  const localPath = path.join(LOCAL_DOCS_DIR, objectName);
  return fs.existsSync(localPath) ? localPath : null;
}

export async function privateDocExists(objectName: string): Promise<boolean> {
  if (!isSafeObjectName(objectName)) return false;
  if (getLocalDocPath(objectName)) return true;
  if (!isDocsGcsEnabled()) return false;
  const storage = await getStorage();
  const [exists] = await storage.bucket(GCS_DOCS_BUCKET).file(objectName).exists();
  return exists;
}

/** Envia arquivo local ou GCS pela API (evita falha de URL assinada no Cloud Run). */
export async function pipePrivateDoc(
  objectName: string,
  res: Response,
  options?: { fileName?: string; mimeType?: string; inline?: boolean }
): Promise<void> {
  if (!isSafeObjectName(objectName)) throw new Error('Caminho inválido.');

  const localPath = getLocalDocPath(objectName);
  if (localPath) {
    if (options?.mimeType) res.setHeader('Content-Type', options.mimeType);
    if (options?.fileName) {
      const mode = options.inline !== false ? 'inline' : 'attachment';
      res.setHeader('Content-Disposition', `${mode}; filename="${options.fileName}"`);
    }
    res.sendFile(path.resolve(localPath));
    return;
  }

  if (isDocsGcsEnabled()) {
    const storage = await getStorage();
    const file = storage.bucket(GCS_DOCS_BUCKET).file(objectName);
    const [exists] = await file.exists();
    if (!exists) throw new Error('Arquivo não encontrado no storage.');

    const [metadata] = await file.getMetadata();
    res.setHeader('Content-Type', options?.mimeType || metadata.contentType || 'application/octet-stream');
    if (options?.fileName) {
      const mode = options.inline !== false ? 'inline' : 'attachment';
      res.setHeader('Content-Disposition', `${mode}; filename="${options.fileName}"`);
    }

    await new Promise<void>((resolve, reject) => {
      const stream = file.createReadStream();
      stream.on('error', reject);
      res.on('finish', resolve);
      res.on('close', resolve);
      stream.pipe(res);
    });
    return;
  }

  throw new Error('Arquivo não encontrado.');
}
