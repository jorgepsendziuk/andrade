import fs from 'fs';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';
import { upgradeSiteContent } from './site-content-upgrade.js';

const CONTENT_FILE = dataFile('site-content.json');
const DOC_ID = 'main';
const COLLECTION = 'site_content';

function readFileContent(): Record<string, unknown> {
  return JSON.parse(fs.readFileSync(CONTENT_FILE, 'utf-8'));
}

function parseStoredContent(data: Record<string, unknown> | undefined): Record<string, unknown> | null {
  if (!data) return null;
  if (typeof data.json === 'string') {
    return JSON.parse(data.json) as Record<string, unknown>;
  }
  // Documento legado (objeto nativo) — pode falhar no Firestore; ignorar se inválido
  const { updatedAt: _, json: __, ...rest } = data;
  if (rest.site && Array.isArray(rest.sections)) {
    return rest as Record<string, unknown>;
  }
  return null;
}

export async function getSiteContent(): Promise<Record<string, unknown>> {
  let raw: Record<string, unknown>;
  if (useFirestore) {
    const db = await getFirestore();
    const doc = await db.collection(COLLECTION).doc(DOC_ID).get();
    if (doc.exists) {
      const parsed = parseStoredContent(doc.data());
      raw = parsed || readFileContent();
    } else {
      raw = readFileContent();
      await saveSiteContent(raw);
    }
  } else {
    raw = readFileContent();
  }

  const { content, changed } = upgradeSiteContent(raw);
  if (changed) {
    await saveSiteContent(content);
  }
  return content;
}

export async function saveSiteContent(content: Record<string, unknown>): Promise<void> {
  const updatedAt = new Date().toISOString();
  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(DOC_ID).set(stripUndefined({
      json: JSON.stringify(content),
      updatedAt,
    }));
    return;
  }
  fs.writeFileSync(CONTENT_FILE, JSON.stringify({ ...content, updatedAt }, null, 2), 'utf-8');
}

export async function getSiteContentMeta(): Promise<{
  title: string;
  updatedAt: string | null;
  sectionsTotal: number;
  sectionsEnabled: number;
}> {
  let updatedAt: string | null = null;
  let content: Record<string, unknown>;

  if (useFirestore) {
    const db = await getFirestore();
    const doc = await db.collection(COLLECTION).doc(DOC_ID).get();
    if (doc.exists) {
      const data = doc.data();
      updatedAt = (data?.updatedAt as string) || null;
      const parsed = parseStoredContent(data);
      content = parsed || readFileContent();
    } else {
      content = readFileContent();
    }
  } else {
    content = readFileContent();
    updatedAt = (content.updatedAt as string) || null;
  }

  const sections = Array.isArray(content.sections)
    ? (content.sections as { enabled?: boolean }[])
    : [];
  const site = content.site as { title?: string } | undefined;

  return {
    title: site?.title || 'Andrade Isenções',
    updatedAt,
    sectionsTotal: sections.length,
    sectionsEnabled: sections.filter((s) => s.enabled !== false).length,
  };
}
