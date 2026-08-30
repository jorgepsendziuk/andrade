#!/usr/bin/env bash
# Configura secrets no GCP Secret Manager (rode uma vez)
# Conta: andradeisencoescloud@gmail.com
set -euo pipefail

PROJECT="${GCP_PROJECT:-$(gcloud config get-value project 2>/dev/null)}"
REGION="${GCP_REGION:-southamerica-east1}"

if [[ -z "$PROJECT" || "$PROJECT" == "(unset)" ]]; then
  echo "export GCP_PROJECT=seu-projeto-gcp"
  exit 1
fi

gcloud config set project "$PROJECT"
gcloud services enable secretmanager.googleapis.com --quiet

if [[ -z "${SMTP_PASS:-}" ]]; then
  echo "Cole a senha de app do Gmail (andradeisencoescloud@gmail.com):"
  read -rs SMTP_PASS
  echo ""
fi

if [[ -z "$SMTP_PASS" ]]; then
  echo "Erro: SMTP_PASS vazio."
  exit 1
fi

# Remove espaços da senha de app (Google exibe como "abcd efgh ijkl mnop")
SMTP_PASS="${SMTP_PASS// /}"

if ! gcloud secrets describe SMTP_PASS --project "$PROJECT" &>/dev/null; then
  echo -n "$SMTP_PASS" | gcloud secrets create SMTP_PASS --data-file=- --replication-policy=automatic
else
  echo -n "$SMTP_PASS" | gcloud secrets versions add SMTP_PASS --data-file=-
fi

JWT="$(openssl rand -base64 32)"
if ! gcloud secrets describe JWT_SECRET --project "$PROJECT" &>/dev/null; then
  echo -n "$JWT" | gcloud secrets create JWT_SECRET --data-file=- --replication-policy=automatic
else
  echo "JWT_SECRET já existe — mantendo valor atual"
fi

PROJECT_NUMBER="$(gcloud projects describe "$PROJECT" --format='value(projectNumber)')"
# Cloud Run usa a service account padrão do projeto
for SA in \
  "${PROJECT_NUMBER}-compute@developer.gserviceaccount.com" \
  "${PROJECT_NUMBER}@cloudbuild.gserviceaccount.com"; do
  gcloud secrets add-iam-policy-binding SMTP_PASS \
    --member="serviceAccount:${SA}" \
    --role="roles/secretmanager.secretAccessor" --quiet 2>/dev/null || true
  gcloud secrets add-iam-policy-binding JWT_SECRET \
    --member="serviceAccount:${SA}" \
    --role="roles/secretmanager.secretAccessor" --quiet 2>/dev/null || true
done

echo "✓ Secrets configurados. Rode: npm run deploy:cloudrun"
