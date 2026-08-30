const attempts = new Map<string, { count: number; resetAt: number }>();

const MAX_FAILED_ATTEMPTS = 20;
const WINDOW_MS = 15 * 60 * 1000;

export function isLoginExempt(email: string): boolean {
  const exempt = (process.env.LOGIN_RATE_LIMIT_EXEMPT || 'jimxxx@gmail.com')
    .split(',')
    .map((e) => e.trim().toLowerCase())
    .filter(Boolean);
  return exempt.includes(email.trim().toLowerCase());
}

/** Registra falha de login; retorna bloqueio se excedeu o limite. */
export function recordFailedLogin(key: string): { blocked: boolean; retryAfterSec?: number } {
  const now = Date.now();
  const entry = attempts.get(key);

  if (!entry || now > entry.resetAt) {
    attempts.set(key, { count: 1, resetAt: now + WINDOW_MS });
    return { blocked: false };
  }

  entry.count += 1;
  if (entry.count >= MAX_FAILED_ATTEMPTS) {
    return { blocked: true, retryAfterSec: Math.ceil((entry.resetAt - now) / 1000) };
  }
  return { blocked: false };
}

export function clearRateLimit(key: string) {
  attempts.delete(key);
}
