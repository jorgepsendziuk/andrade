import type { FileTypeCode, ProcessModality, ProcessRecord, ProcessStep, ProcessStepKey } from './types/process.js';
import {
  buildClientFolderSlug,
  buildProcessFolderSlug,
  resolveClientStorageSlug,
  resolveProcessStorageSlug,
  slugifySegment,
} from './storage-slugs.js';

export const FILE_TYPE_LABELS: Record<FileTypeCode, string> = {
  cnh: 'CNH',
  cpf: 'CPF',
  doc_foto: 'Documento de Identificação com Foto',
  laudo: 'Laudo médico',
  comprovante_residencia: 'Comprovante de Residência',
  alvara_curatela: 'Alvará de Curatela',
  comprovante_pagamento: 'Comprovante de Pagamento',
  outros: 'Outros',
};

export const FILE_TYPE_FOLDERS: Record<FileTypeCode, string> = {
  cnh: 'identidade',
  cpf: 'identidade',
  doc_foto: 'identidade',
  laudo: 'laudos',
  comprovante_residencia: 'comprovantes',
  alvara_curatela: 'comprovantes',
  comprovante_pagamento: 'pagamento',
  outros: 'outros',
};

export const WIZARD_REQUIRED_FILES: FileTypeCode[] = [
  'cnh',
  'laudo',
  'comprovante_residencia',
];

export const STEP_LABELS: Record<ProcessStepKey, string> = {
  documentacao: 'Documentação',
  analise: 'Análise',
  pericia: 'Perícia / Junta Médica',
  ipi: 'IPI — SISEN',
  veiculo: 'Escolha do veículo',
  sefaz_mt: 'SEFAZ MT — ICMS e IPVA',
  sefaz_sp: 'SEFAZ SP — ICMS',
  icms: 'ICMS (SEFAZ)',
  ipva: 'IPVA',
  concluido: 'Concluído',
  cancelado: 'Cancelado',
};

/** Ordem canônica das etapas ativas do processo PCD. */
export const ACTIVE_STEPS: ProcessStepKey[] = [
  'documentacao',
  'analise',
  'pericia',
  'ipi',
  'veiculo',
  'sefaz_mt',
  'sefaz_sp',
  'concluido',
];

const LEGACY_STEP_KEYS: Partial<Record<ProcessStepKey, ProcessStepKey>> = {
  icms: 'sefaz_mt',
  ipva: 'sefaz_mt',
};

const STATUS_RANK: Record<ProcessStep['status'], number> = {
  pendente: 0,
  bloqueada: 1,
  em_andamento: 2,
  concluida: 3,
};

function mergeStepProgress(a: ProcessStep, b: ProcessStep, key: ProcessStepKey): ProcessStep {
  const primary = STATUS_RANK[a.status] >= STATUS_RANK[b.status] ? a : b;
  const secondary = primary === a ? b : a;
  return {
    key,
    label: STEP_LABELS[key],
    status: primary.status,
    protocol: primary.protocol || secondary.protocol,
    startedAt: primary.startedAt || secondary.startedAt,
    completedAt: primary.completedAt || secondary.completedAt,
    internalNote: [primary.internalNote, secondary.internalNote].filter(Boolean).join(' · ') || undefined,
  };
}

function migrateStepKey(key: ProcessStepKey): ProcessStepKey {
  return LEGACY_STEP_KEYS[key] ?? key;
}

export function normalizeProcessRecord(process: ProcessRecord): ProcessRecord {
  const merged = new Map<ProcessStepKey, ProcessStep>();

  for (const step of process.steps) {
    const key = migrateStepKey(step.key);
    const normalized: ProcessStep = {
      ...step,
      key,
      label: STEP_LABELS[key],
    };
    const existing = merged.get(key);
    merged.set(key, existing ? mergeStepProgress(existing, normalized, key) : normalized);
  }

  for (const key of ACTIVE_STEPS) {
    if (!merged.has(key)) {
      merged.set(key, { key, label: STEP_LABELS[key], status: 'pendente' });
    }
  }

  const steps = ACTIVE_STEPS.map((key) => merged.get(key)!);
  let currentStep = migrateStepKey(process.currentStep);
  if (!steps.some((s) => s.key === currentStep)) {
    currentStep =
      steps.find((s) => s.status === 'em_andamento')?.key ??
      steps.find((s) => s.status === 'pendente')?.key ??
      'concluido';
  }

  return { ...process, steps, currentStep };
}

export function createDefaultSteps(): ProcessStep[] {
  return ACTIVE_STEPS.map((key, index) => ({
    key,
    label: STEP_LABELS[key],
    status: index === 0 ? 'em_andamento' : 'pendente',
  }));
}

export interface StoragePathContext {
  clientSlug: string;
  processSlug: string;
}

export function buildStorageContext(
  client: { name: string; cpf: string; storageSlug?: string },
  process: { modality: ProcessModality; createdAt: string; id: string; storageSlug?: string }
): StoragePathContext {
  return {
    clientSlug: resolveClientStorageSlug(client),
    processSlug: resolveProcessStorageSlug(process),
  };
}

/** Nome de arquivo legível: cnh-marisol-ferreira.pdf */
export function buildReadableFileName(fileType: FileTypeCode, originalName: string): string {
  const ext = originalName.includes('.') ? originalName.slice(originalName.lastIndexOf('.')) : '';
  const base = originalName.includes('.') ? originalName.slice(0, originalName.lastIndexOf('.')) : originalName;
  const slug = slugifySegment(base, 50);
  const typePrefix = fileType === 'outros' ? '' : `${fileType}-`;
  return `${typePrefix}${slug}${ext.toLowerCase()}`.slice(0, 120);
}

export function buildFileObjectName(
  ctx: StoragePathContext,
  fileType: FileTypeCode,
  originalName: string
): string {
  const folder = FILE_TYPE_FOLDERS[fileType];
  const fileName = buildReadableFileName(fileType, originalName);
  if (folder === 'pagamento') {
    return `${buildProcessFolderSlug(ctx.clientSlug, ctx.processSlug, 'pagamento')}${fileName}`;
  }
  return `${buildClientFolderSlug(ctx.clientSlug, folder)}${fileName}`;
}

export function buildGeneratedDocPath(ctx: StoragePathContext, templateCode: string, ext = '.pdf'): string {
  const stamp = new Date().toISOString().slice(0, 10);
  const fileName = `${slugifySegment(templateCode, 24)}-${stamp}${ext}`;
  return `${buildProcessFolderSlug(ctx.clientSlug, ctx.processSlug, 'gerados')}${fileName}`;
}

/** Legado — mantido para paths antigos com UUID */
export function buildClientFolder(clientId: string, subfolder: string): string {
  return `clientes/${clientId}/${subfolder}/`;
}

/** Legado — mantido para paths antigos com UUID */
export function buildProcessFolder(clientId: string, processId: string, subfolder: string): string {
  return `clientes/${clientId}/processos/${processId}/${subfolder}/`;
}
