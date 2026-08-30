import type { FileTypeCode, ProcessStepKey, ProcessStepStatus } from '../types/process';

export const STEP_STATUS_LABELS: Record<ProcessStepStatus, string> = {
  pendente: 'Pendente',
  em_andamento: 'Em andamento',
  concluida: 'Concluída',
  bloqueada: 'Bloqueada',
};

export interface StepMeta {
  hint: string;
  checklist: string[];
  docs?: FileTypeCode[];
  agency?: string;
  sla?: string;
}

export const STEP_META: Record<ProcessStepKey, StepMeta> = {
  documentacao: {
    hint: 'Coleta e validação dos documentos iniciais do beneficiário e representante legal, se houver.',
    checklist: [
      'CNH ou documento com foto válido',
      'Laudo médico com CID compatível',
      'Comprovante de residência recente',
      'Alvará de curatela (quando aplicável)',
    ],
    docs: ['cnh', 'cpf', 'doc_foto', 'laudo', 'comprovante_residencia', 'alvara_curatela'],
    sla: '1–3 dias úteis',
  },
  analise: {
    hint: 'Revisão interna da elegibilidade, consistência cadastral e orientação ao cliente sobre próximos passos.',
    checklist: [
      'CPF e dados cadastrais conferidos',
      'Representante legal identificado',
      'Honorários e forma de pagamento definidos',
    ],
    sla: '2–5 dias úteis',
  },
  pericia: {
    hint: 'Agendamento e acompanhamento da perícia médica ou junta médica exigida pelo DETRAN/órgão competente.',
    checklist: [
      'Protocolo DETRAN registrado',
      'Data da perícia confirmada com o cliente',
      'Resultado arquivado no processo',
    ],
    agency: 'DETRAN / Junta Médica',
    sla: '15–45 dias',
  },
  ipi: {
    hint: 'Pedido de isenção de IPI junto ao SISEN/Receita Federal para aquisição do veículo.',
    checklist: [
      'Formulário SISEN preenchido',
      'Laudo e documentos anexados',
      'Protocolo de deferimento ou pendência',
    ],
    agency: 'Receita Federal — SISEN',
    sla: '10–30 dias',
  },
  icms: {
    hint: 'Protocolo de isenção de ICMS na SEFAZ do estado, com acompanhamento de exigências.',
    checklist: [
      'Pedido ICMS protocolado',
      'Documentos do veículo e do beneficiário',
      'Acompanhamento de deferimento',
    ],
    agency: 'SEFAZ estadual',
    sla: '15–60 dias',
  },
  ipva: {
    hint: 'Solicitação de isenção de IPVA após deferimento do ICMS, quando aplicável no estado.',
    checklist: [
      'Comprovante de isenção ICMS',
      'Dados do veículo (placa, RENAVAM)',
      'Protocolo na secretaria de fazenda',
    ],
    agency: 'Secretaria da Fazenda — IPVA',
    sla: '5–20 dias',
  },
  veiculo: {
    hint: 'Escolha do veículo na concessionária, reserva e alinhamento de prazos de entrega com as isenções.',
    checklist: [
      'Modelo e concessionária definidos',
      'Potência dentro do limite legal',
      'Nota fiscal e entrega programada',
    ],
    sla: 'Variável',
  },
  concluido: {
    hint: 'Processo finalizado com todas as etapas concluídas e veículo liberado para retirada.',
    checklist: ['Todas as isenções deferidas', 'Documentação entregue ao cliente', 'Arquivo encerrado'],
  },
  cancelado: {
    hint: 'Processo encerrado sem conclusão. Registre o motivo na observação interna.',
    checklist: ['Motivo do cancelamento documentado', 'Cliente comunicado'],
  },
};

export const REQUIRED_DOCS: FileTypeCode[] = ['cnh', 'laudo', 'comprovante_residencia'];

export function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
