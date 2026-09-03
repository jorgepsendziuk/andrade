import fs from 'fs';
import path from 'path';
import { createHash, randomBytes } from 'crypto';
import { randomUUID } from 'crypto';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';

export type PasswordResetAccountType = 'cliente' | 'staff';

export interface PasswordResetToken {
  id: string;
  tokenHash: string;
  email: string;
  userId: string;
  accountType: PasswordResetAccountType;
  expiresAt: string;
  usedAt?: string;
  createdAt: string;
}

const TOKENS_FILE = dataFile('password-reset-tokens.json');
const COLLECTION = 'password_reset_tokens';
const TOKEN_TTL_MS = 60 * 60 * 1000;

function readFileTokens(): PasswordResetToken[] {
  try {
    return JSON.parse(fs.readFileSync(TOKENS_FILE, 'utf-8'));
  } catch {
    return [];
  }
}

function writeFileTokens(tokens: PasswordResetToken[]) {
  fs.mkdirSync(path.dirname(TOKENS_FILE), { recursive: true });
  fs.writeFileSync(TOKENS_FILE, JSON.stringify(tokens, null, 2), 'utf-8');
}

function hashToken(token: string): string {
  return createHash('sha256').update(token).digest('hex');
}

export function generateResetTokenValue(): string {
  return randomBytes(32).toString('hex');
}

export async function createPasswordResetToken(input: {
  email: string;
  userId: string;
  accountType: PasswordResetAccountType;
}): Promise<{ token: string; record: PasswordResetToken }> {
  const token = generateResetTokenValue();
  const now = new Date();
  const record: PasswordResetToken = {
    id: randomUUID(),
    tokenHash: hashToken(token),
    email: input.email.trim().toLowerCase(),
    userId: input.userId,
    accountType: input.accountType,
    expiresAt: new Date(now.getTime() + TOKEN_TTL_MS).toISOString(),
    createdAt: now.toISOString(),
  };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(record.id).set(stripUndefined(record));
  } else {
    const tokens = readFileTokens().filter((t) => !t.usedAt && t.email !== record.email);
    tokens.push(record);
    writeFileTokens(tokens);
  }

  return { token, record };
}

export async function findValidPasswordResetToken(token: string): Promise<PasswordResetToken | null> {
  const tokenHash = hashToken(token);
  const now = Date.now();

  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).where('tokenHash', '==', tokenHash).limit(1).get();
    if (snap.empty) return null;
    const record = snap.docs[0].data() as PasswordResetToken;
    if (record.usedAt || Date.parse(record.expiresAt) < now) return null;
    return record;
  }

  const record = readFileTokens().find((t) => t.tokenHash === tokenHash && !t.usedAt);
  if (!record || Date.parse(record.expiresAt) < now) return null;
  return record;
}

export async function markPasswordResetTokenUsed(id: string): Promise<void> {
  const usedAt = new Date().toISOString();

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(id).set({ usedAt }, { merge: true });
    return;
  }

  const tokens = readFileTokens();
  const idx = tokens.findIndex((t) => t.id === id);
  if (idx === -1) return;
  tokens[idx] = { ...tokens[idx], usedAt };
  writeFileTokens(tokens);
}
