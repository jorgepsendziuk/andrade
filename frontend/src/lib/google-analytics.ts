const CONSENT_KEY = 'andrade_cookie_consent';
const GA_ID = import.meta.env.VITE_GA_MEASUREMENT_ID as string | undefined;
const ADS_ID = import.meta.env.VITE_GOOGLE_ADS_ID as string | undefined;
const CONVERSION_LABEL = import.meta.env.VITE_GOOGLE_ADS_CONVERSION_LABEL as string | undefined;

type GtagFn = (...args: unknown[]) => void;

let tagsLoaded = false;

function getGtag(): GtagFn | null {
  if (typeof window === 'undefined' || !window.dataLayer) return null;
  return (...args: unknown[]) => {
    window.dataLayer!.push(args);
  };
}

export function hasAnalyticsConsent(): boolean {
  if (typeof window === 'undefined') return false;
  return localStorage.getItem(CONSENT_KEY) === 'analytics';
}

/** Carrega GA4 + Google Ads após consentimento de cookies. */
export function loadGoogleTags(): void {
  if (tagsLoaded || typeof window === 'undefined') return;

  const primaryId = GA_ID || ADS_ID;
  if (!primaryId) return;

  const script = document.createElement('script');
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtag/js?id=${primaryId}`;
  document.head.appendChild(script);

  window.dataLayer = window.dataLayer || [];
  const gtag: GtagFn = (...args) => {
    window.dataLayer!.push(args);
  };
  window.gtag = gtag;

  gtag('js', new Date());
  if (GA_ID) gtag('config', GA_ID);
  if (ADS_ID) gtag('config', ADS_ID);

  tagsLoaded = true;
}

function openUrl(url: string) {
  if (url.startsWith('http')) {
    window.open(url, '_blank', 'noopener,noreferrer');
    return;
  }
  window.location.href = url;
}

/** Dispara conversão Google Ads ("Compra"/lead). */
export function reportGoogleAdsConversion(url?: string): void {
  if (!hasAnalyticsConsent() || !ADS_ID || !CONVERSION_LABEL) {
    if (url) openUrl(url);
    return;
  }

  if (!tagsLoaded) loadGoogleTags();

  const gtag = getGtag();
  if (!gtag) {
    if (url) openUrl(url);
    return;
  }

  let navigated = false;
  const navigate = () => {
    if (!url || navigated) return;
    navigated = true;
    openUrl(url);
  };

  gtag('event', 'conversion', {
    send_to: `${ADS_ID}/${CONVERSION_LABEL}`,
    event_callback: navigate,
  });

  if (url) {
    window.setTimeout(navigate, 1000);
  }
}

export function trackWhatsAppClick(url: string): void {
  reportGoogleAdsConversion(url);

  const gtag = getGtag();
  if (gtag && GA_ID) {
    gtag('event', 'whatsapp_click', { event_category: 'engagement' });
  }
}

export function trackContactFormConversion(): void {
  reportGoogleAdsConversion();

  const gtag = getGtag();
  if (gtag && GA_ID) {
    gtag('event', 'generate_lead', { event_category: 'engagement' });
  }
}
