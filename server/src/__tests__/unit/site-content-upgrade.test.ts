import { describe, expect, it } from 'vitest';
import {
  ASSESSORIA_ITEMS,
  shouldReplaceAssessoriaItems,
  upgradeSiteContent,
} from '../../site-content-upgrade.js';

describe('upgradeSiteContent', () => {
  it('substitui a seção antiga com item de rodízio', () => {
    const { content, changed } = upgradeSiteContent({
      site: { title: 'Andrade Consultoria e Isenções' },
      sections: [
        {
          id: 'servicos',
          data: {
            title: 'Assessoria nos processos de:',
            items: [
              { id: 'ipi', title: 'IPI' },
              { id: 'rodizio', title: 'Cotação' },
            ],
          },
        },
        {
          id: 'quem-somos',
          data: {
            historyParagraphs: [
              'História oficial da Andrade.',
              'A Andrade Consultoria também atua como Correspondente Bancário e Representante Autorizado do Consórcio Nacional Volkswagen.',
            ],
          },
        },
      ],
    });

    expect(changed).toBe(true);
    expect((content.site as { title: string }).title).toBe('Andrade Isenções');
    const servicos = (content.sections as Array<{ id: string; data: { items: Array<{ id: string }> } }>).find(
      (s) => s.id === 'servicos'
    );
    expect(servicos?.data.items.map((i) => i.id)).toEqual(ASSESSORIA_ITEMS.map((i) => i.id));
    const about = (content.sections as Array<{ id: string; data: { historyParagraphs: string[] } }>).find(
      (s) => s.id === 'quem-somos'
    );
    expect(about?.data.historyParagraphs.join(' ')).not.toMatch(/consórcio|correspondente bancário/i);
  });

  it('troca o CTA antigo dos carros', () => {
    const { content, changed } = upgradeSiteContent({
      sections: [{ id: 'carros', data: { ctaText: 'Saber mais' } }],
    });
    expect(changed).toBe(true);
    const carros = (content.sections as Array<{ id: string; data: { ctaText: string } }>).find(
      (s) => s.id === 'carros'
    );
    expect(carros?.data.ctaText).toBe('SOLICITAR MINHA COTAÇÃO');
  });

  it('preenche o link do CTA final quando está vazio', () => {
    const { content, changed } = upgradeSiteContent({
      sections: [{ id: 'final-cta', data: { ctaText: 'QUERO COMEÇAR MINHA ANÁLISE' } }],
    });
    expect(changed).toBe(true);
    const cta = (content.sections as Array<{ id: string; data: { ctaLink: string } }>).find(
      (s) => s.id === 'final-cta'
    );
    expect(cta?.data.ctaLink).toBe('/iniciar');
  });

  it('corrige o subtítulo do simulador que copiou o texto do botão', () => {
    const { content, changed } = upgradeSiteContent({
      sections: [{ id: 'simulador', data: { subtitle: 'QUERO COMEÇAR MINHA ANÁLISE' } }],
    });
    expect(changed).toBe(true);
    const simulador = (content.sections as Array<{ id: string; data: { subtitle: string } }>).find(
      (s) => s.id === 'simulador'
    );
    expect(simulador?.data.subtitle).not.toMatch(/quero come/i);
  });

  it('não altera a seção já atualizada', () => {
    expect(shouldReplaceAssessoriaItems(ASSESSORIA_ITEMS)).toBe(false);
    const { changed } = upgradeSiteContent({
      site: { title: 'Andrade Isenções' },
      sections: [{ id: 'servicos', data: { title: 'ASSESSORIA NOS PROCESSOS DE:', items: ASSESSORIA_ITEMS } }],
    });
    expect(changed).toBe(false);
  });
});
