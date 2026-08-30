export interface Ga4TopPage {
  path: string;
  views: number;
}

export interface Ga4TrafficSource {
  channel: string;
  sessions: number;
}

export interface Ga4DailyMetric {
  date: string;
  users: number;
  sessions: number;
}

export interface Ga4DashboardData {
  configured: boolean;
  propertyId: string;
  error?: string;
  setupHint?: string;
  activeUsers: number | null;
  users7d: number | null;
  sessions7d: number | null;
  pageviews7d: number | null;
  topPages: Ga4TopPage[];
  trafficSources: Ga4TrafficSource[];
  dailyUsers: Ga4DailyMetric[];
  fetchedAt: string | null;
}
