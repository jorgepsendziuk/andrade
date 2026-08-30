import type { ProcessModality } from './types/process.js';

const SLUG_MAX = 64;

/** Normaliza texto para segmento de pasta/arquivo (sem acentos, minúsculas, hífens). */
export function slugifySegment(value: string, max = SLUG_MAX): string {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, max) || 'arquivo';
}

export function normalizeCpfDigits(cpf: string): string {
  return cpf.replace(/\D/g, '');
}

/** Ex: marisol-aparecida-ferreira-12345678909 */
export function buildClientStorageSlug(name: string, cpf: string): string {
  const namePart = slugifySegment(name, 40);
  const cpfPart = normalizeCpfDigits(cpf);
  return `${namePart}-${cpfPart}`.slice(0, 80);
}

/** Ex: pcd-2026-08-30-a26f0c99 */
export function buildProcessStorageSlug(
  modality: ProcessModality,
  createdAt: string,
  processId: string
): string {
  const date = createdAt.slice(0, 10);
  return `${modality}-${date}-${processId.slice(0, 8)}`.slice(0, 48);
}

export function resolveClientStorageSlug(client: {
  name: string;
  cpf: string;
  storageSlug?: string;
}): string {
  return client.storageSlug || buildClientStorageSlug(client.name, client.cpf);
}

export function resolveProcessStorageSlug(process: {
  modality: ProcessModality;
  createdAt: string;
  id: string;
  storageSlug?: string;
}): string {
  return (
    process.storageSlug ||
    buildProcessStorageSlug(process.modality, process.createdAt, process.id)
  );
}

/** Verifica se o cliente tem acesso ao objectName (slug novo ou UUID legado). */
export function clientOwnsObjectName(
  clientId: string,
  clientSlug: string,
  objectName: string
): boolean {
  return (
    objectName.startsWith(`clientes/${clientSlug}/`) ||
    objectName.startsWith(`clientes/${clientId}/`)
  );
}

/** Caminho amigável para exibição na UI. */
export function formatHumanStoragePath(objectName: string): string {
  const trimmed = objectName.replace(/\/$/, '');
  const parts = trimmed.split('/');
  if (parts[0] !== 'clientes' || parts.length < 2) return objectName;
  return parts.slice(1).join(' › ');
}

export function buildClientRootPrefix(clientSlug: string): string {
  return `clientes/${clientSlug}/`;
}

export function buildClientFolderSlug(clientSlug: string, subfolder: string): string {
  return `${buildClientRootPrefix(clientSlug)}${subfolder}/`;
}

export function buildProcessFolderSlug(
  clientSlug: string,
  processSlug: string,
  subfolder: string
): string {
  return `${buildClientRootPrefix(clientSlug)}processos/${processSlug}/${subfolder}/`;
}
