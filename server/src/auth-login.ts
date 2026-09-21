import { findUserByEmail, findUserById, verifyPassword } from './users-store.js';
import { toPublicUser } from './types/user.js';
import type { UserRole } from './types/user.js';
import {
  findClientByEmail,
  findClientById,
  toPublicClient,
  verifyClientPassword,
} from './clients-store.js';
import { signToken } from './auth.js';
import { normalizeAuthEmail } from './auth-email.js';

export type AuthRole = UserRole | 'cliente';

export interface AuthUserPublic {
  id: string;
  email: string;
  name: string;
  role: AuthRole;
  active?: boolean;
  mustChangePassword?: boolean;
  createdAt?: string;
  updatedAt?: string;
}

export async function authenticateUser(
  email: string,
  password: string
): Promise<{ token: string; user: AuthUserPublic } | null> {
  const normalized = normalizeAuthEmail(email);
  const pwd = password.trim();
  if (!pwd) return null;

  const admin = await findUserByEmail(normalized);
  const client = await findClientByEmail(normalized);
  const staffOk = Boolean(admin && admin.active !== false && (await verifyPassword(admin, pwd)));
  const clientOk = Boolean(client && client.active !== false && (await verifyClientPassword(client, pwd)));

  if (admin && admin.active !== false && (staffOk || clientOk)) {
    const payload = { id: admin.id, email: admin.email, name: admin.name, role: admin.role as AuthRole };
    return { token: signToken(payload), user: { ...toPublicUser(admin), role: admin.role } };
  }

  if (clientOk && client) {
    const payload = { id: client.id, email: client.email, name: client.name, role: 'cliente' as AuthRole };
    return { token: signToken(payload), user: { ...toPublicClient(client), role: 'cliente' } };
  }

  return null;
}

export async function getAuthUser(id: string, role: AuthRole): Promise<AuthUserPublic | null> {
  if (role === 'cliente') {
    const client = await findClientById(id);
    if (!client || client.active === false) return null;
    return { ...toPublicClient(client), role: 'cliente' };
  }

  const user = await findUserById(id);
  if (!user || user.active === false) return null;
  return { ...toPublicUser(user), role: user.role };
}

export function isStaffRole(role: AuthRole): boolean {
  return role === 'admin' || role === 'editor' || role === 'comercial';
}

export function canManageProcesses(role: AuthRole): boolean {
  return role === 'admin' || role === 'comercial';
}
