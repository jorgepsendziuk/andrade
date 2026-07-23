import { useState } from 'react';
import { Phone, Mail, Clock, MapPin } from 'lucide-react';
import { EditableText } from '../cms/EditableText';
import { useCms } from '../../context/CmsContext';

export function ContactSection() {
  const { getSection, updateSection, content } = useCms();
  const section = getSection('contato');
  const [formState, setFormState] = useState({ name: '', email: '', phone: '', message: '' });
  const [submitted, setSubmitted] = useState(false);

  if (!section?.enabled || !content) return null;

  const data = section.data as {
    title: string;
    formEnabled: boolean;
    mapEmbed: string;
  };

  const { site } = content;
  const logoFull = (site as { logoFull?: string }).logoFull || site.logo;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const subject = encodeURIComponent('Contato via site - Andrade Isenções');
    const body = encodeURIComponent(
      `Nome: ${formState.name}\nEmail: ${formState.email}\nTelefone: ${formState.phone}\n\nMensagem:\n${formState.message}`
    );
    window.location.href = `mailto:${site.email}?subject=${subject}&body=${body}`;
    setSubmitted(true);
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

        {/* 3 colunas como original */}
        <div className="grid lg:grid-cols-3 gap-6">
          {/* Coluna 1: Onde estamos */}
          <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-100">
            <h3 className="text-brand-600 font-bold uppercase text-sm tracking-wide mb-4">Onde estamos</h3>
            <ul className="space-y-3 text-sm text-slate-600">
              <li className="flex items-start gap-2">
                <MapPin className="text-brand-500 flex-shrink-0 mt-0.5" size={18} />
                <span>{site.address}</span>
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
            >
              WhatsApp
            </a>
          </div>

          {/* Coluna 2: Formulário com logo */}
          <div className="bg-white rounded-lg p-6 shadow-sm border border-slate-100">
            <div className="flex justify-center mb-5">
              <img src={logoFull} alt={site.title} className="h-16 w-auto object-contain" />
            </div>
            {data.formEnabled && (
              <form onSubmit={handleSubmit} className="space-y-3">
                <input
                  type="text"
                  placeholder="Seu nome"
                  required
                  value={formState.name}
                  onChange={(e) => setFormState({ ...formState, name: e.target.value })}
                  className="w-full px-3 py-2.5 text-sm rounded border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none"
                />
                <input
                  type="email"
                  placeholder="Seu e-mail"
                  required
                  value={formState.email}
                  onChange={(e) => setFormState({ ...formState, email: e.target.value })}
                  className="w-full px-3 py-2.5 text-sm rounded border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none"
                />
                <input
                  type="tel"
                  placeholder="Seu telefone"
                  value={formState.phone}
                  onChange={(e) => setFormState({ ...formState, phone: e.target.value })}
                  className="w-full px-3 py-2.5 text-sm rounded border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none"
                />
                <textarea
                  placeholder="Sua mensagem"
                  required
                  rows={4}
                  value={formState.message}
                  onChange={(e) => setFormState({ ...formState, message: e.target.value })}
                  className="w-full px-3 py-2.5 text-sm rounded border border-slate-200 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none resize-none"
                />
                <button type="submit" className="btn-green w-full text-sm">
                  {submitted ? 'Abrindo e-mail...' : 'Enviar'}
                </button>
              </form>
            )}
          </div>

          {/* Coluna 3: Mapa */}
          <div className="bg-white rounded-lg overflow-hidden shadow-sm border border-slate-100 min-h-[360px]">
            <iframe
              src={data.mapEmbed}
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
