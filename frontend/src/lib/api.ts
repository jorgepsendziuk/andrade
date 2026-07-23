const API_BASE = '/api';

export async function fetchContent() {
  const res = await fetch(`${API_BASE}/content`);
  if (!res.ok) throw new Error('Falha ao carregar conteúdo');
  return res.json();
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

export async function login(username: string, password: string) {
  const res = await fetch(`${API_BASE}/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password }),
  });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data.error || 'Falha no login');
  }
  return res.json();
}

export async function uploadFile(file: File, token: string) {
  const form = new FormData();
  form.append('file', file);
  const res = await fetch(`${API_BASE}/upload`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` },
    body: form,
  });
  if (!res.ok) throw new Error('Falha no upload');
  return res.json();
}

export function getToken(): string | null {
  return localStorage.getItem('andrade_token');
}

export function setToken(token: string | null) {
  if (token) localStorage.setItem('andrade_token', token);
  else localStorage.removeItem('andrade_token');
}
