#!/usr/bin/env npx tsx
/**
 * Redefine senha de um admin no Firestore.
 * Uso: GCP_PROJECT=smart-tractor-257319 npx tsx scripts/reset-admin-password.ts jimxxx@gmail.com
 */
import { findUserByEmail, updatePassword, updateUser, verifyPassword } from '../server/src/users-store.js';

async function main() {
  const email = (process.argv[2] || '').trim().toLowerCase();
  const password = process.argv[3] || email;

  if (!email) {
    console.error('Uso: npx tsx scripts/reset-admin-password.ts <email> [nova-senha]');
    process.exit(1);
  }

  const user = await findUserByEmail(email);
  if (!user) {
    console.error(`Usuário não encontrado: ${email}`);
    process.exit(1);
  }

  console.log(`→ Redefinindo senha de ${user.email} (id: ${user.id})`);
  await updatePassword(user.id, password);
  await updateUser(user.id, { active: true, mustChangePassword: false });

  const updated = await findUserByEmail(email);
  const ok = updated ? await verifyPassword(updated, password) : false;
  if (!ok) {
    console.error('Falha ao verificar a nova senha.');
    process.exit(1);
  }

  console.log(`✓ Senha atualizada. Login: ${email} / ${password}`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
