const BRAND_LOGO_MAP: Record<string, string> = {
  volkswagen: '/assets/brands/volkswagen.svg',
  chevrolet: '/assets/brands/chevrolet.svg',
  fiat: '/assets/brands/fiat.svg',
  jeep: '/assets/brands/jeep.svg',
  toyota: '/assets/brands/toyota.svg',
  hyundai: '/assets/brands/hyundai.svg',
  renault: '/assets/brands/renault.svg',
  ford: '/assets/brands/ford.svg',
};

export function getBrandLogo(name: string): string | undefined {
  const key = name
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, '');
  return BRAND_LOGO_MAP[key];
}
