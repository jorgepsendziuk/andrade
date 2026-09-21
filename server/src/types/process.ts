export type ProcessModality = 'pcd' | 'taxi';

export type ProcessStepKey =
  | 'documentacao'
  | 'analise'
  | 'pericia'
  | 'ipi'
  | 'veiculo'
  | 'sefaz_mt'
  | 'sefaz_sp'
  | 'icms'
  | 'ipva'
  | 'concluido'
  | 'cancelado';

export type ProcessStepStatus = 'pendente' | 'em_andamento' | 'concluida' | 'bloqueada';

export type ProcessStatus = 'ativo' | 'concluido' | 'cancelado';

export type FileTypeCode =
  | 'cnh'
  | 'cpf'
  | 'doc_foto'
  | 'laudo'
  | 'comprovante_residencia'
  | 'alvara_curatela'
  | 'comprovante_pagamento'
  | 'outros';

export type DocumentTemplateCode =
  | 'contrato'
  | 'recibo'
  | 'icms_pcd'
  | 'icms_taxi'
  | 'decl_financeira'
  | 'condutor_sp'
  | 'cancel_icms';

export interface ProcessStep {
  key: ProcessStepKey;
  label: string;
  status: ProcessStepStatus;
  protocol?: string;
  startedAt?: string;
  completedAt?: string;
  internalNote?: string;
}

export interface LegalRepresentative {
  nome: string;
  cpf: string;
  rg: string;
  rgOrgaoEmissor?: string;
  rgEstado?: string;
  telefone?: string;
}

export type PagamentoTipo = 'PIX' | 'boleto' | 'dinheiro' | 'transferencia';
export type PagamentoStatusHonorario = 'pendente' | 'pago';
export type ClientGenero = 'F' | 'M';

export interface PagamentoHonorario {
  id: string;
  numero: string;
  valor: number;
  tipo: PagamentoTipo;
  status: PagamentoStatusHonorario;
  data: string;
  descricao?: string;
}

export interface VehicleInfo {
  marca?: string;
  modelo?: string;
  ano?: string;
  potencia?: string;
  placa?: string;
  renavam?: string;
  chassi?: string;
  concessionaria?: string;
  concessionariaCnpj?: string;
  concessionariaIe?: string;
}

export interface ClientRecord {
  id: string;
  email: string;
  name: string;
  passwordHash: string;
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
  representante?: LegalRepresentative | null;
  genero?: ClientGenero;
  lgpdConsentAt?: string;
  termsConsentAt?: string;
  active: boolean;
  storageSlug?: string;
  lastSelfEditAt?: string;
  lastSelfEditFields?: string[];
  lastSelfEditAlertId?: string;
  createdAt: string;
  updatedAt: string;
}

export interface ClientPublic {
  id: string;
  email: string;
  name: string;
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
  representante?: LegalRepresentative | null;
  genero?: ClientGenero;
  lgpdConsentAt?: string;
  termsConsentAt?: string;
  active: boolean;
  storageSlug?: string;
  lastSelfEditAt?: string;
  lastSelfEditFields?: string[];
  lastSelfEditAlertId?: string;
  createdAt: string;
  updatedAt: string;
}

export interface ProcessRecord {
  id: string;
  clientId: string;
  modality: ProcessModality;
  status: ProcessStatus;
  currentStep: ProcessStepKey;
  steps: ProcessStep[];
  vehicle?: VehicleInfo;
  honorarios?: number;
  pagamentos?: PagamentoHonorario[];
  pagamentoTipo?: string;
  pagamentoStatus?: string;
  contactId?: string;
  storageSlug?: string;
  createdAt: string;
  updatedAt: string;
}

export interface ProcessFileRecord {
  id: string;
  processId: string;
  clientId: string;
  fileType: FileTypeCode;
  objectName: string;
  originalName: string;
  mimeType: string;
  size: number;
  uploadedBy: string;
  uploadedByRole: 'cliente' | 'admin' | 'comercial';
  createdAt: string;
}

export interface ConductorRecord {
  id: string;
  processId: string;
  clientId: string;
  nome: string;
  cpf: string;
  rg?: string;
  endereco?: string;
  telefone?: string;
  ordem: number;
  createdAt: string;
  updatedAt: string;
}

export type AuditAction =
  | 'view'
  | 'download'
  | 'upload'
  | 'delete'
  | 'login'
  | 'consent'
  | 'create'
  | 'update'
  | 'password_reset';

export interface AuditChange {
  field: string;
  from?: unknown;
  to?: unknown;
}

export interface AuditLogRecord {
  id: string;
  action: AuditAction;
  resourceType: string;
  resourceId?: string;
  objectName?: string;
  userId: string;
  userRole: string;
  userEmail?: string;
  ip?: string;
  summary?: string;
  changes?: AuditChange[];
  createdAt: string;
}

export type StaffAlertType = 'client_self_edit' | 'process_self_edit';

export interface StaffAlert {
  id: string;
  type: StaffAlertType;
  title: string;
  summary: string;
  clientId: string;
  clientName: string;
  processId?: string;
  changes: AuditChange[];
  unread: boolean;
  createdAt: string;
  readAt?: string;
  readBy?: string;
  readByEmail?: string;
}

export interface DocsFolderEntry {
  name: string;
  prefix: string;
  isFolder: boolean;
  size?: number;
  updatedAt?: string;
  mimeType?: string;
}
