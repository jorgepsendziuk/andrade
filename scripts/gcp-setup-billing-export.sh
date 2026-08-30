#!/usr/bin/env bash
# Prepara exportação de faturamento GCP → BigQuery para custos reais no painel admin
# Conta de faturamento: ANDRADEISENCOES (017163-F935E4-FCF7C4)
set -euo pipefail

PROJECT="${GCP_PROJECT:-smart-tractor-257319}"
BILLING_ACCOUNT="${BILLING_ACCOUNT_ID:-017163-F935E4-FCF7C4}"
DATASET="${BILLING_BQ_DATASET:-billing_export}"
LOCATION="${BILLING_BQ_LOCATION:-US}"

echo "→ Projeto: $PROJECT"
echo "→ Conta de faturamento: $BILLING_ACCOUNT"
echo "→ Dataset BigQuery: $DATASET ($LOCATION)"
echo ""

gcloud config set project "$PROJECT"
gcloud services enable bigquery.googleapis.com bigquerydatatransfer.googleapis.com --quiet

if ! bq show --project_id="$PROJECT" "${PROJECT}:${DATASET}" &>/dev/null; then
  bq --location="$LOCATION" mk --dataset \
    --description="Exportação de faturamento GCP — Andrade Isenções" \
    "${PROJECT}:${DATASET}"
  echo "✓ Dataset criado: ${PROJECT}:${DATASET}"
else
  echo "✓ Dataset já existe: ${PROJECT}:${DATASET}"
fi

PROJECT_NUMBER="$(gcloud projects describe "$PROJECT" --format='value(projectNumber)')"
RUN_SA="${PROJECT_NUMBER}-compute@developer.gserviceaccount.com"

for ROLE in roles/bigquery.dataViewer roles/bigquery.jobUser; do
  gcloud projects add-iam-policy-binding "$PROJECT" \
    --member="serviceAccount:${RUN_SA}" \
    --role="$ROLE" \
    --quiet &>/dev/null || true
done

echo "✓ Permissões BigQuery concedidas ao Cloud Run (${RUN_SA})"
echo ""
echo "══════════════════════════════════════════════════════════════"
echo "  PASSO MANUAL (obrigatório) — habilitar exportação no console:"
echo "══════════════════════════════════════════════════════════════"
echo ""
echo "  1. Abra: https://console.cloud.google.com/billing/${BILLING_ACCOUNT}/export"
echo "  2. Aba \"BigQuery export\""
echo "  3. Em \"Detailed usage cost\" → Edit settings"
echo "  4. Projeto: ${PROJECT}"
echo "  5. Dataset: ${DATASET}"
echo "  6. Salvar"
echo ""
echo "  A tabela será criada automaticamente:"
echo "    gcp_billing_export_v1_${BILLING_ACCOUNT//-/_}"
echo ""
echo "  ⏳ Primeiros dados levam até 24h após habilitar."
echo ""
echo "══════════════════════════════════════════════════════════════"
echo ""
echo "Depois adicione ao deploy (já incluso no script deploy-cloudrun.sh se existir):"
echo "  BILLING_BQ_DATASET=${DATASET}"
echo "  BILLING_ACCOUNT_ID=${BILLING_ACCOUNT}"
echo ""
echo "  npm run deploy:cloudrun"
echo ""
