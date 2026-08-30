import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export const COMPANY = {
  razaoSocial: 'ANDRADE CONSULTORIA E ISENCOES LTDA - EPP',
  nomeFantasia: 'Andrade Isenções',
  cnpj: '35.456.484/0001-62',
  address:
    'Av. Fernando Corrêa da Costa, 1899 — Jd. das Américas, Galeria Itália Center — Cuiabá/MT — CEP 78060-600',
  phone: '(65) 99984-4212',
  email: 'comercial@andradeisencoes.com.br',
  website: 'www.andradeisencoes.com.br',
  city: 'Cuiabá/MT',
} as const;

export const SEFAZ_BRASAO_URL = 'https://andradeisencoes.com.br/sistema/brasao_mt.jpg';

let cachedLogoDataUri: string | undefined;

export function getCompanyLogoDataUri(): string {
  if (cachedLogoDataUri !== undefined) return cachedLogoDataUri;

  const candidates = [
    path.join(__dirname, '../../frontend/public/assets/logo/logo-header.png'),
    path.join(__dirname, '../public/assets/logo/logo-header.png'),
  ];

  for (const logoPath of candidates) {
    if (fs.existsSync(logoPath)) {
      const b64 = fs.readFileSync(logoPath).toString('base64');
      cachedLogoDataUri = `data:image/png;base64,${b64}`;
      return cachedLogoDataUri;
    }
  }

  cachedLogoDataUri = '';
  return cachedLogoDataUri;
}
