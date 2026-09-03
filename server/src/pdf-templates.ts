import { COMPANY, getBrasaoMtDataUri, getCompanyLogoFullDataUri } from './company.js';
import {
  clientAddressLine,
  clientPartyBlock,
  esc,
  fmtCpf,
  fmtDate,
  fmtDateShort,
  fmtMoney,
  g,
  IPVA_MANIFESTACAO,
  pagamentosTable,
  repInline,
  stepProtocol,
  valorPorExtenso,
  vehicleBlock,
} from './document-helpers.js';
import { resolveReciboPagamento } from './pagamentos-utils.js';
import type {
  ClientPublic,
  ConductorRecord,
  DocumentTemplateCode,
  ProcessRecord,
} from './types/process.js';

export interface RenderDocumentOptions {
  pagamentoId?: string;
}

const PRINT_CSS = `
  @page { size: A4; margin: 1.6cm 2cm 2cm; }
  * { box-sizing: border-box; }
  body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 11.5pt;
    line-height: 1.5;
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
  }
  .print-btn svg { width: 20px; height: 20px; }
  .page { max-width: 18cm; margin: 4.5rem auto 2rem; padding: 2rem 2.2rem; background: #fff; box-shadow: 0 2px 24px rgba(0,0,0,0.08); }
  .letterhead {
    display: flex; gap: 1.25rem; align-items: center;
    padding: 1rem 1.15rem; margin-bottom: 1.35rem;
    border: 1px solid #c5d9eb; border-radius: 12px;
    background: linear-gradient(135deg, #f8fbff 0%, #eef5fb 55%, #e8f0f8 100%);
    box-shadow: 0 1px 0 rgba(255,255,255,0.8) inset;
  }
  .letterhead-logo { flex-shrink: 0; padding: 0.25rem 0.5rem 0.25rem 0; }
  .letterhead-logo img { height: 72px; width: auto; max-width: 240px; object-fit: contain; display: block; filter: drop-shadow(0 1px 2px rgba(0,0,0,0.08)); }
  .letterhead-text { flex: 1; min-width: 0; }
  .letterhead-text .razao { font-size: 9pt; font-weight: 700; color: #0b2a4a; text-transform: uppercase; letter-spacing: 0.03em; line-height: 1.3; }
  .letterhead-text .fantasia { font-size: 12pt; font-weight: 700; color: #155a85; margin: 0.1rem 0 0.25rem; }
  .letterhead-text .meta { font-size: 8.5pt; color: #555; line-height: 1.45; font-family: Arial, sans-serif; }
  .header-gov { text-align: center; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid #ddd; }
  .header-gov img { width: 83px; height: auto; margin-bottom: 0.45rem; }
  .header-gov p { font-size: 8pt; color: #666; line-height: 1.45; margin: 0; font-family: Arial, sans-serif; }
  .doc-title { text-align: center; margin: 0 0 0.35rem; font-size: 13.5pt; font-weight: 700; color: #0b2a4a; letter-spacing: 0.02em; text-transform: uppercase; }
  .doc-subtitle { text-align: center; font-size: 10pt; color: #555; margin: 0 0 1.25rem; font-style: italic; }
  .doc-ref { text-align: right; font-size: 9pt; color: #666; margin-bottom: 1rem; font-family: Arial, sans-serif; }
  h2.section { font-size: 11pt; color: #0b2a4a; margin: 1.1rem 0 0.45rem; font-weight: 700; }
  .clause { margin: 0.65rem 0; }
  .clause-title { font-weight: 700; margin-bottom: 0.25rem; }
  .clause ol, .clause ul { margin: 0.35rem 0 0.35rem 1.5rem; padding: 0; }
  .clause li { margin-bottom: 0.25rem; text-align: justify; }
  .center { text-align: center; }
  .justify { text-align: justify; text-indent: 2em; }
  .no-indent { text-indent: 0; }
  .party-block { margin: 0.75rem 0; padding: 0.65rem 0.75rem; background: #f8fafc; border-left: 3px solid #155a85; font-size: 10.5pt; line-height: 1.45; }
  .party-label { font-weight: 700; color: #155a85; font-size: 9pt; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.2rem; }
  .sig { margin-top: 2.25rem; text-align: center; page-break-inside: avoid; }
  .sig-line { border-top: 1px solid #333; width: 72%; margin: 0 auto 0.35rem; padding-top: 0.25rem; }
  .sig small { font-size: 9pt; color: #555; }
  .data-table { width: 100%; border-collapse: collapse; margin: 0.65rem 0 0.85rem; font-size: 10pt; }
  .data-table th, .data-table td { border: 1px solid #ccc; padding: 0.35rem 0.5rem; text-align: left; vertical-align: top; }
  .data-table th { background: #eef4fa; color: #0b2a4a; font-weight: 600; width: 30%; }
  .data-table.payments th { width: auto; font-size: 9pt; }
  .data-table.payments td { font-size: 9pt; }
  .condutor-block { border: 1px solid #ddd; padding: 0.75rem; margin: 0.75rem 0; page-break-inside: avoid; }
  .condutor-block h3 { font-size: 10.5pt; margin: 0 0 0.5rem; color: #155a85; }
  .footer-doc { margin-top: 1.75rem; padding-top: 0.65rem; border-top: 1px solid #ddd; text-align: center; font-size: 8.5pt; color: #888; font-family: Arial, sans-serif; }
  @media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .page { margin: 0; padding: 0; box-shadow: none; max-width: none; }
    .letterhead { border: none; background: none; padding: 0; }
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
  const logo = getCompanyLogoFullDataUri();
  return `<header class="letterhead">
    <div class="letterhead-logo">
      ${logo ? `<img src="${logo}" alt="${esc(COMPANY.nomeFantasia)}" />` : `<strong style="font-size:14pt;color:#155a85">${esc(COMPANY.nomeFantasia)}</strong>`}
    </div>
    <div class="letterhead-text">
      <div class="razao">${esc(COMPANY.razaoSocial)}</div>
      <div class="fantasia">${esc(COMPANY.nomeFantasia)}</div>
      <div class="meta">CNPJ ${esc(COMPANY.cnpj)}<br>
      ${esc(COMPANY.address)}<br>
      Tel. ${esc(COMPANY.phone)} / ${esc(COMPANY.phoneAlt)} · ${esc(COMPANY.email)}</div>
    </div>
  </header>`;
}

function letterheadGov(includeIpva = false): string {
  const brasao = getBrasaoMtDataUri();
  return `<div class="header-gov">
    ${brasao ? `<img src="${brasao}" alt="Brasão do Estado de Mato Grosso" />` : ''}
    <p>GOVERNO DO ESTADO DE MATO GROSSO<br>
    SECRETARIA DE ESTADO DE FAZENDA<br>
    SECRETARIA ADJUNTA DA RECEITA PÚBLICA<br>
    SUPERINTENDÊNCIA DE INFORMAÇÕES SOBRE OUTRAS RECEITAS${includeIpva ? '<br>GERÊNCIA DE INFORMAÇÕES DO IPVA' : ''}</p>
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

