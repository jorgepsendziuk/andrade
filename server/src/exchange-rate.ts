export interface ExchangeRate {
  usdToBrl: number;
  fetchedAt: string;
  source: string;
}

const CACHE_TTL_MS = 60 * 60 * 1000; // 1h
const FALLBACK_USD_BRL = 5.19;

let cached: ExchangeRate | null = null;
let cachedAt = 0;

async function fetchFromAwesomeApi(): Promise<ExchangeRate | null> {
  try {
    const res = await fetch('https://economia.awesomeapi.com.br/json/last/USD-BRL', {
      signal: AbortSignal.timeout(5000),
    });
    if (!res.ok) return null;
    const data = (await res.json()) as {
      USDBRL?: { bid?: string; create_date?: string };
    };
    const bid = Number(data.USDBRL?.bid);
    if (!Number.isFinite(bid) || bid <= 0) return null;
    return {
      usdToBrl: Math.round(bid * 10000) / 10000,
      fetchedAt: data.USDBRL?.create_date ?? new Date().toISOString(),
      source: 'AwesomeAPI (USD-BRL)',
    };
  } catch {
    return null;
  }
}

export async function getUsdToBrlRate(force = false): Promise<ExchangeRate> {
  const now = Date.now();
  if (!force && cached && now - cachedAt < CACHE_TTL_MS) {
    return cached;
  }

  const live = await fetchFromAwesomeApi();
  if (live) {
    cached = live;
    cachedAt = now;
    return live;
  }

  return {
    usdToBrl: FALLBACK_USD_BRL,
    fetchedAt: new Date().toISOString(),
    source: 'Cotação fixa (fallback)',
  };
}

export function convertToBrl(amountUsd: number, rate: number): number {
  return Math.round(amountUsd * rate * 100) / 100;
}

export function convertToUsd(amountBrl: number, rate: number): number {
  return Math.round((amountBrl / rate) * 100) / 100;
}
