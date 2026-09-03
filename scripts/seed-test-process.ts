#!/usr/bin/env npx tsx
/**
 * Cria processo PCD completo com dados de teste, documentos e relatórios gerados.
 * Uso local:  npx tsx scripts/seed-test-process.ts
 * Uso GCP:     CONTACTS_STORAGE=firestore GCP_PROJECT=smart-tractor-257319 npx tsx scripts/seed-test-process.ts
 */
import fs from 'fs';
import path from 'path';
import { randomUUID } from 'crypto';
import { fileURLToPath } from 'url';
import {
  createClient,
  findClientByEmail,
  findClientById,
  toPublicClient,
} from '../server/src/clients-store.js';
import { createProcess, updateProcess, listProcesses } from '../server/src/processes-store.js';
import { createProcessFile } from '../server/src/process-files-store.js';
import { upsertConductors } from '../server/src/conductors-store.js';
import { savePrivateDoc, savePrivateDocRaw } from '../server/src/docs-store.js';
import {
  buildFileObjectName,
  buildStorageContext,
  type StoragePathContext,
} from '../server/src/process-constants.js';
import { renderDocument } from '../server/src/pdf-templates.js';
import { derivePagamentoMeta } from '../server/src/pagamentos-utils.js';
import type { DocumentTemplateCode, FileTypeCode, PagamentoHonorario } from '../server/src/types/process.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const DATA_DIR = path.join(__dirname, '../server/data');

const CLIENT_EMAIL = 'marisol.teste@andradeisencoes.com.br';
const CLIENT_PASSWORD = 'Teste@2026';

const PAGAMENTOS: PagamentoHonorario[] = [
  {
    id: randomUUID(),
    numero: 'REC-2026-0001',
    valor: 1500,
    tipo: 'PIX',
    status: 'pago',
    data: '2026-01-15',
    descricao: 'Entrada — assessoria PcD',
  },
  {
    id: randomUUID(),
    numero: 'REC-2026-0002',
    valor: 1500,
    tipo: 'PIX',
    status: 'pago',
    data: '2026-03-20',
    descricao: 'Perícia aprovada',
  },
  {
    id: randomUUID(),
    numero: 'REC-2026-0003',
    valor: 1500,
    tipo: 'PIX',
    status: 'pago',
    data: '2026-08-28',
    descricao: 'Saldo ICMS',
  },
];

function minimalPdf(title: string, lines: string[] = []): Buffer {
  const content = [title, ...lines].slice(0, 10);
  const yStart = 750;
  const stream = content
    .map((line, i) => `50 ${yStart - i * 16} Td (${line.replace(/[()\\]/g, ' ')}) Tj`)
    .join('\n');
  const fullStream = `BT /F1 10 Tf ${stream} ET`;
  const body = `%PDF-1.4
1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj
2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj
3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj
4 0 obj<</Length ${fullStream.length}>>stream
${fullStream}
endstream endobj
5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj
xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000270 00000 n 
0000000368 00000 n 
trailer<</Size 6/Root 1 0 R>>
startxref
445
%%EOF`;
  return Buffer.from(body, 'utf-8');
}

async function uploadDoc(
  ctx: StoragePathContext,
  processId: string,
  clientId: string,
  fileType: FileTypeCode,
  originalName: string,
  title: string,
  lines: string[],
  uploadedBy: string
) {
  const buffer = minimalPdf(title, lines);
  const objectName = buildFileObjectName(ctx, fileType, originalName);
  await savePrivateDoc(buffer, 'application/pdf', objectName);
  return createProcessFile({
    processId,
    clientId,
    fileType,
    objectName,
    originalName,
    mimeType: 'application/pdf',
    size: buffer.length,
    uploadedBy,
    uploadedByRole: 'admin',
  });
}

function buildHtmlObjectName(ctx: StoragePathContext, slug: string): string {
  const stamp = new Date().toISOString().slice(0, 10);
  return `clientes/${ctx.clientSlug}/processos/${ctx.processSlug}/gerados/${slug}-${stamp}.html`;
}

