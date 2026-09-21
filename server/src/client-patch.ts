import { findClientByCpf, findClientByEmail, updateClient } from './clients-store.js';
import type { ClientGenero, ClientPublic, LegalRepresentative } from './types/process.js';

export const CLIENT_AUDIT_FIELDS = [
  'name',
  'email',
  'cpf',
  'rg',
  'rgEstado',
  'rgOrgaoEmissor',
  'rgDataEmissao',
  'phone',
  'endereco',
  'numero',
  'complemento',
  'bairro',
  'cep',
  'cidade',
  'uf',
  'genero',
  'representante',
  'active',
] as const;

export interface ClientPatchInput {
  name?: string;
  email?: string;
  cpf?: string;
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
  genero?: ClientGenero | '';
  representante?: LegalRepresentative | null;
  active?: boolean;
}

export function normalizeIssueDate(value: string): string {
  const trimmed = value.trim();
  if (!trimmed) return '';
  const iso = trimmed.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (iso) return trimmed;
  const digits = trimmed.replace(/\D/g, '');
  if (digits.length === 8) {
    return `${digits.slice(4, 8)}-${digits.slice(2, 4)}-${digits.slice(0, 2)}`;
  }
  return trimmed;
}

export function parseLegalRepresentative(input: unknown): LegalRepresentative | null | undefined {
  if (input === undefined) return undefined;
  if (input === null) return null;
  if (typeof input !== 'object') return null;
  const r = input as Record<string, unknown>;
  const nome = String(r.nome ?? '').trim();
  const cpf = String(r.cpf ?? '').replace(/\D/g, '');
  if (!nome && !cpf) return null;
  if (cpf && cpf.length !== 11) {
    throw new Error('CPF do representante inválido.');
  }
  return {
    nome,
    cpf,
    rg: String(r.rg ?? '').trim(),
    rgOrgaoEmissor: String(r.rgOrgaoEmissor ?? '').trim() || undefined,
    rgEstado: String(r.rgEstado ?? '').trim() || undefined,
    telefone: String(r.telefone ?? '').trim() || undefined,
  };
}

export async function buildClientPatch(
  clientId: string,
  body: ClientPatchInput,
  options: { allowIdentity: boolean; allowActive: boolean }
): Promise<Record<string, unknown>> {
  const patch: Record<string, unknown> = {};

  if (options.allowIdentity && body.cpf !== undefined) {
    const digits = String(body.cpf).replace(/\D/g, '');
    if (digits.length !== 11) throw new Error('CPF inválido. Informe os 11 dígitos.');
    const other = await findClientByCpf(digits);
    if (other && other.id !== clientId) throw new Error('CPF já cadastrado para outro cliente.');
    patch.cpf = digits;
  }
  if (options.allowIdentity && body.email !== undefined) {
    const normalized = String(body.email).trim().toLowerCase();
    if (!normalized) throw new Error('Informe o e-mail.');
    const other = await findClientByEmail(normalized);
    if (other && other.id !== clientId) throw new Error('E-mail já cadastrado para outro cliente.');
    patch.email = normalized;
  }
  if (body.name !== undefined) patch.name = String(body.name).trim();
  if (body.rg !== undefined) patch.rg = String(body.rg).trim();
  if (body.rgEstado !== undefined) patch.rgEstado = String(body.rgEstado).trim();
  if (body.rgOrgaoEmissor !== undefined) patch.rgOrgaoEmissor = String(body.rgOrgaoEmissor).trim();
  if (body.rgDataEmissao !== undefined) patch.rgDataEmissao = normalizeIssueDate(String(body.rgDataEmissao ?? ''));
  if (body.phone !== undefined) patch.phone = String(body.phone ?? '').trim();
  if (body.endereco !== undefined) patch.endereco = String(body.endereco).trim();
  if (body.numero !== undefined) patch.numero = String(body.numero).trim();
  if (body.complemento !== undefined) patch.complemento = String(body.complemento).trim();
  if (body.bairro !== undefined) patch.bairro = String(body.bairro).trim();
  if (body.cep !== undefined) patch.cep = String(body.cep).replace(/\D/g, '');
  if (body.cidade !== undefined) patch.cidade = String(body.cidade).trim();
  if (body.uf !== undefined) patch.uf = String(body.uf).trim().toUpperCase();
  if (body.genero !== undefined) {
    patch.genero = body.genero === 'F' || body.genero === 'M' ? body.genero : undefined;
  }
  if (body.representante !== undefined) {
    patch.representante = parseLegalRepresentative(body.representante);
  }
  if (options.allowActive && body.active !== undefined) {
    patch.active = body.active !== false;
  }

  return patch;
}

export async function applyClientPatch(
  clientId: string,
  body: ClientPatchInput,
  options: { allowIdentity: boolean; allowActive: boolean }
): Promise<ClientPublic | null> {
  const patch = await buildClientPatch(clientId, body, options);
  if (!Object.keys(patch).length) throw new Error('Nada para atualizar.');
  return updateClient(clientId, patch);
}