function contratadoBlock(): string {
  return `<div class="party-block">
    <div class="party-label">Contratado(a)</div>
    <strong>${esc(COMPANY.responsavelNome)}</strong>, portador da Cédula de Identidade ${esc(COMPANY.responsavelRg)}, inscrito no CPF sob nº ${fmtCpf(COMPANY.responsavelCpf)}, proprietário da <strong>${esc(COMPANY.razaoSocial)}</strong>, inscrita no CNPJ ${esc(COMPANY.cnpj)}, com sede na ${esc(COMPANY.address)}, telefone ${esc(COMPANY.phone)} / ${esc(COMPANY.phoneAlt)}, endereço eletrônico: ${esc(COMPANY.email)}.
  </div>`;
}

function renderContrato(client: ClientPublic, process: ProcessRecord): string {
  const cidade = client.cidade || 'Cuiabá';
  const dataDoc = fmtDate(process.updatedAt);
  const honorarios = process.honorarios ?? 0;
  const pagamentos = process.pagamentos ?? [];
  const rep = client.representante;

  return wrapHtml(
    'Contrato de Prestação de Serviços',
    `${letterheadCompany()}
    <h1 class="doc-title">Contrato de Prestação de Serviços</h1>
    <div class="party-block">
      <div class="party-label">Contratante</div>
      ${clientPartyBlock(client)}${rep ? `, ${repInline(rep)}` : ''}.
    </div>
    ${contratadoBlock()}
    <p class="justify no-indent">Pelo presente instrumento particular de Contrato de Prestação de Serviços, as partes têm entre si justo e contratado o que segue:</p>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 1 – OBJETO</p>
      <p class="justify no-indent">O presente contrato tem por objeto a prestação de serviços de assessoria administrativa para condução, protocolo e acompanhamento de processos de isenção tributária, compreendendo:</p>
      <ol type="I">
        <li>Junta Médica Especial e IPI, junto à Receita Federal do Brasil;</li>
        <li>ICMS e IPVA, junto à Secretaria de Fazenda do Estado de Mato Grosso, no respectivo ano do fato gerador, conforme legislação vigente;</li>
        <li>ICMS SP, junto à Secretaria da Fazenda do Estado de São Paulo, quando necessário.</li>
      </ol>
      <p class="justify no-indent"><strong>Parágrafo único.</strong> Os serviços serão executados pelo CONTRATADO, que declara possuir conhecimento técnico e experiência compatíveis com a prestação dos serviços de assessoria administrativa objeto deste contrato.</p>
    </div>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 2 – LOCAL DA EXECUÇÃO</p>
      <p class="justify no-indent">Os serviços serão executados na sede da empresa situada na ${esc(COMPANY.address)}, telefone ${esc(COMPANY.phone)}.</p>
    </div>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 3 – HONORÁRIOS</p>
      <p class="justify no-indent">O CONTRATANTE pagará ao CONTRATADO o valor de <strong>R$ ${fmtMoney(honorarios)} (${valorPorExtenso(honorarios)})</strong>.</p>
      ${pagamentos.length ? pagamentosTable(pagamentos) : ''}
      <p class="justify no-indent"><strong>§2º</strong> O pagamento será realizado no dia da perícia, podendo ser no PIX ou em até 4x sem juros ou de 5x a 10x, com as taxas de parcelamento da máquina de cartão de responsabilidade do cliente. Caso o pedido seja aprovado, os honorários serão cobrados. Em caso de reprovação, não haverá cobrança de honorários.</p>
      <p class="justify no-indent"><strong>§3º</strong> O presente contrato somente entrará em vigor após a aprovação da perícia pelo órgão competente. Em caso de reprovação do pedido, o contrato será considerado sem efeito.</p>
    </div>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 4 – OBRIGAÇÕES DO CONTRATANTE</p>
      <p class="justify no-indent">O CONTRATANTE compromete-se a:</p>
      <ol type="I">
        <li>Fornecer informações verdadeiras, completas e atualizadas;</li>
        <li>Entregar documentação autêntica e válida;</li>
        <li>Informar eventuais débitos nas esferas federal, estadual e municipal;</li>
        <li>Responsabilizar-se integralmente por informações falsas, inexatas ou omitidas.</li>
      </ol>
      <p class="justify no-indent"><strong>Parágrafo único.</strong> O CONTRATADO não se responsabiliza por prejuízos decorrentes de documentos falsos ou informações inverídicas fornecidas pelo CONTRATANTE.</p>
    </div>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 5 – TAXAS E ENCARGOS DO DETRAN</p>
      <p class="justify no-indent">Fica expressamente acordado que todas as taxas, tarifas, emolumentos e custos cobrados pelo DETRAN, bem como por quaisquer órgãos públicos relacionados à emissão de documentos ou quaisquer outros encargos incidentes sobre o processo, são de inteira e exclusiva responsabilidade do CLIENTE, incluindo taxa de abertura, emissão e foto, e taxa das perícias médicas.</p>
    </div>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 6 – OBRIGAÇÕES DO CONTRATADO</p>
      <p class="justify no-indent">O CONTRATADO compromete-se a:</p>
      <ol type="I">
        <li>Executar os serviços com ética, diligência e observância da legislação aplicável;</li>
        <li>Manter sigilo sobre informações e documentos do CONTRATANTE;</li>
        <li>Fornecer recibo ou comprovante referente ao pagamento dos honorários.</li>
      </ol>
    </div>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 7 – DECLARAÇÃO SOBRE BPC</p>
      <p class="justify no-indent">O CONTRATANTE declara estar ciente das regras do BPC – Benefício de Prestação Continuada (Lei nº 8.742/1993), declarando que não recebe o referido benefício, assumindo total responsabilidade pelas informações prestadas.</p>
    </div>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 8 – DO ACESSO AO MT CIDADÃO</p>
      <p class="justify no-indent">O CONTRATANTE autoriza o fornecimento de seus dados de acesso ao aplicativo MT Cidadão (login e senha), necessário para a abertura do processo objeto deste contrato.</p>
      <p class="justify">A CONTRATADA compromete-se a utilizar tais informações exclusivamente para a execução dos serviços contratados, mantendo absoluto sigilo e confidencialidade dos dados.</p>
      <p class="justify">Caso o CONTRATANTE opte por não fornecer seus dados de acesso, fica estabelecido que deverá comparecer presencialmente à empresa para a realização dos procedimentos necessários à abertura do processo.</p>
    </div>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 9 – GOV.BR E ACESSO AO SISTEMA SISEN</p>
      <p class="justify no-indent">Para fins de protocolo junto à Receita Federal do Brasil, será necessária a utilização da conta Gov.br do CONTRATANTE para acesso ao Sistema SISEN.</p>
      <p class="justify no-indent"><strong>§1º</strong> O CONTRATANTE declara estar ciente de que o acesso ao Sistema SISEN exige autenticação por meio de sua conta Gov.br, que será acessada duas vezes: uma no momento do protocolo e outra após sete dias para retirada do resultado.</p>
      <p class="justify no-indent"><strong>§2º</strong> O CONTRATANTE não é obrigado a fornecer sua senha ao CONTRATADO, podendo, caso prefira, comparecer presencialmente à sede da empresa para realizar pessoalmente a digitação da senha para efetivação do protocolo.</p>
      <p class="justify no-indent"><strong>§3º</strong> Caso o CONTRATANTE opte por fornecer sua senha para fins exclusivos de protocolo e acompanhamento do processo objeto deste contrato, o faz por sua livre e espontânea vontade.</p>
      <p class="justify no-indent"><strong>§4º</strong> O CONTRATADO compromete-se a utilizar o acesso exclusivamente para a finalidade contratada, observando integralmente a Lei nº 13.709/2018 (Lei Geral de Proteção de Dados – LGPD).</p>
      <p class="justify no-indent"><strong>§5º</strong> A responsabilidade pelas informações constantes na conta Gov.br é exclusiva do CONTRATANTE.</p>
    </div>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 10 – VIGÊNCIA</p>
      <p class="justify no-indent">A vigência do contrato encerra-se quando forem executadas todas as etapas do processo de isenções contratadas e mencionadas no objeto deste Contrato.</p>
    </div>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 11 – RESCISÃO</p>
      <p class="justify no-indent">O contrato poderá ser rescindido:</p>
      <ol type="I">
        <li>Por comum acordo entre as partes, não incidindo quaisquer ônus, encargos ou penalidades, ressalvado o cumprimento das obrigações contratuais ainda pendentes;</li>
        <li>Por impossibilidade superveniente de continuidade do processo;</li>
        <li>Por descumprimento contratual. Em caso de desistência por parte do CONTRATANTE após o início da prestação dos serviços, será devido o valor integral contratado.</li>
      </ol>
      <p class="justify no-indent">A parte que desejar rescindir deverá comunicar a outra por escrito com antecedência mínima de 15 (quinze) dias.</p>
    </div>

    <div class="clause">
      <p class="clause-title">CLÁUSULA 12 – LGPD</p>
      <p class="justify no-indent">A Lei Geral de Proteção de Dados (“LGPD”) dispõe que quaisquer dados de terceiros e/ou informações pessoais que possam ser obtidas ou utilizadas por qualquer das partes em decorrência do presente Contrato (“Dados”) serão recolhidos, utilizados, armazenados e mantidos de acordo com os padrões geralmente aceitos para coleta de dados, pela legislação aplicável, qual seja a Lei 13.709/2018.</p>
    </div>

    ${process.vehicle ? `<div class="clause"><p class="clause-title">ANEXO – DADOS DO VEÍCULO</p>${vehicleBlock(process.vehicle)}</div>` : ''}

    <p class="center" style="margin-top:1.5rem">${esc(cidade)}, ${dataDoc}.</p>
    <div class="sig"><div class="sig-line"></div><strong>CONTRATANTE</strong><br>${esc(client.name)}</div>
    <div class="sig"><div class="sig-line"></div><strong>CONTRATADO</strong><br>${esc(COMPANY.responsavelNome)}<br><small>${esc(COMPANY.razaoSocial)} · CNPJ ${esc(COMPANY.cnpj)}</small></div>`,
    `${esc(client.name)}`
  );
}

