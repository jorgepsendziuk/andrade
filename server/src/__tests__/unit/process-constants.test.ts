import { describe, expect, it } from 'vitest';
import { ACTIVE_STEPS, createDefaultSteps, normalizeProcessRecord, WIZARD_REQUIRED_FILES } from '../../process-constants.js';
import type { ProcessRecord } from '../../types/process.js';

describe('process-constants', () => {
  it('createDefaultSteps inicia em documentação', () => {
    const steps = createDefaultSteps();
    expect(steps[0].key).toBe('documentacao');
    expect(steps[0].status).toBe('em_andamento');
  });

  it('ACTIVE_STEPS segue a ordem operacional do processo PCD', () => {
    expect(ACTIVE_STEPS).toEqual([
      'documentacao',
      'analise',
      'pericia',
      'ipi',
      'veiculo',
      'sefaz_mt',
      'sefaz_sp',
      'concluido',
    ]);
  });

  it('normalizeProcessRecord migra etapas legadas icms/ipva', () => {
    const legacy: ProcessRecord = {
      id: '1',
      clientId: 'c1',
      modality: 'pcd',
      status: 'ativo',
      currentStep: 'icms',
      steps: [
        { key: 'documentacao', label: 'Documentação', status: 'concluida' },
        { key: 'analise', label: 'Análise', status: 'concluida' },
        { key: 'pericia', label: 'Perícia', status: 'concluida' },
        { key: 'ipi', label: 'IPI', status: 'concluida' },
        { key: 'icms', label: 'ICMS', status: 'em_andamento', protocol: 'SEFAZ-1' },
        { key: 'ipva', label: 'IPVA', status: 'pendente' },
        { key: 'veiculo', label: 'Veículo', status: 'pendente' },
        { key: 'concluido', label: 'Concluído', status: 'pendente' },
      ],
      createdAt: '2026-01-01T00:00:00.000Z',
      updatedAt: '2026-01-01T00:00:00.000Z',
    };

    const normalized = normalizeProcessRecord(legacy);
    expect(normalized.currentStep).toBe('sefaz_mt');
    expect(normalized.steps.map((s) => s.key)).toEqual(ACTIVE_STEPS);
    expect(normalized.steps.find((s) => s.key === 'sefaz_mt')?.protocol).toBe('SEFAZ-1');
    expect(normalized.steps.find((s) => s.key === 'sefaz_sp')?.status).toBe('pendente');
  });

  it('WIZARD_REQUIRED_FILES inclui documentos essenciais', () => {
    expect(WIZARD_REQUIRED_FILES).toContain('cnh');
    expect(WIZARD_REQUIRED_FILES).toContain('laudo');
    expect(WIZARD_REQUIRED_FILES).toContain('comprovante_residencia');
  });
});
