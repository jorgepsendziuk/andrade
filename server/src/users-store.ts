import fs from 'fs';
import path from 'path';
import { randomUUID } from 'crypto';
import bcrypt from 'bcryptjs';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';
import type { AdminUser, AdminUserPublic, UserRole } from './types/user.js';
import { toPublicUser } from './types/user.js';
import { normalizeAuthEmail } from './auth-email.js';

const USERS_FILE = dataFile('admin-users.json');
const COLLECTION = 'admin_users';

function readFileUsers(): AdminUser[] {
  try {
    return JSON.parse(fs.readFileSync(USERS_FILE, 'utf-8'));
  } catch {
    return [];
  }
}

function writeFileUsers(users: AdminUser[]) {
  fs.mkdirSync(path.dirname(USERS_FILE), { recursive: true });
  fs.writeFileSync(USERS_FILE, JSON.stringify(users, null, 2), 'utf-8');
}

export async function findUserByEmail(email: string): Promise<AdminUser | null> {
  const normalized = normalizeAuthEmail(email);
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).where('email', '==', normalized).limit(1).get();
    if (snap.empty) return null;
    return snap.docs[0].data() as AdminUser;
  }
  return readFileUsers().find((u) => u.email === normalized) ?? null;
}

export async function findUserById(id: string): Promise<AdminUser | null> {
  if (useFirestore) {
    const db = await getFirestore();
    const doc = await db.collection(COLLECTION).doc(id).get();
    if (!doc.exists) return null;
    return doc.data() as AdminUser;
  }
  return readFileUsers().find((u) => u.id === id) ?? null;
}

export async function listUsers(): Promise<AdminUserPublic[]> {
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).orderBy('createdAt', 'desc').get();
    return snap.docs.map((d) => toPublicUser(d.data() as AdminUser));
  }
  return readFileUsers().map(toPublicUser);
}

export async function createUser(input: {
  email: string;
  name: string;
  password: string;
  role: UserRole;
  mustChangePassword?: boolean;
}): Promise<AdminUserPublic> {
  const email = input.email.trim().toLowerCase();
  const existing = await findUserByEmail(email);
  if (existing) throw new Error('E-mail já cadastrado.');

  const now = new Date().toISOString();
  const user: AdminUser = {
    id: randomUUID(),
    email,
    name: input.name.trim(),
    passwordHash: bcrypt.hashSync(input.password, 10),
    role: input.role,
    active: true,
    mustChangePassword: input.mustChangePassword ?? false,
    createdAt: now,
    updatedAt: now,
  };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(user.id).set(stripUndefined(user));
  } else {
    const users = readFileUsers();
    users.push(user);
    writeFileUsers(users);
  }
  return toPublicUser(user);
}

export async function updateUser(
  id: string,
  patch: Partial<Pick<AdminUser, 'name' | 'role' | 'active' | 'mustChangePassword'>>
): Promise<AdminUserPublic | null> {
  const user = await findUserById(id);
  if (!user) return null;

  const updated: AdminUser = {
    ...user,
    ...patch,
    updatedAt: new Date().toISOString(),
  };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(id).set(stripUndefined(updated));
  } else {
    const users = readFileUsers();
    const idx = users.findIndex((u) => u.id === id);
    if (idx === -1) return null;
    users[idx] = updated;
    writeFileUsers(users);
  }
  return toPublicUser(updated);
}

export async function updatePassword(id: string, newPassword: string): Promise<boolean> {
  const user = await findUserById(id);
  if (!user) return false;

  const passwordHash = bcrypt.hashSync(newPassword, 10);
  const updatedAt = new Date().toISOString();

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(id).update({
      passwordHash,
      mustChangePassword: false,
      updatedAt,
    });
  } else {
    const users = readFileUsers();
    const idx = users.findIndex((u) => u.id === id);
    if (idx === -1) return false;
    users[idx] = {
      ...users[idx],
      passwordHash,
      mustChangePassword: false,
      updatedAt,
    };
    writeFileUsers(users);
  }
  return true;
}

export async function verifyPassword(user: AdminUser, password: string): Promise<boolean> {
  return bcrypt.compareSync(password, user.passwordHash);
}

export async function seedUserIfMissing(input: {
  email: string;
  name: string;
  password: string;
  role: UserRole;
  mustChangePassword?: boolean;
}): Promise<void> {
  const existing = await findUserByEmail(input.email);
  if (existing) return;
  await createUser(input);
}