function renderRecibo(
  client: ClientPublic,
  process: ProcessRecord,
  options: RenderDocumentOptions
): string {
  const pagamento = resolveReciboPagamento(process, options.pagamentoId);
  const honorarios = process.honorarios ?? 0;
  const valor = pagamento?.valor ?? honorarios;
  const dataRecibo = pagamento ? fmtDate(pagamento.data) : fmtDate(process.updatedAt);
  const numero = pagamento?.numero ?? '—';
  const cidade = client.cidade || 'Cuiabá';

  return wrapHtml(
    `Recibo ${numero}`,
    `${letterheadCompany()}
    <h1 class="doc-title">Recibo de Prestação de Serviços</h1>
    <p class="doc-ref">RECIBO Nº ${esc(numero)}</p>
    <p class="justify no-indent">Recebemos de ${clientPartyBlock(client)}. O valor de <strong>R$ ${fmtMoney(valor)} (${valorPorExtenso(valor)})</strong>.</p>
    <p class="justify">O presente valor refere-se à prestação de serviços de assessoria e consultoria especializada para instrução, acompanhamento e protocolo do processo administrativo de obtenção das isenções tributárias destinadas à aquisição de veículo na modalidade Pessoa com Deficiência (PCD), compreendendo, quando cabível:</p>
    <ul>
      <li>Assessoria da Junta Médica do DETRAN;</li>
      <li>Receita Federal do Brasil – Isenção de IPI;</li>
      <li>Isenção de ICMS e IPVA perante a Secretaria de Estado de Fazenda de Mato Grosso (SEFAZ/MT);</li>
      <li>Isenção de ICMS perante a Secretaria da Fazenda do Estado de São Paulo (SEFAZ/SP), quando necessária em razão da operação de aquisição do veículo.</li>
    </ul>
    <p class="justify">O presente recibo constitui prova plena do pagamento do valor acima descrito e da contratação dos serviços especificados, produzindo todos os efeitos legais previstos nos artigos 215, 219 e 320 do Código Civil Brasileiro, servindo como instrumento hábil para comprovação da quitação da obrigação financeira assumida pela contratante em relação aos serviços ora contratados.</p>
    <p class="justify">Ressalta-se que a prestação dos serviços possui natureza de obrigação de meio, comprometendo-se a contratada a realizar todos os procedimentos técnicos e administrativos necessários à condução do processo, não constituindo garantia de deferimento das isenções, cuja análise e decisão competem exclusivamente aos órgãos públicos responsáveis.</p>
    <p class="justify no-indent">E, por ser expressão da verdade, firma-se o presente recibo para que produza seus jurídicos e legais efeitos.</p>
    <p class="center" style="margin-top:1.25rem">${esc(cidade)}/MT, ${dataRecibo}.</p>
    <div class="sig">
      <div class="sig-line"></div>
      <strong>${esc(COMPANY.razaoSocial)}</strong><br>
      Responsável: ${esc(COMPANY.responsavelNome)}<br>
      CPF/CNPJ: ${fmtCpf(COMPANY.responsavelCpf)} / ${esc(COMPANY.cnpj)}<br>
      <small>Declaro ter recebido o pagamento acima descrito.</small>
    </div>
    <div class="sig"><div class="sig-line"></div>${esc(COMPANY.responsavelNome)}<br><small>CNPJ ${esc(COMPANY.cnpj)}</small></div>`,
    `${numero} · R$ ${fmtMoney(valor)}`
  );
}

