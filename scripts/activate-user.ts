#!/usr/bin/env npx tsx
/**
 * Reativa um usuário admin no Firestore.
 * Uso: GCP_PROJECT=smart-tractor-257319 CONTACTS_STORAGE=firestore npx tsx scripts/activate-user.ts <email>
 */
import { findUserByEmail, updateUser } from '../server/src/users-store.js';

async function main() {
  const email = (process.argv[2] || '').trim().toLowerCase();
  if (!email) {
    console.error('Uso: npx tsx scripts/activate-user.ts <email>');
    process.exit(1);
  }

  const user = await findUserByEmail(email);
  if (!user) {
    console.error(`Usuário não encontrado: ${email}`);
    process.exit(1);
  }

  console.log(`→ Ativando ${user.email} (id: ${user.id}, active: ${user.active})`);
  await updateUser(user.id, { active: true });

  const updated = await findUserByEmail(email);
  console.log(`✓ Status: ${updated?.active ? 'ativo' : 'inativo'}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
