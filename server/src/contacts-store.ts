import fs from 'fs';
import path from 'path';
import { randomUUID } from 'crypto';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';
import type { ContactSubmission, ContactStatus, CreateContactInput } from './types/contact.js';

const CONTACTS_FILE = dataFile('contacts.json');
const COLLECTION = 'contact_submissions';

function readFileContacts(): ContactSubmission[] {
  try {
    return JSON.parse(fs.readFileSync(CONTACTS_FILE, 'utf-8'));
  } catch {
    return [];
  }
}

function writeFileContacts(contacts: ContactSubmission[]) {
  fs.mkdirSync(path.dirname(CONTACTS_FILE), { recursive: true });
  fs.writeFileSync(CONTACTS_FILE, JSON.stringify(contacts, null, 2), 'utf-8');
}

function toSubmission(id: string, input: CreateContactInput): ContactSubmission {
  return {
    id,
    name: input.name,
    email: input.email,
    phone: input.phone,
    message: input.message,
    createdAt: new Date().toISOString(),
    emailSent: input.emailSent,
    status: 'new',
  };
}

export async function saveContact(input: CreateContactInput): Promise<ContactSubmission> {
  const id = randomUUID();
  const submission = toSubmission(id, input);

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(id).set(stripUndefined(submission));
    return submission;
  }

  const contacts = readFileContacts();
  contacts.unshift(submission);
  writeFileContacts(contacts);
  return submission;
}

export async function listContacts(limit = 100): Promise<ContactSubmission[]> {
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db
      .collection(COLLECTION)
      .orderBy('createdAt', 'desc')
      .limit(limit)
      .get();
    return snap.docs.map((doc) => doc.data() as ContactSubmission);
  }

  return readFileContacts()
    .sort((a, b) => b.createdAt.localeCompare(a.createdAt))
    .slice(0, limit);
}

export async function updateContactStatus(
  id: string,
  status: ContactStatus
): Promise<ContactSubmission | null> {
  if (useFirestore) {
    const db = await getFirestore();
    const ref = db.collection(COLLECTION).doc(id);
    const doc = await ref.get();
    if (!doc.exists) return null;
    await ref.update({ status });
    return { ...(doc.data() as ContactSubmission), status };
  }

  const contacts = readFileContacts();
  const index = contacts.findIndex((c) => c.id === id);
  if (index === -1) return null;
  contacts[index] = { ...contacts[index], status };
  writeFileContacts(contacts);
  return contacts[index];
}

export function getContactsStorageMode(): 'firestore' | 'file' {
  return useFirestore ? 'firestore' : 'file';
}

export interface ContactStats {
  total: number;
  new: number;
  read: number;
  archived: number;
  today: number;
  emailSentRate: number;
  last7Days: { date: string; count: number }[];
}

export async function getContactStats(): Promise<ContactStats> {
  const contacts = await listContacts(500);
  const today = new Date().toISOString().slice(0, 10);
  const last7 = Array.from({ length: 7 }, (_, i) => {
    const d = new Date();
    d.setDate(d.getDate() - (6 - i));
    return d.toISOString().slice(0, 10);
  });

  const byDay = Object.fromEntries(last7.map((d) => [d, 0]));
  for (const c of contacts) {
    const day = c.createdAt.slice(0, 10);
    if (day in byDay) byDay[day] += 1;
  }

  const emailSent = contacts.filter((c) => c.emailSent).length;
  return {
    total: contacts.length,
    new: contacts.filter((c) => c.status === 'new').length,
    read: contacts.filter((c) => c.status === 'read').length,
    archived: contacts.filter((c) => c.status === 'archived').length,
    today: contacts.filter((c) => c.createdAt.startsWith(today)).length,
    emailSentRate: contacts.length ? Math.round((emailSent / contacts.length) * 100) : 0,
    last7Days: last7.map((date) => ({ date, count: byDay[date] })),
  };
}