function renderIcmsPcd(client: ClientPublic, process: ProcessRecord): string {
  const rep = client.representante;
  const cidade = client.cidade || 'Cuiabá';
  const dataDoc = fmtDate(process.updatedAt);

  const rgPart = [
    client.rg ? `portador${g(client, { F: 'a', M: '', N: '(a)' })} do RG nº <strong>${esc(client.rg)}</strong>` : '',
    client.rgDataEmissao ? `expedido em <strong>${esc(fmtDateShort(client.rgDataEmissao))}</strong>` : '',
    client.rgOrgaoEmissor ? `<strong>${esc(client.rgOrgaoEmissor)}</strong>` : '',
    client.rgEstado ? `/${client.rgEstado}` : '',
  ].filter(Boolean).join(', ');

  const repPart = rep
    ? `, ${g(client, { F: 'representada', M: 'representado', N: 'representado(a)' })} legalmente por <strong>${esc(rep.nome)}</strong>, RG ${esc(rep.rg)}${rep.rgOrgaoEmissor ? ` ${esc(rep.rgOrgaoEmissor)}` : ''}${rep.rgEstado ? `/${rep.rgEstado}` : ''}, CPF ${fmtCpf(rep.cpf)}, telefone <strong>${esc(rep.telefone || client.phone || '—')}</strong>`
    : '';

  return wrapHtml(
    'Pedido de Reconhecimento de Isenção do ICMS - PcD',
    `${letterheadGov(true)}
    <h1 class="doc-title">Pedido de Reconhecimento de Isenção do ICMS - PcD</h1>
    <p style="font-size:12pt;margin:1rem 0"><strong>Excelentíssimo Senhor Secretário de Estado de Fazenda de Mato Grosso</strong></p>
    <p class="justify no-indent"><strong>${esc(client.name)}</strong>${rgPart ? `, ${rgPart}` : ''}, inscrit${g(client, { F: 'a', M: 'o', N: 'o(a)' })} no CPF sob nº <strong>${fmtCpf(client.cpf)}</strong>${repPart}, residente à <strong>${esc(clientAddressLine(client))}</strong>, e-mail <strong>${esc(client.email)}</strong>, telefone nº <strong>${esc(client.phone || '—')}</strong>, vem requerer <strong>ISENÇÃO do ICMS-PcD</strong> para aquisição de veículo automotor novo, destinado a pessoa portadora de deficiência física, visual, mental severa ou profunda, ou autista, diretamente ou por intermédio de seu representante legal, nos termos da legislação estadual, conforme documentos em anexo.</p>
    ${IPVA_MANIFESTACAO}
    ${process.vehicle ? `<h2 class="section">Veículo objeto do pedido</h2>${vehicleBlock(process.vehicle)}` : ''}
    <p class="justify no-indent">Protocolo SISEN/IPI: <strong>${esc(stepProtocol(process, 'ipi'))}</strong> · Perícia: <strong>${esc(stepProtocol(process, 'pericia'))}</strong> · ICMS: <strong>${esc(stepProtocol(process, 'icms'))}</strong></p>
    <p class="justify">Nestes termos, pede deferimento.</p>
    <p class="center" style="margin-top:1.25rem">${esc(cidade)}, ${dataDoc}.</p>
    <div class="sig"><div class="sig-line"></div>Assinatura do beneficiário<br><strong>${esc(client.name)}</strong></div>
    ${rep ? `<div class="sig"><div class="sig-line"></div><small>REPRESENTANTE LEGAL</small><br><strong>${esc(rep.nome)}</strong><br>RG ${esc(rep.rg)} · CPF ${fmtCpf(rep.cpf)}</div>` : ''}
    <p class="center" style="font-size:9pt;color:#666;margin-top:1rem">(Este documento pode ser assinado digitalmente com certificado digital no padrão ICP-Brasil ou assinatura eletrônica Gov.br)</p>`,
    `Protocolo SEFAZ: ${stepProtocol(process, 'icms')}`
  );
}