async function saveGeneratedReport(
  ctx: StoragePathContext,
  processId: string,
  clientId: string,
  code: DocumentTemplateCode,
  label: string,
  client: ReturnType<typeof toPublicClient>,
  process: NonNullable<Awaited<ReturnType<typeof updateProcess>>>,
  conductors: Awaited<ReturnType<typeof upsertConductors>>,
  uploadedBy: string,
  options?: { pagamentoId?: string; fileSlug?: string }
) {
  const html = renderDocument(code, client, process, conductors, {
    pagamentoId: options?.pagamentoId,
  });
  const slug = options?.fileSlug ?? code;
  const objectName = buildHtmlObjectName(ctx, slug);
  const buffer = Buffer.from(html, 'utf-8');
  await savePrivateDocRaw(buffer, 'text/html; charset=utf-8', objectName);

  return createProcessFile({
    processId,
    clientId,
    fileType: 'outros',
    objectName,
    originalName: `RELATORIO-${label.replace(/\s+/g, '_')}.html`,
    mimeType: 'text/html',
    size: buffer.length,
    uploadedBy,
    uploadedByRole: 'admin',
  });
}

async function removeExistingClient(email: string) {
  const existing = await findClientByEmail(email);
  if (!existing) return;

  const { useFirestore, getFirestore } = await import('../server/src/firestore-client.js');

  if (useFirestore) {
    const db = await getFirestore();
    const processes = (await listProcesses()).filter((p) => p.clientId === existing.id);
    for (const p of processes) {
      const filesSnap = await db.collection('process_files').where('processId', '==', p.id).get();
      for (const doc of filesSnap.docs) await doc.ref.delete();
      const condSnap = await db.collection('conductors').where('processId', '==', p.id).get();
      for (const doc of condSnap.docs) await doc.ref.delete();
      await db.collection('processes').doc(p.id).delete();
    }
    await db.collection('clients').doc(existing.id).delete();
  } else {
    const clients = JSON.parse(fs.readFileSync(path.join(DATA_DIR, 'clients.json'), 'utf-8'));
    fs.writeFileSync(
      path.join(DATA_DIR, 'clients.json'),
      JSON.stringify(clients.filter((c: { id: string }) => c.id !== existing.id), null, 2)
    );

    const allProcesses = JSON.parse(fs.readFileSync(path.join(DATA_DIR, 'processes.json'), 'utf-8'));
    fs.writeFileSync(
      path.join(DATA_DIR, 'processes.json'),
      JSON.stringify(allProcesses.filter((p: { clientId: string }) => p.clientId !== existing.id), null, 2)
    );

    const allFiles = JSON.parse(fs.readFileSync(path.join(DATA_DIR, 'process-files.json'), 'utf-8'));
    fs.writeFileSync(
      path.join(DATA_DIR, 'process-files.json'),
      JSON.stringify(allFiles.filter((f: { clientId: string }) => f.clientId !== existing.id), null, 2)
    );

    const docsDir = path.join(DATA_DIR, 'private-docs', `clientes/${existing.id}`);
    if (fs.existsSync(docsDir)) fs.rmSync(docsDir, { recursive: true, force: true });

    const slugDocs = path.join(DATA_DIR, 'private-docs', 'clientes');
    if (fs.existsSync(slugDocs)) {
      for (const entry of fs.readdirSync(slugDocs)) {
        const full = path.join(slugDocs, entry);
        if (fs.statSync(full).isDirectory() && entry.includes('marisol')) {
          fs.rmSync(full, { recursive: true, force: true });
        }
      }
    }
  }

  console.log(`  (removido cadastro anterior: ${existing.name})`);
}

