import { findClientById } from './clients-store.js';
import { findProcessById } from './processes-store.js';
import { buildStorageContext, type StoragePathContext } from './process-constants.js';
import { buildClientStorageSlug, resolveClientStorageSlug } from './storage-slugs.js';
import type { ClientRecord } from './types/process.js';

export async function resolveStorageContext(
  clientId: string,
  processId: string
): Promise<StoragePathContext> {
  const [client, process] = await Promise.all([
    findClientById(clientId),
    findProcessById(processId),
  ]);
  if (!client || !process) {
    throw new Error('Cliente ou processo não encontrado.');
  }
  return buildStorageContext(client, process);
}

/** Garante storageSlug persistido no cliente (retrocompatível). */
export async function ensureClientStorageSlug(client: ClientRecord): Promise<string> {
  if (client.storageSlug) return client.storageSlug;
  const storageSlug = buildClientStorageSlug(client.name, client.cpf);
  const { updateClient } = await import('./clients-store.js');
  await updateClient(client.id, { storageSlug });
  return storageSlug;
}

export function getClientStoragePrefix(client: {
  name: string;
  cpf: string;
  storageSlug?: string;
}): string {
  return `clientes/${resolveClientStorageSlug(client)}/`;
}
