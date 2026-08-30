import fs from 'fs';
import path from 'path';
import { randomUUID } from 'crypto';
import bcrypt from 'bcryptjs';
import { getFirestore, useFirestore } from './firestore-client.js';
import { stripUndefined } from './firestore-utils.js';
import { dataFile } from './data-paths.js';
import type { ClientRecord, ClientPublic } from './types/process.js';
import { buildClientStorageSlug } from './storage-slugs.js';

const CLIENTS_FILE = dataFile('clients.json');
const COLLECTION = 'clients';

function readFileClients(): ClientRecord[] {
  try {
    return JSON.parse(fs.readFileSync(CLIENTS_FILE, 'utf-8'));
  } catch {
    return [];
  }
}

function writeFileClients(clients: ClientRecord[]) {
  fs.mkdirSync(path.dirname(CLIENTS_FILE), { recursive: true });
  fs.writeFileSync(CLIENTS_FILE, JSON.stringify(clients, null, 2), 'utf-8');
}

export function toPublicClient(client: ClientRecord): ClientPublic {
  const { passwordHash: _, ...publicClient } = client;
  return publicClient;
}

export async function findClientByEmail(email: string): Promise<ClientRecord | null> {
  const normalized = email.trim().toLowerCase();
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).where('email', '==', normalized).limit(1).get();
    if (snap.empty) return null;
    return snap.docs[0].data() as ClientRecord;
  }
  return readFileClients().find((c) => c.email === normalized) ?? null;
}

export async function findClientById(id: string): Promise<ClientRecord | null> {
  if (useFirestore) {
    const db = await getFirestore();
    const doc = await db.collection(COLLECTION).doc(id).get();
    if (!doc.exists) return null;
    return doc.data() as ClientRecord;
  }
  return readFileClients().find((c) => c.id === id) ?? null;
}

export async function findClientByCpf(cpf: string): Promise<ClientRecord | null> {
  const normalized = cpf.replace(/\D/g, '');
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).where('cpf', '==', normalized).limit(1).get();
    if (snap.empty) return null;
    return snap.docs[0].data() as ClientRecord;
  }
  return readFileClients().find((c) => c.cpf === normalized) ?? null;
}

export async function listClients(limit = 200): Promise<ClientPublic[]> {
  if (useFirestore) {
    const db = await getFirestore();
    const snap = await db.collection(COLLECTION).orderBy('createdAt', 'desc').limit(limit).get();
    return snap.docs.map((d) => toPublicClient(d.data() as ClientRecord));
  }
  return readFileClients()
    .sort((a, b) => b.createdAt.localeCompare(a.createdAt))
    .slice(0, limit)
    .map(toPublicClient);
}

export async function createClient(input: {
  email: string;
  name: string;
  password: string;
  cpf: string;
  rg?: string;
  rgEstado?: string;
  rgOrgaoEmissor?: string;
  rgDataEmissao?: string;
  phone?: string;
  endereco?: string;
  numero?: string;
  complemento?: string;
  bairro?: string;
  cep?: string;
  cidade?: string;
  uf?: string;
  representante?: ClientRecord['representante'];
  genero?: ClientRecord['genero'];
  lgpdConsentAt?: string;
  termsConsentAt?: string;
}): Promise<ClientPublic> {
  const email = input.email.trim().toLowerCase();
  const cpf = input.cpf.replace(/\D/g, '');

  const existingEmail = await findClientByEmail(email);
  if (existingEmail) throw new Error('E-mail já cadastrado.');

  const existingCpf = await findClientByCpf(cpf);
  if (existingCpf) throw new Error('CPF já cadastrado.');

  const now = new Date().toISOString();
  const client: ClientRecord = {
    id: randomUUID(),
    email,
    name: input.name.trim(),
    passwordHash: bcrypt.hashSync(input.password, 10),
    cpf,
    rg: input.rg,
    rgEstado: input.rgEstado,
    rgOrgaoEmissor: input.rgOrgaoEmissor,
    rgDataEmissao: input.rgDataEmissao,
    phone: input.phone,
    endereco: input.endereco,
    numero: input.numero,
    complemento: input.complemento,
    bairro: input.bairro,
    cep: input.cep,
    cidade: input.cidade,
    uf: input.uf,
    representante: input.representante ?? null,
    genero: input.genero,
    lgpdConsentAt: input.lgpdConsentAt,
    termsConsentAt: input.termsConsentAt,
    active: true,
    storageSlug: buildClientStorageSlug(input.name.trim(), cpf),
    createdAt: now,
    updatedAt: now,
  };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(client.id).set(stripUndefined(client));
  } else {
    const clients = readFileClients();
    clients.push(client);
    writeFileClients(clients);
  }

  return toPublicClient(client);
}

export async function updateClient(
  id: string,
  patch: Partial<Omit<ClientRecord, 'id' | 'passwordHash' | 'createdAt'>>
): Promise<ClientPublic | null> {
  const client = await findClientById(id);
  if (!client) return null;

  const updated: ClientRecord = {
    ...client,
    ...patch,
    updatedAt: new Date().toISOString(),
  };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(id).set(stripUndefined(updated));
  } else {
    const clients = readFileClients();
    const idx = clients.findIndex((c) => c.id === id);
    if (idx === -1) return null;
    clients[idx] = updated;
    writeFileClients(clients);
  }

  return toPublicClient(updated);
}

export async function verifyClientPassword(client: ClientRecord, password: string): Promise<boolean> {
  return bcrypt.compareSync(password, client.passwordHash);
}

export async function updateClientPassword(id: string, newPassword: string): Promise<boolean> {
  const client = await findClientById(id);
  if (!client) return false;

  const updated: ClientRecord = {
    ...client,
    passwordHash: bcrypt.hashSync(newPassword, 10),
    updatedAt: new Date().toISOString(),
  };

  if (useFirestore) {
    const db = await getFirestore();
    await db.collection(COLLECTION).doc(id).set(stripUndefined(updated));
  } else {
    const clients = readFileClients();
    const idx = clients.findIndex((c) => c.id === id);
    if (idx === -1) return false;
    clients[idx] = updated;
    writeFileClients(clients);
  }
  return true;
}