function renderIcmsTaxi(client: ClientPublic, process: ProcessRecord): string {
  const cidade = client.cidade || 'Cuiabá';
  const dataDoc = fmtDate(process.updatedAt);
  const v = process.vehicle;

  return wrapHtml(
    'Pedido de Reconhecimento de Isenção do ICMS - Táxi',
    `${letterheadGov(true)}
    <h1 class="doc-title">Pedido de Reconhecimento de Isenção do ICMS - Táxi</h1>
    <p class="justify no-indent"><strong>${esc(client.name)}</strong>, inscrit${g(client, { F: 'a', M: 'o', N: 'o(a)' })} no CPF sob nº <strong>${fmtCpf(client.cpf)}</strong>, residente e domiciliad${g(client, { F: 'a', M: 'o', N: 'o(a)' })} à <strong>${esc(clientAddressLine(client))}</strong>, e-mail <strong>${esc(client.email)}</strong>, telefone nº <strong>${esc(client.phone || '—')}</strong>, vem requerer <strong>ISENÇÃO DO ICMS – TÁXI</strong> para aquisição de automóvel novo de passageiros equipado com motor de cilindrada até dois mil centímetros cúbicos (2.0l), movido a combustíveis de origem renovável, sistema reversível de combustão ou híbrido, destinado a motorista profissional, apresentando cópia da documentação exigida para concessão do benefício${v?.concessionaria ? `, sendo a concessionária interveniente <strong>${esc(v.concessionaria)}</strong>${v.concessionariaCnpj ? `, CNPJ nº <strong>${esc(v.concessionariaCnpj)}</strong>` : ''}${v.concessionariaIe ? `, inscrição estadual nº <strong>${esc(v.concessionariaIe)}</strong>` : ''}` : ''}${v?.marca ? `, fabricado pela empresa <strong>${esc(v.marca)}</strong>` : ''}.</p>
    <p class="justify">DECLARA que exerce há pelo menos um ano a atividade de condutor autônomo de passageiros, na categoria de aluguel – táxi, em veículo de sua propriedade e que não adquiriu nos últimos 2 (dois) anos veículo com isenção ou redução da base de cálculo do ICMS outorgada à categoria, conforme disposto no Convênio ICMS nº 038/2001 c/c Artigo 100 Anexo IV do Decreto nº 2.212/2014.</p>
    <h2 class="section">Solicita a isenção do ICMS para o veículo</h2>
    ${vehicleBlock(v)}
    ${IPVA_MANIFESTACAO}
    <p class="justify">Nestes termos, pede deferimento.</p>
    <p class="center" style="margin-top:1.25rem">${esc(cidade)}, ${dataDoc}.</p>
    <div class="sig"><div class="sig-line"></div>Assinatura do requerente<br><strong>${esc(client.name)}</strong></div>
    <p class="center" style="font-size:9pt;color:#666;margin-top:1rem">(Este documento pode ser assinado digitalmente com certificado digital no padrão ICP-Brasil ou assinatura eletrônica Gov.br)</p>`,
    `Condutor autônomo de táxi`
  );
}

