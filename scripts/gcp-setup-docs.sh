#!/usr/bin/env bash
# Bucket GCS PRIVADO para documentos de clientes (LGPD)
set -euo pipefail

PROJECT="${GCP_PROJECT:-$(gcloud config get-value project 2>/dev/null)}"
REGION="${GCP_REGION:-southamerica-east1}"
BUCKET="${GCS_DOCS_BUCKET:-andrade-docs}"

if [[ -z "$PROJECT" || "$PROJECT" == "(unset)" ]]; then
  echo "Defina o projeto: export GCP_PROJECT=seu-projeto-gcp"
  exit 1
fi

echo "→ Projeto: $PROJECT | Região: $REGION | Bucket PRIVADO: $BUCKET"
gcloud config set project "$PROJECT"
gcloud services enable storage.googleapis.com storage-api.googleapis.com --quiet

if ! gcloud storage buckets describe "gs://${BUCKET}" --project "$PROJECT" &>/dev/null; then
  gcloud storage buckets create "gs://${BUCKET}" \
    --project="$PROJECT" \
    --location="$REGION" \
    --uniform-bucket-level-access
  echo "✓ Bucket privado criado: gs://${BUCKET}"
else
  echo "✓ Bucket já existe: gs://${BUCKET}"
fi

# NÃO adicionar allUsers — bucket permanece privado
PROJECT_NUMBER="$(gcloud projects describe "$PROJECT" --format='value(projectNumber)')"
RUN_SA="${PROJECT_NUMBER}-compute@developer.gserviceaccount.com"

gcloud storage buckets add-iam-policy-binding "gs://${BUCKET}" \
  --member="serviceAccount:${RUN_SA}" \
  --role=roles/storage.objectAdmin \
  --quiet

echo ""
echo "✓ Bucket privado configurado (sem acesso público)"
echo "  Acesso apenas via API com URLs assinadas"
echo ""
echo "Próximos passos:"
echo "  1. export GCS_DOCS_BUCKET=${BUCKET}"
echo "  2. Adicione GCS_DOCS_BUCKET ao deploy do Cloud Run"
