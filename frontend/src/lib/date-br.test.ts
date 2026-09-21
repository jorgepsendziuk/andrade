import { describe, expect, it } from 'vitest';
import { formatDateBrInput, parseFlexibleDate, toStoredDate } from './date-br';

describe('date-br', () => {
  it('mascara a digitação em dd/mm/aaaa', () => {
    expect(formatDateBrInput('1')).toBe('1');
    expect(formatDateBrInput('10')).toBe('10');
    expect(formatDateBrInput('1003')).toBe('10/03');
    expect(formatDateBrInput('10032015')).toBe('10/03/2015');
  });

  it('aceita ISO e data brasileira', () => {
    expect(parseFlexibleDate('2015-03-10')).toEqual({ iso: '2015-03-10', br: '10/03/2015' });
    expect(parseFlexibleDate('10/03/2015')).toEqual({ iso: '2015-03-10', br: '10/03/2015' });
    expect(parseFlexibleDate('31/02/2020')).toBeNull();
  });

  it('grava ISO quando a data é válida', () => {
    expect(toStoredDate('10/03/2015')).toBe('2015-03-10');
    expect(toStoredDate('')).toBe('');
  });
});
