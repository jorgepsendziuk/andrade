import { Navigate, Outlet, useLocation } from 'react-router-dom';
import { useEffect, useState } from 'react';
import { Loader2 } from 'lucide-react';
import { getToken, fetchMe, setToken } from '../../lib/api';
import { useCms } from '../../context/CmsContext';
import type { AdminUser } from '../../types/user';
import { PORTAL_CLIENT_PROCESS } from '../../lib/portal-routes';

interface AdminGuardProps {
  children?: React.ReactNode;
  onUser?: (user: AdminUser | null) => void;
}

export function AdminGuard({ children, onUser }: AdminGuardProps) {
  const location = useLocation();
  const { logout } = useCms();
  const [checking, setChecking] = useState(true);
  const [authed, setAuthed] = useState(false);

  useEffect(() => {
    const token = getToken();
    if (!token) {
      setAuthed(false);
      setChecking(false);
      onUser?.(null);
      return;
    }
    fetchMe()
      .then((user) => {
        if (user.role === 'cliente') {
          setAuthed(false);
          onUser?.(null);
          return;
        }
        setAuthed(true);
        onUser?.(user);
      })
      .catch(() => {
        setToken(null);
        logout();
        setAuthed(false);
        onUser?.(null);
      })
      .finally(() => setChecking(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [location.pathname]);

  if (checking) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-slate-100">
        <Loader2 className="animate-spin text-brand-500" size={32} />
      </div>
    );
  }

  if (!authed) {
    const token = getToken();
    if (token) {
      return <Navigate to={PORTAL_CLIENT_PROCESS} replace />;
    }
    return <Navigate to="/entrar" replace state={{ from: location.pathname }} />;
  }

  return children ? <>{children}</> : <Outlet />;
}
