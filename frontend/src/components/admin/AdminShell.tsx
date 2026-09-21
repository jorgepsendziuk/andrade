import { useEffect, useState } from 'react';
import { AdminGuard } from './AdminGuard';
import { AdminLayout } from './AdminLayout';
import { fetchAdminStats, fetchMe } from '../../lib/api';
import { fetchAdminAlertsCount } from '../../lib/portal-api';
import type { AdminUser } from '../../types/user';

export function AdminShell() {
  const [user, setUser] = useState<AdminUser | null>(null);
  const [newContacts, setNewContacts] = useState(0);
  const [unreadAlerts, setUnreadAlerts] = useState(0);

  useEffect(() => {
    if (!user) return;
    const load = () => {
      fetchAdminStats()
        .then((s) => setNewContacts(s.contacts.new))
        .catch(() => {});
      if (user.role === 'admin' || user.role === 'comercial') {
        fetchAdminAlertsCount()
          .then(setUnreadAlerts)
          .catch(() => {});
      }
    };
    load();
    window.addEventListener('focus', load);
    return () => window.removeEventListener('focus', load);
  }, [user]);

  return (
    <AdminGuard onUser={setUser}>
      <AdminLayout
        user={user}
        newContacts={newContacts}
        unreadAlerts={unreadAlerts}
        onLogout={() => setUser(null)}
      />
    </AdminGuard>
  );
}

export function useAdminUser() {
  const [user, setUser] = useState<AdminUser | null>(null);
  useEffect(() => {
    fetchMe().then(setUser).catch(() => setUser(null));
  }, []);
  return user;
}
