#!/usr/bin/env bash
# Mapeia andradeisencoes.com.br → Cloud Run via Load Balancer + SSL gerenciado
# DNS já está no Cloud DNS (zona "andrade") no mesmo projeto GCP
set -euo pipefail

PROJECT="${GCP_PROJECT:-smart-tractor-257319}"
REGION="${GCP_REGION:-southamerica-east1}"
SERVICE="${GCP_SERVICE:-andrade-isencoes}"
DNS_ZONE="${GCP_DNS_ZONE:-andrade}"
DOMAIN="andradeisencoes.com.br"
PREFIX="andrade-isencoes"

gcloud config set project "$PROJECT"
gcloud services enable compute.googleapis.com --quiet

echo "→ Subnet proxy-only (obrigatória para Application LB + Cloud Run)..."
if ! gcloud compute networks subnets describe andrade-proxy-only --region="$REGION" &>/dev/null; then
  gcloud compute networks subnets create andrade-proxy-only \
    --purpose=REGIONAL_MANAGED_PROXY \
    --role=ACTIVE \
    --region="$REGION" \
    --network=default \
    --range=10.0.0.0/23
fi

echo "→ Reservando IP global..."
if ! gcloud compute addresses describe "${PREFIX}-ip" --global &>/dev/null; then
  gcloud compute addresses create "${PREFIX}-ip" --global --ip-version=IPV4
fi
LB_IP="$(gcloud compute addresses describe "${PREFIX}-ip" --global --format='value(address)')"
echo "   IP: $LB_IP"

echo "→ Serverless NEG..."
if ! gcloud compute network-endpoint-groups describe "${PREFIX}-neg" --region="$REGION" &>/dev/null; then
  gcloud compute network-endpoint-groups create "${PREFIX}-neg" \
    --region="$REGION" \
    --network-endpoint-type=serverless \
    --cloud-run-service="$SERVICE"
fi

echo "→ Backend service..."
if ! gcloud compute backend-services describe "${PREFIX}-backend" --global &>/dev/null; then
  gcloud compute backend-services create "${PREFIX}-backend" \
    --global \
    --load-balancing-scheme=EXTERNAL_MANAGED
fi

# Idempotente: add-backend falha se já existe
gcloud compute backend-services add-backend "${PREFIX}-backend" \
  --global \
  --network-endpoint-group="${PREFIX}-neg" \
  --network-endpoint-group-region="$REGION" 2>/dev/null || true

echo "→ URL map..."
if ! gcloud compute url-maps describe "${PREFIX}-lb" --global &>/dev/null; then
  gcloud compute url-maps create "${PREFIX}-lb" \
    --default-service="${PREFIX}-backend"
fi

echo "→ Certificado SSL gerenciado..."
if ! gcloud compute ssl-certificates describe "${PREFIX}-cert" --global &>/dev/null; then
  gcloud compute ssl-certificates create "${PREFIX}-cert" \
    --domains="${DOMAIN},www.${DOMAIN}" \
    --global
fi

echo "→ HTTPS proxy + forwarding rule..."
if ! gcloud compute target-https-proxies describe "${PREFIX}-https-proxy" --global &>/dev/null; then
  gcloud compute target-https-proxies create "${PREFIX}-https-proxy" \
    --url-map="${PREFIX}-lb" \
    --ssl-certificates="${PREFIX}-cert"
fi

if ! gcloud compute forwarding-rules describe "${PREFIX}-https-rule" --global &>/dev/null; then
  gcloud compute forwarding-rules create "${PREFIX}-https-rule" \
    --global \
    --target-https-proxy="${PREFIX}-https-proxy" \
    --address="${PREFIX}-ip" \
    --ports=443
fi

echo "→ Redirect HTTP → HTTPS..."
if ! gcloud compute url-maps describe "${PREFIX}-http-redirect" --global &>/dev/null; then
  gcloud compute url-maps import "${PREFIX}-http-redirect" --global --source /dev/stdin <<'YAML'
name: andrade-isencoes-http-redirect
defaultUrlRedirect:
  redirectResponseCode: MOVED_PERMANENTLY_DEFAULT
  httpsRedirect: true
YAML
fi

if ! gcloud compute target-http-proxies describe "${PREFIX}-http-proxy" --global &>/dev/null; then
  gcloud compute target-http-proxies create "${PREFIX}-http-proxy" \
    --url-map="${PREFIX}-http-redirect"
fi

if ! gcloud compute forwarding-rules describe "${PREFIX}-http-rule" --global &>/dev/null; then
  gcloud compute forwarding-rules create "${PREFIX}-http-rule" \
    --global \
    --target-http-proxy="${PREFIX}-http-proxy" \
    --address="${PREFIX}-ip" \
    --ports=80
fi

echo "→ Atualizando DNS (apex + www → $LB_IP)..."
gcloud dns record-sets transaction start --zone="$DNS_ZONE"
gcloud dns record-sets transaction remove \
  --zone="$DNS_ZONE" \
  --name="${DOMAIN}." \
  --type=A \
  --ttl=300 \
  "34.70.180.143" 2>/dev/null || true
gcloud dns record-sets transaction add \
  --zone="$DNS_ZONE" \
  --name="${DOMAIN}." \
  --type=A \
  --ttl=300 \
  "$LB_IP"
gcloud dns record-sets transaction remove \
  --zone="$DNS_ZONE" \
  --name="www.${DOMAIN}." \
  --type=A \
  --ttl=300 \
  "34.70.180.143" 2>/dev/null || true
gcloud dns record-sets transaction add \
  --zone="$DNS_ZONE" \
  --name="www.${DOMAIN}." \
  --type=A \
  --ttl=300 \
  "$LB_IP"
gcloud dns record-sets transaction execute --zone="$DNS_ZONE"

echo ""
echo "✓ Load Balancer configurado"
echo "  IP: $LB_IP"
echo "  DNS atualizado: ${DOMAIN} e www.${DOMAIN} → $LB_IP"
echo ""
echo "⏳ Certificado SSL: provisionamento leva 15–60 min após DNS propagar."
echo "   Status: gcloud compute ssl-certificates describe ${PREFIX}-cert --global --format='value(managed.status)'"
echo ""
echo "   Registros de e-mail (MX, SPF, etc.) foram preservados."
