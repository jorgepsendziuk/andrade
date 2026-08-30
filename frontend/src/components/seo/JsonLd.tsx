import { useEffect } from 'react';

interface JsonLdProps {
  data: object | object[];
}

export function JsonLd({ data }: JsonLdProps) {
  const json = JSON.stringify(data);

  useEffect(() => {
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.id = 'json-ld-seo';
    script.textContent = json;
    document.head.appendChild(script);

    return () => {
      script.remove();
    };
  }, [json]);

  return null;
}
