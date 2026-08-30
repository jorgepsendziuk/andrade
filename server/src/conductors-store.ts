import fs from 'fs';
import path from 'path';
import { randomUUID } from 'crypto';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';
import type { ConductorRecord } from './types/process.js';

const CONDUCTORS_FILE = dataFile('conductors.json');
const COLLECTION = 'conductors';

function readFileConductors(): ConductorRecord[] {
  try {
    return JSON.parse(fs.readFileSync(CONDUCTORS_FILE, 'utf-8'));
  } catch {
    return [];
  }
}

function writeFileConductors(conductors: ConductorRecord[]) {
  fs.mkdirSync(path.dirname(CONDUCTORS_FILE), { recursive: true });
  fs.writeFileSync(CONDUCTORS_FILE, JSON.stringify(conductors, null, 2), 'utf-8');
}

export async function listConductors(processId: string): Promise<ConductorRecord[]> {
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).where('processId', '==', processId).get();
    return snap.docs
      .map((d) => d.data() as ConductorRecord)
      .sort((a, b) => a.ordem - b.ordem);
  }
  return readFileConductors()
    .filter((c) => c.processId === processId)
    .sort((a, b) => a.ordem - b.ordem);
}

export async function upsertConductors(
  processId: string,
  clientId: string,
  items: { nome: string; cpf: string; rg?: string; endereco?: string; telefone?: string; ordem?: number }[]
): Promise<ConductorRecord[]> {
  const now = new Date().toISOString();
  const records: ConductorRecord[] = items.slice(0, 3).map((item, index) =>
    stripUndefined({
      id: randomUUID(),
      processId,
      clientId,
      ...item,
      ordem: index + 1,
      createdAt: now,
      updatedAt: now,
    })
  );

  if (useFirestore) {
    const db = await getFirestore();
    const existing = await db.collection(COLLECTION).where('processId', '==', processId).get();
    const batch = db.batch();
    existing.docs.forEach((d) => batch.delete(d.ref));
    records.forEach((r) => batch.set(db.collection(COLLECTION).doc(r.id), stripUndefined(r)));
    await batch.commit();
  } else {
    const others = readFileConductors().filter((c) => c.processId !== processId);
    writeFileConductors([...others, ...records]);
  }

  return records;
}
