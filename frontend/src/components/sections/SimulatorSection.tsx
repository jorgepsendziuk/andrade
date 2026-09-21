import { useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowRight, ArrowLeft, ClipboardList } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';
import { WhatsAppLink } from '../ui/WhatsAppLink';
import { MT_CIDADES } from '../../data/mt-cidades';

interface SimulatorData {
  title: string;
  subtitle: string;
  questions: Array<{
    id: string;
    question: string;
    options: string[];
  }>;
  resultTitle: string;
  resultText: string;
  ctaText: string;
  processCtaText?: string;
  processCtaLink?: string;
}

export function SimulatorSection() {
  const { getSection, updateSection, isEditing } = useCms();
  const section = getSection('simulador');
  const [step, setStep] = useState(0);
  const [answers, setAnswers] = useState<Record<string, string>>({});

  if (!section?.enabled) return null;
  const data = section.data as unknown as SimulatorData;

  const totalSteps = data.questions.length;
  const isComplete = step >= totalSteps;
  const currentQ = data.questions[step];

  const handleAnswer = (value: string) => {
    setAnswers((prev) => ({ ...prev, [currentQ.id]: value }));
    setStep((s) => s + 1);
  };

  const reset = () => {
    setStep(0);
    setAnswers({});
  };

  const whatsappMessage = [
    'Olá! Fiz o simulador no site da Andrade Isenções.',
    ...data.questions.map((q) => `${q.question}: ${answers[q.id] || '—'}`),
    'Gostaria de uma análise especializada do meu caso.',
  ].join('\n');

  const isCityQuestion = currentQ?.id === 'cidade';

  return (
    <section id="simulador" className="py-8 md:py-10 bg-surface">
      <div className="max-w-2xl mx-auto px-4">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('simulador', { title: v })}
          as="h2"
          className="section-title"
        />
        <EditableText
          value={data.subtitle}
          onChange={(v) => updateSection('simulador', { subtitle: v })}
          as="p"
          className="section-subtitle"
        />

        {isEditing && (
          <div className="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl space-y-3 text-sm">
            <p className="font-semibold text-amber-900">Editar perguntas do simulador</p>
            {data.questions.map((q, qi) => (
              <div key={q.id} className="p-3 bg-white rounded-lg border border-amber-100 space-y-2">
                <EditableText
                  value={q.question}
                  onChange={(v) => {
                    const questions = data.questions.map((item, i) =>
                      i === qi ? { ...item, question: v } : item
                    );
                    updateSection('simulador', { questions });
                  }}
                  as="p"
                  className="font-medium text-brand-800"
                />
                <EditableText
                  value={q.options.join(' | ')}
                  onChange={(v) => {
                    const questions = data.questions.map((item, i) =>
                      i === qi ? { ...item, options: v.split('|').map((o) => o.trim()).filter(Boolean) } : item
                    );
                    updateSection('simulador', { questions });
                  }}
                  as="p"
                  className="text-xs text-slate-600"
                  multiline
                />
                <p className="text-[10px] text-slate-400">Opções separadas por | (exceto cidade, que usa lista MT)</p>
              </div>
            ))}
            <EditableText
              value={data.ctaText}
              onChange={(v) => updateSection('simulador', { ctaText: v })}
              as="p"
              className="text-xs"
            />
            <EditableText
              value={data.processCtaText || 'Iniciar processo'}
              onChange={(v) => updateSection('simulador', { processCtaText: v })}
              as="p"
              className="text-xs"
            />
          </div>
        )}

        <div className="bg-white rounded-xl shadow-md border border-brand-100 p-5 md:p-6" aria-live="polite">
          {!isComplete && currentQ && (
            <>
              <div
                className="flex gap-1 mb-6"
                role="progressbar"
                aria-valuenow={step + 1}
                aria-valuemin={1}
                aria-valuemax={totalSteps}
                aria-label={`Progresso: pergunta ${step + 1} de ${totalSteps}`}
              >
                {data.questions.map((_, i) => (
                  <div
                    key={i}
                    className={`h-1.5 flex-1 rounded-full ${i <= step ? 'bg-accent' : 'bg-brand-100'}`}
                    aria-hidden
                  />
                ))}
              </div>
              <p className="text-sm text-text-secondary mb-2" id="simulator-step-label">
                Pergunta {step + 1} de {totalSteps}
              </p>
              <h3 className="font-display font-bold text-brand-800 text-lg mb-6" id="simulator-question">
                {currentQ.question}
              </h3>

              {isCityQuestion ? (
                <div className="space-y-3">
                  <label htmlFor="simulator-cidade" className="sr-only">
                    Selecione sua cidade em Mato Grosso
                  </label>
                  <select
                    id="simulator-cidade"
                    value={answers.cidade || ''}
                    onChange={(e) => setAnswers((prev) => ({ ...prev, cidade: e.target.value }))}
                    className="w-full px-4 py-3 rounded-lg border border-brand-100 text-sm text-brand-800 bg-white focus:border-accent focus:ring-1 focus:ring-accent outline-none"
                    aria-labelledby="simulator-question simulator-step-label"
                  >
                    <option value="">Selecione sua cidade</option>
                    {MT_CIDADES.map((city) => (
                      <option key={city} value={city}>
                        {city}
                      </option>
                    ))}
                  </select>
                  <button
                    type="button"
                    disabled={!answers.cidade}
                    onClick={() => handleAnswer(answers.cidade)}
                    className="btn-primary w-full justify-center text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    Continuar
                    <ArrowRight size={16} />
                  </button>
                </div>
              ) : (
                <div className="space-y-2" role="radiogroup" aria-labelledby="simulator-question">
                  {currentQ.options.map((opt) => (
                    <button
                      key={opt}
                      type="button"
                      role="radio"
                      aria-checked={answers[currentQ.id] === opt}
                      onClick={() => handleAnswer(opt)}
                      className="w-full text-left px-4 py-3 rounded-lg border border-brand-100 hover:border-accent hover:bg-accent/5 transition-colors text-sm font-medium text-brand-800"
                    >
                      {opt}
                    </button>
                  ))}
                </div>
              )}

              {step > 0 && (
                <button
                  type="button"
                  onClick={() => setStep((s) => s - 1)}
                  className="mt-4 flex items-center gap-1 text-text-secondary text-sm hover:text-brand-600"
                >
                  <ArrowLeft size={16} aria-hidden /> Voltar
                </button>
              )}
            </>
          )}

          {isComplete && (
            <div className="text-center">
              <EditableText
                value={data.resultTitle}
                onChange={(v) => updateSection('simulador', { resultTitle: v })}
                as="h3"
                className="font-display font-bold text-brand-800 text-xl mb-3"
              />
              <EditableText
                value={data.resultText}
                onChange={(v) => updateSection('simulador', { resultText: v })}
                as="p"
                className="text-text-secondary text-sm mb-6"
                multiline
              />
              <div className="flex flex-col sm:flex-row gap-3 justify-center items-stretch sm:items-center mb-2">
                <WhatsAppLink message={whatsappMessage} className="btn-primary justify-center">
                  {data.ctaText}
                  <ArrowRight size={18} />
                </WhatsAppLink>
                <Link
                  to={data.processCtaLink || '/iniciar'}
                  className="btn-secondary justify-center"
                >
                  <ClipboardList size={18} />
                  {data.processCtaText || 'Iniciar processo'}
                </Link>
              </div>
              <button onClick={reset} className="block mx-auto mt-4 text-sm text-brand-500 hover:underline">
                Refazer simulador
              </button>
            </div>
          )}
        </div>
      </div>
    </section>
  );
}
