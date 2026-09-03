import type { LucideIcon } from 'lucide-react';
import {
  BadgeCheck,
  Ban,
  Car,
  ClipboardList,
  FileCheck,
  FileImage,
  FileText,
  FileX,
  FolderOpen,
  IdCard,
  Landmark,
  Receipt,
  Scale,
  ScrollText,
  Stethoscope,
  UserCheck,
  Wallet,
} from 'lucide-react';
import type { DocumentTemplateCode, FileTypeCode, ProcessStepKey } from '../types/process';

export const FILE_TYPE_ICONS: Record<FileTypeCode, LucideIcon> = {
  cnh: IdCard,
  cpf: BadgeCheck,
  doc_foto: FileImage,
  laudo: Stethoscope,
  comprovante_residencia: ScrollText,
  alvara_curatela: Scale,
  comprovante_pagamento: Wallet,
  outros: FolderOpen,
};

export const STEP_ICONS: Record<ProcessStepKey, LucideIcon> = {
  documentacao: ClipboardList,
  analise: FileCheck,
  pericia: Stethoscope,
  ipi: Landmark,
  veiculo: Car,
  sefaz_mt: Scale,
  sefaz_sp: UserCheck,
  icms: Scale,
  ipva: Receipt,
  concluido: BadgeCheck,
  cancelado: Ban,
};

export const TEMPLATE_ICONS: Record<DocumentTemplateCode, LucideIcon> = {
  contrato: FileText,
  recibo: Receipt,
  icms_pcd: Scale,
  icms_taxi: Car,
  decl_financeira: Wallet,
  condutor_sp: UserCheck,
  cancel_icms: FileX,
};

export function mimeIcon(mimeType: string): LucideIcon {
  if (mimeType.startsWith('image/')) return FileImage;
  return FileText;
}
