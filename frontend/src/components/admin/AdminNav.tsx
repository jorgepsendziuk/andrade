import { NavLink } from 'react-router-dom';
import {
  BarChart3,
  Bell,
  BookOpen,
  ClipboardList,
  Cloud,
  FolderOpen,
  HeartPulse,
  Image,
  Inbox,
  LayoutDashboard,
  Pencil,
  ScrollText,
  Settings,
  UserCircle,
  Users,
} from 'lucide-react';

import type { AuthRole, UserRole } from '../../types/user';
import { PORTAL_STAFF } from '../../lib/portal-routes';

const NAV: {
  to: string;
  label: string;
  icon: typeof LayoutDashboard;
  badgeKey?: 'contacts' | 'alerts';
  roles: UserRole[];
}[] = [
  { to: PORTAL_STAFF.home, label: 'Início', icon: LayoutDashboard, roles: ['admin', 'editor', 'comercial'] },
  { to: PORTAL_STAFF.contacts, label: 'Contatos', icon: Inbox, badgeKey: 'contacts', roles: ['admin', 'editor', 'comercial'] },
  { to: PORTAL_STAFF.alerts, label: 'Alertas', icon: Bell, badgeKey: 'alerts', roles: ['admin', 'comercial'] },
  { to: PORTAL_STAFF.clients, label: 'Clientes', icon: UserCircle, roles: ['admin', 'comercial'] },
  { to: PORTAL_STAFF.processes, label: 'Processos PCD', icon: ClipboardList, roles: ['admin', 'comercial'] },
  { to: PORTAL_STAFF.files, label: 'Arquivos', icon: FolderOpen, roles: ['admin', 'comercial'] },
  { to: PORTAL_STAFF.audit, label: 'Histórico', icon: ScrollText, roles: ['admin', 'comercial'] },
  { to: PORTAL_STAFF.site, label: 'Editar site', icon: Pencil, roles: ['admin', 'editor'] },
  { to: PORTAL_STAFF.conditions, label: 'Condições PCD', icon: HeartPulse, roles: ['admin', 'editor'] },
  { to: PORTAL_STAFF.guia, label: 'Guia PCD', icon: BookOpen, roles: ['admin', 'editor'] },
  { to: PORTAL_STAFF.media, label: 'Mídia', icon: Image, roles: ['admin', 'editor'] },
  { to: PORTAL_STAFF.settings, label: 'Configurações', icon: Settings, roles: ['admin', 'editor'] },
  { to: PORTAL_STAFF.users, label: 'Usuários', icon: Users, roles: ['admin'] },
  { to: PORTAL_STAFF.analytics, label: 'Analytics', icon: BarChart3, roles: ['admin', 'editor'] },
  { to: PORTAL_STAFF.infra, label: 'Infraestrutura', icon: Cloud, roles: ['admin'] },
];

interface AdminNavProps {
  newContacts?: number;
  unreadAlerts?: number;
  role?: AuthRole;
  onNavigate?: () => void;
}

export function AdminNav({ newContacts = 0, unreadAlerts = 0, role: userRole = 'comercial', onNavigate }: AdminNavProps) {
  return (
    <nav className="space-y-1">
      {NAV.filter((item) => userRole === 'cliente' ? false : item.roles.includes(userRole as UserRole)).map(({ to, label, icon: Icon, badgeKey }) => (
        <NavLink
          key={to}
          to={to}
          onClick={onNavigate}
          className={({ isActive }) =>
            `flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors ${
              isActive
                ? 'bg-brand-500 text-white'
                : 'text-slate-300 hover:bg-brand-700 hover:text-white'
            }`
          }
        >
          <Icon size={18} />
          <span className="flex-1">{label}</span>
          {badgeKey === 'contacts' && newContacts > 0 && (
            <span className="bg-accent text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[1.25rem] text-center">
              {newContacts > 99 ? '99+' : newContacts}
            </span>
          )}
          {badgeKey === 'alerts' && unreadAlerts > 0 && (
            <span className="bg-amber-400 text-amber-950 text-xs font-bold px-2 py-0.5 rounded-full min-w-[1.25rem] text-center">
              {unreadAlerts > 99 ? '99+' : unreadAlerts}
            </span>
          )}
        </NavLink>
      ))}
    </nav>
  );
}
