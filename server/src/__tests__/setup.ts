import fs from 'fs';
import os from 'os';
import path from 'path';
import bcrypt from 'bcryptjs';

process.env.CONTACTS_STORAGE = 'file';
process.env.JWT_SECRET = 'test-jwt-secret-andrade';
process.env.NODE_ENV = 'test';
process.env.BACKUP_CRON_SECRET = 'test-backup-secret';

const tmp = fs.mkdtempSync(path.join(os.tmpdir(), 'andrade-test-'));
process.env.ANDRADE_DATA_DIR = tmp;

const now = new Date().toISOString();
const seed = {
  users: [
    {
      id: 'admin-test-id',
      email: 'admin@test.com',
      name: 'Admin Test',
      role: 'admin',
      active: true,
      mustChangePassword: false,
      passwordHash: bcrypt.hashSync('Test@1234', 4),
      createdAt: now,
      updatedAt: now,
    },
    {
      id: 'comercial-test-id',
      email: 'comercial@test.com',
      name: 'Comercial Test',
      role: 'comercial',
      active: true,
      mustChangePassword: false,
      passwordHash: bcrypt.hashSync('Test@1234', 4),
      createdAt: now,
      updatedAt: now,
    },
  ],
};

fs.mkdirSync(path.join(tmp, 'private-docs'), { recursive: true });
fs.writeFileSync(path.join(tmp, 'admin-users.json'), JSON.stringify(seed.users, null, 2));
fs.writeFileSync(path.join(tmp, 'clients.json'), '[]');
fs.writeFileSync(path.join(tmp, 'processes.json'), '[]');
fs.writeFileSync(path.join(tmp, 'process-files.json'), '[]');
fs.writeFileSync(path.join(tmp, 'conductors.json'), '[]');
fs.writeFileSync(path.join(tmp, 'audit-logs.json'), '[]');
fs.writeFileSync(path.join(tmp, 'staff-alerts.json'), '[]');
fs.writeFileSync(path.join(tmp, 'contacts.json'), '[]');
fs.writeFileSync(path.join(tmp, 'app-settings.json'), '{}');
fs.writeFileSync(
  path.join(tmp, 'site-content.json'),
  JSON.stringify({ site: { name: 'Test Site', title: 'Test' }, sections: [] }, null, 2)
);
