#!/usr/bin/env npx tsx
/**
 * Envia e-mail de redefinição de senha para todos os clientes ativos.
 * Uso: GCP_PROJECT=smart-tractor-257319 CONTACTS_STORAGE=firestore npx tsx scripts/send-client-password-resets.ts
 *
 * Opções:
 *   --dry-run   Lista clientes sem enviar e-mails
 *   --email=x   Envia apenas para um e-mail específico
 */
import { findClientByEmail } from '../server/src/clients-store.js';
import {
  sendClientPasswordResetLink,
  sendPasswordResetToAllActiveClients,
} from '../server/src/auth-password-reset.js';

async function main() {
  const args = process.argv.slice(2);
  const dryRun = args.includes('--dry-run');
  const emailArg = args.find((a) => a.startsWith('--email='))?.split('=')[1]?.trim().toLowerCase();

  if (emailArg) {
    const client = await findClientByEmail(emailArg);
    if (!client) {
      console.error(`Cliente não encontrado: ${emailArg}`);
      process.exit(1);
    }
    if (client.active === false) {
      console.error(`Cliente inativo: ${emailArg}`);
      process.exit(1);
    }
    if (dryRun) {
      console.log(`[dry-run] Enviaria reset para ${client.email} (${client.name})`);
      return;
    }
    const result = await sendClientPasswordResetLink(client);
    if (!result.sent) {
      console.error(`Falha: ${result.error}`);
      process.exit(1);
    }
    console.log(`✓ E-mail enviado para ${client.email}`);
    return;
  }

  if (dryRun) {
    const { listClients } = await import('../server/src/clients-store.js');
    const clients = (await listClients(5000)).filter((c) => c.active !== false);
    console.log(`[dry-run] ${clients.length} cliente(s) ativo(s):`);
    for (const c of clients) {
      console.log(`  - ${c.email} (${c.name})`);
    }
    return;
  }

  console.log('Enviando e-mails de redefinição de senha para clientes ativos…');
  const result = await sendPasswordResetToAllActiveClients();
  console.log(`\nConcluído: ${result.sent}/${result.total} enviados.`);
  if (result.failed.length > 0) {
    console.log('Falhas:');
    for (const f of result.failed) {
      console.log(`  - ${f.email}: ${f.error}`);
    }
    process.exit(1);
  }
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
