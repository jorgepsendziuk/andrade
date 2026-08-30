export interface EmailSettingsView {
  smtpHost: string;
  smtpPort: number;
  smtpSecure: boolean;
  smtpUser: string;
  smtpFrom: string;
  contactEmail: string;
  smtpPassConfigured: boolean;
  source: 'env' | 'settings' | 'mixed';
}

export interface IntegrationSettings {
  googlePlaceId: string;
  instagramUsername: string;
  instagramProfileUrl: string;
  siteUrl: string;
}

export interface IntegrationStatus {
  googleReviews: { configured: boolean; source: string };
  instagram: { configured: boolean; source: string };
  analytics: { configured: boolean; measurementId: string | null };
}

export interface AdminSettingsResponse {
  email: EmailSettingsView;
  emailStatus: { ok: boolean; message: string | null };
  integrations: IntegrationSettings;
  integrationStatus: IntegrationStatus;
  updatedAt: string | null;
}