function renderDeclFinanceira(client: ClientPublic, process: ProcessRecord): string {
  const rep = client.representante;
  const cidade = client.cidade || 'Cuiabá';
  const dataDoc = fmtDate(process.updatedAt);

  return wrapHtml(
    'Declaração de Disponibilidade Financeira ou Patrimonial',
    `${letterheadGov()}
    <h1 class="doc-title">Declaração de Disponibilidade Financeira ou Patrimonial</h1>
    <h2 class="section">1. Identificação</h2>
    <p class="justify no-indent"><strong>${esc(client.name)}</strong><br>CPF <strong>${fmtCpf(client.cpf)}</strong></p>
    <h2 class="section">2. Declaração</h2>
    <p class="justify">O interessado acima identificado${rep ? `, representado por <strong>${esc(rep.nome)}</strong>, CPF nº <strong>${fmtCpf(rep.cpf)}</strong>` : ''}, DECLARA, sob as penas da lei, que possui disponibilidade financeira ou patrimonial compatível, nos termos do item 2, alínea C, inciso III, §4º, art. 32 do Anexo IV do Decreto 2.212/2014 (RICMS), de 20 de março de 2014, com o valor do veículo a ser adquirido com a isenção do Imposto sobre Circulação de Mercadorias e Prestações de Serviços de Transporte Interestadual e Intermunicipal e de Comunicação – ICMS a que se refere o art. 1º da Lei nº 8.698, de 07 de agosto de 2007${process.vehicle ? ` (<strong>${esc([process.vehicle.marca, process.vehicle.modelo, process.vehicle.ano].filter(Boolean).join(' '))}</strong>)` : ''}.</p>
    <h2 class="section">3. Declaração de responsabilidade</h2>
    <ul>
      <li>O declarante ou seu representante legal responsabiliza-se pela exatidão e veracidade das informações prestadas.</li>
      <li>Declara estar ciente do que dispõe o art. 299 do Decreto-Lei nº 2.848, de 7 de dezembro de 1940 (Código Penal): “Omitir, em documento público ou particular, declaração que dele devia constar, ou nele inserir declaração falsa ou diversa da que devia ser escrita, com o fim de prejudicar direito, criar obrigação ou alterar a verdade sobre fato juridicamente relevante: Pena – reclusão, de 1 (um) a 5 (cinco) anos”.</li>
    </ul>
    <h2 class="section">4. Assinatura</h2>
    <p class="justify no-indent">Nome: <strong>${esc(client.name)}</strong><br>CPF: <strong>${fmtCpf(client.cpf)}</strong><br>Data: <strong>${dataDoc}</strong></p>
    <div class="sig"><div class="sig-line"></div>Assinatura<br><strong>${esc(client.name)}</strong></div>
    <p class="center" style="font-size:9pt;color:#666;margin-top:1rem">(Este documento pode ser assinado digitalmente com certificado digital no padrão ICP-Brasil)</p>`,
    `Beneficiário: ${client.name}`
  );
}

