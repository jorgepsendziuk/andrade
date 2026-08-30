import { useEffect, useState } from 'react';
import { AdminGuard } from './AdminGuard';
import { AdminLayout } from './AdminLayout';
import { fetchAdminStats, fetchMe } from '../../lib/api';
import type { AdminUser } from '../../types/user';

export function AdminShell() {
  const [user, setUser] = useState<AdminUser | null>(null);
  const [newContacts, setNewContacts] = useState(0);

  useEffect(() => {
    if (!user) return;
    fetchAdminStats()
      .then((s) => setNewContacts(s.contacts.new))
      .catch(() => {});
  }, [user]);

  return (
    <AdminGuard onUser={setUser}>
      <AdminLayout user={user} newContacts={newContacts} onLogout={() => setUser(null)} />
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
