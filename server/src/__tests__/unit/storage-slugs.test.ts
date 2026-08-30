import { describe, expect, it } from 'vitest';
import {
  buildClientStorageSlug,
  clientOwnsObjectName,
  slugifySegment,
} from '../../storage-slugs.js';

describe('storage-slugs', () => {
  it('slugifySegment normaliza acentos e espaços', () => {
    expect(slugifySegment('Marisol Aparecida')).toBe('marisol-aparecida');
  });

  it('buildClientStorageSlug inclui CPF', () => {
    const slug = buildClientStorageSlug('Maria Silva', '123.456.789-09');
    expect(slug).toContain('maria-silva');
    expect(slug).toContain('12345678909');
  });

  it('clientOwnsObjectName aceita slug e UUID legado', () => {
    expect(clientOwnsObjectName('uuid-1', 'maria-123', 'clientes/maria-123/doc.pdf')).toBe(true);
    expect(clientOwnsObjectName('uuid-1', 'maria-123', 'clientes/uuid-1/doc.pdf')).toBe(true);
    expect(clientOwnsObjectName('uuid-1', 'maria-123', 'clientes/outro/doc.pdf')).toBe(false);
  });
});
