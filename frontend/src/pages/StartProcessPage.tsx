import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import {
  ArrowLeft, ArrowRight, CheckCircle, FileText, Shield, Upload, User,
} from 'lucide-react';
import { PageLayout } from '../components/layout/PageLayout';
import { SeoHead } from '../components/seo/SeoHead';
import { BrandLogo } from '../components/ui/BrandLogo';
import { DocumentUploadField } from '../components/ui/DocumentUploadField';
import { registerPortal } from '../lib/portal-api';
import { PORTAL_CLIENT_PROCESS } from '../lib/portal-routes';
import { setToken } from '../lib/api';
import { FILE_TYPE_LABELS, type FileTypeCode } from '../types/process';

const STEPS = ['lgpd', 'dados', 'representante', 'orientacao', 'documentos', 'senha'] as const;
type Step = (typeof STEPS)[number];

const DOC_GUIDE: { type: FileTypeCode; title: string; desc: string }[] = [
  { type: 'cnh', title: 'CNH', desc: 'Carteira Nacional de Habilitação válida (frente e verso em um único PDF ou foto legível).' },
  { type: 'laudo', title: 'Laudo médico', desc: 'Laudo com CID e descrição da deficiência, emitido por médico habilitado.' },
  { type: 'comprovante_residencia', title: 'Comprovante de residência', desc: 'Conta de luz, água ou telefone dos últimos 90 dias, em seu nome ou de familiar.' },
  { type: 'cpf', title: 'CPF (opcional)', desc: 'Se não constar na CNH, envie cópia do CPF.' },
  { type: 'alvara_curatela', title: 'Alvará de curatela', desc: 'Obrigatório quando há representante legal/tutor.' },
];

