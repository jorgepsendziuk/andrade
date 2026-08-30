import type { ConditionPage, GuiaArticle } from '../types/site';
import type { ContactSubmission, ContactStatus } from '../types/contact';
import type { AdminUser, AdminStats, UserRole } from '../types/user';
import type { AdminSettingsResponse } from '../types/settings';
import type { Ga4DashboardData } from '../types/ga4';
import type { MediaListResponse } from '../types/media';
import type { InfraInfo } from '../types/infra';

const API_BASE = '/api';

export interface GoogleReview {
  author: string;
  rating: number;
  text: string;
  time?: string;
}

export interface GoogleReviewsData {
  rating: number;
  totalReviews: number;
  reviews: GoogleReview[];
  source: 'api' | 'fallback';
}

export interface InstagramPost {
  id: string;
  caption: string;
  mediaType: 'IMAGE' | 'VIDEO' | 'CAROUSEL_ALBUM';
  mediaUrl: string;
  thumbnailUrl?: string;
  permalink: string;
  timestamp: string;
}

export interface InstagramFeedData {
  posts: InstagramPost[];
  username: string;
  profileUrl: string;
  source: 'api' | 'fallback';
}

export async function fetchContent() {
  const res = await fetch(`${API_BASE}/content`);
  if (!res.ok) throw new Error('Falha ao carregar conteúdo');
  return res.json();
}

export async function fetchGoogleReviews(): Promise<GoogleReviewsData> {
  const res = await fetch(`${API_BASE}/google-reviews`);
  if (!res.ok) throw new Error('Falha ao carregar avaliações');
  return res.json();
}

export async function fetchInstagramFeed(): Promise<InstagramFeedData> {
  const res = await fetch(`${API_BASE}/instagram`);
  if (!res.ok) throw new Error('Falha ao carregar Instagram');
  return res.json();
}

export async function fetchConditions(): Promise<ConditionPage[]> {
  const res = await fetch(`${API_BASE}/conditions`);
  if (!res.ok) throw new Error('Falha ao carregar condições');
  return res.json();
}

export async function fetchCondition(slug: string): Promise<ConditionPage> {
  const res = await fetch(`${API_BASE}/conditions/${slug}`);
  if (!res.ok) throw new Error('Condição não encontrada');
  return res.json();
}

export async function fetchGuiaArticles(): Promise<GuiaArticle[]> {
  const res = await fetch(`${API_BASE}/guia`);
  if (!res.ok) throw new Error('Falha ao carregar guia');
  return res.json();
}

export async function fetchGuiaArticle(slug: string): Promise<GuiaArticle> {
  const res = await fetch(`${API_BASE}/guia/${slug}`);
  if (!res.ok) throw new Error('Artigo não encontrado');
  return res.json();
}

export class ContactApiError extends Error {
  status: number;
  code?: string;

  constructor(message: string, status: number, code?: string) {
    super(message);
    this.status = status;
    this.code = code;
  }
}

export async function submitContact(payload: {
  name: string;
  email: string;
  phone: string;
  message: string;
  to?: string;
}) {
  const res = await fetch(`${API_BASE}/contact`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new ContactApiError(
      data.error || 'Falha ao enviar mensagem',
      res.status,
      data.code
    );
  }
  return data;
}

export async function saveContent(content: unknown, token: string) {
  const res = await fetch(`${API_BASE}/content`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(content),
  });
  if (!res.ok) throw new Error('Falha ao salvar conteúdo');
  return res.json();
}

export async function login(email: string, password: string) {
  const res = await fetch(`${API_BASE}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data.error || 'Falha no login');
  }
  return res.json() as Promise<{ token: string; user: AdminUser }>;
}

export async function fetchMe(): Promise<AdminUser> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/auth/me`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!res.ok) throw new Error('Sessão expirada');
  return res.json();
}

export async function changePassword(currentPassword: string, newPassword: string) {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/auth/password`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({ currentPassword, newPassword }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao alterar senha');
  return data;
}

export async function fetchAdminStats(): Promise<AdminStats> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/stats`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!res.ok) throw new Error('Falha ao carregar estatísticas');
  return res.json();
}

