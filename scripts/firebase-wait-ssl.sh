#!/usr/bin/env bash
# Aguarda SSL do Firebase Hosting ficar ativo
set -euo pipefail

PROJECT="${GCP_PROJECT:-smart-tractor-257319}"
SITE="${FIREBASE_SITE:-$PROJECT}"
DOMAIN="${SITE_DOMAIN:-andradeisencoes.com.br}"
MAX_WAIT="${MAX_WAIT:-3600}"
INTERVAL="${INTERVAL:-30}"

deadline=$((SECONDS + MAX_WAIT))

echo "→ Aguardando SSL de https://${DOMAIN} (máx ${MAX_WAIT}s)..."

while (( SECONDS < deadline )); do
  token="$(gcloud auth print-access-token)"
  status="$(curl -s -H "Authorization: Bearer $token" -H "x-goog-user-project: $PROJECT" \
    "https://firebasehosting.googleapis.com/v1beta1/projects/${PROJECT}/sites/${SITE}/customDomains/${DOMAIN}" \
    | python3 -c "import json,sys; d=json.load(sys.stdin); c=d.get('cert',{}); print(c.get('type','?'), c.get('state','?'), d.get('hostState','?'))")"

  code="$(curl -s -o /dev/null -w "%{http_code}" "https://${DOMAIN}/" --max-time 20 2>/dev/null || echo err)"
  echo "  cert/host: $status | https=$code"

  if [[ "$code" == "200" ]]; then
    echo ""
    echo "✓ Site ativo em https://${DOMAIN}"
    echo "  Rode: npm run teardown:loadbalancer"
    exit 0
  fi

  sleep "$INTERVAL"
done

echo ""
echo "⚠ Timeout — SSL ainda provisionando. Verifique no console:"
echo "  https://console.firebase.google.com/project/${PROJECT}/hosting/sites"
exit 1