export function StartProcessPage() {
  const navigate = useNavigate();
  const [step, setStep] = useState<Step>('lgpd');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const [lgpdConsent, setLgpdConsent] = useState(false);
  const [termsConsent, setTermsConsent] = useState(false);
  const [hasRepresentante, setHasRepresentante] = useState(false);

  const [form, setForm] = useState({
    name: '', email: '', cpf: '', phone: '', rg: '', rgEstado: 'MT',
    endereco: '', numero: '', bairro: '', cep: '', cidade: '', uf: 'MT',
    repNome: '', repCpf: '', repRg: '', repTelefone: '',
    password: '', passwordConfirm: '',
  });

  const [files, setFiles] = useState<Partial<Record<FileTypeCode, File>>>({});
  const [docHint, setDocHint] = useState('');

  const requiredDocTypes: FileTypeCode[] = [
    'cnh',
    'laudo',
    'comprovante_residencia',
    ...(hasRepresentante ? (['alvara_curatela'] as FileTypeCode[]) : []),
  ];

  const missingDocTypes = requiredDocTypes.filter((type) => !files[type]);
  const docsComplete = missingDocTypes.length === 0;

  const stepIndex = STEPS.indexOf(step);
  const progress = ((stepIndex + 1) / STEPS.length) * 100;

  const set = (key: keyof typeof form, value: string) =>
    setForm((f) => ({ ...f, [key]: value }));

  const next = () => {
    setDocHint('');
    setStep(STEPS[stepIndex + 1]);
  };
  const back = () => {
    setDocHint('');
    setStep(STEPS[stepIndex - 1]);
  };

  const tryNextFromDocs = () => {
    if (docsComplete) {
      next();
      return;
    }
    setDocHint(`Envie os documentos obrigatórios: ${missingDocTypes.map((t) => FILE_TYPE_LABELS[t]).join(', ')}.`);
  };

  const handleSubmit = async () => {
    setError('');
    if (form.password !== form.passwordConfirm) {
      setError('As senhas não coincidem.');
      return;
    }
    if (form.password.length < 8) {
      setError('A senha deve ter no mínimo 8 caracteres.');
      return;
    }

    setLoading(true);
    try {
      const fd = new FormData();
      Object.entries(form).forEach(([k, v]) => fd.append(k, v));
      fd.append('lgpdConsent', 'true');
      fd.append('termsConsent', 'true');
      fd.append('hasRepresentante', hasRepresentante ? 'true' : 'false');
      fd.append('modality', 'pcd');

      for (const [type, file] of Object.entries(files)) {
        if (file) fd.append(type, file);
      }

      const result = await registerPortal(fd);
      setToken(result.token);
      navigate(PORTAL_CLIENT_PROCESS, { replace: true });
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Erro ao cadastrar');
    } finally {
      setLoading(false);
    }
  };

  return (
    <PageLayout>
      <SeoHead
        title="Iniciar processo de isenção PCD"
        description="Cadastre-se e envie seus documentos para iniciar o processo de isenção."
        path="/iniciar"
      />
      <div className="bg-brand-800 py-8">
        <div className="max-w-2xl mx-auto px-4 text-center">
          <BrandLogo
            src="/assets/logo/logo-header.png"
            alt="Andrade Isenções"
            imgClassName="h-10 mx-auto brightness-0 invert mb-4"
            markClassName="text-brand-100"
          />
          <h1 className="font-display text-2xl font-extrabold text-white">Iniciar processo</h1>
          <p className="text-brand-100 text-sm mt-2">Cadastro e envio de documentos para análise</p>
          <div className="mt-4 h-2 bg-brand-700 rounded-full overflow-hidden">
            <div className="h-full bg-accent transition-all" style={{ width: `${progress}%` }} />
          </div>
        </div>
      </div>

      <div className="max-w-2xl mx-auto px-4 py-8">
        {error && (
          <div className="mb-4 bg-red-50 text-red-600 px-4 py-3 rounded-xl text-sm">{error}</div>
        )}

        {step === 'lgpd' && (
          <div className="bg-white rounded-2xl p-6 shadow-card border border-brand-100 space-y-4">
            <div className="flex items-center gap-3 text-brand-800">
              <Shield size={24} />
              <h2 className="font-display font-bold text-lg">Privacidade e termos</h2>
            </div>
            <p className="text-sm text-slate-600 leading-relaxed">
              Para iniciar seu processo, precisamos tratar seus dados pessoais e de saúde conforme a{' '}
              <Link to="/privacidade" className="text-brand-600 underline" target="_blank">Política de Privacidade</Link>{' '}
              e os <Link to="/termos" className="text-brand-600 underline" target="_blank">Termos de Uso</Link>.
            </p>
            <label className="flex items-start gap-3 text-sm cursor-pointer">
              <input type="checkbox" checked={lgpdConsent} onChange={(e) => setLgpdConsent(e.target.checked)} className="mt-1" />
              <span>Li e concordo com a Política de Privacidade e autorizo o tratamento dos meus dados para assessoria de isenção PCD.</span>
            </label>
            <label className="flex items-start gap-3 text-sm cursor-pointer">
              <input type="checkbox" checked={termsConsent} onChange={(e) => setTermsConsent(e.target.checked)} className="mt-1" />
              <span>Li e aceito os Termos de Uso do portal e dos serviços de assessoria.</span>
            </label>
            <button
              type="button"
              disabled={!lgpdConsent || !termsConsent}
              onClick={next}
              className="btn-primary w-full justify-center disabled:opacity-50"
            >
              Continuar <ArrowRight size={16} />
            </button>
          </div>
        )}

        {step === 'dados' && (
          <div className="bg-white rounded-2xl p-6 shadow-card border border-brand-100 space-y-4">
            <div className="flex items-center gap-3 text-brand-800">
              <User size={24} />
              <h2 className="font-display font-bold text-lg">Seus dados</h2>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
              <label className="sm:col-span-2 text-sm">Nome completo *
                <input className="input-field mt-1" value={form.name} onChange={(e) => set('name', e.target.value)} required />
              </label>
              <label className="text-sm">CPF *
                <input className="input-field mt-1" value={form.cpf} onChange={(e) => set('cpf', e.target.value)} placeholder="000.000.000-00" required />
              </label>
              <label className="text-sm">Telefone
                <input className="input-field mt-1" value={form.phone} onChange={(e) => set('phone', e.target.value)} />
              </label>
              <label className="sm:col-span-2 text-sm">E-mail *
                <input type="email" className="input-field mt-1" value={form.email} onChange={(e) => set('email', e.target.value)} required />
              </label>
              <label className="text-sm">RG
                <input className="input-field mt-1" value={form.rg} onChange={(e) => set('rg', e.target.value)} />
              </label>
              <label className="text-sm">UF do RG
                <input className="input-field mt-1" value={form.rgEstado} onChange={(e) => set('rgEstado', e.target.value)} />
              </label>
              <label className="sm:col-span-2 text-sm">Endereço
                <input className="input-field mt-1" value={form.endereco} onChange={(e) => set('endereco', e.target.value)} />
              </label>
              <label className="text-sm">Número
                <input className="input-field mt-1" value={form.numero} onChange={(e) => set('numero', e.target.value)} />
              </label>
              <label className="text-sm">Bairro
                <input className="input-field mt-1" value={form.bairro} onChange={(e) => set('bairro', e.target.value)} />
              </label>
              <label className="text-sm">Cidade
                <input className="input-field mt-1" value={form.cidade} onChange={(e) => set('cidade', e.target.value)} />
              </label>
              <label className="text-sm">UF
                <input className="input-field mt-1" value={form.uf} onChange={(e) => set('uf', e.target.value)} />
              </label>
            </div>
            <div className="flex gap-3">
              <button type="button" onClick={back} className="btn-secondary flex-1 justify-center"><ArrowLeft size={16} /> Voltar</button>
              <button type="button" onClick={next} disabled={!form.name || !form.cpf || !form.email} className="btn-primary flex-1 justify-center disabled:opacity-50">Continuar <ArrowRight size={16} /></button>
            </div>
          </div>
        )}

        {step === 'representante' && (
          <div className="bg-white rounded-2xl p-6 shadow-card border border-brand-100 space-y-4">
            <h2 className="font-display font-bold text-lg text-brand-800">Representante legal</h2>
            <label className="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" checked={hasRepresentante} onChange={(e) => setHasRepresentante(e.target.checked)} />
              Tenho representante legal / tutor / curador
            </label>
            {hasRepresentante && (
              <div className="grid gap-3 sm:grid-cols-2">
                <label className="sm:col-span-2 text-sm">Nome do representante
                  <input className="input-field mt-1" value={form.repNome} onChange={(e) => set('repNome', e.target.value)} />
                </label>
                <label className="text-sm">CPF
                  <input className="input-field mt-1" value={form.repCpf} onChange={(e) => set('repCpf', e.target.value)} />
                </label>
                <label className="text-sm">RG
                  <input className="input-field mt-1" value={form.repRg} onChange={(e) => set('repRg', e.target.value)} />
                </label>
                <label className="sm:col-span-2 text-sm">Telefone
                  <input className="input-field mt-1" value={form.repTelefone} onChange={(e) => set('repTelefone', e.target.value)} />
                </label>
              </div>
            )}
            <div className="flex gap-3">
              <button type="button" onClick={back} className="btn-secondary flex-1 justify-center"><ArrowLeft size={16} /> Voltar</button>
              <button type="button" onClick={next} className="btn-primary flex-1 justify-center">Continuar <ArrowRight size={16} /></button>
            </div>
          </div>
        )}

        {step === 'orientacao' && (
          <div className="bg-white rounded-2xl p-6 shadow-card border border-brand-100 space-y-4">
            <div className="flex items-center gap-3 text-brand-800">
              <FileText size={24} />
              <h2 className="font-display font-bold text-lg">Documentos necessários</h2>
            </div>
            <p className="text-sm text-slate-600">Na próxima etapa você enviará os arquivos. Use PDF ou foto (JPEG/PNG), legíveis.</p>
            <ul className="space-y-3">
              {DOC_GUIDE.filter((d) => d.type !== 'alvara_curatela' || hasRepresentante).map((doc) => (
                <li key={doc.type} className="flex gap-3 p-3 bg-brand-50 rounded-xl text-sm">
                  <CheckCircle size={18} className="text-brand-600 flex-shrink-0 mt-0.5" />
                  <div>
                    <strong>{doc.title}</strong>
                    <p className="text-slate-600 mt-0.5">{doc.desc}</p>
                  </div>
                </li>
              ))}
            </ul>
            <div className="flex gap-3">
              <button type="button" onClick={back} className="btn-secondary flex-1 justify-center"><ArrowLeft size={16} /> Voltar</button>
              <button type="button" onClick={next} className="btn-primary flex-1 justify-center">Enviar documentos <ArrowRight size={16} /></button>
            </div>
          </div>
        )}

        {step === 'documentos' && (
          <div className="bg-white rounded-2xl p-6 shadow-card border border-brand-100 space-y-4">
            <div className="flex items-center gap-3 text-brand-800">
              <Upload size={24} />
              <h2 className="font-display font-bold text-lg">Upload de documentos</h2>
            </div>
            <p className="text-sm text-slate-600">
              Toque em cada campo abaixo para escolher o arquivo no celular ou computador. Formatos aceitos: PDF, JPEG ou PNG.
            </p>
            {missingDocTypes.length > 0 && (
              <p className="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                Faltam {missingDocTypes.length} documento(s): {missingDocTypes.map((t) => FILE_TYPE_LABELS[t]).join(', ')}.
              </p>
            )}
            {docHint && (
              <p className="text-sm text-red-600 bg-red-50 border border-red-200 rounded-xl px-4 py-3">{docHint}</p>
            )}
            {requiredDocTypes.map((type) => {
              const guide = DOC_GUIDE.find((d) => d.type === type);
              return (
                <DocumentUploadField
                  key={type}
                  label={FILE_TYPE_LABELS[type]}
                  required
                  description={guide?.desc}
                  file={files[type]}
                  onSelect={(file) => {
                    setDocHint('');
                    setFiles((f) => ({ ...f, [type]: file }));
                  }}
                />
              );
            })}
            <DocumentUploadField
              label="CPF"
              description="Opcional — envie apenas se o CPF não constar na CNH."
              file={files.cpf}
              onSelect={(file) => {
                setDocHint('');
                setFiles((f) => ({ ...f, cpf: file }));
              }}
            />
            <div className="flex gap-3">
              <button type="button" onClick={back} className="btn-secondary flex-1 justify-center"><ArrowLeft size={16} /> Voltar</button>
              <button
                type="button"
                onClick={tryNextFromDocs}
                className={`btn-primary flex-1 justify-center ${!docsComplete ? 'opacity-60' : ''}`}
              >
                Continuar <ArrowRight size={16} />
              </button>
            </div>
          </div>
        )}

        {step === 'senha' && (
          <div className="bg-white rounded-2xl p-6 shadow-card border border-brand-100 space-y-4">
            <h2 className="font-display font-bold text-lg text-brand-800">Criar senha do portal</h2>
            <p className="text-sm text-slate-600">Use esta senha para acompanhar seu processo em /entrar</p>
            <label className="text-sm block">Senha (mín. 8 caracteres)
              <input type="password" className="input-field mt-1" value={form.password} onChange={(e) => set('password', e.target.value)} />
            </label>
            <label className="text-sm block">Confirmar senha
              <input type="password" className="input-field mt-1" value={form.passwordConfirm} onChange={(e) => set('passwordConfirm', e.target.value)} />
            </label>
            <div className="flex gap-3">
              <button type="button" onClick={back} className="btn-secondary flex-1 justify-center"><ArrowLeft size={16} /> Voltar</button>
              <button type="button" onClick={handleSubmit} disabled={loading} className="btn-primary flex-1 justify-center disabled:opacity-50">
                {loading ? 'Enviando…' : 'Finalizar cadastro'}
              </button>
            </div>
          </div>
        )}
      </div>
    </PageLayout>
  );
}