export async function fetchInfraInfo(refresh = false): Promise<InfraInfo> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const query = refresh ? '?refresh=1' : '';
  const res = await fetch(`${API_BASE}/admin/infra${query}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!res.ok) throw new Error('Falha ao carregar infraestrutura');
  return res.json();
}

export async function triggerBackup(force = false) {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/backup/run`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ force }),
  });
  if (!res.ok) {
    const data = (await res.json().catch(() => ({}))) as { error?: string };
    throw new Error(data.error || 'Falha ao executar backup');
  }
  return res.json();
}

export async function fetchGa4Dashboard(refresh = false): Promise<Ga4DashboardData> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const query = refresh ? '?refresh=1' : '';
  const res = await fetch(`${API_BASE}/admin/analytics/ga4${query}`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!res.ok) throw new Error('Falha ao carregar analytics GA4');
  return res.json();
}

export async function fetchUsers(): Promise<AdminUser[]> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/users`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!res.ok) throw new Error('Falha ao carregar usuários');
  return res.json();
}

export async function createUser(payload: {
  email: string;
  name: string;
  password: string;
  role: UserRole;
}) {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/users`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao criar usuário');
  return data as AdminUser;
}

export async function updateUser(
  id: string,
  patch: Partial<Pick<AdminUser, 'name' | 'role' | 'active'>>
) {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/users/${id}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(patch),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao atualizar usuário');
  return data as AdminUser;
}

export async function deactivateUser(id: string) {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/users/${id}`, {
    method: 'DELETE',
    headers: { Authorization: `Bearer ${token}` },
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao desativar usuário');
  return data as AdminUser;
}

export async function fetchContacts(): Promise<ContactSubmission[]> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/contacts`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!res.ok) throw new Error('Falha ao carregar contatos');
  return res.json();
}

export async function updateContactStatus(
  id: string,
  status: ContactStatus
): Promise<ContactSubmission> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/contacts/${id}`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({ status }),
  });
  if (!res.ok) throw new Error('Falha ao atualizar contato');
  return res.json();
}

export async function uploadFile(file: File, token: string): Promise<{ url: string }> {
  const form = new FormData();
  form.append('file', file);
  const res = await fetch(`${API_BASE}/upload`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` },
    body: form,
  });
  if (!res.ok) {
    const data = (await res.json().catch(() => ({}))) as { error?: string };
    throw new Error(data.error || 'Falha no upload');
  }
  return res.json();
}

export async function fetchMediaLibrary(): Promise<MediaListResponse> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/media`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!res.ok) {
    const data = (await res.json().catch(() => ({}))) as { error?: string };
    throw new Error(data.error || 'Falha ao carregar mídia');
  }
  return res.json();
}

export async function deleteMediaFile(objectName: string): Promise<void> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/media`, {
    method: 'DELETE',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({ objectName }),
  });
  if (!res.ok) {
    const data = (await res.json().catch(() => ({}))) as { error?: string };
    throw new Error(data.error || 'Falha ao excluir arquivo');
  }
}

export async function fetchAdminSettings(): Promise<AdminSettingsResponse> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/settings`, {
    headers: { Authorization: `Bearer ${token}` },
  });
  if (!res.ok) throw new Error('Falha ao carregar configurações');
  return res.json();
}

export async function updateEmailSettings(payload: {
  smtpHost?: string;
  smtpPort?: number;
  smtpSecure?: boolean;
  smtpUser?: string;
  smtpFrom?: string;
  contactEmail?: string;
  smtpPass?: string | null;
}): Promise<AdminSettingsResponse> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/settings/email`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao salvar e-mail');
  return data;
}

export async function updateIntegrationSettings(payload: {
  googlePlaceId?: string;
  instagramUsername?: string;
  instagramProfileUrl?: string;
  siteUrl?: string;
}): Promise<AdminSettingsResponse> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/settings/integrations`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(payload),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha ao salvar integrações');
  return data;
}

export async function sendTestEmail(to: string): Promise<void> {
  const token = getToken();
  if (!token) throw new Error('Não autenticado');
  const res = await fetch(`${API_BASE}/admin/settings/email/test`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({ to }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Falha no teste de e-mail');
}

export function getToken(): string | null {
  return localStorage.getItem('andrade_token');
}

export function setToken(token: string | null) {
  if (token) localStorage.setItem('andrade_token', token);
  else localStorage.removeItem('andrade_token');
}
