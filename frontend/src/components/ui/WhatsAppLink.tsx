import { MessageCircle } from 'lucide-react';
import { useCms } from '../../context/CmsContext';
import { trackWhatsAppClick } from '../../lib/google-analytics';

interface WhatsAppLinkProps {
  message?: string;
  className?: string;
  children: React.ReactNode;
}

export function WhatsAppLink({ message, className = 'btn-primary', children }: WhatsAppLinkProps) {
  const { content } = useCms();
  const phone = content?.site.whatsapp ?? '5565999844212';
  const href = `https://wa.me/${phone}${message ? `?text=${encodeURIComponent(message)}` : ''}`;

  return (
    <a
      href={href}
      target="_blank"
      rel="noopener noreferrer"
      className={className}
      onClick={(e) => {
        e.preventDefault();
        trackWhatsAppClick(href);
      }}
    >
      <MessageCircle size={18} />
      {children}
    </a>
  );
}
