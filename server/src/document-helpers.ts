import type { ClientPublic, LegalRepresentative, PagamentoHonorario, ProcessRecord, VehicleInfo } from './types/process.js';

export function esc(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

export function fmtDate(d: Date | string = new Date()): string {
  const date = typeof d === 'string' ? new Date(d.includes('T') ? d : `${d}T12:00:00`) : d;
  return date.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    timeZone: 'America/Cuiaba',
  });
}

export function fmtDateShort(d: Date | string = new Date()): string {
  const date = typeof d === 'string' ? new Date(d.includes('T') ? d : `${d}T12:00:00`) : d;
  return date.toLocaleDateString('pt-BR', { timeZone: 'America/Cuiaba' });
}

export function fmtCpf(cpf: string): string {
  const n = cpf.replace(/\D/g, '');
  if (n.length !== 11) return cpf;
  return n.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

export function fmtCnpj(cnpj: string): string {
  const n = cnpj.replace(/\D/g, '');
  if (n.length !== 14) return cnpj;
  return n.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
}

export function fmtMoney(value: number): string {
  return value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const UNIDADES = ['', 'um', 'dois', 'três', 'quatro', 'cinco', 'seis', 'sete', 'oito', 'nove'];
const DEZ_A_DEZENOVE = [
  'dez', 'onze', 'doze', 'treze', 'quatorze', 'quinze', 'dezesseis', 'dezessete', 'dezoito', 'dezenove',
];
const DEZENAS = ['', '', 'vinte', 'trinta', 'quarenta', 'cinquenta', 'sessenta', 'setenta', 'oitenta', 'noventa'];
const CENTENAS = ['', 'cento', 'duzentos', 'trezentos', 'quatrocentos', 'quinhentos', 'seiscentos', 'setecentos', 'oitocentos', 'novecentos'];

function extensoAte999(n: number): string {
  if (n === 0) return '';
  if (n === 100) return 'cem';
  const c = Math.floor(n / 100);
  const r = n % 100;
  const parts: string[] = [];
  if (c) parts.push(CENTENAS[c]);
  if (r >= 10 && r < 20) parts.push(DEZ_A_DEZENOVE[r - 10]);
  else {
    const d = Math.floor(r / 10);
    const u = r % 10;
    if (d) parts.push(DEZENAS[d]);
    if (u) parts.push(UNIDADES[u]);
  }
  return parts.join(' e ');
}

export function valorPorExtenso(valor: number): string {
  const inteiro = Math.floor(valor);
  const centavos = Math.round((valor - inteiro) * 100);
  if (inteiro === 0 && centavos === 0) return 'zero reais';

  const partes: string[] = [];
  const milhoes = Math.floor(inteiro / 1_000_000);
  const mil = Math.floor((inteiro % 1_000_000) / 1000);
  const resto = inteiro % 1000;

  if (milhoes) partes.push(`${extensoAte999(milhoes)} ${milhoes === 1 ? 'milhão' : 'milhões'}`);
  if (mil) partes.push(mil === 1 ? 'mil' : `${extensoAte999(mil)} mil`);
  if (resto) partes.push(extensoAte999(resto));

  let texto = `${partes.join(' e ')} ${inteiro === 1 ? 'real' : 'reais'}`;
  if (centavos > 0) {
    texto += ` e ${extensoAte999(centavos)} ${centavos === 1 ? 'centavo' : 'centavos'}`;
  }
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

export function g(client: ClientPublic, forms: { F: string; M: string; N: string }): string {
  if (client.genero === 'F') return forms.F;
  if (client.genero === 'M') return forms.M;
  return forms.N;
}

export function clientAddressLine(client: ClientPublic): string {
  const parts = [
    client.endereco,
    client.numero,
    client.complemento,
    client.bairro ? `bairro ${client.bairro}` : undefined,
    client.cidade ? `cidade de ${client.cidade}` : undefined,
    client.uf ? `estado de ${client.uf}` : undefined,
    client.cep ? `CEP ${client.cep}` : undefined,
  ].filter(Boolean);
  return parts.join(', ') || '—';
}

export function clientPartyBlock(client: ClientPublic): string {
  const rgPart = [
    client.rg ? `RG: ${client.rg}` : undefined,
    client.rgOrgaoEmissor ? client.rgOrgaoEmissor : undefined,
    client.rgEstado ? client.rgEstado : undefined,
  ]
    .filter(Boolean)
    .join(' ');

  return `<strong>${esc(client.name)}</strong>${rgPart ? `, ${esc(rgPart)}` : ''}, inscrit${g(client, { F: 'a', M: 'o', N: 'o(a)' })} no CPF sob nº <strong>${fmtCpf(client.cpf)}</strong>, residente e domiciliad${g(client, { F: 'a', M: 'o', N: 'o(a)' })} à <strong>${esc(clientAddressLine(client))}</strong>, e-mail <strong>${esc(client.email)}</strong>, telefone nº <strong>${esc(client.phone || '—')}</strong>`;
}

export function repInline(rep: LegalRepresentative): string {
  return `representante legal <strong>${esc(rep.nome)}</strong>, RG ${esc(rep.rg)}${rep.rgOrgaoEmissor ? ` ${esc(rep.rgOrgaoEmissor)}` : ''}${rep.rgEstado ? `/${rep.rgEstado}` : ''}, CPF ${fmtCpf(rep.cpf)}, telefone <strong>${esc(rep.telefone || '—')}</strong>`;
}

export function stepProtocol(process: ProcessRecord, key: string): string {
  const direct = process.steps.find((s) => s.key === key)?.protocol;
  if (direct) return direct;
  if (key === 'icms' || key === 'ipva') {
    return process.steps.find((s) => s.key === 'sefaz_mt')?.protocol ?? '—';
  }
  return '—';
}

export function vehicleBlock(vehicle?: VehicleInfo): string {
  if (!vehicle) return '';
  const rows = [
    ['Marca / Modelo', `${vehicle.marca ?? '—'} ${vehicle.modelo ?? ''}`.trim()],
    ['Ano / Potência', `${vehicle.ano ?? '—'}${vehicle.potencia ? ` · ${vehicle.potencia}` : ''}`],
    ['Placa', vehicle.placa ?? '—'],
    ['Chassi', vehicle.chassi ?? '—'],
    ['RENAVAM', vehicle.renavam ?? '—'],
    ['Concessionária', vehicle.concessionaria ?? '—'],
    ['CNPJ / IE', `${vehicle.concessionariaCnpj ?? '—'}${vehicle.concessionariaIe ? ` · IE ${vehicle.concessionariaIe}` : ''}`],
  ];
  return `<table class="data-table"><tbody>${rows
    .map(([label, value]) => `<tr><th>${esc(label)}</th><td>${esc(value)}</td></tr>`)
    .join('')}</tbody></table>`;
}

export function pagamentosTable(pagamentos: PagamentoHonorario[] = []): string {
  if (pagamentos.length === 0) return '';
  return `<table class="data-table payments"><thead><tr>
    <th>Nº recibo</th><th>Descrição</th><th>Valor</th><th>Forma</th><th>Data</th><th>Status</th>
  </tr></thead><tbody>${pagamentos
    .map(
      (p) => `<tr>
        <td>${esc(p.numero)}</td>
        <td>${esc(p.descricao ?? '—')}</td>
        <td>R$ ${fmtMoney(p.valor)}</td>
        <td>${esc(p.tipo.toUpperCase())}</td>
        <td>${esc(fmtDateShort(p.data))}</td>
        <td>${p.status === 'pago' ? 'Pago' : 'Pendente'}</td>
      </tr>`
    )
    .join('')}</tbody></table>`;
}

export const IPVA_MANIFESTACAO = `
<h2 class="section">Da manifestação expressa para fins de isenção do IPVA</h2>
<p class="justify">Declaro, de forma expressa e inequívoca, meu interesse no reconhecimento posterior da isenção do IPVA referente ao veículo a ser adquirido com base na isenção do ICMS ora requerida, nos termos da legislação vigente e atendidos todos os requisitos legais. Declaro ainda tratar-se de pedido único desta natureza e que o veículo será utilizado exclusivamente para as finalidades essenciais previstas nos artigos 2º e 5º da Portaria nº 125/2020.</p>
<p class="justify">Declaro, para os devidos fins, que não há, neste momento, interesse na formalização do pedido de reconhecimento da isenção do Imposto sobre a Propriedade de Veículos Automotores (IPVA).</p>
<p class="justify">Ressalta-se que, caso não haja a devida seleção das opções disponibilizadas neste formulário/pedido, será presumido, para todos os efeitos legais, que o interessado não possui interesse na formalização do requerimento de isenção do Imposto sobre a Propriedade de Veículos Automotores (IPVA).</p>`;
