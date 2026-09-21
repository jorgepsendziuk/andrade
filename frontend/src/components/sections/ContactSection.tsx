import { useState } from 'react';
import { Phone, Mail, Clock, MapPin } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';
import { submitContact, ContactApiError } from '../../lib/api';
import { trackContactFormConversion } from '../../lib/google-analytics';
import { BrandLogo } from '../ui/BrandLogo';
import { trackWhatsAppClick } from '../../lib/google-analytics';

const DEFAULT_MAP_URL = 'https://maps.app.goo.gl/334gwCfDPaFuRTC7A?g_st=iw';
const DEFAULT_MAP_EMBED =
  'https://maps.google.com/maps?q=Av.+Fernando+Correa+da+Costa,+1899,+Galeria+Italia+Center,+Cuiab%C3%A1+-+MT,+78060-600&hl=pt&z=17&output=embed';

export function ContactSection() {
  const { getSection, updateSection, content } = useCms();
  const section = getSection('contato');
  const [formState, setFormState] = useState({ name: '', email: '', phone: '', message: '' });
  const [lgpdConsent, setLgpdConsent] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');

  if (!section?.enabled || !content) return null;

  const data = section.data as {
    title: string;
    formEnabled: boolean;
    mapEmbed: string;
    mapUrl?: string;
  };

  const { site } = content;
  const logoFull = (site as { logoFull?: string }).logoFull || site.logo;
  const mapUrl = data.mapUrl || DEFAULT_MAP_URL;
  const mapEmbed = data.mapEmbed || DEFAULT_MAP_EMBED;

  const mailtoFallback = () => {
    const subject = encodeURIComponent('Contato via site - Andrade Isenções');
    const body = encodeURIComponent(
      `Nome: ${formState.name}\nEmail: ${formState.email}\nTelefone: ${formState.phone}\n\nMensagem:\n${formState.message}`
    );
    window.location.href = `mailto:${site.email}?subject=${subject}&body=${body}`;
    setSubmitted(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!lgpdConsent) {
      setError('É necessário aceitar a Política de Privacidade para enviar.');
      return;
    }
    setSubmitting(true);
    setError('');
    setSubmitted(false);
    try {
      await submitContact({ ...formState, to: site.email });
      trackContactFormConversion();
      setSubmitted(true);
      setFormState({ name: '', email: '', phone: '', message: '' });
    } catch (err) {
      if (err instanceof ContactApiError && err.status === 503) {
        mailtoFallback();
        return;
      }
      const msg =
        err instanceof ContactApiError
          ? err.message
          : 'Não foi possível enviar agora. Tente pelo WhatsApp ou e-mail direto.';
      setError(msg);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <section id="contato" className="py-12 md:py-16 bg-brand-50">
      <div className="max-w-6xl mx-auto px-4">
        <EditableText
          value={data.title}
          onChange={(v) => updateSection('contato', { title: v })}
          as="h2"
          className="section-title"
        />

        <div className="grid lg:grid-cols-3 gap-6">
          <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-100">
            <h3 className="text-brand-600 font-bold uppercase text-sm tracking-wide mb-4">Onde estamos</h3>
            <ul className="space-y-3 text-sm text-slate-600">
              <li className="flex items-start gap-2">
                <MapPin className="text-brand-500 flex-shrink-0 mt-0.5" size={18} aria-hidden />
                <a href={mapUrl} target="_blank" rel="noopener noreferrer" className="hover:text-brand-600">
                  {site.address}
                </a>
              </li>
              <li className="flex items-center gap-2">
                <Phone className="text-brand-500" size={18} />
                <a href={`tel:${site.phone.replace(/\s/g, '')}`} className="hover:text-brand-600 font-semibold">
                  {site.phone}
                </a>
              </li>
              <li className="flex items-center gap-2">
                <Mail className="text-brand-500" size={18} />
                <a href={`mailto:${site.email}`} className="hover:text-brand-600 break-all">
                  {site.email}
                </a>
              </li>
              <li className="flex items-center gap-2">
                <Clock className="text-brand-500" size={18} />
                {site.hours}
              </li>
            </ul>
            <a
              href={`https://wa.me/${site.whatsapp}`}
              target="_blank"
              rel="noopener noreferrer"
              className="btn-green w-full mt-6 text-sm"
              onClick={(e) => {
                e.preventDefault();
                trackWhatsAppClick(`https://wa.me/${site.whatsapp}`);
              }}
            >
              WhatsApp
            </a>
            <a
              href={mapUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="btn-secondary w-full mt-2 text-sm justify-center"
            >
              Abrir no Google Maps
            </a>
          </div>

          <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-100">
            <div className="flex justify-center mb-5">
              <BrandLogo
                src={logoFull}
                alt={site.title}
                imgClassName="h-16 w-auto object-contain"
              />
            </div>
            {data.formEnabled && (
              <form onSubmit={handleSubmit} className="space-y-3" aria-label="Formulário de contato">
                <div>
                  <label htmlFor="contact-name" className="block text-sm font-medium text-brand-800 mb-1">
                    Nome
                  </label>
                  <input
                    id="contact-name"
                    name="name"
                    type="text"
                    placeholder="Seu nome"
                    required
                    autoComplete="name"
                    value={formState.name}
                    onChange={(e) => setFormState({ ...formState, name: e.target.value })}
                    className="w-full px-3 py-2.5 text-sm rounded border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none"
                  />
                </div>
                <div>
                  <label htmlFor="contact-email" className="block text-sm font-medium text-brand-800 mb-1">
                    E-mail
                  </label>
                  <input
                    id="contact-email"
                    name="email"
                    type="email"
                    placeholder="Seu e-mail"
                    required
                    autoComplete="email"
                    value={formState.email}
                    onChange={(e) => setFormState({ ...formState, email: e.target.value })}
                    className="w-full px-3 py-2.5 text-sm rounded border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none"
                  />
                </div>
                <div>
                  <label htmlFor="contact-phone" className="block text-sm font-medium text-brand-800 mb-1">
                    Telefone
                  </label>
                  <input
                    id="contact-phone"
                    name="phone"
                    type="tel"
                    placeholder="Seu telefone"
                    autoComplete="tel"
                    value={formState.phone}
                    onChange={(e) => setFormState({ ...formState, phone: e.target.value })}
                    className="w-full px-3 py-2.5 text-sm rounded border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none"
                  />
                </div>
                <div>
                  <label htmlFor="contact-message" className="block text-sm font-medium text-brand-800 mb-1">
                    Mensagem
                  </label>
                  <textarea
                    id="contact-message"
                    name="message"
                    placeholder="Sua mensagem"
                    required
                    rows={4}
                    value={formState.message}
                    onChange={(e) => setFormState({ ...formState, message: e.target.value })}
                    className="w-full px-3 py-2.5 text-sm rounded border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none resize-none"
                  />
                </div>
                {error && (
                  <p className="text-red-600 text-sm" role="alert">
                    {error}
                  </p>
                )}
                <label className="flex items-start gap-2 text-sm text-slate-600 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={lgpdConsent}
                    onChange={(e) => setLgpdConsent(e.target.checked)}
                    className="mt-0.5"
                  />
                  <span>
                    Li e concordo com a{' '}
                    <a href="/privacidade" target="_blank" rel="noopener noreferrer" className="text-brand-600 underline">
                      Política de Privacidade
                    </a>{' '}
                    para tratamento dos meus dados neste contato.
                  </span>
                </label>
                <p className="sr-only" aria-live="polite" aria-atomic="true">
                  {submitting ? 'Enviando mensagem...' : submitted ? 'Mensagem enviada com sucesso.' : ''}
                </p>
                <button type="submit" disabled={submitting} className="btn-green w-full text-sm disabled:opacity-60">
                  {submitting ? 'Enviando...' : submitted ? 'Mensagem enviada!' : 'Enviar'}
                </button>
              </form>
            )}
          </div>

          <div className="bg-white rounded-lg overflow-hidden shadow-sm border border-slate-100 min-h-[360px]">
            <iframe
              src={mapEmbed}
              className="w-full h-full min-h-[360px] border-0"
              allowFullScreen
              loading="lazy"
              referrerPolicy="no-referrer-when-downgrade"
              title="Localização Andrade Isenções"
            />
          </div>
        </div>
      </div>
    </section>
  );
}
