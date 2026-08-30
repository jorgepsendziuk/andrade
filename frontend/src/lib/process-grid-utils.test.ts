import { describe, expect, it } from 'vitest';
import {
  filterProcesses,
  formatCpf,
  sortProcesses,
} from '../lib/process-grid-utils';
import type { ProcessListItem } from '../types/process';

const sample: ProcessListItem[] = [
  {
    id: '1',
    clientId: 'c1',
    clientName: 'Ana Silva',
    clientCpf: '12345678901',
    clientEmail: 'ana@test.com',
    modality: 'pcd',
    status: 'ativo',
    currentStep: 'documentacao',
    steps: [],
    createdAt: '2026-01-02T00:00:00Z',
    updatedAt: '2026-01-02T00:00:00Z',
  },
  {
    id: '2',
    clientId: 'c2',
    clientName: 'Bruno Costa',
    clientCpf: '98765432100',
    clientEmail: 'bruno@test.com',
    modality: 'taxi',
    status: 'concluido',
    currentStep: 'concluido',
    steps: [],
    createdAt: '2026-01-01T00:00:00Z',
    updatedAt: '2026-01-03T00:00:00Z',
  },
];

describe('process-grid-utils', () => {
  it('formatCpf mascara CPF válido', () => {
    expect(formatCpf('12345678901')).toBe('123.456.789-01');
  });

  it('filterProcesses filtra por status', () => {
    const filtered = filterProcesses(sample, {
      search: '',
      status: 'ativo',
      step: 'all',
      modality: 'all',
    });
    expect(filtered).toHaveLength(1);
    expect(filtered[0].clientName).toBe('Ana Silva');
  });

  it('sortProcesses ordena por nome', () => {
    const sorted = sortProcesses(sample, 'clientName', 'asc');
    expect(sorted[0].clientName).toBe('Ana Silva');
    expect(sorted[1].clientName).toBe('Bruno Costa');
  });
});
