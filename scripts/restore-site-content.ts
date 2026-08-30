#!/usr/bin/env npx tsx
/** Restaura site-content.json no Firestore (produção). */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { saveSiteContent } from '../server/src/content-store.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const content = JSON.parse(
  fs.readFileSync(path.join(__dirname, '../server/data/site-content.json'), 'utf-8')
);

process.env.CONTACTS_STORAGE = 'firestore';

await saveSiteContent(content);
console.log('✓ Conteúdo restaurado no Firestore');
