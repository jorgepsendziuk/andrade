import type { BillingSummary, ExchangeRate } from '../types/infra';

export function formatMoney(amount: number, currency: 'USD' | 'BRL'): string {
  try {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount);
  } catch {
    return currency === 'BRL' ? `R$ ${amount.toFixed(2)}` : `US$ ${amount.toFixed(2)}`;
  }
}

export function toUsd(amount: number, currency: string, rate: number): number {
  if (currency === 'BRL') return Math.round((amount / rate) * 100) / 100;
  return amount;
}

export function toBrl(amount: number, currency: string, rate: number): number {
  if (currency === 'USD') return Math.round(amount * rate * 100) / 100;
  return amount;
}

export function formatDualCost(
  amount: number,
  currency: string,
  exchangeRate: ExchangeRate
): { primary: string; secondary: string } {
  const usd = toUsd(amount, currency, exchangeRate.usdToBrl);
  const brl = toBrl(amount, currency, exchangeRate.usdToBrl);
  return {
    primary: formatMoney(usd, 'USD'),
    secondary: formatMoney(brl, 'BRL'),
  };
}

export function formatExchangeRateLabel(rate: ExchangeRate): string {
  const date = rate.fetchedAt.includes('T')
    ? new Date(rate.fetchedAt).toLocaleString('pt-BR')
    : rate.fetchedAt;
  return `US$ 1 = ${formatMoney(rate.usdToBrl, 'BRL')} · ${rate.source} · ${date}`;
}

export function getBillingTotals(billing: BillingSummary) {
  return {
    usd: formatMoney(billing.totalUsd, 'USD'),
    brl: formatMoney(billing.totalBrl, 'BRL'),
  };
}
