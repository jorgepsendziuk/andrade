#!/usr/bin/env npx tsx
/**
 * Seed inicial dos usuários admin no Firestore.
 * Uso: GCP_PROJECT=smart-tractor-257319 CONTACTS_STORAGE=firestore npx tsx scripts/seed-admin-users.ts
 */
import { seedUserIfMissing } from '../server/src/users-store.js';

const USERS = [
  { email: 'comercial@andradeisencoes.com.br', name: 'Comercial', role: 'admin' as const },
  { email: 'andradeisencoescloud@gmail.com', name: 'Cloud Andrade', role: 'admin' as const },
  { email: 'jimxxx@gmail.com', name: 'Jorge', role: 'admin' as const },
];

async function main() {
  for (const u of USERS) {
    await seedUserIfMissing({
      email: u.email,
      name: u.name,
      password: u.email,
      role: u.role,
      mustChangePassword: true,
    });
    console.log(`✓ ${u.email}`);
  }
  console.log('\nUsuários criados (senha inicial = e-mail). Troque no primeiro acesso.');
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
