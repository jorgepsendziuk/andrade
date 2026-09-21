import fs from 'fs';
import path from 'path';
import { randomUUID } from 'crypto';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';
import { createDefaultSteps, normalizeProcessRecord } from './process-constants.js';
import { buildProcessStorageSlug } from './storage-slugs.js';
import type { ProcessModality, ProcessRecord, ProcessStatus, ProcessStep, ProcessStepKey } from './types/process.js';

const PROCESSES_FILE = dataFile('processes.json');
const COLLECTION = 'processes';

function readFileProcesses(): ProcessRecord[] {
  try {
    return JSON.parse(fs.readFileSync(PROCESSES_FILE, 'utf-8'));
  } catch {
    return [];
  }
}

function writeFileProcesses(processes: ProcessRecord[]) {
  fs.mkdirSync(path.dirname(PROCESSES_FILE), { recursive: true });
  fs.writeFileSync(PROCESSES_FILE, JSON.stringify(processes, null, 2), 'utf-8');
}

export async function findProcessById(id: string): Promise<ProcessRecord | null> {
  let process: ProcessRecord | null = null;
  if (useFirestore) {
    const db = await getFirestore();
    const doc = await db.collection(COLLECTION).doc(id).get();
    if (!doc.exists) return null;
    process = doc.data() as ProcessRecord;
  } else {
    process = readFileProcesses().find((p) => p.id === id) ?? null;
  }
  return process ? normalizeProcessRecord(process) : null;
}

export async function findActiveProcessByClientId(clientId: string): Promise<ProcessRecord | null> {
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).where('clientId', '==', clientId).get();
    const active = snap.docs
      .map((d) => normalizeProcessRecord(d.data() as ProcessRecord))
      .filter((p) => p.status === 'ativo')
      .sort((a, b) => b.createdAt.localeCompare(a.createdAt));
    return active[0] ?? null;
  }
  return (
    readFileProcesses()
      .map(normalizeProcessRecord)
      .find((p) => p.clientId === clientId && p.status === 'ativo') ?? null
  );
}

export async function listProcessesByClientId(clientId: string, limit = 100): Promise<ProcessRecord[]> {
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).where('clientId', '==', clientId).limit(limit).get();
    return snap.docs
      .map((d) => normalizeProcessRecord(d.data() as ProcessRecord))
      .sort((a, b) => b.createdAt.localeCompare(a.createdAt));
  }
  return readFileProcesses()
    .map(normalizeProcessRecord)
    .filter((p) => p.clientId === clientId)
    .sort((a, b) => b.createdAt.localeCompare(a.createdAt))
    .slice(0, limit);
}

export async function listProcesses(limit = 200): Promise<ProcessRecord[]> {
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).orderBy('createdAt', 'desc').limit(limit).get();
    return snap.docs.map((d) => normalizeProcessRecord(d.data() as ProcessRecord));
  }
  return readFileProcesses()
    .map(normalizeProcessRecord)
    .sort((a, b) => b.createdAt.localeCompare(a.createdAt))
    .slice(0, limit);
}

export async function createProcess(input: {
  clientId: string;
  modality?: ProcessModality;
  contactId?: string;
}): Promise<ProcessRecord> {
  const now = new Date().toISOString();
  const id = randomUUID();
  const process: ProcessRecord = {
    id,
    clientId: input.clientId,
    modality: input.modality ?? 'pcd',
    status: 'ativo',
    currentStep: 'documentacao',
    steps: createDefaultSteps(),
    storageSlug: buildProcessStorageSlug(input.modality ?? 'pcd', now, id),
    ...(input.contactId ? { contactId: input.contactId } : {}),
    createdAt: now,
    updatedAt: now,
  };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(process.id).set(stripUndefined(process));
  } else {
    const processes = readFileProcesses();
    processes.push(process);
    writeFileProcesses(processes);
  }

  return process;
}

export async function updateProcess(
  id: string,
  patch: Partial<Omit<ProcessRecord, 'id' | 'clientId' | 'createdAt'>>
): Promise<ProcessRecord | null> {
  const process = await findProcessById(id);
  if (!process) return null;

  const updated: ProcessRecord = {
    ...process,
    ...patch,
    updatedAt: new Date().toISOString(),
  };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(id).set(stripUndefined(updated));
  } else {
    const processes = readFileProcesses();
    const idx = processes.findIndex((p) => p.id === id);
    if (idx === -1) return null;
    processes[idx] = updated;
    writeFileProcesses(processes);
  }

  return updated;
}

export async function updateProcessStep(
  processId: string,
  stepKey: ProcessStepKey,
  patch: Partial<ProcessStep>
): Promise<ProcessRecord | null> {
  const process = await findProcessById(processId);
  if (!process) return null;

  const steps = process.steps.map((s) =>
    s.key === stepKey ? { ...s, ...patch } : s
  );

  let currentStep = process.currentStep;
  let status: ProcessStatus = process.status;

  if (stepKey === 'concluido' && patch.status === 'concluida') {
    currentStep = 'concluido';
    status = 'concluido';
  } else if (stepKey === 'cancelado' && patch.status === 'concluida') {
    currentStep = 'cancelado';
    status = 'cancelado';
  } else if (patch.status === 'em_andamento') {
    currentStep = stepKey;
  }

  return updateProcess(processId, { steps, currentStep, status });
}

export async function advanceProcessStep(processId: string): Promise<ProcessRecord | null> {
  const process = await findProcessById(processId);
  if (!process) return null;

  const idx = process.steps.findIndex((s) => s.key === process.currentStep);
  if (idx === -1) return process;

  const now = new Date().toISOString();
  const steps = process.steps.map((s, i) => {
    if (i === idx) return { ...s, status: 'concluida' as const, completedAt: now };
    if (i === idx + 1) return { ...s, status: 'em_andamento' as const, startedAt: now };
    return s;
  });

  const nextStep = steps[idx + 1]?.key ?? 'concluido';
  const status: ProcessStatus = nextStep === 'concluido' ? 'concluido' : process.status;

  return updateProcess(processId, {
    steps,
    currentStep: nextStep,
    status,
  });
}
