import fs from 'fs';
import path from 'path';
import { randomUUID } from 'crypto';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';
import { updateClient } from './clients-store.js';
import { labelFields } from './field-labels.js';
import type { AuditChange, StaffAlert, StaffAlertType } from './types/process.js';

const ALERTS_FILE = dataFile('staff-alerts.json');
const COLLECTION = 'staff_alerts';

function readFileAlerts(): StaffAlert[] {
  try {
    return JSON.parse(fs.readFileSync(ALERTS_FILE, 'utf-8'));
  } catch {
    return [];
  }
}

function writeFileAlerts(alerts: StaffAlert[]) {
  fs.mkdirSync(path.dirname(ALERTS_FILE), { recursive: true });
  fs.writeFileSync(ALERTS_FILE, JSON.stringify(alerts, null, 2), 'utf-8');
}

async function saveAlert(alert: StaffAlert): Promise<void> {
  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(alert.id).set(stripUndefined(alert));
    return;
  }
  const alerts = readFileAlerts();
  const idx = alerts.findIndex((a) => a.id === alert.id);
  if (idx >= 0) alerts[idx] = alert;
  else alerts.unshift(alert);
  writeFileAlerts(alerts.slice(0, 3000));
}

export async function createStaffAlert(input: Omit<StaffAlert, 'id' | 'createdAt' | 'unread'> & { unread?: boolean }): Promise<StaffAlert> {
  const alert: StaffAlert = {
    id: randomUUID(),
    ...input,
    unread: input.unread !== false,
    createdAt: new Date().toISOString(),
  };
  await saveAlert(alert);
  return alert;
}

export async function listStaffAlerts(options?: {
  limit?: number;
  unreadOnly?: boolean;
  clientId?: string;
}): Promise<StaffAlert[]> {
  const limit = Math.min(Math.max(options?.limit ?? 200, 1), 500);
  let alerts: StaffAlert[];

  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).orderBy('createdAt', 'desc').limit(500).get();
    alerts = snap.docs.map((d) => d.data() as StaffAlert);
  } else {
    alerts = readFileAlerts().sort((a, b) => b.createdAt.localeCompare(a.createdAt));
  }

  return alerts
    .filter((alert) => {
      if (options?.unreadOnly && !alert.unread) return false;
      if (options?.clientId && alert.clientId !== options.clientId) return false;
      return true;
    })
    .slice(0, limit);
}

export async function countUnreadStaffAlerts(): Promise<number> {
  const unread = await listStaffAlerts({ unreadOnly: true, limit: 500 });
  return unread.length;
}

export async function findStaffAlert(id: string): Promise<StaffAlert | null> {
  if (useFirestore) {
    const db = await getFirestore();
    const doc = await db.collection(COLLECTION).doc(id).get();
    return doc.exists ? (doc.data() as StaffAlert) : null;
  }
  return readFileAlerts().find((a) => a.id === id) ?? null;
}

export async function recordClientSelfEdit(input: {
  type: StaffAlertType;
  client: { id: string; name: string };
  processId?: string;
  changes: AuditChange[];
}): Promise<StaffAlert> {
  const fields = input.changes.map((c) => c.field);
  const title =
    input.type === 'process_self_edit'
      ? `${input.client.name} alterou o veículo do processo`
      : `${input.client.name} alterou o cadastro`;
  const alert = await createStaffAlert({
    type: input.type,
    title,
    summary: `Campos: ${labelFields(fields)}`,
    clientId: input.client.id,
    clientName: input.client.name,
    processId: input.processId,
    changes: input.changes,
  });
  await updateClient(input.client.id, {
    lastSelfEditAt: alert.createdAt,
    lastSelfEditFields: fields,
    lastSelfEditAlertId: alert.id,
  });
  return alert;
}

export async function acknowledgeStaffAlert(
  id: string,
  reader: { id: string; email?: string }
): Promise<StaffAlert | null> {
  const alert = await findStaffAlert(id);
  if (!alert) return null;

  const updated: StaffAlert = {
    ...alert,
    unread: false,
    readAt: new Date().toISOString(),
    readBy: reader.id,
    readByEmail: reader.email,
  };
  await saveAlert(updated);

  const remaining = await listStaffAlerts({ clientId: alert.clientId, unreadOnly: true, limit: 20 });
  if (!remaining.length) {
    await updateClient(alert.clientId, {
      lastSelfEditAt: undefined,
      lastSelfEditFields: undefined,
      lastSelfEditAlertId: undefined,
    });
  } else {
    const latest = remaining[0];
    await updateClient(alert.clientId, {
      lastSelfEditAt: latest.createdAt,
      lastSelfEditFields: latest.changes.map((c) => c.field),
      lastSelfEditAlertId: latest.id,
    });
  }

  return updated;
}
