import type { MediaFile } from '../types/media';

export type MediaSizeFilter = 'all' | 'xs' | 'sm' | 'md' | 'lg';
export type MediaSort = 'date-desc' | 'date-asc' | 'size-desc' | 'size-asc' | 'name-asc' | 'name-desc';
export type MediaViewMode = 'grid' | 'list' | 'duplicates';

export interface DuplicateGroup {
  key: string;
  label: string;
  reason: 'same-name' | 'same-size' | 'same-stem';
  files: MediaFile[];
}

const SIZE_LIMITS: Record<Exclude<MediaSizeFilter, 'all'>, { min: number; max: number; label: string }> = {
  xs: { min: 0, max: 100 * 1024, label: '< 100 KB' },
  sm: { min: 100 * 1024, max: 500 * 1024, label: '100 KB – 500 KB' },
  md: { min: 500 * 1024, max: 2 * 1024 * 1024, label: '500 KB – 2 MB' },
  lg: { min: 2 * 1024 * 1024, max: Infinity, label: '≥ 2 MB' },
};

export function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function formatDate(iso: string): string {
  if (!iso) return '—';
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

/** Remove prefixo típico de upload (timestamp-random). */
export function stripUploadPrefix(name: string): string {
  return name.replace(/^\d{10,13}-[a-z0-9]+/i, '');
}

export function stemKey(name: string): string {
  const stripped = stripUploadPrefix(name).toLowerCase();
  const dot = stripped.lastIndexOf('.');
  return dot > 0 ? stripped.slice(0, dot) : stripped;
}

export function matchesSizeFilter(size: number, filter: MediaSizeFilter): boolean {
  if (filter === 'all') return true;
  const { min, max } = SIZE_LIMITS[filter];
  return size >= min && size < max;
}

export function sizeFilterLabel(filter: MediaSizeFilter): string {
  if (filter === 'all') return 'Qualquer tamanho';
  return SIZE_LIMITS[filter].label;
}

export function sortMediaFiles(files: MediaFile[], sort: MediaSort): MediaFile[] {
  const copy = [...files];
  copy.sort((a, b) => {
    switch (sort) {
      case 'date-asc':
        return a.updatedAt.localeCompare(b.updatedAt);
      case 'size-desc':
        return b.size - a.size || a.name.localeCompare(b.name);
      case 'size-asc':
        return a.size - b.size || a.name.localeCompare(b.name);
      case 'name-asc':
        return a.name.localeCompare(b.name, 'pt-BR');
      case 'name-desc':
        return b.name.localeCompare(a.name, 'pt-BR');
      case 'date-desc':
      default:
        return b.updatedAt.localeCompare(a.updatedAt);
    }
  });
  return copy;
}

export function buildDuplicateGroups(files: MediaFile[]): DuplicateGroup[] {
  const byName = new Map<string, MediaFile[]>();
  const bySize = new Map<number, MediaFile[]>();
  const byStem = new Map<string, MediaFile[]>();

  for (const file of files) {
    const nameKey = file.name.toLowerCase();
    byName.set(nameKey, [...(byName.get(nameKey) ?? []), file]);

    if (file.size > 0) {
      bySize.set(file.size, [...(bySize.get(file.size) ?? []), file]);
    }

    const stem = stemKey(file.name);
    if (stem.length > 2) {
      byStem.set(stem, [...(byStem.get(stem) ?? []), file]);
    }
  }

  const groups: DuplicateGroup[] = [];
  const usedKeys = new Set<string>();

  for (const [name, group] of byName) {
    if (group.length < 2) continue;
    const key = `name:${name}`;
    if (usedKeys.has(key)) continue;
    usedKeys.add(key);
    groups.push({
      key,
      label: group[0].name,
      reason: 'same-name',
      files: sortMediaFiles(group, 'date-desc'),
    });
  }

  for (const [size, group] of bySize) {
    if (group.length < 2) continue;
    const stems = new Set(group.map((f) => stemKey(f.name)));
    if (stems.size === group.length && group.length > 2) continue;
    const key = `size:${size}`;
    if (usedKeys.has(key)) continue;
    const alreadyInNameGroup = group.every((f) =>
      groups.some((g) => g.reason === 'same-name' && g.files.some((x) => x.objectName === f.objectName))
    );
    if (alreadyInNameGroup) continue;
    usedKeys.add(key);
    groups.push({
      key,
      label: `${group.length} arquivos · ${formatBytes(size)}`,
      reason: 'same-size',
      files: sortMediaFiles(group, 'date-desc'),
    });
  }

  for (const [stem, group] of byStem) {
    if (group.length < 2) continue;
    const uniqueNames = new Set(group.map((f) => f.name.toLowerCase()));
    if (uniqueNames.size === 1) continue;
    const key = `stem:${stem}`;
    if (usedKeys.has(key)) continue;
    const overlap = group.some((f) =>
      groups.some((g) => g.files.some((x) => x.objectName === f.objectName))
    );
    if (overlap) continue;
    usedKeys.add(key);
    groups.push({
      key,
      label: stem,
      reason: 'same-stem',
      files: sortMediaFiles(group, 'date-desc'),
    });
  }

  return groups.sort((a, b) => b.files.length - a.files.length || a.label.localeCompare(b.label, 'pt-BR'));
}

export function duplicateCountMap(groups: DuplicateGroup[]): Map<string, number> {
  const map = new Map<string, number>();
  for (const group of groups) {
    for (const file of group.files) {
      map.set(file.objectName, group.files.length);
    }
  }
  return map;
}

export function filterDuplicateFiles(files: MediaFile[], groups: DuplicateGroup[]): MediaFile[] {
  const ids = new Set(groups.flatMap((g) => g.files.map((f) => f.objectName)));
  return files.filter((f) => ids.has(f.objectName));
}

export const SORT_OPTIONS: { value: MediaSort; label: string }[] = [
  { value: 'date-desc', label: 'Mais recentes' },
  { value: 'date-asc', label: 'Mais antigos' },
  { value: 'size-desc', label: 'Maior tamanho' },
  { value: 'size-asc', label: 'Menor tamanho' },
  { value: 'name-asc', label: 'Nome A–Z' },
  { value: 'name-desc', label: 'Nome Z–A' },
];

export const SIZE_FILTER_OPTIONS: { value: MediaSizeFilter; label: string }[] = [
  { value: 'all', label: 'Qualquer tamanho' },
  { value: 'xs', label: '< 100 KB' },
  { value: 'sm', label: '100 KB – 500 KB' },
  { value: 'md', label: '500 KB – 2 MB' },
  { value: 'lg', label: '≥ 2 MB' },
];
