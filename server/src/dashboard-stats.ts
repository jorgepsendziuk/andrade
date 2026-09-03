import { readConditions, readGuiaArticles } from './content-data.js';
import { getContactStats, getContactsStorageMode, listContacts } from './contacts-store.js';
import { getSiteContentMeta } from './content-store.js';
import { getGa4DashboardData } from './ga4-analytics.js';
import { getAdminSettings } from './settings-store.js';
import { listUsers } from './users-store.js';
import type { ContactSubmission } from './types/contact.js';

export interface DashboardRecentContact {
  id: string;
  name: string;
  email: string;
  createdAt: string;
  status: ContactSubmission['status'];
  emailSent: boolean;
}

export interface DashboardStats {
  contacts: Awaited<ReturnType<typeof getContactStats>>;
  recentContacts: DashboardRecentContact[];
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

function mapRecentContact(c: ContactSubmission): DashboardRecentContact {
  return {
    id: c.id,
    name: c.name,
    email: c.email,
    createdAt: c.createdAt,
    status: c.status,
    emailSent: c.emailSent,
  };
}

export async function getDashboardStats(includeUsers = false): Promise<DashboardStats> {
  const [contactStats, recentContacts, siteMeta, settings, ga4] = await Promise.all([
    getContactStats(),
    listContacts(5),
    getSiteContentMeta(),
    getAdminSettings(),
    getGa4DashboardData(),
  ]);

  const [conditions, guiaArticles] = await Promise.all([readConditions(), readGuiaArticles()]);

  const stats: DashboardStats = {
    contacts: contactStats,
    recentContacts: recentContacts.map(mapRecentContact),
    site: {
      title: siteMeta.title,
      sectionsTotal: siteMeta.sectionsTotal,
      sectionsEnabled: siteMeta.sectionsEnabled,
      lastUpdated: siteMeta.updatedAt,
    },
    content: {
      conditions: Array.isArray(conditions) ? conditions.length : 0,
      guiaArticles: Array.isArray(guiaArticles) ? guiaArticles.length : 0,
    },
    integrations: {
      email: {
        ok: settings.emailStatus.ok,
        message: settings.emailStatus.message,
      },
      ga4: {
        configured: ga4.configured,
        activeUsers: ga4.activeUsers,
        users7d: ga4.users7d,
        sessions7d: ga4.sessions7d,
        pageviews7d: ga4.pageviews7d,
        error: ga4.error,
      },
      instagram: settings.integrationStatus.instagram.configured,
      googleReviews: settings.integrationStatus.googleReviews.configured,
    },
    system: {
      siteUrl: settings.integrations.siteUrl || 'https://andradeisencoes.com.br',
      contactsStorage: getContactsStorageMode(),
    },
  };

  if (includeUsers) {
    const users = await listUsers();
    stats.users = {
      total: users.length,
      active: users.filter((u) => u.active).length,
    };
  }

  return stats;
}
