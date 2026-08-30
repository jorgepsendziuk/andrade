import fs from 'fs';
import path from 'path';
import { randomUUID } from 'crypto';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';
import type { FileTypeCode, ProcessFileRecord } from './types/process.js';

const FILES_FILE = dataFile('process-files.json');
const COLLECTION = 'process_files';

function readFileRecords(): ProcessFileRecord[] {
  try {
    return JSON.parse(fs.readFileSync(FILES_FILE, 'utf-8'));
  } catch {
    return [];
  }
}

function writeFileRecords(records: ProcessFileRecord[]) {
  fs.mkdirSync(path.dirname(FILES_FILE), { recursive: true });
  fs.writeFileSync(FILES_FILE, JSON.stringify(records, null, 2), 'utf-8');
}

export async function listProcessFiles(processId: string): Promise<ProcessFileRecord[]> {
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).where('processId', '==', processId).get();
    return snap.docs
      .map((d) => d.data() as ProcessFileRecord)
      .sort((a, b) => b.createdAt.localeCompare(a.createdAt));
  }
  return readFileRecords()
    .filter((f) => f.processId === processId)
    .sort((a, b) => b.createdAt.localeCompare(a.createdAt));
}

export async function listClientFiles(clientId: string): Promise<ProcessFileRecord[]> {
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).where('clientId', '==', clientId).get();
    return snap.docs
      .map((d) => d.data() as ProcessFileRecord)
      .sort((a, b) => b.createdAt.localeCompare(a.createdAt));
  }
  return readFileRecords()
    .filter((f) => f.clientId === clientId)
    .sort((a, b) => b.createdAt.localeCompare(a.createdAt));
}

export async function findProcessFileById(id: string): Promise<ProcessFileRecord | null> {
  if (useFirestore) {
    const db = await getFirestore();
    const doc = await db.collection(COLLECTION).doc(id).get();
    if (!doc.exists) return null;
    return doc.data() as ProcessFileRecord;
  }
  return readFileRecords().find((f) => f.id === id) ?? null;
}

export async function createProcessFile(input: {
  processId: string;
  clientId: string;
  fileType: FileTypeCode;
  objectName: string;
  originalName: string;
  mimeType: string;
  size: number;
  uploadedBy: string;
  uploadedByRole: ProcessFileRecord['uploadedByRole'];
}): Promise<ProcessFileRecord> {
  const record: ProcessFileRecord = {
    id: randomUUID(),
    ...input,
    createdAt: new Date().toISOString(),
  };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(record.id).set(stripUndefined(record));
  } else {
    const records = readFileRecords();
    records.push(record);
    writeFileRecords(records);
  }

  return record;
}

export async function updateProcessFile(
  id: string,
  patch: Partial<Pick<ProcessFileRecord, 'fileType' | 'originalName' | 'objectName' | 'mimeType' | 'size'>>
): Promise<ProcessFileRecord | null> {
  const record = await findProcessFileById(id);
  if (!record) return null;

  const updated: ProcessFileRecord = { ...record, ...patch };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(id).set(stripUndefined(updated));
  } else {
    const records = readFileRecords();
    const idx = records.findIndex((f) => f.id === id);
    if (idx === -1) return null;
    records[idx] = updated;
    writeFileRecords(records);
  }

  return updated;
}

export async function deleteProcessFile(id: string): Promise<ProcessFileRecord | null> {
  const record = await findProcessFileById(id);
  if (!record) return null;

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(id).delete();
  } else {
    const records = readFileRecords().filter((f) => f.id !== id);
    writeFileRecords(records);
  }

  return record;
}
