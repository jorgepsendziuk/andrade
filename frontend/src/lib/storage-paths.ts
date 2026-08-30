/** Formata segmento de pasta para exibição humana. */
export function slugifySegment(value: string, max = 64): string {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .slice(0, max) || 'arquivo';
}

export function buildClientStoragePrefix(name: string, cpf: string, storageSlug?: string): string {
  const slug = storageSlug || `${slugifySegment(name, 40)}-${cpf.replace(/\D/g, '')}`.slice(0, 80);
  return `clientes/${slug}/`;
}

export function formatStorageSegment(segment: string): string {
  if (segment === 'clientes') return 'Clientes';
  if (segment === 'empresa') return 'Empresa';
  if (segment === 'modelos') return 'Modelos';
  if (segment === 'templates') return 'Templates';
  if (segment === 'contratos') return 'Contratos';
  if (segment === 'fiscal') return 'Fiscal';
  if (segment === 'marketing') return 'Marketing';
  if (segment === 'interno') return 'Interno';
  if (segment === 'processos') return 'Processos';
  if (segment === 'identidade') return 'Identidade';
  if (segment === 'laudos') return 'Laudos';
  if (segment === 'comprovantes') return 'Comprovantes';
  if (segment === 'pagamento') return 'Pagamento';
  if (segment === 'gerados') return 'Gerados';
  if (segment === 'outros') return 'Outros';
  if (segment === 'pcd' || segment === 'taxi') return segment.toUpperCase();

  if (/^\d{11}$/.test(segment.replace(/\D/g, '')) && segment.includes('-')) {
    const parts = segment.split('-');
    const cpf = parts[parts.length - 1];
    if (/^\d{11}$/.test(cpf)) {
      const name = parts.slice(0, -1).join(' ');
      return `${name} (CPF ${cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')})`;
    }
  }

  return segment.replace(/-/g, ' ');
}

export function formatHumanStoragePath(objectName: string): string {
  const parts = objectName.replace(/\/$/, '').split('/').filter(Boolean);
  if (parts.length === 0) return 'Raiz';
  return parts.map(formatStorageSegment).join(' › ');
}