async function main() {
  console.log('→ Preparando dados de teste…');
  await removeExistingClient(CLIENT_EMAIL);

  console.log('→ Criando cliente…');
  const client = await createClient({
    email: CLIENT_EMAIL,
    password: CLIENT_PASSWORD,
    name: 'Marisol Aparecida Ferreira',
    cpf: '12345678909',
    genero: 'F',
    rg: '1.234.567',
    rgEstado: 'MT',
    rgOrgaoEmissor: 'SSP',
    rgDataEmissao: '2015-03-10',
    phone: '(65) 99912-3456',
    endereco: 'Av. Fernando Corrêa da Costa',
    numero: '1899',
    complemento: 'Apto 302',
    bairro: 'Jardim das Américas',
    cep: '78060-600',
    cidade: 'Cuiabá',
    uf: 'MT',
    representante: {
      nome: 'João Ferreira Santos',
      cpf: '98765432100',
      rg: '2.345.678',
      rgOrgaoEmissor: 'SSP',
      rgEstado: 'MT',
      telefone: '(65) 99887-7665',
    },
    lgpdConsentAt: new Date().toISOString(),
    termsConsentAt: new Date().toISOString(),
  });

  console.log('→ Criando processo PCD…');
  const process = await createProcess({ clientId: client.id, modality: 'pcd' });

  const now = new Date().toISOString();
  const doneKeys = new Set(['documentacao', 'analise', 'pericia', 'ipi']);
  const steps = process.steps.map((s) => {
    if (doneKeys.has(s.key)) {
      return {
        ...s,
        status: 'concluida' as const,
        completedAt: now,
        protocol:
          s.key === 'ipi' ? 'SISEN-2026-004521' :
          s.key === 'pericia' ? 'DETRAN-88421/2026' :
          s.key === 'analise' ? 'INT-2026-001' : undefined,
      };
    }
    if (s.key === 'sefaz_mt') {
      return {
        ...s,
        status: 'em_andamento' as const,
        startedAt: now,
        protocol: 'SEFAZ-MT-2026-11892',
        internalNote: 'Aguardando deferimento da SEFAZ',
      };
    }
    return { ...s, status: 'pendente' as const };
  });

  const meta = derivePagamentoMeta({ honorarios: 4500, pagamentos: PAGAMENTOS });

  const updatedProcess = await updateProcess(process.id, {
    currentStep: 'sefaz_mt',
    steps,
    honorarios: 4500,
    pagamentos: PAGAMENTOS,
    pagamentoTipo: meta.pagamentoTipo,
    pagamentoStatus: meta.pagamentoStatus,
    vehicle: {
      marca: 'Volkswagen',
      modelo: 'T-Cross Comfortline 1.0 TSI',
      ano: '2026',
      potencia: '128 cv',
      placa: 'QWE4F21',
      chassi: '9BWZZZ377VT004251',
      renavam: '01234567890',
      concessionaria: 'Auto Cuiabá Veículos Ltda',
      concessionariaCnpj: '12.345.678/0001-90',
      concessionariaIe: '131234567',
    },
  });
  if (!updatedProcess) throw new Error('Falha ao atualizar processo');

  const adminId = 'seed-admin';
  const clientRecord = await findClientById(client.id);
  if (!clientRecord) throw new Error('Cliente não encontrado');
  const pub = toPublicClient(clientRecord);
  const storageCtx = buildStorageContext(clientRecord, updatedProcess);

  console.log('→ Anexando documentos…');
  const uploads: { type: FileTypeCode; name: string; title: string; lines: string[] }[] = [
    {
      type: 'cnh',
      name: 'CNH-Marisol-Ferreira.pdf',
      title: 'CNH - Marisol Aparecida Ferreira',
      lines: ['Cat. B · Validade 15/08/2028', 'DETRAN/MT · 1ª habilitação 2010'],
    },
    {
      type: 'cpf',
      name: 'CPF-Marisol.pdf',
      title: 'CPF - Marisol Aparecida Ferreira',
      lines: ['123.456.789-09', 'Situação cadastral: Regular'],
    },
    {
      type: 'doc_foto',
      name: 'RG-Marisol.pdf',
      title: 'RG com foto - Marisol',
      lines: ['SSP/MT nº 1.234.567', 'Emissão 10/03/2015'],
    },
    {
      type: 'laudo',
      name: 'Laudo-Neurologico.pdf',
      title: 'Laudo Médico Neurológico',
      lines: ['CID G40.9 — Epilepsia', 'Dr. Carlos Silva — CRM-MT 4521', 'Junta Médica: apta PcD'],
    },
    {
      type: 'comprovante_residencia',
      name: 'Conta-Luz-Mar-2026.pdf',
      title: 'Comprovante de Residência',
      lines: ['Energisa MT — Mar/2026', 'Av. Fernando Corrêa da Costa, 1899 — Cuiabá/MT'],
    },
    {
      type: 'alvara_curatela',
      name: 'Alvara-Curatela.pdf',
      title: 'Alvará de Curatela',
      lines: ['Tutor: João Ferreira Santos', 'Vara de Família — Cuiabá/MT'],
    },
    {
      type: 'comprovante_pagamento',
      name: 'PIX-Honorarios-Entrada-1500.pdf',
      title: 'Comprovante PIX — Entrada',
      lines: ['R$ 1.500,00', 'REC-2026-0001 · Honorários assessoria'],
    },
    {
      type: 'comprovante_pagamento',
      name: 'PIX-Honorarios-Pericia-1500.pdf',
      title: 'Comprovante PIX — Perícia',
      lines: ['R$ 1.500,00', 'REC-2026-0002 · Honorários assessoria'],
    },
    {
      type: 'comprovante_pagamento',
      name: 'PIX-Honorarios-Saldo-1500.pdf',
      title: 'Comprovante PIX — Saldo',
      lines: ['R$ 1.500,00', 'REC-2026-0003 · Honorários assessoria'],
    },
  ];

  for (const u of uploads) {
    await uploadDoc(storageCtx, process.id, client.id, u.type, u.name, u.title, u.lines, adminId);
    console.log(`  ✓ ${u.name}`);
  }

  console.log('→ Condutores autorizados…');
  const conductors = await upsertConductors(process.id, client.id, [
    {
      nome: 'João Ferreira Santos',
      cpf: '98765432100',
      rg: '2.345.678',
      endereco: 'Av. Fernando Corrêa da Costa, 1899 — Cuiabá/MT',
      telefone: '(65) 99887-7665',
    },
    {
      nome: 'Ana Paula Ferreira',
      cpf: '11122233344',
      rg: '3.456.789',
      endereco: 'Rua das Palmeiras, 450 — Cuiabá/MT',
      telefone: '(65) 99123-4567',
    },
  ]);
  console.log(`  ✓ ${conductors.length} condutores`);

  console.log('→ Gerando relatórios completos…');
  const reports: { code: DocumentTemplateCode; label: string; pagamentoId?: string; fileSlug?: string }[] = [
    { code: 'contrato', label: 'Contrato_de_Servicos' },
    { code: 'icms_pcd', label: 'Pedido_ICMS_PcD' },
    { code: 'decl_financeira', label: 'Declaracao_Financeira' },
    { code: 'condutor_sp', label: 'Formulario_Condutor_SP' },
    { code: 'cancel_icms', label: 'Cancelamento_ICMS' },
    ...PAGAMENTOS.map((p) => ({
      code: 'recibo' as DocumentTemplateCode,
      label: `Recibo_${p.numero}`,
      pagamentoId: p.id,
      fileSlug: `recibo-${p.numero.toLowerCase()}`,
    })),
  ];

  for (const r of reports) {
    await saveGeneratedReport(
      storageCtx,
      process.id,
      client.id,
      r.code,
      r.label,
      pub,
      updatedProcess,
      conductors,
      adminId,
      { pagamentoId: r.pagamentoId, fileSlug: r.fileSlug }
    );
    console.log(`  ✓ ${r.label}`);
  }

  console.log('\n══════════════════════════════════════════');
  console.log('Processo de teste criado!');
  console.log('══════════════════════════════════════════');
  console.log(`Cliente:  ${client.name}`);
  console.log(`Pasta:    clientes/${storageCtx.clientSlug}/`);
  console.log(`Processo: ${storageCtx.processSlug} (${process.id})`);
  console.log(`CPF:      ${client.cpf}`);
  console.log(`E-mail:   ${CLIENT_EMAIL}`);
  console.log(`Senha:    ${CLIENT_PASSWORD}`);
  console.log(`Etapa:    ICMS (em andamento)`);
  console.log(`Honorários: R$ 4.500 em ${PAGAMENTOS.length} parcelas`);
  console.log(`Anexos:   ${uploads.length} documentos + ${reports.length} relatórios HTML`);
  console.log('\nAcessos:');
  console.log('  Cliente → /entrar → /conta');
  console.log(`  Admin   → /admin/processos/${process.id}`);
  console.log('══════════════════════════════════════════\n');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
