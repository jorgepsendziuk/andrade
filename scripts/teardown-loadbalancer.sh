#!/usr/bin/env bash
# Remove Load Balancer após migração para Firebase Hosting (economia ~US$ 18/mês)
set -euo pipefail

PROJECT="${GCP_PROJECT:-smart-tractor-257319}"
REGION="${GCP_REGION:-southamerica-east1}"
PREFIX="andrade-isencoes"
DOMAIN="${SITE_DOMAIN:-andradeisencoes.com.br}"

echo "⚠ Isso vai REMOVER o Load Balancer e liberar o IP ${PREFIX}-ip"
echo "  Certifique-se de que ${DOMAIN} já funciona via Firebase Hosting!"
echo ""
if [[ "${1:-}" != "--yes" ]]; then
  read -r -p "Continuar? (s/N) " CONFIRM
  if [[ "${CONFIRM:-}" != "s" && "${CONFIRM:-}" != "S" ]]; then
    echo "Cancelado."
    exit 0
  fi
fi

gcloud config set project "$PROJECT"

echo "→ Removendo forwarding rules..."
gcloud compute forwarding-rules delete "${PREFIX}-https-rule" --global --quiet 2>/dev/null || true
gcloud compute forwarding-rules delete "${PREFIX}-http-rule" --global --quiet 2>/dev/null || true

echo "→ Removendo proxies..."
gcloud compute target-https-proxies delete "${PREFIX}-https-proxy" --global --quiet 2>/dev/null || true
gcloud compute target-http-proxies delete "${PREFIX}-http-proxy" --global --quiet 2>/dev/null || true

echo "→ Removendo URL maps..."
gcloud compute url-maps delete "${PREFIX}-lb" --global --quiet 2>/dev/null || true
gcloud compute url-maps delete "${PREFIX}-http-redirect" --global --quiet 2>/dev/null || true

echo "→ Removendo certificado SSL..."
gcloud compute ssl-certificates delete "${PREFIX}-cert" --global --quiet 2>/dev/null || true

echo "→ Removendo backend service..."
gcloud compute backend-services delete "${PREFIX}-backend" --global --quiet 2>/dev/null || true

echo "→ Removendo NEG..."
gcloud compute network-endpoint-groups delete "${PREFIX}-neg" --region="$REGION" --quiet 2>/dev/null || true

echo "→ Liberando IP global..."
gcloud compute addresses delete "${PREFIX}-ip" --global --quiet 2>/dev/null || true

echo ""
echo "✓ Load Balancer removido"
echo "  Economia estimada: ~US$ 18/mês"
echo "  Domínio deve apontar para Firebase Hosting (npm run migrate:dns-firebase)"
echo ""
