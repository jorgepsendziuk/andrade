import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export const COMPANY = {
  razaoSocial: 'ANDRADE CONSULTORIA E ISENCOES LTDA - EPP',
  nomeFantasia: 'Andrade Isenções',
  cnpj: '35.456.484/0001-62',
  address:
    'Av. Fernando Corrêa da Costa, 1899 — Sala 51B — Galeria Itália Center — Jardim das Américas — Cuiabá/MT — CEP 78060-600',
  addressShort:
    'Av. Fernando Corrêa da Costa, 1899 — Jd. das Américas, Galeria Itália Center — Cuiabá/MT — CEP 78060-600',
  phone: '(65) 99984-4212',
  phoneAlt: '(65) 2137-9392',
  email: 'comercial@andradeisencoes.com.br',
  website: 'www.andradeisencoes.com.br',
  city: 'Cuiabá/MT',
  responsavelNome: 'Luiz Carlos Ferreira de Andrade Junior',
  responsavelCpf: '270.763.838-29',
  responsavelRg: '2577647-7 SSP/MT',
} as const;

let cachedLogoDataUri: string | undefined;
let cachedLogoFullDataUri: string | undefined;
let cachedBrasaoDataUri: string | undefined;

function readImageDataUri(candidates: string[], mime: string): string {
  for (const imagePath of candidates) {
    if (fs.existsSync(imagePath)) {
      const b64 = fs.readFileSync(imagePath).toString('base64');
      return `data:${mime};base64,${b64}`;
    }
  }
  return '';
}

export function getCompanyLogoDataUri(): string {
  if (cachedLogoDataUri !== undefined) return cachedLogoDataUri;
  cachedLogoDataUri = readImageDataUri(
    [
      path.join(__dirname, '../../frontend/public/assets/logo/logo-header.png'),
      path.join(__dirname, '../public/assets/logo/logo-header.png'),
    ],
    'image/png'
  );
  return cachedLogoDataUri;
}

export function getCompanyLogoFullDataUri(): string {
  if (cachedLogoFullDataUri !== undefined) return cachedLogoFullDataUri;
  cachedLogoFullDataUri = readImageDataUri(
    [
      path.join(__dirname, '../assets/logo/logo-full.png'),
      path.join(__dirname, '../../frontend/public/assets/logo/logo-full.png'),
      path.join(__dirname, '../public/assets/logo/logo-full.png'),
      path.join(__dirname, '../../frontend/public/assets/logo/logo-header.png'),
    ],
    'image/png'
  );
  return cachedLogoFullDataUri;
}

export function getBrasaoMtDataUri(): string {
  if (cachedBrasaoDataUri !== undefined) return cachedBrasaoDataUri;
  cachedBrasaoDataUri = readImageDataUri(
    [
      path.join(__dirname, 'assets/brasao-mt.png'),
      path.join(__dirname, '../assets/brasao-mt.png'),
      path.join(__dirname, '../../frontend/public/assets/brasao-mt.png'),
    ],
    'image/png'
  );
  return cachedBrasaoDataUri;
}
