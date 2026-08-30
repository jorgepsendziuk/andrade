#!/usr/bin/env bash
set -euo pipefail

# Deploy Andrade Isenções no Google Cloud Run
# Conta: andradeisencoescloud@gmail.com

SERVICE_NAME="${GCP_SERVICE:-andrade-isencoes}"
REGION="${GCP_REGION:-southamerica-east1}"
PROJECT="${GCP_PROJECT:-$(gcloud config get-value project 2>/dev/null)}"

if [[ -z "$PROJECT" || "$PROJECT" == "(unset)" ]]; then
  echo "Defina o projeto: export GCP_PROJECT=seu-projeto-gcp"
  exit 1
fi

echo "→ Projeto: $PROJECT | Região: $REGION | Serviço: $SERVICE_NAME"
echo "→ Conta: $(gcloud auth list --filter=status:ACTIVE --format='value(account)')"
echo ""

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

gcloud config set project "$PROJECT"
gcloud services enable run.googleapis.com cloudbuild.googleapis.com artifactregistry.googleapis.com secretmanager.googleapis.com analyticsdata.googleapis.com --quiet

PROJECT_NUMBER="$(gcloud projects describe "$PROJECT" --format='value(projectNumber)')"

DEPLOY_ARGS=(
  --source .
  --region "$REGION"
  --platform managed
  --allow-unauthenticated
  --port 8080
  --memory 512Mi
  --cpu 1
  --min-instances 0
  --max-instances 3
  --set-env-vars "NODE_ENV=production,SITE_URL=https://andradeisencoes.com.br,GCS_BUCKET=${GCS_BUCKET:-andrade-media},GCS_DOCS_BUCKET=${GCS_DOCS_BUCKET:-andrade-docs},GCS_BACKUP_BUCKET=${GCS_BACKUP_BUCKET:-andrade-backups},BACKUP_RETENTION_DAYS=${BACKUP_RETENTION_DAYS:-90},SMTP_HOST=smtp.gmail.com,SMTP_PORT=587,SMTP_SECURE=false,SMTP_USER=andradeisencoescloud@gmail.com,SMTP_FROM=andradeisencoescloud@gmail.com,CONTACT_EMAIL=comercial@andradeisencoes.com.br,LOGIN_RATE_LIMIT_EXEMPT=jimxxx@gmail.com,GA4_PROPERTY_ID=${GA4_PROPERTY_ID:-347102827},GCP_PROJECT_NUMBER=${PROJECT_NUMBER},BILLING_BQ_DATASET=${BILLING_BQ_DATASET:-billing_export},BILLING_ACCOUNT_ID=${BILLING_ACCOUNT_ID:-017163-F935E4-FCF7C4}"
)

GA_MEASUREMENT_ID="${VITE_GA_MEASUREMENT_ID:-G-BM0PH4LDQG}"
BUILD_ENV="VITE_SITE_URL=https://andradeisencoes.com.br,VITE_GA4_PROPERTY_ID=${VITE_GA4_PROPERTY_ID:-347102827},VITE_GA_MEASUREMENT_ID=${GA_MEASUREMENT_ID},BUILD_CACHE_BUST=$(date +%s)"
echo "→ GA4 measurement ID: ${GA_MEASUREMENT_ID}"
DEPLOY_ARGS+=(--set-build-env-vars "$BUILD_ENV")

SECRETS="SMTP_PASS=SMTP_PASS:latest,JWT_SECRET=JWT_SECRET:latest"
if gcloud secrets describe BACKUP_CRON_SECRET --project "$PROJECT" &>/dev/null; then
  SECRETS="${SECRETS},BACKUP_CRON_SECRET=BACKUP_CRON_SECRET:latest"
  echo "→ Backup cron secret configurado"
fi
if gcloud secrets describe GA4_SERVICE_ACCOUNT_JSON --project "$PROJECT" &>/dev/null; then
  SECRETS="${SECRETS},GA4_SERVICE_ACCOUNT_JSON=GA4_SERVICE_ACCOUNT_JSON:latest"
  echo "→ GA4 service account configurada"
fi

if gcloud secrets describe SMTP_PASS --project "$PROJECT" &>/dev/null; then
  DEPLOY_ARGS+=(--set-secrets "$SECRETS")
  echo "→ Usando secrets do Secret Manager"
else
  echo "⚠ SMTP_PASS não encontrado no Secret Manager."
  echo "  Rode primeiro: bash scripts/gcp-setup-secrets.sh"
  echo "  Ou passe SMTP_PASS via --set-env-vars manualmente no console."
fi

gcloud run deploy "$SERVICE_NAME" "${DEPLOY_ARGS[@]}" --quiet

echo ""
echo "✓ URL:"
gcloud run services describe "$SERVICE_NAME" --region "$REGION" --format='value(status.url)'
