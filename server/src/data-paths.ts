import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const DEFAULT_DATA_DIR = path.join(__dirname, '../data');

export function getDataDir(): string {
  return process.env.ANDRADE_DATA_DIR || DEFAULT_DATA_DIR;
}

export function dataFile(filename: string): string {
  return path.join(getDataDir(), filename);
}

export function privateDocsDir(): string {
  return path.join(getDataDir(), 'private-docs');
}

export function backupsDir(): string {
  return path.join(getDataDir(), 'backups');
}
