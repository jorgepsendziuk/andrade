import { COMPANY, getCompanyLogoDataUri, SEFAZ_BRASAO_URL } from './company.js';
import { fmtMoney, resolveReciboPagamento } from './pagamentos-utils.js';
import type {
  ClientPublic,
  ConductorRecord,
  DocumentTemplateCode,
  PagamentoHonorario,
  ProcessRecord,
  VehicleInfo,
} from './types/process.js';

export interface RenderDocumentOptions {
  pagamentoId?: string;
}

function fmtDate(d: Date | string = new Date()): string {
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    timeZone: 'America/Cuiaba',
  });
}

function fmtCpf(cpf: string): string {
  const n = cpf.replace(/\D/g, '');
  if (n.length !== 11) return cpf;
  return n.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
}

function esc(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function clientAddress(client: ClientPublic): string {
  const parts = [
    client.endereco,
    client.numero,
    client.complemento,
    client.bairro,
    client.cidade,
    client.uf,
    client.cep,
  ].filter(Boolean);
  return parts.join(', ') || '—';
}

function g(client: ClientPublic, forms: { F: string; M: string; N: string }): string {
  if (client.genero === 'F') return forms.F;
  if (client.genero === 'M') return forms.M;
  return forms.N;
}

function stepProtocol(process: ProcessRecord, key: string): string {
  return process.steps.find((s) => s.key === key)?.protocol ?? '—';
}

function vehicleBlock(vehicle?: VehicleInfo): string {
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

function pagamentosTable(pagamentos: PagamentoHonorario[] = []): string {
  if (pagamentos.length === 0) return '<p class="justify no-indent">Parcelas a combinar entre as partes.</p>';
  return `<table class="data-table payments"><thead><tr>
    <th>Nº recibo</th><th>Descrição</th><th>Valor</th><th>Forma</th><th>Data</th><th>Status</th>
  </tr></thead><tbody>${pagamentos
    .map(
      (p) => `<tr>
        <td>${esc(p.numero)}</td>
        <td>${esc(p.descricao ?? '—')}</td>
        <td>R$ ${fmtMoney(p.valor)}</td>
        <td>${esc(p.tipo.toUpperCase())}</td>
        <td>${esc(fmtDate(p.data))}</td>
        <td>${p.status === 'pago' ? 'Pago' : 'Pendente'}</td>
      </tr>`
    )
    .join('')}</tbody></table>`;
}

const PRINT_CSS = `
  @page { size: A4; margin: 1.6cm 2cm 2cm; }
  * { box-sizing: border-box; }
  body {
    font-family: 'Georgia', 'Times New Roman', Times, serif;
    font-size: 11.5pt;
    line-height: 1.55;
    color: #111;
    margin: 0;
    padding: 0;
    background: #f0f4f8;
  }
  .print-toolbar {
    position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    padding: 0.65rem 1.25rem;
    background: linear-gradient(135deg, #0b2a4a 0%, #155a85 100%);
    color: #fff;
    box-shadow: 0 4px 20px rgba(11,42,74,0.35);
  }
  .print-toolbar-title { font-family: system-ui, sans-serif; font-size: 0.95rem; font-weight: 600; }
  .print-toolbar-sub { font-size: 0.75rem; opacity: 0.85; margin-top: 0.1rem; }
  .print-btn {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.65rem 1.4rem; min-height: 44px;
    font-family: system-ui, sans-serif; font-size: 0.95rem; font-weight: 700;
    color: #0b2a4a; background: #fff; border: none; border-radius: 8px;
    cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transition: transform 0.15s, box-shadow 0.15s;
  }
  .print-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,0.2); }
  .print-btn svg { width: 20px; height: 20px; }
  .page { max-width: 18cm; margin: 4.5rem auto 2rem; padding: 2rem 2.2rem; background: #fff; box-shadow: 0 2px 24px rgba(0,0,0,0.08); }
  .letterhead { display: flex; gap: 1rem; align-items: center; padding-bottom: 1rem; border-bottom: 2px solid #155a85; margin-bottom: 1.5rem; }
  .letterhead img { height: 52px; width: auto; }
  .letterhead-text { flex: 1; }
  .letterhead-text .razao { font-size: 10pt; font-weight: 700; color: #0b2a4a; text-transform: uppercase; letter-spacing: 0.02em; }
  .letterhead-text .fantasia { font-size: 13pt; font-weight: 700; color: #155a85; margin: 0.15rem 0; }
  .letterhead-text .meta { font-size: 8.5pt; color: #555; line-height: 1.4; font-family: system-ui, sans-serif; }
  .header-gov { text-align: center; margin-bottom: 1.5rem; }
  .header-gov img { width: 70px; height: auto; margin-bottom: 0.5rem; }
  .header-gov p { font-size: 8pt; color: #666; line-height: 1.45; margin: 0; font-family: Arial, sans-serif; }
  .doc-title { text-align: center; margin: 0 0 0.35rem; font-size: 14pt; font-weight: 700; color: #0b2a4a; letter-spacing: 0.03em; text-transform: uppercase; }
  .doc-subtitle { text-align: center; font-size: 10pt; color: #555; margin: 0 0 1.5rem; font-style: italic; }
  .doc-ref { text-align: right; font-size: 9pt; color: #666; margin-bottom: 1rem; font-family: system-ui, sans-serif; }
  h2.section { font-size: 11pt; color: #155a85; margin: 1.25rem 0 0.5rem; text-transform: uppercase; letter-spacing: 0.04em; }
  .center { text-align: center; }
  .justify { text-align: justify; text-indent: 2em; }
  .no-indent { text-indent: 0; }
  .sig { margin-top: 2.5rem; text-align: center; page-break-inside: avoid; }
  .sig-line { border-top: 1px solid #333; width: 70%; margin: 0 auto 0.4rem; padding-top: 0.25rem; }
  .sig small { font-size: 9pt; color: #555; }
  .data-table { width: 100%; border-collapse: collapse; margin: 0.75rem 0 1rem; font-size: 10pt; }
  .data-table th, .data-table td { border: 1px solid #ccc; padding: 0.35rem 0.5rem; text-align: left; vertical-align: top; }
  .data-table th { background: #eef4fa; color: #0b2a4a; font-weight: 600; width: 32%; }
  .data-table.payments th { width: auto; font-size: 9pt; }
  .data-table.payments td { font-size: 9pt; }
  .footer-doc { margin-top: 2rem; padding-top: 0.75rem; border-top: 1px solid #ddd; text-align: center; font-size: 8.5pt; color: #888; font-family: system-ui, sans-serif; }
  @media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .page { margin: 0; padding: 0; box-shadow: none; max-width: none; }
  }
`;

function printToolbar(title: string, subtitle?: string): string {
  return `<div class="no-print print-toolbar">
    <div>
      <div class="print-toolbar-title">${esc(title)}</div>
      ${subtitle ? `<div class="print-toolbar-sub">${esc(subtitle)}</div>` : ''}
    </div>
    <button type="button" class="print-btn" onclick="window.print()">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Imprimir / Salvar PDF
    </button>
  </div>`;
}

function letterheadCompany(): string {
  const logo = getCompanyLogoDataUri();
  return `<header class="letterhead">
    ${logo ? `<img src="${logo}" alt="${esc(COMPANY.nomeFantasia)}" />` : ''}
    <div class="letterhead-text">
      <div class="razao">${esc(COMPANY.razaoSocial)}</div>
      <div class="fantasia">${esc(COMPANY.nomeFantasia)}</div>
      <div class="meta">CNPJ ${esc(COMPANY.cnpj)}<br>
      ${esc(COMPANY.address)}<br>
      Tel. ${esc(COMPANY.phone)} · ${esc(COMPANY.email)}</div>
    </div>
  </header>`;
}

function letterheadGov(): string {
  return `<div class="header-gov">
    <img src="${SEFAZ_BRASAO_URL}" alt="Brasão do Estado de Mato Grosso" />
    <p>GOVERNO DO ESTADO DE MATO GROSSO<br>
    SECRETARIA DE ESTADO DE FAZENDA<br>
    SECRETARIA ADJUNTA DA RECEITA PÚBLICA<br>
    SUPERINTENDÊNCIA DE INFORMAÇÕES SOBRE OUTRAS RECEITAS</p>
  </div>`;
}

function footerDoc(): string {
  return `<div class="footer-doc">${esc(COMPANY.nomeFantasia)} · ${esc(COMPANY.city)} · CNPJ ${esc(COMPANY.cnpj)}</div>`;
}

function wrapHtml(title: string, body: string, subtitle?: string): string {
  return `<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><title>${esc(title)}</title><style>${PRINT_CSS}</style></head><body>
    ${printToolbar(title, subtitle)}
    <div class="page">${body}${footerDoc()}</div>
  </body></html>`;
}

function modalityLabel(process: ProcessRecord): string {
  return process.modality === 'taxi' ? 'Táxi' : 'PcD';
}

function contractObject(process: ProcessRecord): string {
  if (process.modality === 'taxi') {
    return 'assessoria na obtenção de isenção de ICMS para aquisição de veículo automotor novo destinado à atividade de condutor autônomo de táxi, nos termos do Convênio ICMS 038/2001 e legislação estadual vigente';
  }
  return 'assessoria na obtenção de isenções de IPI, ICMS e IPVA na aquisição de veículo automotor zero km por pessoa com deficiência, nos termos da legislação federal e estadual vigente';
}

export function renderDocument(
  code: DocumentTemplateCode,
  client: ClientPublic,
  process: ProcessRecord,
  conductors: ConductorRecord[] = [],
  options: RenderDocumentOptions = {}
): string {
  const rep = client.representante;
  const cidade = client.cidade || 'Cuiabá';
  const dataDoc = fmtDate(process.updatedAt);
  const honorarios = process.honorarios ?? 0;
  const pagamentos = process.pagamentos ?? [];

  const repClause = rep
    ? `, ${g(client, { F: 'representada', M: 'representado', N: 'representado(a)' })} legalmente por <strong>${esc(rep.nome)}</strong>, CPF ${fmtCpf(rep.cpf)}, RG ${esc(rep.rg)}${rep.rgOrgaoEmissor ? ` ${esc(rep.rgOrgaoEmissor)}` : ''}${rep.rgEstado ? `/${rep.rgEstado}` : ''}`
    : '';

  switch (code) {
    case 'contrato':
      return wrapHtml(
        'Contrato de Prestação de Serviços',
        `${letterheadCompany()}
        <h1 class="doc-title">Contrato de Prestação de Serviços</h1>
        <p class="doc-subtitle">Assessoria em Isenções Tributárias — ${modalityLabel(process)}</p>
        <p class="justify no-indent">Pelo presente instrumento particular, de um lado <strong>${esc(client.name)}</strong>, CPF ${fmtCpf(client.cpf)}, doravante <strong>CONTRATANTE</strong>${repClause}, residente em <strong>${esc(clientAddress(client))}</strong>, e de outro lado <strong>${esc(COMPANY.razaoSocial)}</strong>, CNPJ ${esc(COMPANY.cnpj)}, doravante <strong>CONTRATADA</strong>, com sede em ${esc(COMPANY.address)}, firmam o presente contrato para ${contractObject(process)}.</p>
        <h2 class="section">Cláusula 1 — Do objeto</h2>
        <p class="justify">A CONTRATADA prestará serviços de assessoria administrativa e documental, incluindo protocolo, acompanhamento e orientação junto à Receita Federal (SISEN/IPI), SEFAZ/MT (ICMS/IPVA), DETRAN/MT, Junta Médica e demais órgãos competentes, conforme a modalidade ${modalityLabel(process)}.</p>
        <h2 class="section">Cláusula 2 — Dos honorários</h2>
        <p class="justify no-indent">O valor total dos honorários é de <strong>R$ ${fmtMoney(honorarios)}</strong>, conforme parcelas abaixo:</p>
        ${pagamentosTable(pagamentos)}
        <h2 class="section">Cláusula 3 — Das obrigações do contratante</h2>
        <p class="justify">O CONTRATANTE compromete-se a fornecer documentação verídica e atualizada, comparecer a perícias e exames quando convocado, autorizar acesso aos sistemas Gov.br e MT Cidadão quando necessário, e comunicar alterações de endereço, telefone ou representante legal.</p>
        <h2 class="section">Cláusula 4 — Da proteção de dados (LGPD)</h2>
        <p class="justify">Os dados pessoais e de saúde serão tratados exclusivamente para a finalidade deste contrato, com medidas de segurança adequadas, nos termos da Lei nº 13.709/2018.</p>
        ${process.vehicle ? `<h2 class="section">Dados do veículo</h2>${vehicleBlock(process.vehicle)}` : ''}
        <p class="center" style="margin-top:1.5rem">${esc(cidade)}, ${dataDoc}.</p>
        <div class="sig"><div class="sig-line"></div><strong>CONTRATANTE</strong><br>${esc(client.name)}</div>
        <div class="sig"><div class="sig-line"></div><strong>CONTRATADA</strong><br>${esc(COMPANY.razaoSocial)}<br><small>CNPJ ${esc(COMPANY.cnpj)}</small></div>`,
        `Modalidade ${modalityLabel(process)} · ${esc(client.name)}`
      );

    case 'recibo': {
      const pagamento = resolveReciboPagamento(process, options.pagamentoId);
      const valor = pagamento?.valor ?? honorarios;
      const dataRecibo = pagamento ? fmtDate(pagamento.data) : dataDoc;
      const numero = pagamento?.numero ?? '—';
      const forma = pagamento?.tipo?.toUpperCase() ?? process.pagamentoTipo ?? '—';
      const descricao = pagamento?.descricao ?? `honorários de assessoria em isenções ${modalityLabel(process)}`;

      return wrapHtml(
        `Recibo ${numero}`,
        `${letterheadCompany()}
        <h1 class="doc-title">Recibo de Honorários</h1>
        <p class="doc-subtitle">Prestação de serviços de assessoria em isenções tributárias</p>
        <p class="doc-ref">Nº ${esc(numero)}</p>
        <p class="justify">Recebi de <strong>${esc(client.name)}</strong>, CPF ${fmtCpf(client.cpf)}, a quantia de <strong>R$ ${fmtMoney(valor)}</strong> (${esc(descricao)}), paga por <strong>${esc(forma)}</strong>, referente aos serviços de assessoria para isenção ${modalityLabel(process)} (Junta Médica, IPI, ICMS/IPVA MT e ICMS SP quando aplicável).</p>
        <p class="justify no-indent">Para maior clareza, firmo o presente recibo.</p>
        <p class="center" style="margin-top:1.5rem">${esc(cidade)}, ${dataRecibo}.</p>
        <div class="sig"><div class="sig-line"></div><strong>${esc(COMPANY.razaoSocial)}</strong><br><small>CNPJ ${esc(COMPANY.cnpj)}</small></div>`,
        `${numero} · R$ ${fmtMoney(valor)}`
      );
    }

    case 'icms_pcd':
      return wrapHtml(
        'Pedido de Isenção ICMS PcD',
        `${letterheadGov()}
        <p style="font-size:12pt;margin-bottom:1rem"><strong>Excelentíssimo Senhor Secretário de Estado de Fazenda de Mato Grosso</strong></p>
        <p class="justify"><strong>${esc(client.name)}</strong>, ${g(client, { F: 'portadora', M: 'portador', N: 'titular' })} do RG nº <strong>${esc(client.rg || '—')}</strong>${client.rgDataEmissao ? `, expedido em <strong>${esc(fmtDate(client.rgDataEmissao))}</strong>` : ''}${client.rgOrgaoEmissor ? ` por <strong>${esc(client.rgOrgaoEmissor)}</strong>` : ''}${client.rgEstado ? `/${client.rgEstado}` : ''}, ${g(client, { F: 'inscrita', M: 'inscrito', N: 'inscrito(a)' })} no CPF sob nº <strong>${fmtCpf(client.cpf)}</strong>${rep ? `, ${g(client, { F: 'representada', M: 'representado', N: 'representado(a)' })} legalmente por <strong>${esc(rep.nome)}</strong>, CPF ${fmtCpf(rep.cpf)}, RG ${esc(rep.rg)}${rep.rgOrgaoEmissor ? ` ${esc(rep.rgOrgaoEmissor)}` : ''}${rep.rgEstado ? `/${rep.rgEstado}` : ''}, telefone <strong>${esc(rep.telefone || client.phone || '—')}</strong>` : ''}, residente à <strong>${esc(clientAddress(client))}</strong>, e-mail <strong>${esc(client.email)}</strong>, telefone <strong>${esc(client.phone || '—')}</strong>, vem requerer <strong>ISENÇÃO do ICMS</strong> para aquisição de veículo automotor novo, destinado a pessoa com deficiência, nos termos da legislação estadual vigente, conforme documentos em anexo.</p>
        ${process.vehicle ? `<h2 class="section">Veículo objeto do pedido</h2>${vehicleBlock(process.vehicle)}` : ''}
        <p class="justify">Protocolo SISEN/IPI: <strong>${esc(stepProtocol(process, 'ipi'))}</strong> · Perícia: <strong>${esc(stepProtocol(process, 'pericia'))}</strong></p>
        <p class="justify">Nestes termos, pede deferimento.</p>
        <p class="center" style="margin-top:1.5rem">${esc(cidade)}, ${dataDoc}.</p>
        <div class="sig"><div class="sig-line"></div>Assinatura do beneficiário<br><strong>${esc(client.name)}</strong></div>
        ${rep ? `<div class="sig"><div class="sig-line"></div><small>REPRESENTANTE LEGAL</small><br><strong>${esc(rep.nome)}</strong><br>CPF ${fmtCpf(rep.cpf)}</div>` : ''}`,
        `Protocolo SEFAZ: ${stepProtocol(process, 'icms')}`
      );

    case 'icms_taxi':
      return wrapHtml(
        'Pedido de Isenção ICMS Táxi',
        `${letterheadGov()}
        <p style="font-size:12pt;margin-bottom:1rem"><strong>Excelentíssimo Senhor Secretário de Estado de Fazenda de Mato Grosso</strong></p>
        <p class="justify"><strong>${esc(client.name)}</strong>, CPF <strong>${fmtCpf(client.cpf)}</strong>, condutor autônomo de táxi, ${g(client, { F: 'residente', M: 'residente', N: 'residente' })} em <strong>${esc(clientAddress(client))}</strong>, e-mail <strong>${esc(client.email)}</strong>, telefone <strong>${esc(client.phone || '—')}</strong>, vem requerer <strong>ISENÇÃO do ICMS</strong> para aquisição de veículo automotor novo, nos termos do Convênio ICMS 038/2001 e legislação estadual vigente.</p>
        ${process.vehicle ? `<h2 class="section">Veículo objeto do pedido</h2>${vehicleBlock(process.vehicle)}` : ''}
        <p class="justify">Nestes termos, pede deferimento.</p>
        <p class="center" style="margin-top:1.5rem">${esc(cidade)}, ${dataDoc}.</p>
        <div class="sig"><div class="sig-line"></div>Assinatura do requerente<br><strong>${esc(client.name)}</strong></div>`,
        `Condutor autônomo de táxi`
      );

    case 'decl_financeira':
      return wrapHtml(
        'Declaração de Disponibilidade Financeira',
        `${letterheadCompany()}
        <h1 class="doc-title">Declaração de Disponibilidade Financeira ou Patrimonial</h1>
        <p class="doc-subtitle">RICMS/MT — art. 32, Anexo IV, Decreto nº 2.212/2014</p>
        <p class="justify">Eu, <strong>${esc(client.name)}</strong>, CPF <strong>${fmtCpf(client.cpf)}</strong>${repClause}, declaro, para os devidos fins fiscais, possuir disponibilidade financeira ou patrimonial para a aquisição do veículo objeto do pedido de isenção de ICMS${process.vehicle ? ` (<strong>${esc([process.vehicle.marca, process.vehicle.modelo, process.vehicle.ano].filter(Boolean).join(' '))}</strong>)` : ''}, assumindo integral responsabilidade pela veracidade das informações prestadas.</p>
        <p class="center" style="margin-top:1.5rem">${esc(cidade)}, ${dataDoc}.</p>
        <div class="sig"><div class="sig-line"></div><strong>${esc(client.name)}</strong><br><small>CPF ${fmtCpf(client.cpf)}</small></div>`,
        `Beneficiário: ${client.name}`
      );

    case 'condutor_sp':
      return wrapHtml(
        'Formulário de Condutores Autorizados — SP',
        `${letterheadCompany()}
        <h1 class="doc-title">Identificação de Condutores Autorizados</h1>
        <p class="doc-subtitle">ICMS — Estado de São Paulo</p>
        <p class="justify no-indent">Beneficiário: <strong>${esc(client.name)}</strong>, CPF ${fmtCpf(client.cpf)}.</p>
        <h2 class="section">Condutores autorizados</h2>
        ${conductors.length === 0 ? '<p class="justify no-indent">Nenhum condutor cadastrado.</p>' : `<table class="data-table"><thead><tr><th>#</th><th>Nome</th><th>CPF</th><th>RG</th><th>Endereço</th><th>Telefone</th></tr></thead><tbody>${conductors
          .map(
            (c, i) => `<tr>
              <td>${i + 1}</td>
              <td>${esc(c.nome)}</td>
              <td>${fmtCpf(c.cpf)}</td>
              <td>${esc(c.rg ?? '—')}</td>
              <td>${esc(c.endereco ?? '—')}</td>
              <td>${esc(c.telefone ?? '—')}</td>
            </tr>`
          )
          .join('')}</tbody></table>`}
        <p class="center" style="margin-top:1.5rem">${esc(cidade)}, ${dataDoc}.</p>`,
        `${conductors.length} condutor(es) cadastrado(s)`
      );

    case 'cancel_icms':
      return wrapHtml(
        'Solicitação de Cancelamento de ICMS',
        `${letterheadGov()}
        <h1 class="doc-title" style="font-size:12pt">Solicitação de Cancelamento de Isenção de ICMS</h1>
        <p class="justify no-indent">À Secretaria de Estado de Fazenda de Mato Grosso — SEFAZ/MT</p>
        <p class="justify"><strong>${esc(COMPANY.razaoSocial)}</strong>, CNPJ ${esc(COMPANY.cnpj)}, com sede em ${esc(COMPANY.address)}, na qualidade de procuradora de <strong>${esc(client.name)}</strong>, CPF ${fmtCpf(client.cpf)}, solicita o <strong>CANCELAMENTO</strong> da autorização de isenção de ICMS anteriormente concedida, para fins de atualização do teto e novo pedido de reconhecimento.</p>
        <p class="justify"><strong>Motivo:</strong> atualização de valor/teto do veículo a ser adquirido${process.vehicle ? ` (${esc([process.vehicle.marca, process.vehicle.modelo, process.vehicle.ano].filter(Boolean).join(' '))})` : ''}.</p>
        <p class="center" style="margin-top:1.5rem">${esc(cidade)}, ${dataDoc}.</p>
        <div class="sig"><div class="sig-line"></div><strong>${esc(COMPANY.razaoSocial)}</strong><br><small>CNPJ ${esc(COMPANY.cnpj)} · Procuradora</small></div>`,
        `Cliente: ${client.name}`
      );

    default:
      return wrapHtml('Documento', `${letterheadCompany()}<p>Template não encontrado.</p>`);
  }
}

export const DOCUMENT_TEMPLATES: { code: DocumentTemplateCode; label: string }[] = [
  { code: 'contrato', label: 'Contrato de Serviços' },
  { code: 'recibo', label: 'Recibo de Honorários' },
  { code: 'icms_pcd', label: 'Pedido ICMS PcD' },
  { code: 'icms_taxi', label: 'Pedido ICMS Táxi' },
  { code: 'decl_financeira', label: 'Declaração Financeira' },
  { code: 'condutor_sp', label: 'Formulário Condutor SP' },
  { code: 'cancel_icms', label: 'Cancelamento ICMS' },
];
