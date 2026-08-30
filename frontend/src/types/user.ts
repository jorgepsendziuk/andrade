export type UserRole = 'admin' | 'editor' | 'comercial';
export type AuthRole = UserRole | 'cliente';

import type { ContactStatus } from './contact';

export type { ContactStatus };

export interface AdminUser {
  id: string;
  email: string;
  name: string;
  role: AuthRole;
  active?: boolean;
  mustChangePassword?: boolean;
  createdAt?: string;
  updatedAt?: string;
}

export interface AdminStats {
  contacts: {
    total: number;
    new: number;
    read: number;
    archived: number;
    today: number;
    emailSentRate: number;
    last7Days: { date: string; count: number }[];
  };
  recentContacts: {
    id: string;
    name: string;
    email: string;
    createdAt: string;
    status: ContactStatus;
    emailSent: boolean;
  }[];
  site: {
    title: string;
    sectionsTotal: number;
    sectionsEnabled: number;
    lastUpdated: string | null;
  };
  content: {
    conditions: number;
    guiaArticles: number;
  };
  integrations: {
    email: { ok: boolean; message: string | null };
    ga4: {
      configured: boolean;
      activeUsers: number | null;
      users7d: number | null;
      sessions7d: number | null;
      pageviews7d: number | null;
      error?: string;
    };
    instagram: boolean;
    googleReviews: boolean;
  };
  users?: {
    total: number;
    active: number;
  };
  system: {
    siteUrl: string;
    contactsStorage: 'firestore' | 'file';
  };
}