function renderCondutorSp(client: ClientPublic, process: ProcessRecord, conductors: ConductorRecord[]): string {
  const cidade = client.cidade || 'Cuiabá';
  const dataDoc = fmtDate(process.updatedAt);

  const blocks = conductors.length
    ? conductors
        .map(
          (c, i) => `<div class="condutor-block">
            <h3>Identificação do condutor autorizado – ${i + 1}</h3>
            <p class="no-indent"><strong>CPF:</strong> ${fmtCpf(c.cpf)}<br><strong>Nome:</strong> ${esc(c.nome)}${c.rg ? `<br><strong>RG:</strong> ${esc(c.rg)}` : ''}</p>
            <p class="no-indent" style="margin-top:0.5rem"><strong>Endereço:</strong> ${esc(c.endereco || '—')}<br><strong>Telefone:</strong> ${esc(c.telefone || '—')}</p>
          </div>`
        )
        .join('')
    : `<p class="justify no-indent">Nenhum condutor cadastrado.</p>`;

  return wrapHtml(
    'Formulário de Condutor SP',
    `${letterheadCompany()}
    <p class="doc-title" style="font-size:11pt">ESTADO DE SÃO PAULO</p>
    <h1 class="doc-title">Anexo VI — Identificação do Condutor Autorizado</h1>
    <p class="justify no-indent">Beneficiário do processo de isenção: <strong>${esc(client.name)}</strong>, CPF ${fmtCpf(client.cpf)}.</p>
    ${blocks}
    <p class="justify">Declaram o requerente ou o seu representante legal, e o(s) condutor(es) autorizado(s) serem autênticas e verdadeiras as informações prestadas.</p>
    <table class="data-table" style="margin-top:1.5rem">
      <thead><tr><th>Papel</th><th>Identificação</th><th>Assinatura</th></tr></thead>
      <tbody>
        <tr><td>Requerente / Representante legal</td><td>${esc(client.name)}</td><td>_________________________</td></tr>
        ${conductors.map((c) => `<tr><td>Condutor autorizado</td><td>${esc(c.nome)}</td><td>_________________________</td></tr>`).join('')}
      </tbody>
    </table>
    <p class="center" style="margin-top:1.25rem">${esc(cidade)}, ${dataDoc}.</p>`,
    `${conductors.length} condutor(es)`
  );
}

