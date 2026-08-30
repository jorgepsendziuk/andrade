import { useState } from 'react';
import { Link, Outlet } from 'react-router-dom';
import { ExternalLink, LogOut, Menu, X } from 'lucide-react';
import { AdminNav } from './AdminNav';
import { setToken } from '../../lib/api';
import type { AdminUser } from '../../types/user';

interface AdminLayoutProps {
  user: AdminUser | null;
  newContacts?: number;
  onLogout?: () => void;
}

export function AdminLayout({ user, newContacts = 0, onLogout }: AdminLayoutProps) {
  const [sidebarOpen, setSidebarOpen] = useState(false);

  const handleLogout = () => {
    setToken(null);
    onLogout?.();
    window.location.href = '/entrar';
  };

  return (
    <div className="min-h-screen bg-slate-100 flex">
      {sidebarOpen && (
        <button
          type="button"
          className="fixed inset-0 bg-black/40 z-40 lg:hidden"
          onClick={() => setSidebarOpen(false)}
          aria-label="Fechar menu"
        />
      )}

      <aside
        className={`fixed lg:static inset-y-0 left-0 z-50 w-64 bg-brand-800 text-white flex flex-col transform transition-transform lg:translate-x-0 ${
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        <div className="p-5 border-b border-brand-700">
          <p className="font-bold text-lg">Portal Andrade</p>
          <p className="text-brand-300 text-xs mt-0.5">Gestão e acompanhamento</p>
        </div>
        <div className="flex-1 p-4 overflow-y-auto">
          <AdminNav
            newContacts={newContacts}
            role={user?.role}
            onNavigate={() => setSidebarOpen(false)}
          />
        </div>
        <div className="p-4 border-t border-brand-700 space-y-2">
          <Link
            to="/"
            target="_blank"
            className="flex items-center gap-2 text-sm text-brand-200 hover:text-white px-3 py-2"
          >
            <ExternalLink size={16} />
            Ver site público
          </Link>
        </div>
      </aside>

      <div className="flex-1 flex flex-col min-w-0">
        <header className="bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between gap-4 sticky top-0 z-30">
          <button
            type="button"
            className="lg:hidden p-2 rounded-lg hover:bg-slate-100"
            onClick={() => setSidebarOpen(true)}
            aria-label="Abrir menu"
          >
            <Menu size={22} />
          </button>
          <div className="flex-1" />
          <div className="flex items-center gap-3">
            <div className="text-right hidden sm:block">
              <p className="text-sm font-medium text-slate-800">{user?.name}</p>
              <p className="text-xs text-slate-500">{user?.email}</p>
            </div>
            <button
              type="button"
              onClick={handleLogout}
              className="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100"
            >
              <LogOut size={16} />
              <span className="hidden sm:inline">Sair</span>
            </button>
          </div>
          {sidebarOpen && (
            <button
              type="button"
              className="lg:hidden absolute top-3 right-3 p-2"
              onClick={() => setSidebarOpen(false)}
            >
              <X size={22} />
            </button>
          )}
        </header>

        <main className="flex-1 p-4 md:p-6 overflow-auto">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
