const RODIZIO_PHRASE =
  /Possibilidade de isen[cç][aã]o de rod[ií]zio e cart[aã]o especial/gi;

export function stripRodizioText(text: string): string {
  return text
    .replace(RODIZIO_PHRASE, 'Possibilidade de cartão especial de estacionamento')
    .replace(/isen[cç][aã]o de rod[ií]zio( e)?/gi, '')
    .replace(/rod[ií]zio/gi, '')
    .replace(/\s{2,}/g, ' ')
    .replace(/\s+,/g, ',')
    .replace(/\s+\./g, '.')
    .trim();
}

export function stripRodizioFromValue<T>(value: T): { value: T; changed: boolean } {
  if (typeof value === 'string') {
    const next = stripRodizioText(value);
    return { value: next as T, changed: next !== value };
  }

  if (Array.isArray(value)) {
    let changed = false;
    const next = value.map((item) => {
      const result = stripRodizioFromValue(item);
      if (result.changed) changed = true;
      return result.value;
    });
    return { value: next as T, changed };
  }

  if (value && typeof value === 'object') {
    let changed = false;
    const next: Record<string, unknown> = {};
    for (const [key, nested] of Object.entries(value as Record<string, unknown>)) {
      const result = stripRodizioFromValue(nested);
      next[key] = result.value;
      if (result.changed) changed = true;
    }
    return { value: next as T, changed };
  }

  return { value, changed: false };
}
