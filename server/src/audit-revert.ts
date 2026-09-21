import { applyClientPatch, CLIENT_AUDIT_FIELDS, parseLegalRepresentative } from './client-patch.js';
import { findProcessById, updateProcess } from './processes-store.js';
import { findClientById } from './clients-store.js';
import { diffRecords, summarizeChanges } from './audit-diff.js';
import type { AuditChange, AuditLogRecord, ProcessRecord, ProcessStep } from './types/process.js';

const CLIENT_REVERT_FIELDS = new Set<string>(CLIENT_AUDIT_FIELDS);
const PROCESS_REVERT_FIELDS = new Set([
  'modality',
  'status',
  'vehicle',
  'honorarios',
  'pagamentoTipo',
  'pagamentoStatus',
  'pagamentos',
  'currentStep',
  'steps',
  'step',
]);

export function isRevertableAudit(log: AuditLogRecord): boolean {
  if (log.action !== 'update' || !log.resourceId || !log.changes?.length) return false;
  if (log.resourceType === 'client') {
    return log.changes.every((change) => CLIENT_REVERT_FIELDS.has(change.field));
  }
  if (log.resourceType === 'process') {
    return log.changes.every((change) => PROCESS_REVERT_FIELDS.has(change.field));
  }
  return false;
}

function emptyToBlank(value: unknown): string {
  if (value === null || value === undefined) return '';
  return String(value);
}

function buildClientRevertBody(changes: AuditChange[]) {
  const body: Record<string, unknown> = {};
  for (const change of changes) {
    if (change.field === 'representante') {
      body.representante = change.from === null || change.from === undefined
        ? null
        : parseLegalRepresentative(change.from);
      continue;
    }
    if (change.field === 'active') {
      body.active = change.from !== false;
      continue;
    }
    if (change.field === 'genero') {
      body.genero = change.from === 'F' || change.from === 'M' ? change.from : '';
      continue;
    }
    body[change.field] = emptyToBlank(change.from);
  }
  return body;
}

function buildProcessRevertPatch(process: ProcessRecord, changes: AuditChange[]): Partial<ProcessRecord> {
  const patch: Partial<ProcessRecord> = {};
  for (const change of changes) {
    if (change.field === 'step') {
      const previous = change.from as ProcessStep | null;
      if (previous?.key) {
        patch.steps = process.steps.map((step) => (step.key === previous.key ? previous : step));
      }
      continue;
    }
    if (change.field === 'vehicle') {
      patch.vehicle = change.from && typeof change.from === 'object' ? (change.from as ProcessRecord['vehicle']) : {};
      continue;
    }
    (patch as Record<string, unknown>)[change.field] = change.from ?? null;
  }
  return patch;
}

export async function revertAuditLog(log: AuditLogRecord): Promise<{
  resourceType: string;
  resourceId: string;
  changes: AuditChange[];
}> {
  if (!isRevertableAudit(log) || !log.resourceId || !log.changes) {
    throw new Error('Esta alteração não pode ser desfeita.');
  }

  if (log.resourceType === 'client') {
    const before = await findClientById(log.resourceId);
    if (!before) throw new Error('Cadastro não encontrado.');
    const updated = await applyClientPatch(log.resourceId, buildClientRevertBody(log.changes), {
      allowIdentity: true,
      allowActive: true,
    });
    if (!updated) throw new Error('Cadastro não encontrado.');
    return {
      resourceType: 'client',
      resourceId: log.resourceId,
      changes: diffRecords(
        before as unknown as Record<string, unknown>,
        updated as unknown as Record<string, unknown>,
        [...CLIENT_AUDIT_FIELDS]
      ),
    };
  }

  const before = await findProcessById(log.resourceId);
  if (!before) throw new Error('Processo não encontrado.');
  const updated = await updateProcess(log.resourceId, buildProcessRevertPatch(before, log.changes));
  if (!updated) throw new Error('Processo não encontrado.');
  return {
    resourceType: 'process',
    resourceId: log.resourceId,
    changes: diffRecords(
      before as unknown as Record<string, unknown>,
      updated as unknown as Record<string, unknown>,
      [...PROCESS_REVERT_FIELDS]
    ),
  };
}

export { summarizeChanges };
