export interface MediaFile {
  objectName: string;
  url: string;
  name: string;
  folder: string;
  size: number;
  contentType: string;
  updatedAt: string;
  inUse: boolean;
}

export interface MediaListResponse {
  files: MediaFile[];
  total: number;
  storage: 'gcs' | 'local';
}
