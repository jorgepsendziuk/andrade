import type {
  ClientPublic,
  DocsBrowseResult,
  FileTypeCode,
  ProcessFileRecord,
  ProcessListItem,
  ProcessModality,
  ProcessRecord,
  ProcessStatus,
} from '../types/process';
import type { AdminUser } from '../types/user';
import { getToken } from './api';

const API_BASE = '/api';

function authHeaders(): HeadersInit {
  const token = getToken();
  return token ? { Authorization: `Bearer ${token}` } : {};
}

export async function registerPortal(form: FormData) {
  const res = await fetch(`${API_BASE}/portal/register`, { method: 'POST', body: form });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha no cadastro');
  return data as {
    success: boolean;
    client: ClientPublic;
    processId: string;
    token: string;
    user: AdminUser;
  };
}

export async function fetchPortalMe(): Promise<ClientPublic> {
  const res = await fetch(`${API_BASE}/portal/me`, { headers: authHeaders() });
  if (!res.ok) throw new Error('Falha ao carregar dados');
  return res.json();
}

export async function fetchPortalProcess(): Promise<{
  process: ProcessRecord;
  files: ProcessFileRecord[];
}> {
  const res = await fetch(`${API_BASE}/portal/process`, { headers: authHeaders() });
  if (!res.ok) throw new Error('Falha ao carregar processo');
  return res.json();
}

