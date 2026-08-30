import fs from 'fs';
import path from 'path';
import { randomUUID } from 'crypto';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';
import type { AuditLogRecord } from './types/process.js';

const AUDIT_FILE = dataFile('audit-logs.json');
const COLLECTION = 'audit_logs';

function readFileLogs(): AuditLogRecord[] {
  try {
    return JSON.parse(fs.readFileSync(AUDIT_FILE, 'utf-8'));
  } catch {
    return [];
  }
}

function writeFileLogs(logs: AuditLogRecord[]) {
  fs.mkdirSync(path.dirname(AUDIT_FILE), { recursive: true });
  fs.writeFileSync(AUDIT_FILE, JSON.stringify(logs, null, 2), 'utf-8');
}

export async function logAudit(input: Omit<AuditLogRecord, 'id' | 'createdAt'>): Promise<void> {
  const record: AuditLogRecord = {
    id: randomUUID(),
    ...input,
    createdAt: new Date().toISOString(),
  };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(record.id).set(stripUndefined(record));
  } else {
    const logs = readFileLogs();
    logs.unshift(record);
    writeFileLogs(logs.slice(0, 5000));
  }
}

export async function listAuditLogs(limit = 100): Promise<AuditLogRecord[]> {
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).orderBy('createdAt', 'desc').limit(limit).get();
    return snap.docs.map((d) => d.data() as AuditLogRecord);
  }
  return readFileLogs().slice(0, limit);
}
