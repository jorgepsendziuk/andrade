export const ASSESSORIA_TITLE = 'ASSESSORIA NOS PROCESSOS DE:';

export const ASSESSORIA_ITEMS = [
  {
    id: 'analise-documental',
    title: 'Análise Documental e Juntada de Documentos',
    description:
      'Analisamos a documentação, orientamos sobre os documentos necessários e realizamos a juntada para o andamento do processo.',
    highlight: false,
  },
  {
    id: 'junta-detran',
    title: 'Junta Médica Detran (para quem possui CNH)',
    description: 'Orientamos desde a abertura do processo, agendamento e acompanhamento na perícia.',
    highlight: false,
  },
  {
    id: 'junta-sus',
    title: 'Junta Médica SUS (para quem não possui CNH)',
    description: 'Orientamos desde a abertura do processo, agendamento e acompanhamento na perícia.',
    highlight: false,
  },
  {
    id: 'cotacao',
    title: 'Cotação de Valores Personalizada',
    description:
      'Preparamos cotações personalizadas conforme a escolha de cada cliente, com marca, modelo, versão, cor, itens e valores aplicáveis.',
    highlight: true,
  },
  {
    id: 'receita-federal',
    title: 'Receita Federal IPI e IOF',
    description: 'Protocolos, acompanhamento e defesa dos processos quando necessários.',
    highlight: false,
  },
  {
    id: 'sefaz-mt',
    title: 'Sefaz MT ICMS e IPVA',
    description: 'Protocolos, acompanhamento e defesa dos processos quando necessários.',
    highlight: true,
  },
  {
    id: 'sefaz-sp',
    title: 'Sefaz SP ICMS',
    description: 'Protocolos, acompanhamento e defesa dos processos quando necessários.',
    highlight: false,
  },
] as const;

const NEW_ASSESSORIA_IDS = new Set(ASSESSORIA_ITEMS.map((item) => item.id));

export function shouldReplaceAssessoriaItems(items: unknown): boolean {
  if (!Array.isArray(items) || items.length === 0) return true;
  const ids = items.map((item) =>
    item && typeof item === 'object' && 'id' in item ? String((item as { id?: unknown }).id) : ''
  );
  if (ids.some((id) => /rod[ií]zio/i.test(id))) return true;
  return !ASSESSORIA_ITEMS.every((item) => ids.includes(item.id));
}

function stripOutdatedHistory(paragraphs: unknown): string[] | null {
  if (!Array.isArray(paragraphs)) return null;
  const next = paragraphs
    .map((p) => String(p ?? ''))
    .filter((p) => p.trim() && !/cons[oó]rcio|correspondente banc[aá]rio/i.test(p));
  if (next.length === paragraphs.length && next.every((p, i) => p === String(paragraphs[i] ?? ''))) {
    return null;
  }
  return next;
}

export function upgradeSiteContent(content: Record<string, unknown>): {
  content: Record<string, unknown>;
  changed: boolean;
} {
  let changed = false;
  const next = { ...content };

  const site = next.site && typeof next.site === 'object' ? { ...(next.site as Record<string, unknown>) } : null;
  if (site && typeof site.title === 'string' && /consultoria e isen/i.test(site.title)) {
    site.title = 'Andrade Isenções';
    next.site = site;
    changed = true;
  }

  if (!Array.isArray(next.sections)) return { content: next, changed };

  const sections = (next.sections as Record<string, unknown>[]).map((section) => {
    if (section.id === 'servicos') {
      const data = { ...((section.data as Record<string, unknown>) || {}) };
      if (shouldReplaceAssessoriaItems(data.items)) {
        data.title = ASSESSORIA_TITLE;
        data.items = ASSESSORIA_ITEMS.map((item) => ({ ...item }));
        delete data.description;
        delete data.highlight;
        changed = true;
        return { ...section, data };
      }
    }

    if (section.id === 'carros') {
      const data = { ...((section.data as Record<string, unknown>) || {}) };
      if (typeof data.ctaText === 'string' && /saiba mais|saber mais/i.test(data.ctaText.trim())) {
        data.ctaText = 'SOLICITAR MINHA COTAÇÃO';
        changed = true;
        return { ...section, data };
      }
    }

    if (section.id === 'final-cta') {
      const data = { ...((section.data as Record<string, unknown>) || {}) };
      if (!String(data.ctaLink ?? '').trim()) {
        data.ctaLink = '/iniciar';
        changed = true;
        return { ...section, data };
      }
    }

    if (section.id === 'simulador') {
      const data = { ...((section.data as Record<string, unknown>) || {}) };
      if (/quero come[cç]ar minha an[aá]lise/i.test(String(data.subtitle ?? ''))) {
        data.subtitle = 'Responda algumas perguntas e receba orientação personalizada.';
        changed = true;
        return { ...section, data };
      }
    }

    if (section.id === 'quem-somos') {
      const data = { ...((section.data as Record<string, unknown>) || {}) };
      const cleaned = stripOutdatedHistory(data.historyParagraphs);
      if (cleaned) {
        data.historyParagraphs = cleaned;
        changed = true;
        return { ...section, data };
      }
    }

    return section;
  });

  next.sections = sections;
  return { content: next, changed };
}

export function hasNewAssessoriaIds(items: unknown): boolean {
  if (!Array.isArray(items)) return false;
  const ids = new Set(
    items.map((item) =>
      item && typeof item === 'object' && 'id' in item ? String((item as { id?: unknown }).id) : ''
    )
  );
  return [...NEW_ASSESSORIA_IDS].every((id) => ids.has(id));
}
