#!/usr/bin/env bash
# Registra domínios customizados no Firebase Hosting via REST API
set -euo pipefail

PROJECT="${GCP_PROJECT:-smart-tractor-257319}"
SITE="${FIREBASE_SITE:-$PROJECT}"
DOMAIN="${SITE_DOMAIN:-andradeisencoes.com.br}"

api() {
  local method="$1" path="$2" body="${3:-}"
  local token
  token="$(gcloud auth print-access-token)"
  if [[ -n "$body" ]]; then
    curl -s -X "$method" \
      -H "Authorization: Bearer $token" \
      -H "Content-Type: application/json" \
      -H "x-goog-user-project: $PROJECT" \
      "https://firebasehosting.googleapis.com/v1beta1/$path" \
      -d "$body"
  else
    curl -s -X "$method" \
      -H "Authorization: Bearer $token" \
      -H "x-goog-user-project: $PROJECT" \
      "https://firebasehosting.googleapis.com/v1beta1/$path"
  fi
}

create_domain() {
  local domain="$1"
  echo "→ Registrando ${domain} no Firebase Hosting..."
  api POST "projects/${PROJECT}/sites/${SITE}/customDomains?customDomainId=${domain}" '{}' \
    | python3 -c "import json,sys; d=json.load(sys.stdin); print('  ', d.get('name', d.get('error',{}).get('message','ok')))" 2>/dev/null || true
}

create_domain "$DOMAIN"
create_domain "www.${DOMAIN}"

echo ""
echo "✓ Domínios registrados. Aguarde ~30s e rode:"
echo "  npm run migrate:dns-firebase"
echo ""
