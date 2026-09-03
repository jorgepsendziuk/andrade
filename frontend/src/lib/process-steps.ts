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
  veiculo: {
    hint: 'Escolha do veículo na concessionária. Para dar entrada no processo na SEFAZ de Mato Grosso é obrigatória a definição do veículo.',
    checklist: [
      'Modelo e concessionária definidos',
      'Potência dentro do limite legal',
      'Dados do veículo registrados no processo',
    ],
    sla: 'Variável',
  },
  sefaz_mt: {
    hint: 'Protocolo de isenção de ICMS e IPVA na SEFAZ de Mato Grosso, com acompanhamento de exigências.',
    checklist: [
      'Pedido ICMS protocolado na SEFAZ MT',
      'Isenção de IPVA solicitada após deferimento do ICMS',
      'Documentos do veículo e do beneficiário anexados',
    ],
    agency: 'SEFAZ MT',
    sla: '15–60 dias',
  },
  sefaz_sp: {
    hint: 'Isenção de ICMS na SEFAZ de São Paulo para veículos fabricados em SP (Tracker, Polo, Virtus, Hyundai Creta, Honda City e similares).',
    checklist: [
      'Verificar se o veículo é fabricado em SP',
      'Pedido ICMS protocolado na SEFAZ SP',
      'Acompanhamento de deferimento',
    ],
    agency: 'SEFAZ SP',
    sla: '15–45 dias',
  },
  icms: {
    hint: 'Etapa legada — use SEFAZ MT.',
    checklist: [],
  },
  ipva: {
    hint: 'Etapa legada — use SEFAZ MT.',
    checklist: [],
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
