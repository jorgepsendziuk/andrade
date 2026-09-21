import { describe, expect, it } from 'vitest';
import { stripRodizioFromValue, stripRodizioText } from '../../public-copy.js';

describe('stripRodizioText', () => {
  it('troca a frase padrão por cartão especial', () => {
    expect(stripRodizioText('Possibilidade de isenção de rodízio e cartão especial')).toBe(
      'Possibilidade de cartão especial de estacionamento'
    );
  });

  it('remove qualquer menção restante a rodízio', () => {
    expect(stripRodizioText('Isenção de rodízio municipal')).not.toMatch(/rod[ií]zio/i);
  });
});

describe('stripRodizioFromValue', () => {
  it('limpa benefícios em objeto de condição', () => {
    const { value, changed } = stripRodizioFromValue({
      benefits: ['Isenção de IPI', 'Possibilidade de isenção de rodízio e cartão especial'],
    });
    expect(changed).toBe(true);
    expect(value.benefits[1]).toBe('Possibilidade de cartão especial de estacionamento');
  });
});
