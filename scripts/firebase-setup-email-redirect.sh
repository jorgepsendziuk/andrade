#!/usr/bin/env bash
# Corrige SSL de email.andradeisencoes.com.br com redirect via Firebase Hosting (grátis)
set -euo pipefail

PROJECT="${GCP_PROJECT:-smart-tractor-257319}"
EMAIL_SITE="${FIREBASE_EMAIL_SITE:-andrade-email}"
DNS_ZONE="${GCP_DNS_ZONE:-andrade}"
DOMAIN="${SITE_DOMAIN:-andradeisencoes.com.br}"
EMAIL_HOST="email.${DOMAIN}"
GODADDY_LOGIN="${EMAIL_LOGIN_URL:-https://sso.godaddy.com/?app=email&realm=pass}"

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

echo "→ Corrigindo SSL de ${EMAIL_HOST}"
echo "→ Redirect para: ${GODADDY_LOGIN}"
echo ""

gcloud config set project "$PROJECT"

if ! npx --yes firebase-tools hosting:sites:get "$EMAIL_SITE" --project "$PROJECT" &>/dev/null; then
  echo "→ Criando site Firebase Hosting: ${EMAIL_SITE}"
  npx --yes firebase-tools hosting:sites:create "$EMAIL_SITE" --project "$PROJECT"
fi

echo "→ Deploy do redirect de e-mail..."
npx --yes firebase-tools deploy --only "hosting:${EMAIL_SITE}" --project "$PROJECT" --non-interactive

echo "→ Registrando domínio ${EMAIL_HOST} no Firebase..."
TOKEN="$(gcloud auth print-access-token)"
curl -s -X POST \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "x-goog-user-project: $PROJECT" \
  "https://firebasehosting.googleapis.com/v1beta1/projects/${PROJECT}/sites/${EMAIL_SITE}/customDomains?customDomainId=${EMAIL_HOST}" \
  -d '{}' | python3 -c "import json,sys; d=json.load(sys.stdin); print(d.get('name', d.get('error',{}).get('message','ok')))"

sleep 8

echo "→ Consultando registros DNS necessários..."
DOMAIN_JSON="$(curl -s -H "Authorization: Bearer $TOKEN" -H "x-goog-user-project: $PROJECT" \
  "https://firebasehosting.googleapis.com/v1beta1/projects/${PROJECT}/sites/${EMAIL_SITE}/customDomains/${EMAIL_HOST}")"

CNAME_TARGET="$(echo "$DOMAIN_JSON" | python3 -c "
import json,sys
d=json.load(sys.stdin)
for block in d.get('requiredDnsUpdates',{}).get('desired',[]):
    for r in block.get('records',[]):
        if r.get('type')=='CNAME' and r.get('requiredAction')=='ADD':
            print(r.get('rdata','')); break
" 2>/dev/null || echo "${EMAIL_SITE}.web.app")"

if [[ "$CNAME_TARGET" != *"." ]]; then
  CNAME_TARGET="${CNAME_TARGET}."
fi

ACME_TXT="$(echo "$DOMAIN_JSON" | python3 -c "
import json,sys
d=json.load(sys.stdin)
for block in d.get('cert',{}).get('verification',{}).get('dns',{}).get('desired',[]):
    for r in block.get('records',[]):
        if r.get('type')=='TXT':
            print(r.get('rdata',''))
" 2>/dev/null || true)"

echo "→ Atualizando DNS (${EMAIL_HOST} → ${CNAME_TARGET})..."
gcloud dns record-sets transaction start --zone="$DNS_ZONE"

gcloud dns record-sets transaction remove \
  --zone="$DNS_ZONE" --name="${EMAIL_HOST}." --type=CNAME --ttl=300 \
  "email.secureserver.net." 2>/dev/null || true

gcloud dns record-sets transaction remove \
  --zone="$DNS_ZONE" --name="${EMAIL_HOST}." --type=A --ttl=300 \
  "199.36.158.100" 2>/dev/null || true

gcloud dns record-sets transaction remove \
  --zone="$DNS_ZONE" --name="${EMAIL_HOST}." --type=TXT --ttl=300 \
  '"hosting-site=andrade-email"' 2>/dev/null || true

gcloud dns record-sets transaction add \
  --zone="$DNS_ZONE" --name="${EMAIL_HOST}." --type=CNAME --ttl=300 \
  "$CNAME_TARGET"

gcloud dns record-sets transaction execute --zone="$DNS_ZONE"

if [[ -n "$ACME_TXT" ]]; then
  echo "→ Adicionando ACME challenge..."
  gcloud dns record-sets create "_acme-challenge.${EMAIL_HOST}." \
    --zone="$DNS_ZONE" --type=TXT --ttl=300 \
    --rrdatas="\"${ACME_TXT}\"" --project="$PROJECT" 2>/dev/null || true
fi

echo ""
echo "✓ Configurado. Aguarde 15–60 min para o SSL provisionar."
echo "  ${EMAIL_HOST} → login GoDaddy (com certificado válido)"
echo "  MX/e-mail de envio/recebimento: inalterados"
echo ""
echo "  Teste: curl -I https://${EMAIL_HOST}"
echo ""
