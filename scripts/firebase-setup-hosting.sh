#!/usr/bin/env bash
# Habilita Firebase Hosting no projeto GCP existente e prepara rewrite → Cloud Run
set -euo pipefail

PROJECT="${GCP_PROJECT:-smart-tractor-257319}"
SERVICE="${GCP_SERVICE:-andrade-isencoes}"
REGION="${GCP_REGION:-southamerica-east1}"
DOMAIN="${SITE_DOMAIN:-andradeisencoes.com.br}"

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

echo "→ Projeto: $PROJECT | Cloud Run: $SERVICE ($REGION)"
echo "→ Domínio alvo: $DOMAIN"
echo ""

gcloud config set project "$PROJECT"

echo "→ Habilitando APIs do Firebase..."
gcloud services enable \
  firebase.googleapis.com \
  firebasehosting.googleapis.com \
  firebaserules.googleapis.com \
  --quiet

PROJECT_NUMBER="$(gcloud projects describe "$PROJECT" --format='value(projectNumber)')"
FIREBASE_SA="service-${PROJECT_NUMBER}@gcp-sa-firebasehosting.iam.gserviceaccount.com"

echo "→ Vinculando Firebase ao projeto GCP (se ainda não estiver)..."
if ! firebase projects:list 2>/dev/null | grep -q "$PROJECT"; then
  npx --yes firebase-tools projects:addfirebase "$PROJECT" 2>/dev/null || {
    echo "  (Firebase pode já estar vinculado — continuando)"
  }
fi

echo "→ Concedendo Cloud Run Invoker ao Firebase Hosting..."
gcloud run services add-iam-policy-binding "$SERVICE" \
  --region="$REGION" \
  --member="serviceAccount:${FIREBASE_SA}" \
  --role="roles/run.invoker" \
  --quiet 2>/dev/null || true

# Garante acesso público (já deve existir)
gcloud run services add-iam-policy-binding "$SERVICE" \
  --region="$REGION" \
  --member="allUsers" \
  --role="roles/run.invoker" \
  --quiet 2>/dev/null || true

echo "→ Deploy do Firebase Hosting (rewrite → Cloud Run)..."
npx --yes firebase-tools deploy --only hosting --project "$PROJECT" --non-interactive

HOSTING_URL="$(npx --yes firebase-tools hosting:sites:get "$PROJECT" --project "$PROJECT" 2>/dev/null | grep -i 'default url' | awk '{print $NF}' || true)"
if [[ -z "$HOSTING_URL" ]]; then
  HOSTING_URL="https://${PROJECT}.web.app"
fi

echo ""
echo "✓ Firebase Hosting configurado"
echo "  URL temporária: ${HOSTING_URL}"
echo ""
echo "══════════════════════════════════════════════════════════════"
echo "  PRÓXIMO PASSO — domínio customizado"
echo "══════════════════════════════════════════════════════════════"
echo ""
  echo "  Opção A (script):"
  echo "    npm run setup:firebase-domain && npm run migrate:dns-firebase"
echo ""
echo "  Opção B (console):"
echo "    https://console.firebase.google.com/project/${PROJECT}/hosting/sites"
echo "    → Adicionar domínio: ${DOMAIN} e www.${DOMAIN}"
echo "    → Depois rode: npm run migrate:dns-firebase"
echo ""
echo "  Após DNS propagar e SSL ativo (~15–60 min):"
echo "    npm run teardown:loadbalancer"
echo ""
