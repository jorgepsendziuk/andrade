import { describe, expect, it } from 'vitest';
import { stripUndefined } from '../../firestore-utils.js';

describe('stripUndefined', () => {
  it('remove campos undefined no nível raiz', () => {
    expect(stripUndefined({ rg: undefined, name: 'Ana' })).toEqual({ name: 'Ana' });
  });

  it('remove undefined em objetos aninhados', () => {
    expect(
      stripUndefined({
        representante: { nome: 'João', rg: undefined, cpf: '123' },
        phone: undefined,
      })
    ).toEqual({ representante: { nome: 'João', cpf: '123' } });
  });

  it('preserva null e valores falsy válidos', () => {
    expect(stripUndefined({ a: null, b: 0, c: false, d: '' })).toEqual({
      a: null,
      b: 0,
      c: false,
      d: '',
    });
  });

  it('trata arrays com objetos contendo undefined', () => {
    expect(stripUndefined([{ rg: undefined, nome: 'A' }])).toEqual([{ nome: 'A' }]);
  });
});
