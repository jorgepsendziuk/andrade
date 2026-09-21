import { describe, expect, it } from 'vitest';
import { normalizeIssueDate, parseLegalRepresentative } from '../../client-patch.js';

describe('parseLegalRepresentative', () => {
  it('mantém undefined quando o campo não veio', () => {
    expect(parseLegalRepresentative(undefined)).toBeUndefined();
  });

  it('remove representante vazio ou nulo', () => {
    expect(parseLegalRepresentative(null)).toBeNull();
    expect(parseLegalRepresentative({ nome: '', cpf: '' })).toBeNull();
  });

  it('normaliza CPF e ignora campos vazios', () => {
    expect(
      parseLegalRepresentative({
        nome: '  Maria Silva  ',
        cpf: '123.456.789-01',
        rg: 'MG-12',
        rgOrgaoEmissor: '',
        rgEstado: 'mg',
        telefone: '65999999999',
      })
    ).toEqual({
      nome: 'Maria Silva',
      cpf: '12345678901',
      rg: 'MG-12',
      rgEstado: 'mg',
      telefone: '65999999999',
    });
  });

  it('normaliza data de emissão para ISO', () => {
    expect(normalizeIssueDate('10/03/2015')).toBe('2015-03-10');
    expect(normalizeIssueDate('2015-03-10')).toBe('2015-03-10');
  });

  it('rejeita CPF do representante com tamanho inválido', () => {
    expect(() => parseLegalRepresentative({ nome: 'João', cpf: '123' })).toThrow(/CPF do representante/i);
  });
});
