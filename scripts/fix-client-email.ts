#!/usr/bin/env npx tsx
/**
 * Corrige e-mail de cliente no Firestore (remove ponto final inválido, etc.).
 * Uso: GCP_PROJECT=... CONTACTS_STORAGE=firestore npx tsx scripts/fix-client-email.ts <email-atual> <email-novo>
 */
import { getFirestore } from '../server/src/firestore-client.js';

async function main() {
  const from = (process.argv[2] || '').trim().toLowerCase();
  const to = (process.argv[3] || '').trim().toLowerCase().replace(/\.+$/, '');
  if (!from || !to) {
    console.error('Uso: npx tsx scripts/fix-client-email.ts <email-atual> <email-novo>');
    process.exit(1);
  }

  const db = await getFirestore();
  const snap = await db.collection('clients').where('email', '==', from).limit(1).get();
  if (snap.empty) {
    console.error(`Cliente não encontrado: ${from}`);
    process.exit(1);
  }

  const doc = snap.docs[0];
  await doc.ref.set({ email: to, updatedAt: new Date().toISOString() }, { merge: true });
  console.log(`✓ ${from} → ${to} (id: ${doc.id})`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
