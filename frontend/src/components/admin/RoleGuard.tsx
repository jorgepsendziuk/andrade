import { Navigate, Outlet } from 'react-router-dom';
import type { UserRole } from '../../types/user';
import { PORTAL_STAFF_HOME } from '../../lib/portal-routes';
import { useAdminUser } from './AdminShell';

interface RoleGuardProps {
  roles: UserRole[];
  children?: React.ReactNode;
}

export function RoleGuard({ roles, children }: RoleGuardProps) {
  const user = useAdminUser();

  if (!user) {
    return (
      <div className="flex items-center justify-center py-20 text-slate-500 text-sm">
        Carregando permissões…
      </div>
    );
  }

  if (!roles.includes(user.role as UserRole)) {
    return <Navigate to={PORTAL_STAFF_HOME} replace />;
  }

  return children ? <>{children}</> : <Outlet />;
}
