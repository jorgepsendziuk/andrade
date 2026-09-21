export interface AuditChange {
  field: string;
  from?: unknown;
  to?: unknown;
}

function isPlainObject(value: unknown): value is Record<string, unknown> {
  return Boolean(value) && typeof value === 'object' && !Array.isArray(value);
}

function normalizeComparable(value: unknown): unknown {
  if (value === undefined || value === null || value === '') return null;
  if (Array.isArray(value)) return value.map(normalizeComparable);
  if (isPlainObject(value)) {
    const out: Record<string, unknown> = {};
    for (const key of Object.keys(value).sort()) {
      const next = normalizeComparable(value[key]);
      if (next !== null) out[key] = next;
    }
    return Object.keys(out).length ? out : null;
  }
  return value;
}

function same(a: unknown, b: unknown): boolean {
  return JSON.stringify(normalizeComparable(a)) === JSON.stringify(normalizeComparable(b));
}

export function diffRecords(
  before: Record<string, unknown>,
  after: Record<string, unknown>,
  fields: string[]
): AuditChange[] {
  const changes: AuditChange[] = [];
  for (const field of fields) {
    if (same(before[field], after[field])) continue;
    changes.push({
      field,
      from: normalizeComparable(before[field]),
      to: normalizeComparable(after[field]),
    });
  }
  return changes;
}

export function summarizeChanges(changes: AuditChange[]): string {
  if (!changes.length) return 'Nenhuma alteração';
  return changes.map((c) => c.field).join(', ');
}
