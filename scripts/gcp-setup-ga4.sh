#!/usr/bin/env bash
# Configura acesso GA4 Data API para o painel admin
set -euo pipefail

PROJECT="${GCP_PROJECT:-$(gcloud config get-value project 2>/dev/null)}"
GA4_PROPERTY_ID="${GA4_PROPERTY_ID:-347102827}"
SA_NAME="${GA4_SA_NAME:-andrade-ga4-reader}"

if [[ -z "$PROJECT" || "$PROJECT" == "(unset)" ]]; then
  echo "export GCP_PROJECT=seu-projeto-gcp"
  exit 1
fi

gcloud config set project "$PROJECT"
gcloud services enable analyticsdata.googleapis.com --quiet

PROJECT_NUMBER="$(gcloud projects describe "$PROJECT" --format='value(projectNumber)')"
CLOUD_RUN_SA="${PROJECT_NUMBER}-compute@developer.gserviceaccount.com"
DEDICATED_SA="${SA_NAME}@${PROJECT}.iam.gserviceaccount.com"

echo ""
echo "→ Projeto: $PROJECT"
echo "→ Propriedade GA4: $GA4_PROPERTY_ID"
echo ""

# Service account dedicada (opcional, para chave JSON no Secret Manager)
if ! gcloud iam service-accounts describe "$DEDICATED_SA" --project "$PROJECT" &>/dev/null; then
  gcloud iam service-accounts create "$SA_NAME" \
    --display-name="Andrade GA4 Reader" \
    --project "$PROJECT"
  echo "✓ Service account criada: $DEDICATED_SA"
else
  echo "→ Service account já existe: $DEDICATED_SA"
fi

KEY_FILE="$(mktemp)"
gcloud iam service-accounts keys create "$KEY_FILE" \
  --iam-account="$DEDICATED_SA" \
  --project "$PROJECT"

if ! gcloud secrets describe GA4_SERVICE_ACCOUNT_JSON --project "$PROJECT" &>/dev/null; then
  gcloud secrets create GA4_SERVICE_ACCOUNT_JSON \
    --data-file="$KEY_FILE" \
    --replication-policy=automatic \
    --project "$PROJECT"
else
  gcloud secrets versions add GA4_SERVICE_ACCOUNT_JSON \
    --data-file="$KEY_FILE" \
    --project "$PROJECT"
fi
rm -f "$KEY_FILE"
echo "✓ Secret GA4_SERVICE_ACCOUNT_JSON atualizado"

for SA in \
  "${PROJECT_NUMBER}-compute@developer.gserviceaccount.com" \
  "${PROJECT_NUMBER}@cloudbuild.gserviceaccount.com"; do
  gcloud secrets add-iam-policy-binding GA4_SERVICE_ACCOUNT_JSON \
    --member="serviceAccount:${SA}" \
    --role="roles/secretmanager.secretAccessor" \
    --project "$PROJECT" --quiet 2>/dev/null || true
done

echo ""
echo "══════════════════════════════════════════════════════════════"
echo "  PASSO MANUAL (obrigatório) — no Google Analytics:"
echo "══════════════════════════════════════════════════════════════"
echo ""
echo "  1. Abra: https://analytics.google.com/analytics/web/#/a163733965p${GA4_PROPERTY_ID}/admin/accountsettings"
echo "  2. Admin → Gerenciamento de acesso à propriedade"
echo "  3. Adicione como Leitor (Viewer):"
echo ""
echo "     ${DEDICATED_SA}"
echo "     ${CLOUD_RUN_SA}"
echo ""
echo "  (Basta uma das duas se usar só ADC ou só o secret JSON)"
echo ""
echo "══════════════════════════════════════════════════════════════"
echo ""
echo "Depois rode o deploy:"
echo "  GCP_PROJECT=$PROJECT npm run deploy:cloudrun"
echo ""
