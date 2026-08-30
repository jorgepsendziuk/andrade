import { BetaAnalyticsDataClient } from '@google-analytics/data';
import type { Ga4DashboardData } from './types/ga4.js';

const PROPERTY_ID = process.env.GA4_PROPERTY_ID || '347102827';
const PROPERTY = `properties/${PROPERTY_ID}`;

const CACHE_TTL_MS = 5 * 60 * 1000;
const REALTIME_CACHE_TTL_MS = 30 * 1000;

let client: BetaAnalyticsDataClient | null | undefined;
let cached: Ga4DashboardData | null = null;
let cachedAt = 0;

function parseMetricValue(value: string | null | undefined): number {
  const n = Number(value ?? 0);
  return Number.isFinite(n) ? n : 0;
}

function getClient(): BetaAnalyticsDataClient | null {
  if (client !== undefined) return client;

  try {
    const json = process.env.GA4_SERVICE_ACCOUNT_JSON?.trim();
    if (json) {
      client = new BetaAnalyticsDataClient({ credentials: JSON.parse(json) });
      return client;
    }
    client = new BetaAnalyticsDataClient();
    return client;
  } catch (err) {
    console.error('GA4 client init error', err);
    client = null;
    return null;
  }
}

function notConfigured(message: string, hint?: string): Ga4DashboardData {
  return {
    configured: false,
    propertyId: PROPERTY_ID,
    error: message,
    setupHint: hint,
    activeUsers: null,
    users7d: null,
    sessions7d: null,
    pageviews7d: null,
    topPages: [],
    trafficSources: [],
    dailyUsers: [],
    fetchedAt: null,
  };
}

function ga4SetupHint(): string {
  const projectNumber = process.env.GCP_PROJECT_NUMBER;
  const sa = projectNumber
    ? `${projectNumber}-compute@developer.gserviceaccount.com`
    : 'SERVICE_ACCOUNT_DO_CLOUD_RUN';
  return `Adicione ${sa} como Leitor na propriedade GA4 (${PROPERTY_ID}) em Admin → Acesso à propriedade.`;
}

async function fetchRealtimeActiveUsers(gaClient: BetaAnalyticsDataClient): Promise<number> {
  const [response] = await gaClient.runRealtimeReport({
    property: PROPERTY,
    metrics: [{ name: 'activeUsers' }],
  });
  return parseMetricValue(response.rows?.[0]?.metricValues?.[0]?.value);
}

async function fetchWeeklySummary(gaClient: BetaAnalyticsDataClient) {
  const [response] = await gaClient.runReport({
    property: PROPERTY,
    dateRanges: [{ startDate: '7daysAgo', endDate: 'today' }],
    metrics: [
      { name: 'activeUsers' },
      { name: 'sessions' },
      { name: 'screenPageViews' },
    ],
  });

  const values = response.rows?.[0]?.metricValues ?? [];
  return {
    users7d: parseMetricValue(values[0]?.value),
    sessions7d: parseMetricValue(values[1]?.value),
    pageviews7d: parseMetricValue(values[2]?.value),
  };
}

async function fetchTopPages(gaClient: BetaAnalyticsDataClient) {
  const [response] = await gaClient.runReport({
    property: PROPERTY,
    dateRanges: [{ startDate: '7daysAgo', endDate: 'today' }],
    dimensions: [{ name: 'pagePath' }],
    metrics: [{ name: 'screenPageViews' }],
    orderBys: [{ metric: { metricName: 'screenPageViews' }, desc: true }],
    limit: 5,
  });

  return (response.rows ?? []).map((row) => ({
    path: row.dimensionValues?.[0]?.value || '/',
    views: parseMetricValue(row.metricValues?.[0]?.value),
  }));
}

