import { useEffect, useState } from 'react';
import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { Loader2, LogOut } from 'lucide-react';
import { getToken, fetchMe, setToken } from '../../lib/api';
import { Link } from 'react-router-dom';
import { BrandLogo } from '../ui/BrandLogo';
import type { AdminUser } from '../../types/user';
import { PORTAL_CLIENT_PROCESS, PORTAL_STAFF_HOME } from '../../lib/portal-routes';

interface PortalGuardProps {
  onUser?: (user: AdminUser | null) => void;
}

export function PortalGuard({ onUser }: PortalGuardProps) {
  const location = useLocation();
  const [checking, setChecking] = useState(true);
  const [user, setUser] = useState<AdminUser | null>(null);

  useEffect(() => {
    const token = getToken();
    if (!token) {
      setChecking(false);
      onUser?.(null);
      return;
    }
    fetchMe()
      .then((u) => {
        if (u.role !== 'cliente') {
          setUser(null);
          onUser?.(null);
        } else {
          setUser(u);
          onUser?.(u);
        }
      })
      .catch(() => {
        setToken(null);
        onUser?.(null);
      })
      .finally(() => setChecking(false));
  }, [location.pathname, onUser]);

  if (checking) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-brand-50">
        <Loader2 className="animate-spin text-brand-500" size={32} />
      </div>
    );
  }

  if (!user) {
    const token = getToken();
    if (token) {
      return <Navigate to={PORTAL_STAFF_HOME} replace />;
    }
    return <Navigate to="/entrar" replace state={{ from: location.pathname }} />;
  }

  return (
    <div className="min-h-screen bg-brand-50">
      <header className="bg-brand-800 text-white">
        <div className="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
          <Link to={PORTAL_CLIENT_PROCESS}>
            <BrandLogo
              src="/assets/logo/logo-header.png"
              alt="Andrade Isenções"
              imgClassName="h-8 brightness-0 invert"
              markClassName="text-brand-100"
            />
          </Link>
          <div className="flex items-center gap-4">
            <span className="text-sm text-brand-100 hidden sm:inline">{user.name}</span>
            <button
              type="button"
              onClick={() => { setToken(null); window.location.href = '/entrar'; }}
              className="flex items-center gap-1 text-sm text-brand-200 hover:text-white"
            >
              <LogOut size={16} /> Sair
            </button>
          </div>
        </div>
      </header>
      <main className="max-w-4xl mx-auto px-4 py-6">
        <Outlet context={{ user }} />
      </main>
    </div>
  );
}
