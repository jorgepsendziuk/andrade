/** Normaliza e-mail para login, busca e tokens de autenticação. */
export function normalizeAuthEmail(email: string): string {
  return email.trim().toLowerCase().replace(/\.+$/, '');
}