async function fetchTrafficSources(gaClient: BetaAnalyticsDataClient) {
  const [response] = await gaClient.runReport({
    property: PROPERTY,
    dateRanges: [{ startDate: '7daysAgo', endDate: 'today' }],
    dimensions: [{ name: 'sessionDefaultChannelGroup' }],
    metrics: [{ name: 'sessions' }],
    orderBys: [{ metric: { metricName: 'sessions' }, desc: true }],
    limit: 5,
  });

  return (response.rows ?? []).map((row) => ({
    channel: row.dimensionValues?.[0]?.value || 'Desconhecido',
    sessions: parseMetricValue(row.metricValues?.[0]?.value),
  }));
}

async function fetchDailyUsers(gaClient: BetaAnalyticsDataClient) {
  const [response] = await gaClient.runReport({
    property: PROPERTY,
    dateRanges: [{ startDate: '7daysAgo', endDate: 'today' }],
    dimensions: [{ name: 'date' }],
    metrics: [{ name: 'activeUsers' }, { name: 'sessions' }],
    orderBys: [{ dimension: { dimensionName: 'date' } }],
  });

  return (response.rows ?? []).map((row) => {
    const raw = row.dimensionValues?.[0]?.value || '';
    const date =
      raw.length === 8 ? `${raw.slice(0, 4)}-${raw.slice(4, 6)}-${raw.slice(6, 8)}` : raw;
    return {
      date,
      users: parseMetricValue(row.metricValues?.[0]?.value),
      sessions: parseMetricValue(row.metricValues?.[1]?.value),
    };
  });
}

function isPermissionError(err: unknown): boolean {
  const message = err instanceof Error ? err.message : String(err);
  return /permission|PERMISSION_DENIED|403|insufficient/i.test(message);
}

function isApiDisabledError(err: unknown): boolean {
  const message = err instanceof Error ? err.message : String(err);
  return /analyticsdata|API has not been used|SERVICE_DISABLED/i.test(message);
}

export async function getGa4DashboardData(force = false): Promise<Ga4DashboardData> {
  const now = Date.now();
  if (!force && cached && now - cachedAt < CACHE_TTL_MS) {
    return cached;
  }

  const gaClient = getClient();
  if (!gaClient) {
    return notConfigured(
      'Credenciais GA4 não configuradas.',
      'Defina GA4_SERVICE_ACCOUNT_JSON ou use Application Default Credentials no servidor.'
    );
  }

  try {
    const useRealtimeCache = !force && cached && now - cachedAt < REALTIME_CACHE_TTL_MS;
    const activeUsers = useRealtimeCache && cached?.activeUsers != null
      ? cached.activeUsers
      : await fetchRealtimeActiveUsers(gaClient);

    const [summary, topPages, trafficSources, dailyUsers] = await Promise.all([
      fetchWeeklySummary(gaClient),
      fetchTopPages(gaClient),
      fetchTrafficSources(gaClient),
      fetchDailyUsers(gaClient),
    ]);

    const data: Ga4DashboardData = {
      configured: true,
      propertyId: PROPERTY_ID,
      activeUsers,
      users7d: summary.users7d,
      sessions7d: summary.sessions7d,
      pageviews7d: summary.pageviews7d,
      topPages,
      trafficSources,
      dailyUsers,
      fetchedAt: new Date().toISOString(),
    };

    cached = data;
    cachedAt = now;
    return data;
  } catch (err) {
    console.error('GA4 fetch error', err);

    if (isApiDisabledError(err)) {
      return notConfigured(
        'Google Analytics Data API não está ativada no projeto GCP.',
        'Execute: gcloud services enable analyticsdata.googleapis.com'
      );
    }

    if (isPermissionError(err)) {
      return notConfigured('Sem permissão para ler a propriedade GA4.', ga4SetupHint());
    }

    const message = err instanceof Error ? err.message : 'Falha ao consultar GA4';
    return notConfigured(message, ga4SetupHint());
  }
}

export function isGa4Configured(): boolean {
  return Boolean(getClient());
}
