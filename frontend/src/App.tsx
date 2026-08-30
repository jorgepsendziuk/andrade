import { Routes, Route, Navigate } from 'react-router-dom';
import { CmsProvider } from './context/CmsContext';
import { AccessibilityProvider } from './context/AccessibilityContext';
import { HomePage } from './pages/HomePage';
import { LoginPage } from './pages/LoginPage';
import { ConditionPage } from './pages/ConditionPage';
import { GuiaPage } from './pages/GuiaPage';
import { ArticlePage } from './pages/ArticlePage';
import { StartProcessPage } from './pages/StartProcessPage';
import { PortalAccountPage } from './pages/PortalAccountPage';
import { PrivacyPage } from './pages/PrivacyPage';
import { TermsPage } from './pages/TermsPage';
import { CookiesPage } from './pages/CookiesPage';
import { AdminShell } from './components/admin/AdminShell';
import { PortalGuard } from './components/portal/PortalGuard';
import { AdminDashboardPage } from './pages/admin/AdminDashboardPage';
import { AdminContactsPage } from './pages/admin/AdminContactsPage';
import { AdminSitePage } from './pages/admin/AdminSitePage';
import { AdminUsersPage } from './pages/admin/AdminUsersPage';
import { AdminAnalyticsPage } from './pages/admin/AdminAnalyticsPage';
import { AdminSettingsPage } from './pages/admin/AdminSettingsPage';
import { AdminInfraPage } from './pages/admin/AdminInfraPage';
import { AdminMediaPage } from './pages/admin/AdminMediaPage';
import { AdminProcessesPage } from './pages/admin/AdminProcessesPage';
import { AdminProcessDetailPage } from './pages/admin/AdminProcessDetailPage';
import { AdminDocsBrowserPage } from './pages/admin/AdminDocsBrowserPage';
import { RoleGuard } from './components/admin/RoleGuard';
import { LegacyAdminRedirect, LegacyContaRedirect } from './components/routing/LegacyPortalRedirects';
import { PORTAL_LOGIN } from './lib/portal-routes';

export default function App() {
  return (
    <AccessibilityProvider>
      <CmsProvider>
        <Routes>
          <Route path="/" element={<HomePage />} />
          <Route path="/isencao-pcd/:slug" element={<ConditionPage />} />
          <Route path="/guia" element={<GuiaPage />} />
          <Route path="/guia/:slug" element={<ArticlePage />} />
          <Route path="/iniciar" element={<StartProcessPage />} />
          <Route path="/privacidade" element={<PrivacyPage />} />
          <Route path="/termos" element={<TermsPage />} />
          <Route path="/cookies" element={<CookiesPage />} />
          <Route path={PORTAL_LOGIN} element={<LoginPage />} />

          {/* Legado */}
          <Route path="/conta" element={<LegacyContaRedirect />} />
          <Route path="/admin/*" element={<LegacyAdminRedirect />} />

          {/* Cliente */}
          <Route path="/portal/meu-processo" element={<PortalGuard />}>
            <Route index element={<PortalAccountPage />} />
          </Route>

          {/* Equipe */}
          <Route path="/portal">
            <Route index element={<Navigate to={PORTAL_LOGIN} replace />} />
            <Route element={<AdminShell />}>
              <Route path="inicio" element={<AdminDashboardPage />} />
              <Route path="contatos" element={<AdminContactsPage />} />
              <Route path="processos" element={<RoleGuard roles={['admin', 'comercial']}><AdminProcessesPage /></RoleGuard>} />
              <Route path="processos/:id" element={<RoleGuard roles={['admin', 'comercial']}><AdminProcessDetailPage /></RoleGuard>} />
              <Route path="arquivos" element={<RoleGuard roles={['admin', 'comercial']}><AdminDocsBrowserPage /></RoleGuard>} />
              <Route path="site" element={<RoleGuard roles={['admin', 'editor']}><AdminSitePage /></RoleGuard>} />
              <Route path="midia" element={<RoleGuard roles={['admin', 'editor']}><AdminMediaPage /></RoleGuard>} />
              <Route path="configuracoes" element={<RoleGuard roles={['admin', 'editor']}><AdminSettingsPage /></RoleGuard>} />
              <Route path="usuarios" element={<RoleGuard roles={['admin']}><AdminUsersPage /></RoleGuard>} />
              <Route path="analytics" element={<RoleGuard roles={['admin', 'editor']}><AdminAnalyticsPage /></RoleGuard>} />
              <Route path="infraestrutura" element={<RoleGuard roles={['admin']}><AdminInfraPage /></RoleGuard>} />
            </Route>
          </Route>

          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </CmsProvider>
    </AccessibilityProvider>
  );
}
