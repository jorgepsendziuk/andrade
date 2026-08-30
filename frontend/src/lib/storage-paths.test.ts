import { describe, expect, it } from 'vitest';
import {
  buildClientStoragePrefix,
  formatHumanStoragePath,
  formatStorageSegment,
  slugifySegment,
} from './storage-paths';

describe('storage-paths', () => {
  it('slugifySegment remove acentos', () => {
    expect(slugifySegment('José da Silva')).toBe('jose-da-silva');
  });

  it('buildClientStoragePrefix monta pasta do cliente', () => {
    expect(buildClientStoragePrefix('Maria', '12345678901')).toBe('clientes/maria-12345678901/');
  });

  it('formatStorageSegment traduz pastas conhecidas', () => {
    expect(formatStorageSegment('clientes')).toBe('Clientes');
    expect(formatStorageSegment('empresa')).toBe('Empresa');
  });

  it('formatHumanStoragePath monta breadcrumb', () => {
    expect(formatHumanStoragePath('clientes/maria-123/processos/')).toBe(
      'Clientes › maria 123 › Processos'
    );
  });
});
