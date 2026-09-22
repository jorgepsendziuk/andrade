import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { stripRodizioFromValue } from './public-copy.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const DATA_DIR = path.join(__dirname, '../data');
const CONDITIONS_DIR = path.join(DATA_DIR, 'conditions');
const GUIA_DIR = path.join(DATA_DIR, 'guia');
const CONDITIONS_COLLECTION = 'cms_conditions';
const GUIA_COLLECTION = 'cms_guia';

export function safeSlug(input: string): string {
  return input
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9-]/g, '-')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
}

function readConditionsFromFiles(): Record<string, unknown>[] {
  if (!fs.existsSync(CONDITIONS_DIR)) return [];
  return fs
    .readdirSync(CONDITIONS_DIR)
    .filter((f) => f.endsWith('.json'))
    .map((f) => JSON.parse(fs.readFileSync(path.join(CONDITIONS_DIR, f), 'utf-8')));
}

function readGuiaFromFiles(): Record<string, unknown>[] {
  if (!fs.existsSync(GUIA_DIR)) return [];
  return fs
    .readdirSync(GUIA_DIR)
    .filter((f) => f.endsWith('.json'))
    .map((f) => JSON.parse(fs.readFileSync(path.join(GUIA_DIR, f), 'utf-8')))
    .sort((a: { publishedAt?: string }, b: { publishedAt?: string }) =>
      (b.publishedAt ?? '').localeCompare(a.publishedAt ?? '')
    );
}

function writeConditionFile(slug: string, data: Record<string, unknown>) {
  fs.mkdirSync(CONDITIONS_DIR, { recursive: true });
  fs.writeFileSync(path.join(CONDITIONS_DIR, `${slug}.json`), JSON.stringify(data, null, 2), 'utf-8');
}

function writeGuiaFile(slug: string, data: Record<string, unknown>) {
  fs.mkdirSync(GUIA_DIR, { recursive: true });
  fs.writeFileSync(path.join(GUIA_DIR, `${slug}.json`), JSON.stringify(data, null, 2), 'utf-8');
}

function deleteConditionFile(slug: string) {
  const file = path.join(CONDITIONS_DIR, `${slug}.json`);
  if (fs.existsSync(file)) fs.unlinkSync(file);
}

function deleteGuiaFile(slug: string) {
  const file = path.join(GUIA_DIR, `${slug}.json`);
  if (fs.existsSync(file)) fs.unlinkSync(file);
}

async function bootstrapFirestoreCollection(
  collection: string,
  items: Record<string, unknown>[]
) {
  const db = await getFirestore();
  const batch = db.batch();
  const now = new Date().toISOString();
  for (const item of items) {
    const slug = String(item.slug || '');
    if (!slug) continue;
    const ref = db.collection(collection).doc(slug);
    batch.set(ref, stripUndefined({ json: JSON.stringify(item), updatedAt: now }));
  }
  await batch.commit();
}

async function listFromFirestore(collection: string, fallback: Record<string, unknown>[]) {
  const db = await getFirestore();
  const snap = await db.collection(collection).get();
  if (snap.empty && fallback.length > 0) {
    await bootstrapFirestoreCollection(collection, fallback);
    return fallback;
  }
  return parseFirestoreDocs(snap);
}

function parseFirestoreDocs(snap: { docs: Array<{ data: () => Record<string, unknown> }> }): Record<string, unknown>[] {
  return snap.docs.flatMap((d) => {
    try {
      const parsed = JSON.parse(String(d.data().json)) as Record<string, unknown>;
      return parsed && typeof parsed === 'object' ? [parsed] : [];
    } catch {
      return [];
    }
  });
}

/** Leitura rápida para sitemap: sem bootstrap nem gravações de sanitização. */
async function listFromFirestoreForSitemap(
  collection: string,
  fallback: Record<string, unknown>[]
): Promise<Record<string, unknown>[]> {
  try {
    const db = await getFirestore();
    const snap = await db.collection(collection).get();
    if (snap.empty) return fallback;
    return parseFirestoreDocs(snap);
  } catch (err) {
    console.error(`sitemap firestore list ${collection}`, err);
    return fallback;
  }
}

export async function listConditionsForSitemap(): Promise<Record<string, unknown>[]> {
  const fromFiles = readConditionsFromFiles();
  if (useFirestore) return listFromFirestoreForSitemap(CONDITIONS_COLLECTION, fromFiles);
  return fromFiles;
}

export async function listGuiaArticlesForSitemap(): Promise<Record<string, unknown>[]> {
  const fromFiles = readGuiaFromFiles();
  if (useFirestore) {
    const items = await listFromFirestoreForSitemap(GUIA_COLLECTION, fromFiles);
    return items.sort((a, b) => String(b.publishedAt ?? '').localeCompare(String(a.publishedAt ?? '')));
  }
  return fromFiles;
}

