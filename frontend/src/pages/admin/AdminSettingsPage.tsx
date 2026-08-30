import { useEffect, useState } from 'react';
import { Loader2, Mail, Plus, Trash2 } from 'lucide-react';
import { SeoHead } from '../../components/seo/SeoHead';
import { useCms } from '../../context/CmsContext';
import { useAdminUser } from '../../components/admin/AdminShell';
import {
  AdminCard,
  AdminField,
  AdminInput,
  AdminTabs,
  AdminTextarea,
  SaveBar,
  StatusBadge,
} from '../../components/admin/AdminForm';
import { ImageUploadField } from '../../components/admin/ImageUploadField';
import {
  fetchAdminSettings,
  sendTestEmail,
  updateEmailSettings,
  updateIntegrationSettings,
} from '../../lib/api';
import type { AdminSettingsResponse } from '../../types/settings';
import type { FooterSocial, SiteInfo } from '../../types/site';

type TabId = 'geral' | 'email' | 'rodape' | 'integracoes';

export function AdminSettingsPage() {
  const user = useAdminUser();
  const isAdmin = user?.role === 'admin';
  const { content, loading: cmsLoading, save } = useCms();

  const [tab, setTab] = useState<TabId>('geral');
  const [siteForm, setSiteForm] = useState<SiteInfo | null>(null);
  const [footerDesc, setFooterDesc] = useState('');
  const [social, setSocial] = useState<FooterSocial[]>([]);

  const [settings, setSettings] = useState<AdminSettingsResponse | null>(null);
  const [emailForm, setEmailForm] = useState({
    smtpHost: '',
    smtpPort: 587,
    smtpSecure: false,
    smtpUser: '',
    smtpFrom: '',
    contactEmail: '',
    smtpPass: '',
  });
  const [integrationsForm, setIntegrationsForm] = useState({
    googlePlaceId: '',
    instagramUsername: '',
    instagramProfileUrl: '',
    siteUrl: '',
  });

  const [saving, setSaving] = useState(false);
  const [saved, setSaved] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [testingEmail, setTestingEmail] = useState(false);
  const [testEmailTo, setTestEmailTo] = useState('');
  const [testResult, setTestResult] = useState<string | null>(null);

  useEffect(() => {
    if (!content?.site) return;
    setSiteForm({ ...content.site });
    setFooterDesc(content.footer?.description ?? '');
    setSocial(content.footer?.social ? [...content.footer.social] : []);
  }, [content]);

  useEffect(() => {
    if (!isAdmin) return;
    fetchAdminSettings()
      .then((data) => {
        setSettings(data);
        setEmailForm({
          smtpHost: data.email.smtpHost,
          smtpPort: data.email.smtpPort,
          smtpSecure: data.email.smtpSecure,
          smtpUser: data.email.smtpUser,
          smtpFrom: data.email.smtpFrom,
          contactEmail: data.email.contactEmail,
          smtpPass: '',
        });
        setIntegrationsForm({ ...data.integrations });
        setTestEmailTo(data.email.contactEmail);
      })
      .catch(console.error);
  }, [isAdmin]);

  const tabs = [
    { id: 'geral' as const, label: 'Informações do site' },
    { id: 'rodape' as const, label: 'Rodapé e redes' },
    ...(isAdmin
      ? [
          { id: 'email' as const, label: 'E-mail (SMTP)' },
          { id: 'integracoes' as const, label: 'Integrações' },
        ]
      : []),
  ];

  const handleSaveSite = async () => {
    if (!siteForm || !content) return;
    setSaving(true);
    setError(null);
    setSaved(false);
    try {
      const next = {
        ...content,
        site: siteForm,
        footer: {
          ...(content.footer ?? { columns: [], social: [] }),
          description: footerDesc,
          social,
        },
      };
      await save(next);
      setSaved(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao salvar');
    } finally {
      setSaving(false);
    }
  };

  const handleSaveEmail = async () => {
    setSaving(true);
    setError(null);
    setSaved(false);
    try {
      const data = await updateEmailSettings({
        ...emailForm,
        smtpPass: emailForm.smtpPass || null,
      });
      setSettings(data);
      setEmailForm((f) => ({ ...f, smtpPass: '' }));
      setSaved(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao salvar');
    } finally {
      setSaving(false);
    }
  };

  const handleSaveIntegrations = async () => {
    setSaving(true);
    setError(null);
    setSaved(false);
    try {
      const data = await updateIntegrationSettings(integrationsForm);
      setSettings(data);
      setSaved(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao salvar');
    } finally {
      setSaving(false);
    }
  };

  const handleTestEmail = async () => {
    setTestingEmail(true);
    setTestResult(null);
    try {
      await sendTestEmail(testEmailTo);
      setTestResult('E-mail de teste enviado com sucesso!');
    } catch (err) {
      setTestResult(err instanceof Error ? err.message : 'Falha no envio');
    } finally {
      setTestingEmail(false);
    }
  };

  const handleSave = () => {
    if (tab === 'geral' || tab === 'rodape') return handleSaveSite();
    if (tab === 'email') return handleSaveEmail();
    if (tab === 'integracoes') return handleSaveIntegrations();
  };

  if (cmsLoading || !siteForm) {
    return (
      <div className="flex items-center justify-center py-20 text-slate-500">
        <Loader2 className="animate-spin mr-2" size={20} />
        Carregando…
      </div>
    );
  }

  return (
    <>
      <SeoHead
        title="Configurações | Portal Andrade"
        description="Configurações do site Andrade Isenções."
        noindex
      />
      <div className="max-w-3xl mx-auto space-y-6 pb-20">
        <div>
          <h1 className="text-2xl font-bold text-brand-800">Configurações</h1>
          <p className="text-slate-500 mt-1">
            Dados do site, e-mail de contato e integrações externas.
          </p>
        </div>

        <AdminTabs tabs={tabs} active={tab} onChange={(id) => { setTab(id as TabId); setSaved(false); setError(null); }} />

        {tab === 'geral' && (
          <AdminCard title="Dados institucionais" description="Exibidos no site, rodapé e formulário de contato.">
            <div className="grid sm:grid-cols-2 gap-4">
              <AdminField label="Nome do site" className="sm:col-span-2">
                <AdminInput
                  value={siteForm.title}
                  onChange={(e) => setSiteForm({ ...siteForm, title: e.target.value })}
                />
              </AdminField>
              <AdminField label="Descrição (SEO)" className="sm:col-span-2">
                <AdminTextarea
                  value={siteForm.description}
                  onChange={(e) => setSiteForm({ ...siteForm, description: e.target.value })}
                />
              </AdminField>
              <AdminField label="Telefone">
                <AdminInput
                  value={siteForm.phone}
                  onChange={(e) => setSiteForm({ ...siteForm, phone: e.target.value })}
                />
              </AdminField>
              <AdminField label="WhatsApp (só números)" hint="Ex: 5565999844212">
                <AdminInput
                  value={siteForm.whatsapp}
                  onChange={(e) => setSiteForm({ ...siteForm, whatsapp: e.target.value })}
                />
              </AdminField>
              <AdminField label="E-mail exibido no site" className="sm:col-span-2">
                <AdminInput
                  type="email"
                  value={siteForm.email}
                  onChange={(e) => setSiteForm({ ...siteForm, email: e.target.value })}
                />
              </AdminField>
              <AdminField label="Horário de atendimento" className="sm:col-span-2">
                <AdminInput
                  value={siteForm.hours}
                  onChange={(e) => setSiteForm({ ...siteForm, hours: e.target.value })}
                />
              </AdminField>
              <AdminField label="Endereço" className="sm:col-span-2">
                <AdminTextarea
                  value={siteForm.address}
                  onChange={(e) => setSiteForm({ ...siteForm, address: e.target.value })}
                />
              </AdminField>
              <AdminField label="Link avaliações Google" className="sm:col-span-2">
                <AdminInput
                  value={siteForm.googleReviewsUrl}
                  onChange={(e) => setSiteForm({ ...siteForm, googleReviewsUrl: e.target.value })}
                />
              </AdminField>
              <AdminField label="Logo (cabeçalho)" className="sm:col-span-2">
                <ImageUploadField
                  label="Logo cabeçalho"
                  value={siteForm.logo}
                  onChange={(url) => setSiteForm({ ...siteForm, logo: url })}
                  hint="Recomendado: PNG com fundo transparente. Fica no build ou no GCS após upload."
                />
              </AdminField>
              <AdminField label="Logo completo" className="sm:col-span-2">
                <ImageUploadField
                  label="Logo completo"
                  value={siteForm.logoFull || ''}
                  onChange={(url) => setSiteForm({ ...siteForm, logoFull: url })}
                  hint="Usado no rodapé, SEO e compartilhamento."
                />
              </AdminField>
              <AdminField label="Copyright" className="sm:col-span-2">
                <AdminInput
                  value={siteForm.copyright}
                  onChange={(e) => setSiteForm({ ...siteForm, copyright: e.target.value })}
                />
              </AdminField>
            </div>
          </AdminCard>
        )}

        {tab === 'rodape' && (
          <>
            <AdminCard title="Texto do rodapé">
              <AdminField label="Descrição">
                <AdminTextarea
                  value={footerDesc}
                  onChange={(e) => setFooterDesc(e.target.value)}
                />
              </AdminField>
            </AdminCard>

            <AdminCard
              title="Redes sociais"
              description="Links exibidos no rodapé. Ícones: instagram, whatsapp."
            >
              <div className="space-y-3">
                {social.map((item, i) => (
                  <div key={item.id} className="grid sm:grid-cols-12 gap-2 items-end p-3 bg-slate-50 rounded-lg">
                    <AdminField label="Rótulo" className="sm:col-span-3">
                      <AdminInput
                        value={item.label}
                        onChange={(e) => {
                          const next = [...social];
                          next[i] = { ...item, label: e.target.value };
                          setSocial(next);
                        }}
                      />
                    </AdminField>
                    <AdminField label="URL" className="sm:col-span-6">
                      <AdminInput
                        value={item.url}
                        onChange={(e) => {
                          const next = [...social];
                          next[i] = { ...item, url: e.target.value };
                          setSocial(next);
                        }}
                      />
                    </AdminField>
                    <AdminField label="Ícone" className="sm:col-span-2">
                      <AdminInput
                        value={item.icon}
                        onChange={(e) => {
                          const next = [...social];
                          next[i] = { ...item, icon: e.target.value };
                          setSocial(next);
                        }}
                      />
                    </AdminField>
                    <button
                      type="button"
                      onClick={() => setSocial(social.filter((_, j) => j !== i))}
                      className="sm:col-span-1 p-2 text-red-500 hover:bg-red-50 rounded-lg justify-self-end"
                      aria-label="Remover"
                    >
                      <Trash2 size={16} />
                    </button>
                  </div>
                ))}
                <button
                  type="button"
                  onClick={() =>
                    setSocial([
                      ...social,
                      {
                        id: `s-${Date.now()}`,
                        label: 'Nova rede',
                        url: 'https://',
                        icon: 'instagram',
                      },
                    ])
                  }
                  className="flex items-center gap-2 text-sm text-brand-600 hover:text-brand-800 font-medium"
                >
                  <Plus size={16} />
                  Adicionar rede social
                </button>
              </div>
            </AdminCard>
          </>
        )}

        {tab === 'email' && isAdmin && settings && (
          <>
            <div className="flex flex-wrap items-center gap-3">
              <StatusBadge
                ok={settings.emailStatus.ok}
                label={settings.emailStatus.ok ? 'SMTP configurado' : 'SMTP incompleto'}
              />
              <span className="text-xs text-slate-400">
                Origem: {settings.email.source === 'env' ? 'variáveis do servidor' : settings.email.source === 'settings' ? 'painel admin' : 'misto'}
              </span>
            </div>

            {settings.emailStatus.message && (
              <div className="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-xl text-sm">
                {settings.emailStatus.message}
              </div>
            )}

            <AdminCard
              title="Servidor SMTP"
              description="Usado para enviar contatos do formulário do site. No Cloud Run, a senha pode vir do Secret Manager."
            >
              <div className="grid sm:grid-cols-2 gap-4">
                <AdminField label="Host SMTP" className="sm:col-span-2">
                  <AdminInput
                    value={emailForm.smtpHost}
                    onChange={(e) => setEmailForm({ ...emailForm, smtpHost: e.target.value })}
                    placeholder="smtp.gmail.com"
                  />
                </AdminField>
                <AdminField label="Porta">
                  <AdminInput
                    type="number"
                    value={emailForm.smtpPort}
                    onChange={(e) =>
                      setEmailForm({ ...emailForm, smtpPort: Number(e.target.value) || 587 })
                    }
                  />
                </AdminField>
                <AdminField label="Conexão segura (SSL)">
                  <label className="flex items-center gap-2 mt-2">
                    <input
                      type="checkbox"
                      checked={emailForm.smtpSecure}
                      onChange={(e) => setEmailForm({ ...emailForm, smtpSecure: e.target.checked })}
                      className="rounded border-slate-300"
                    />
                    <span className="text-sm text-slate-600">SMTP_SECURE (porta 465)</span>
                  </label>
                </AdminField>
                <AdminField label="Usuário SMTP">
                  <AdminInput
                    value={emailForm.smtpUser}
                    onChange={(e) => setEmailForm({ ...emailForm, smtpUser: e.target.value })}
                  />
                </AdminField>
                <AdminField
                  label="Senha SMTP"
                  hint={
                    settings.email.smtpPassConfigured
                      ? 'Senha já configurada. Deixe em branco para manter.'
                      : 'Obrigatória para envio de e-mails.'
                  }
                >
                  <AdminInput
                    type="password"
                    value={emailForm.smtpPass}
                    onChange={(e) => setEmailForm({ ...emailForm, smtpPass: e.target.value })}
                    placeholder="••••••••"
                    autoComplete="new-password"
                  />
                </AdminField>
                <AdminField label="Remetente (From)" className="sm:col-span-2">
                  <AdminInput
                    value={emailForm.smtpFrom}
                    onChange={(e) => setEmailForm({ ...emailForm, smtpFrom: e.target.value })}
                  />
                </AdminField>
                <AdminField
                  label="E-mail de destino dos contatos"
                  hint="Para onde vão as mensagens do formulário do site."
                  className="sm:col-span-2"
                >
                  <AdminInput
                    type="email"
                    value={emailForm.contactEmail}
                    onChange={(e) => setEmailForm({ ...emailForm, contactEmail: e.target.value })}
                  />
                </AdminField>
              </div>
            </AdminCard>

            <AdminCard title="Testar envio" description="Envia um e-mail de teste para validar a configuração.">
              <div className="flex flex-col sm:flex-row gap-3">
                <AdminInput
                  type="email"
                  value={testEmailTo}
                  onChange={(e) => setTestEmailTo(e.target.value)}
                  placeholder="seu@email.com"
                  className="flex-1"
                />
                <button
                  type="button"
                  onClick={handleTestEmail}
                  disabled={testingEmail || !testEmailTo}
                  className="flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-brand-300 text-brand-700 text-sm font-semibold hover:bg-brand-50 disabled:opacity-50"
                >
                  {testingEmail ? <Loader2 size={16} className="animate-spin" /> : <Mail size={16} />}
                  Enviar teste
                </button>
              </div>
              {testResult && (
                <p className={`text-sm mt-2 ${testResult.includes('sucesso') ? 'text-accent' : 'text-red-600'}`}>
                  {testResult}
                </p>
              )}
            </AdminCard>
          </>
        )}

        {tab === 'integracoes' && isAdmin && settings && (
          <>
            <AdminCard title="Status das integrações" description="Chaves sensíveis (API keys) ficam nas variáveis do servidor.">
              <div className="space-y-3">
                <div className="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                  <div>
                    <p className="font-medium text-slate-700">Google Reviews</p>
                    <p className="text-xs text-slate-400">{settings.integrationStatus.googleReviews.source}</p>
                  </div>
                  <StatusBadge
                    ok={settings.integrationStatus.googleReviews.configured}
                    label={settings.integrationStatus.googleReviews.configured ? 'Ativo' : 'Pendente'}
                  />
                </div>
                <div className="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                  <div>
                    <p className="font-medium text-slate-700">Instagram (feed)</p>
                    <p className="text-xs text-slate-400">{settings.integrationStatus.instagram.source}</p>
                  </div>
                  <StatusBadge
                    ok={settings.integrationStatus.instagram.configured}
                    label={settings.integrationStatus.instagram.configured ? 'Ativo' : 'Pendente'}
                  />
                </div>
                <div className="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                  <div>
                    <p className="font-medium text-slate-700">Google Analytics</p>
                    <p className="text-xs text-slate-400">
                      {settings.integrationStatus.analytics.measurementId || 'GA_MEASUREMENT_ID não definido'}
                    </p>
                  </div>
                  <StatusBadge
                    ok={settings.integrationStatus.analytics.configured}
                    label={settings.integrationStatus.analytics.configured ? 'Ativo' : 'Pendente'}
                  />
                </div>
              </div>
            </AdminCard>

            <AdminCard title="Parâmetros editáveis" description="IDs e URLs usados nas integrações e SEO.">
              <div className="grid sm:grid-cols-2 gap-4">
                <AdminField label="URL do site" className="sm:col-span-2">
                  <AdminInput
                    value={integrationsForm.siteUrl}
                    onChange={(e) =>
                      setIntegrationsForm({ ...integrationsForm, siteUrl: e.target.value })
                    }
                  />
                </AdminField>
                <AdminField label="Google Place ID" hint="Requer GOOGLE_PLACES_API_KEY no servidor.">
                  <AdminInput
                    value={integrationsForm.googlePlaceId}
                    onChange={(e) =>
                      setIntegrationsForm({ ...integrationsForm, googlePlaceId: e.target.value })
                    }
                  />
                </AdminField>
                <AdminField label="Instagram @usuario">
                  <AdminInput
                    value={integrationsForm.instagramUsername}
                    onChange={(e) =>
                      setIntegrationsForm({ ...integrationsForm, instagramUsername: e.target.value })
                    }
                  />
                </AdminField>
                <AdminField label="URL do perfil Instagram" className="sm:col-span-2">
                  <AdminInput
                    value={integrationsForm.instagramProfileUrl}
                    onChange={(e) =>
                      setIntegrationsForm({ ...integrationsForm, instagramProfileUrl: e.target.value })
                    }
                  />
                </AdminField>
              </div>
            </AdminCard>

            <div className="bg-brand-50 border border-brand-100 rounded-xl p-4 text-sm text-brand-800">
              <p className="font-semibold mb-1">Variáveis do servidor (não editáveis aqui)</p>
              <ul className="text-xs text-brand-700 space-y-1 list-disc list-inside">
                <li><code>GOOGLE_PLACES_API_KEY</code> — chave da API Google Places</li>
                <li><code>INSTAGRAM_ACCESS_TOKEN</code> — token do Instagram Graph API</li>
                <li><code>GA_MEASUREMENT_ID</code> — ID do Google Analytics 4</li>
                <li><code>SMTP_PASS</code> — senha SMTP (Secret Manager no Cloud Run)</li>
              </ul>
            </div>
          </>
        )}

        <SaveBar saving={saving} saved={saved} error={error} onSave={handleSave} />
      </div>
    </>
  );
}
