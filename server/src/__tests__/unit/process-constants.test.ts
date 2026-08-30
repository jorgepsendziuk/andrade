import { describe, expect, it } from 'vitest';
import { createDefaultSteps, WIZARD_REQUIRED_FILES } from '../../process-constants.js';

describe('process-constants', () => {
  it('createDefaultSteps inicia em documentação', () => {
    const steps = createDefaultSteps();
    expect(steps[0].key).toBe('documentacao');
    expect(steps[0].status).toBe('em_andamento');
  });

  it('WIZARD_REQUIRED_FILES inclui documentos essenciais', () => {
    expect(WIZARD_REQUIRED_FILES).toContain('cnh');
    expect(WIZARD_REQUIRED_FILES).toContain('laudo');
    expect(WIZARD_REQUIRED_FILES).toContain('comprovante_residencia');
  });
});
