type IconProps = { className?: string };

function Frame({ children, className }: { children: React.ReactNode; className?: string }) {
  return (
    <svg
      viewBox="0 0 80 64"
      className={className}
      width="80"
      height="64"
      aria-hidden
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
    >
      {children}
    </svg>
  );
}

export function IconAnaliseDocumental({ className }: IconProps) {
  return (
    <Frame className={className}>
      <rect x="22" y="8" width="28" height="38" rx="3" fill="#e8f1f8" stroke="#124a6e" strokeWidth="2" />
      <path d="M28 18h16M28 24h16M28 30h10" stroke="#124a6e" strokeWidth="2" strokeLinecap="round" />
      <circle cx="50" cy="42" r="10" fill="#18a957" stroke="#0f6b36" strokeWidth="1.5" />
      <path d="M45.5 42.2l3 3.1 6.2-6.4" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" />
    </Frame>
  );
}

export function IconJuntaDetran({ className }: IconProps) {
  return (
    <Frame className={className}>
      <rect x="14" y="16" width="44" height="28" rx="4" fill="#124a6e" />
      <rect x="18" y="20" width="14" height="14" rx="7" fill="#d4e4f2" />
      <path d="M36 24h16M36 30h12" stroke="#d4e4f2" strokeWidth="2" strokeLinecap="round" />
      <rect x="46" y="36" width="22" height="14" rx="3" fill="#155a85" stroke="#0b2a4a" strokeWidth="1.2" />
      <text x="57" y="46" textAnchor="middle" fill="#fff" fontSize="8" fontWeight="800" fontFamily="Inter, sans-serif">
        CNH
      </text>
    </Frame>
  );
}

export function IconJuntaSus({ className }: IconProps) {
  return (
    <Frame className={className}>
      <path d="M18 30V18h10V12h14v6h10v12" fill="#124a6e" />
      <rect x="16" y="28" width="48" height="24" fill="#155a85" />
      <rect x="22" y="34" width="8" height="10" fill="#d4e4f2" />
      <rect x="36" y="34" width="8" height="10" fill="#d4e4f2" />
      <rect x="50" y="34" width="8" height="18" fill="#0b2a4a" />
      <circle cx="58" cy="16" r="9" fill="#e11d48" />
      <path d="M58 11v10M53 16h10" stroke="#fff" strokeWidth="2.4" strokeLinecap="round" />
    </Frame>
  );
}

export function IconCotacao({ className }: IconProps) {
  return (
    <Frame className={className}>
      <path
        d="M16 40h6l4-12h22l6 12h6v6H16v-6z"
        fill="#124a6e"
      />
      <path d="M28 28h16l3 8H25l3-8z" fill="#2d7ab5" />
      <circle cx="26" cy="48" r="5" fill="#0b2a4a" stroke="#d4e4f2" strokeWidth="1.5" />
      <circle cx="50" cy="48" r="5" fill="#0b2a4a" stroke="#d4e4f2" strokeWidth="1.5" />
      <circle cx="58" cy="20" r="9" fill="#18a957" stroke="#0f6b36" strokeWidth="1.5" />
      <circle cx="58" cy="20" r="4.2" stroke="#fff" strokeWidth="2" />
      <path d="M61.2 23.4l4.2 4.2" stroke="#fff" strokeWidth="2.2" strokeLinecap="round" />
    </Frame>
  );
}

export function IconReceitaFederal({ className }: IconProps) {
  return (
    <Frame className={className}>
      <path
        d="M40 6l22 12v20c0 12-9.5 18.5-22 22C27.5 56.5 18 50 18 38V18L40 6z"
        fill="#124a6e"
      />
      <path d="M40 12l15 8v16c0 8-6.5 12.5-15 15-8.5-2.5-15-7-15-15V20l15-8z" fill="#155a85" />
      <path d="M40 18v26M28 28h24" stroke="#fff" strokeWidth="2.4" strokeLinecap="round" />
      <circle cx="40" cy="28" r="5" fill="#fff" />
      <text x="40" y="31" textAnchor="middle" fill="#124a6e" fontSize="7" fontWeight="800" fontFamily="Inter, sans-serif">
        RF
      </text>
    </Frame>
  );
}

export function IconSefazMt({ className }: IconProps) {
  return (
    <Frame className={className}>
      <path
        d="M22 14l18-6 16 8 4 16-10 16-18 4-14-10 4-28z"
        fill="#124a6e"
      />
      <path d="M28 20l10-3 10 5 2 11-6 10-11 2-8-6 3-19z" fill="#155a85" />
      <rect x="48" y="30" width="20" height="24" rx="2" fill="#e8f1f8" stroke="#124a6e" strokeWidth="1.6" />
      <text x="58" y="47" textAnchor="middle" fill="#124a6e" fontSize="14" fontWeight="800" fontFamily="Inter, sans-serif">
        %
      </text>
    </Frame>
  );
}

export function IconSefazSp({ className }: IconProps) {
  return (
    <Frame className={className}>
      <path
        d="M18 28c2-10 12-16 24-16 10 0 18 5 22 13 2 5 1 12-4 16-6 5-14 8-24 6-9-2-16-8-18-19z"
        fill="#124a6e"
      />
      <path
        d="M24 30c2-7 9-11 18-11 8 0 14 4 17 10 1 4 0 8-3 11-5 4-11 6-18 4-7-1-12-6-14-14z"
        fill="#155a85"
      />
      <rect x="50" y="32" width="18" height="22" rx="2" fill="#e8f1f8" stroke="#124a6e" strokeWidth="1.6" />
      <path d="M54 39h10M54 44h10M54 49h7" stroke="#124a6e" strokeWidth="1.6" strokeLinecap="round" />
    </Frame>
  );
}

export const ASSESSORIA_ICONS: Record<string, React.ComponentType<IconProps>> = {
  'analise-documental': IconAnaliseDocumental,
  'junta-detran': IconJuntaDetran,
  'junta-sus': IconJuntaSus,
  cotacao: IconCotacao,
  'receita-federal': IconReceitaFederal,
  'sefaz-mt': IconSefazMt,
  'sefaz-sp': IconSefazSp,
};
