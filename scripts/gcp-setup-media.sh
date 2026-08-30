#!/usr/bin/env bash
# Bucket GCS para fotos do site (fora do build Docker)
# Conta: andradeisencoescloud@gmail.com
set -euo pipefail

PROJECT="${GCP_PROJECT:-$(gcloud config get-value project 2>/dev/null)}"
REGION="${GCP_REGION:-southamerica-east1}"
BUCKET="${GCS_BUCKET:-andrade-media}"

if [[ -z "$PROJECT" || "$PROJECT" == "(unset)" ]]; then
  echo "Defina o projeto: export GCP_PROJECT=seu-projeto-gcp"
  exit 1
fi

echo "→ Projeto: $PROJECT | Região: $REGION | Bucket: $BUCKET"
gcloud config set project "$PROJECT"
gcloud services enable storage.googleapis.com storage-api.googleapis.com --quiet

if ! gcloud storage buckets describe "gs://${BUCKET}" --project "$PROJECT" &>/dev/null; then
  gcloud storage buckets create "gs://${BUCKET}" \
    --project="$PROJECT" \
    --location="$REGION" \
    --uniform-bucket-level-access
  echo "✓ Bucket criado: gs://${BUCKET}"
else
  echo "✓ Bucket já existe: gs://${BUCKET}"
fi

# Leitura pública dos objetos (URLs diretas storage.googleapis.com)
gcloud storage buckets add-iam-policy-binding "gs://${BUCKET}" \
  --member=allUsers \
  --role=roles/storage.objectViewer \
  --quiet

PROJECT_NUMBER="$(gcloud projects describe "$PROJECT" --format='value(projectNumber)')"
RUN_SA="${PROJECT_NUMBER}-compute@developer.gserviceaccount.com"

gcloud storage buckets add-iam-policy-binding "gs://${BUCKET}" \
  --member="serviceAccount:${RUN_SA}" \
  --role=roles/storage.objectAdmin \
  --quiet

echo ""
echo "✓ Bucket configurado para leitura pública e upload pelo Cloud Run"
echo "  URL base: https://storage.googleapis.com/${BUCKET}/"
echo ""
echo "Próximos passos:"
echo "  1. export GCS_BUCKET=${BUCKET}"
echo "  2. npm run migrate:media     # envia fotos atuais e atualiza site-content.json"
echo "  3. npm run deploy:cloudrun # inclui GCS_BUCKET no serviço"
