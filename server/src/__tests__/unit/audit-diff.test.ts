import { describe, expect, it } from 'vitest';
import { diffRecords, summarizeChanges } from '../../audit-diff.js';

describe('diffRecords', () => {
  it('detecta alteração simples e ignora campo igual', () => {
    const changes = diffRecords(
      { name: 'Ana', phone: '11', extra: 'x' },
      { name: 'Ana Costa', phone: '11', extra: 'y' },
      ['name', 'phone']
    );
    expect(changes).toEqual([{ field: 'name', from: 'Ana', to: 'Ana Costa' }]);
  });

  it('trata representante como objeto e null como remoção', () => {
    const changes = diffRecords(
      { representante: { nome: 'João', cpf: '12345678901', rg: '' } },
      { representante: null },
      ['representante']
    );
    expect(changes).toHaveLength(1);
    expect(changes[0].field).toBe('representante');
    expect(changes[0].from).toEqual({ cpf: '12345678901', nome: 'João' });
    expect(changes[0].to).toBeNull();
  });

  it('resume os campos alterados', () => {
    expect(summarizeChanges([{ field: 'name', from: 'A', to: 'B' }, { field: 'cpf' }])).toBe('name, cpf');
    expect(summarizeChanges([])).toBe('Nenhuma alteração');
  });
});