export async function uploadPortalFile(file: File, fileType: FileTypeCode): Promise<ProcessFileRecord> {
  const form = new FormData();
  form.append('file', file);
  form.append('fileType', fileType);
  const res = await fetch(`${API_BASE}/portal/files`, {
    method: 'POST',
    headers: authHeaders(),
    body: form,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha no upload');
  return data;
}

export async function getPortalFileUrl(fileId: string): Promise<string> {
  const res = await fetch(`${API_BASE}/portal/files/${fileId}/url`, { headers: authHeaders() });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao abrir arquivo');
  return data.url as string;
}

export async function openPortalFile(fileId: string): Promise<void> {
  const url = await getPortalFileUrl(fileId);
  if (url.startsWith('http://') || url.startsWith('https://')) {
    window.open(url, '_blank', 'noopener,noreferrer');
    return;
  }
  const fileRes = await fetch(url, { headers: authHeaders() });
  if (!fileRes.ok) throw new Error('Falha ao carregar arquivo');
  const blob = await fileRes.blob();
  const blobUrl = URL.createObjectURL(blob);
  window.open(blobUrl, '_blank', 'noopener,noreferrer');
  window.setTimeout(() => URL.revokeObjectURL(blobUrl), 120_000);
}

export async function fetchAdminClients(): Promise<ClientPublic[]> {
  const res = await fetch(`${API_BASE}/admin/clients`, { headers: authHeaders() });
  if (!res.ok) throw new Error('Falha ao carregar clientes');
  return res.json();
}

export async function createAdminProcess(input: {
  clientId?: string;
  modality?: ProcessModality;
  force?: boolean;
  newClient?: {
    name: string;
    email: string;
    cpf: string;
    phone?: string;
    password?: string;
  };
}): Promise<{ process: ProcessListItem; tempPassword?: string }> {
  const res = await fetch(`${API_BASE}/admin/processes`, {
    method: 'POST',
    headers: { ...authHeaders(), 'Content-Type': 'application/json' },
    body: JSON.stringify(input),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao criar processo');
  return data;
}

export async function updateAdminProcessStatus(id: string, status: ProcessStatus) {
  return updateAdminProcess(id, { status });
}

export async function fetchAdminProcesses(): Promise<ProcessListItem[]> {
  const res = await fetch(`${API_BASE}/admin/processes`, { headers: authHeaders() });
  if (!res.ok) throw new Error('Falha ao carregar processos');
  return res.json();
}

export async function fetchAdminProcess(id: string) {
  const res = await fetch(`${API_BASE}/admin/processes/${id}`, { headers: authHeaders() });
  if (!res.ok) throw new Error('Falha ao carregar processo');
  return res.json() as Promise<{
    process: ProcessRecord;
    client: ClientPublic | null;
    storagePrefix?: string;
    files: ProcessFileRecord[];
    conductors: { id: string; nome: string; cpf: string; rg?: string; endereco?: string; telefone?: string; ordem: number }[];
    documentTemplates: { code: string; label: string }[];
  }>;
}

export async function updateAdminProcess(id: string, patch: Record<string, unknown>) {
  const res = await fetch(`${API_BASE}/admin/processes/${id}`, {
    method: 'PATCH',
    headers: { ...authHeaders(), 'Content-Type': 'application/json' },
    body: JSON.stringify(patch),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao atualizar');
  return data;
}

export async function updateProcessStep(id: string, stepKey: string, patch: Record<string, unknown>) {
  const res = await fetch(`${API_BASE}/admin/processes/${id}/step`, {
    method: 'PATCH',
    headers: { ...authHeaders(), 'Content-Type': 'application/json' },
    body: JSON.stringify({ stepKey, ...patch }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao atualizar etapa');
  return data;
}

export async function advanceProcessStep(id: string) {
  const res = await fetch(`${API_BASE}/admin/processes/${id}/advance`, {
    method: 'POST',
    headers: authHeaders(),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao avançar');
  return data;
}

export async function uploadAdminProcessFile(processId: string, file: File, fileType: FileTypeCode) {
  const form = new FormData();
  form.append('file', file);
  form.append('fileType', fileType);
  const res = await fetch(`${API_BASE}/admin/processes/${processId}/files`, {
    method: 'POST',
    headers: authHeaders(),
    body: form,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha no upload');
  return data;
}

export async function getAdminFileUrl(fileId: string): Promise<string> {
  const res = await fetch(`${API_BASE}/admin/files/${fileId}/url`, { headers: authHeaders() });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao abrir arquivo');
  return data.url as string;
}

/** Abre arquivo autenticado (GCS assinado ou blob local). */
export async function openAdminFile(fileId: string): Promise<void> {
  const res = await fetch(`${API_BASE}/admin/files/${fileId}/url`, { headers: authHeaders() });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao abrir arquivo');

  const url = data.url as string;
  if (url.startsWith('http://') || url.startsWith('https://')) {
    window.open(url, '_blank', 'noopener,noreferrer');
    return;
  }

  const fileRes = await fetch(url, { headers: authHeaders() });
  if (!fileRes.ok) throw new Error('Falha ao carregar arquivo');
  const blob = await fileRes.blob();
  const blobUrl = URL.createObjectURL(blob);
  window.open(blobUrl, '_blank', 'noopener,noreferrer');
  window.setTimeout(() => URL.revokeObjectURL(blobUrl), 120_000);
}

export async function downloadAdminFile(fileId: string, fileName?: string): Promise<void> {
  const res = await fetch(`${API_BASE}/admin/files/${fileId}/download`, { headers: authHeaders() });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data.error || 'Falha ao baixar');
  }
  const blob = await res.blob();
  const blobUrl = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = blobUrl;
  a.download = fileName || 'documento';
  a.click();
  URL.revokeObjectURL(blobUrl);
}

export async function updateAdminFile(
  fileId: string,
  patch: { fileType?: FileTypeCode; originalName?: string }
) {
  const res = await fetch(`${API_BASE}/admin/files/${fileId}`, {
    method: 'PATCH',
    headers: { ...authHeaders(), 'Content-Type': 'application/json' },
    body: JSON.stringify(patch),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao atualizar arquivo');
  return data as ProcessFileRecord;
}

export async function replaceAdminFile(fileId: string, file: File) {
  const form = new FormData();
  form.append('file', file);
  const res = await fetch(`${API_BASE}/admin/files/${fileId}/replace`, {
    method: 'POST',
    headers: authHeaders(),
    body: form,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao substituir arquivo');
  return data as ProcessFileRecord;
}

export async function deleteAdminFile(fileId: string) {
  const res = await fetch(`${API_BASE}/admin/files/${fileId}`, {
    method: 'DELETE',
    headers: authHeaders(),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao excluir arquivo');
  return data;
}

export async function browseDocs(prefix: string): Promise<DocsBrowseResult> {
  const res = await fetch(`${API_BASE}/admin/docs/browse?prefix=${encodeURIComponent(prefix)}`, {
    headers: authHeaders(),
  });
  if (!res.ok) throw new Error('Falha ao listar arquivos');
  return res.json();
}

export async function uploadToDocsPrefix(prefix: string, file: File) {
  const form = new FormData();
  form.append('file', file);
  form.append('prefix', prefix);
  const res = await fetch(`${API_BASE}/admin/docs/upload`, {
    method: 'POST',
    headers: authHeaders(),
    body: form,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha no upload');
  return data;
}

export function printProcessDocument(processId: string, template: string) {
  const token = getToken();
  window.open(
    `${API_BASE}/admin/processes/${processId}/print/${template}?token=${encodeURIComponent(token || '')}`,
    '_blank'
  );
}

export async function convertContactToProcess(contactId: string) {
  const res = await fetch(`${API_BASE}/admin/contacts/${contactId}/to-process`, {
    method: 'POST',
    headers: authHeaders(),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao criar processo');
  return data;
}
