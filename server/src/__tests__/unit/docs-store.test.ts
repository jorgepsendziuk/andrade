import { describe, expect, it } from 'vitest';
import { isSafeStoragePath, validateDocMime } from '../../docs-store.js';

describe('docs-store', () => {
  it('validateDocMime aceita PDF e imagens', () => {
    expect(validateDocMime('application/pdf')).toBe(true);
    expect(validateDocMime('image/jpeg')).toBe(true);
    expect(validateDocMime('text/plain')).toBe(false);
  });

  it('isSafeStoragePath rejeita traversal e paths absolutos', () => {
    expect(isSafeStoragePath('clientes/foo/bar.pdf')).toBe(true);
    expect(isSafeStoragePath('empresa/modelo.pdf')).toBe(true);
    expect(isSafeStoragePath('../etc/passwd')).toBe(false);
    expect(isSafeStoragePath('/absolute/path')).toBe(false);
    expect(isSafeStoragePath('')).toBe(false);
  });
});
