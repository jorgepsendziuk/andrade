#!/usr/bin/env bash
# Migra DNS do Load Balancer para Firebase Hosting (preserva MX/SPF)
set -euo pipefail

PROJECT="${GCP_PROJECT:-smart-tractor-257319}"
SITE="${FIREBASE_SITE:-$PROJECT}"
DNS_ZONE="${GCP_DNS_ZONE:-andrade}"
DOMAIN="${SITE_DOMAIN:-andradeisencoes.com.br}"
LB_IP="${LB_IP:-8.233.48.205}"
FIREBASE_APEX_IP="${FIREBASE_APEX_IP:-199.36.158.100}"
AUTO_CONFIRM="${AUTO_CONFIRM:-}"

if [[ "${AUTO_CONFIRM:-}" != "yes" && "${1:-}" != "--yes" ]]; then
  echo "⚠ Isso altera os registros A/CNAME de ${DOMAIN} para Firebase Hosting."
  echo "  MX, SPF e DMARC são preservados."
  read -r -p "Continuar? (s/N) " CONFIRM
  if [[ "${CONFIRM:-}" != "s" && "${CONFIRM:-}" != "S" && "${1:-}" != "--yes" ]]; then
    echo "Cancelado."
    exit 0
  fi
fi

gcloud config set project "$PROJECT"

fetch_domain_json() {
  local domain="$1"
  local token
  token="$(gcloud auth print-access-token)"
  curl -s -H "Authorization: Bearer $token" -H "x-goog-user-project: $PROJECT" \
    "https://firebasehosting.googleapis.com/v1beta1/projects/${PROJECT}/sites/${SITE}/customDomains/${domain}"
}

add_acme_records() {
  local domain="$1"
  local json
  json="$(fetch_domain_json "$domain")"
  python3 - "$DNS_ZONE" "$PROJECT" <<'PY' <<<"$json"
import json, subprocess, sys
zone, project = sys.argv[1], sys.argv[2]
data = json.load(sys.stdin)
desired = data.get("cert", {}).get("verification", {}).get("dns", {}).get("desired", [])
for block in desired:
    name = block.get("domainName", "")
    if not name.endswith("."):
        name += "."
    for rec in block.get("records", []):
        if rec.get("type") != "TXT":
            continue
        rdata = rec.get("rdata", "")
        fqdn = name
        print(f"→ ACME TXT {fqdn}")
        subprocess.run([
            "gcloud", "dns", "record-sets", "create", fqdn,
            f"--zone={zone}", "--type=TXT", "--ttl=300",
            f"--rrdatas=\"{rdata}\"", f"--project={project}",
        ], check=False, capture_output=True)
PY
}

echo "→ Atualizando apex e www..."
gcloud dns record-sets transaction start --zone="$DNS_ZONE"

gcloud dns record-sets transaction remove \
  --zone="$DNS_ZONE" --name="${DOMAIN}." --type=A --ttl=300 "$LB_IP" 2>/dev/null || true

gcloud dns record-sets transaction add \
  --zone="$DNS_ZONE" --name="${DOMAIN}." --type=A --ttl=300 "$FIREBASE_APEX_IP"

gcloud dns record-sets transaction remove \
  --zone="$DNS_ZONE" --name="${DOMAIN}." --type=TXT --ttl=300 \
  '"v=spf1 include:secureserver.net -all"' '"D3714017"' 2>/dev/null || true

gcloud dns record-sets transaction add \
  --zone="$DNS_ZONE" --name="${DOMAIN}." --type=TXT --ttl=300 \
  '"v=spf1 include:secureserver.net -all"' '"D3714017"' "\"hosting-site=${SITE}\""

gcloud dns record-sets transaction remove \
  --zone="$DNS_ZONE" --name="www.${DOMAIN}." --type=A --ttl=300 "$LB_IP" 2>/dev/null || true

gcloud dns record-sets transaction add \
  --zone="$DNS_ZONE" --name="www.${DOMAIN}." --type=CNAME --ttl=300 "${SITE}.web.app."

gcloud dns record-sets transaction execute --zone="$DNS_ZONE"

echo "→ Adicionando registros ACME para SSL..."
sleep 10
add_acme_records "$DOMAIN"
add_acme_records "www.${DOMAIN}"

echo ""
echo "✓ DNS migrado para Firebase Hosting"
echo "  ⏳ SSL: aguarde 15–60 min (até 24h em casos raros)"
echo "  Teste: curl -I https://${DOMAIN}"
echo ""
echo "  Quando HTTPS retornar 200:"
echo "    npm run teardown:loadbalancer"
echo ""