async function getFromFirestore(collection: string, slug: string, fallback: Record<string, unknown> | null) {
  const db = await getFirestore();
  const doc = await db.collection(collection).doc(slug).get();
  if (doc.exists && doc.data()?.json) {
    return JSON.parse(String(doc.data()!.json)) as Record<string, unknown>;
  }
  if (fallback) {
    await db.collection(collection).doc(slug).set(stripUndefined({
      json: JSON.stringify(fallback),
      updatedAt: new Date().toISOString(),
    }));
    return fallback;
  }
  return null;
}

async function saveToFirestore(collection: string, slug: string, data: Record<string, unknown>) {
  const db = await getFirestore();
  await db.collection(collection).doc(slug).set(stripUndefined({
    json: JSON.stringify(data),
    updatedAt: new Date().toISOString(),
  }));
}

async function deleteFromFirestore(collection: string, slug: string) {
  const db = await getFirestore();
  await db.collection(collection).doc(slug).delete();
}

async function sanitizeCondition(item: Record<string, unknown>, persist: boolean): Promise<Record<string, unknown>> {
  const { value, changed } = stripRodizioFromValue(item);
  if (changed && persist) {
    const slug = String(value.slug || item.slug || '');
    if (slug) await saveCondition(value);
  }
  return value;
}

export async function listConditions(): Promise<Record<string, unknown>[]> {
  const fromFiles = readConditionsFromFiles();
  const items = useFirestore ? await listFromFirestore(CONDITIONS_COLLECTION, fromFiles) : fromFiles;
  return Promise.all(items.map((item) => sanitizeCondition(item, useFirestore)));
}

export async function getCondition(slug: string): Promise<Record<string, unknown> | null> {
  const fromFile = readConditionsFromFiles().find((c) => c.slug === slug) ?? null;
  const item = useFirestore ? await getFromFirestore(CONDITIONS_COLLECTION, slug, fromFile) : fromFile;
  if (!item) return null;
  return sanitizeCondition(item, useFirestore);
}

export async function saveCondition(data: Record<string, unknown>, previousSlug?: string): Promise<Record<string, unknown>> {
  const slug = safeSlug(String(data.slug || ''));
  if (!slug) throw new Error('Slug inválido.');
  const payload = { ...data, slug };
  if (useFirestore) {
    if (previousSlug && previousSlug !== slug) {
      await deleteFromFirestore(CONDITIONS_COLLECTION, previousSlug);
    }
    await saveToFirestore(CONDITIONS_COLLECTION, slug, payload);
  } else {
    if (previousSlug && previousSlug !== slug) deleteConditionFile(previousSlug);
    writeConditionFile(slug, payload);
  }
  return payload;
}

export async function deleteCondition(slug: string): Promise<void> {
  if (useFirestore) await deleteFromFirestore(CONDITIONS_COLLECTION, slug);
  deleteConditionFile(slug);
}

export async function listGuiaArticles(): Promise<Record<string, unknown>[]> {
  const fromFiles = readGuiaFromFiles();
  if (useFirestore) {
    const items = await listFromFirestore(GUIA_COLLECTION, fromFiles);
    return items.sort((a, b) =>
      String(b.publishedAt ?? '').localeCompare(String(a.publishedAt ?? ''))
    );
  }
  return fromFiles;
}

export async function getGuiaArticle(slug: string): Promise<Record<string, unknown> | null> {
  const fromFile = readGuiaFromFiles().find((a) => a.slug === slug) ?? null;
  if (useFirestore) return getFromFirestore(GUIA_COLLECTION, slug, fromFile);
  return fromFile;
}

export async function saveGuiaArticle(data: Record<string, unknown>, previousSlug?: string): Promise<Record<string, unknown>> {
  const slug = safeSlug(String(data.slug || ''));
  if (!slug) throw new Error('Slug inválido.');
  const payload = { ...data, slug };
  if (useFirestore) {
    if (previousSlug && previousSlug !== slug) {
      await deleteFromFirestore(GUIA_COLLECTION, previousSlug);
    }
    await saveToFirestore(GUIA_COLLECTION, slug, payload);
  } else {
    if (previousSlug && previousSlug !== slug) deleteGuiaFile(previousSlug);
    writeGuiaFile(slug, payload);
  }
  return payload;
}

export async function deleteGuiaArticle(slug: string): Promise<void> {
  if (useFirestore) await deleteFromFirestore(GUIA_COLLECTION, slug);
  deleteGuiaFile(slug);
}

export async function listConditionSlugs(): Promise<string[]> {
  const items = await listConditions();
  return items.map((c) => String(c.slug)).filter(Boolean);
}

export async function listGuiaSlugs(): Promise<string[]> {
  const items = await listGuiaArticles();
  return items.map((a) => String(a.slug)).filter(Boolean);
}