function renderCancelIcms(client: ClientPublic, process: ProcessRecord): string {
  const rep = client.representante;
  const cidade = client.cidade || 'Cuiabá';
  const dataDoc = fmtDate(process.updatedAt);
  const icmsProtocol = stepProtocol(process, 'icms');

  const solicitanteBlock = rep
    ? `<strong>${esc(client.name)}</strong>, RG: ${esc(client.rg || '—')}, CPF: ${fmtCpf(client.cpf)}, representado pelo tutor <strong>${esc(rep.nome)}</strong>, RG ${esc(rep.rg)}${rep.rgOrgaoEmissor ? ` ${esc(rep.rgOrgaoEmissor)}` : ''}${rep.rgEstado ? `/${rep.rgEstado}` : ''}, CPF ${fmtCpf(rep.cpf)}, residente à ${esc(clientAddressLine(client))}, telefone ${esc(rep.telefone || client.phone || '—')}, e-mail ${esc(client.email)}`
    : clientPartyBlock(client);

  return wrapHtml(
    'Solicitação de Cancelamento de ICMS',
    `${letterheadGov()}
    <h1 class="doc-title">Solicitação de Cancelamento de ICMS</h1>
    <p class="justify no-indent">À SECRETARIA DE FAZENDA DO ESTADO DE MATO GROSSO</p>
    <p class="justify no-indent">Eu <strong>${esc(COMPANY.responsavelNome)}</strong>, portador do RG ${esc(COMPANY.responsavelRg)} e CPF ${fmtCpf(COMPANY.responsavelCpf)}, procurador neste processo, proprietário da <strong>${esc(COMPANY.razaoSocial)}</strong>, CNPJ ${esc(COMPANY.cnpj)}, venho por meio desta, em caráter de procurador da solicitante ${solicitanteBlock}, pedir gentilmente o <strong>cancelamento da autorização de ICMS</strong>${icmsProtocol !== '—' ? `, número do processo/ano: <strong>${esc(icmsProtocol)}</strong>` : ''}, deferido anteriormente em nome de <strong>${esc(client.name)}</strong>.</p>
    <p class="justify">Precisamos cancelar para solicitar uma nova autorização, pois a autorização anterior está nos moldes anteriores, sem o novo teto atualizado. O pedido da nova autorização segue em anexo no sistema e processo.</p>
    <p class="justify">Estivemos na Secretaria de Fazenda para pedir o cancelamento presencialmente e fomos instruídos a fazer o cancelamento no e-process junto com a nova solicitação para o novo modelo.</p>
    <p class="justify no-indent">Sem mais, agradecemos a disponibilidade!</p>
    <p class="center" style="margin-top:1.25rem">${esc(cidade)}, ${dataDoc}.</p>
    <div class="sig"><div class="sig-line"></div><strong>${esc(COMPANY.responsavelNome)}</strong><br><small>CNPJ ${esc(COMPANY.cnpj)} · Procurador</small></div>`,
    `Cliente: ${client.name}`
  );
}

export function renderDocument(
  code: DocumentTemplateCode,
  client: ClientPublic,
  process: ProcessRecord,
  conductors: ConductorRecord[] = [],
  options: RenderDocumentOptions = {}
): string {
  switch (code) {
    case 'contrato':
      return renderContrato(client, process);
    case 'recibo':
      return renderRecibo(client, process, options);
    case 'icms_pcd':
      return renderIcmsPcd(client, process);
    case 'icms_taxi':
      return renderIcmsTaxi(client, process);
    case 'decl_financeira':
      return renderDeclFinanceira(client, process);
    case 'condutor_sp':
      return renderCondutorSp(client, process, conductors);
    case 'cancel_icms':
      return renderCancelIcms(client, process);
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
