import { describe, expect, it } from 'vitest';
import {
  auditActionLabel,
  auditFieldLabel,
  auditFriendlySummary,
  auditResourceLabel,
  auditRoleLabel,
  canRevertAuditLog,
  formatAuditValue,
  sortAuditLogs,
} from './audit-ui';
import type { AuditLogRecord } from '../types/process';

function log(partial: Partial<AuditLogRecord>): AuditLogRecord {
  return {
    id: '1',
    action: 'update',
    resourceType: 'client',
    resourceId: 'c1',
    userId: 'u1',
    userRole: 'cliente',
    createdAt: '2026-09-04T12:00:00.000Z',
    ...partial,
  };
}

describe('audit-ui', () => {
  it('traduz ações, recursos e papéis', () => {
    expect(auditActionLabel('upload')).toBe('Enviou arquivo');
    expect(auditActionLabel('download')).toBe('Baixou arquivo');
    expect(auditResourceLabel('process_file')).toBe('Arquivo');
    expect(auditRoleLabel('admin')).toBe('Administração');
    expect(auditFieldLabel('representante')).toBe('Representante legal');
  });

  it('resume alterações em português', () => {
    expect(
      auditFriendlySummary(
        log({
          changes: [
            { field: 'phone', from: '1', to: '2' },
            { field: 'representante', from: null, to: { nome: 'Ana' } },
          ],
        })
      )
    ).toBe('Alterou Telefone, Representante legal');
  });

  it('formata valores sem JSON cru', () => {
    expect(formatAuditValue(null)).toBe('vazio');
    expect(formatAuditValue({ nome: 'João', cpf: '1' })).toBe('João');
    expect(formatAuditValue({ marca: 'Fiat', modelo: 'Mobi', ano: '2024' })).toBe('Fiat Mobi 2024');
  });

  it('só permite desfazer alteração de cadastro ou processo com histórico', () => {
    expect(canRevertAuditLog(log({ changes: [{ field: 'phone', from: '1', to: '2' }] }))).toBe(true);
    expect(canRevertAuditLog(log({ action: 'upload', changes: [] }))).toBe(false);
    expect(canRevertAuditLog(log({ resourceType: 'process_file', changes: [{ field: 'name', from: 'a', to: 'b' }] }))).toBe(false);
  });

  it('ordena por quem fez', () => {
    const sorted = sortAuditLogs(
      [log({ id: 'a', userEmail: 'zeta@test.com' }), log({ id: 'b', userEmail: 'ana@test.com' })],
      'who',
      'asc'
    );
    expect(sorted.map((item) => item.id)).toEqual(['b', 'a']);
  });
});
