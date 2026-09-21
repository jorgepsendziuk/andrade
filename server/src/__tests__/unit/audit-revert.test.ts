import { describe, expect, it } from 'vitest';
import { isRevertableAudit } from '../../audit-revert.js';
import type { AuditLogRecord } from '../../types/process.js';

function log(partial: Partial<AuditLogRecord>): AuditLogRecord {
  return {
    id: '1',
    action: 'update',
    resourceType: 'client',
    resourceId: 'c1',
    userId: 'u1',
    userRole: 'cliente',
    createdAt: '2026-09-04T12:00:00.000Z',
    changes: [{ field: 'phone', from: '', to: '11' }],
    ...partial,
  };
}

describe('isRevertableAudit', () => {
  it('permite desfazer alteração de cadastro', () => {
    expect(isRevertableAudit(log({}))).toBe(true);
  });

  it('permite desfazer alteração de processo', () => {
    expect(
      isRevertableAudit(
        log({
          resourceType: 'process',
          changes: [{ field: 'vehicle', from: null, to: { marca: 'Fiat' } }],
        })
      )
    ).toBe(true);
  });

  it('bloqueia envio de arquivo e registros sem histórico', () => {
    expect(isRevertableAudit(log({ action: 'upload' }))).toBe(false);
    expect(isRevertableAudit(log({ changes: [] }))).toBe(false);
    expect(isRevertableAudit(log({ resourceId: undefined }))).toBe(false);
  });
});
