#!/usr/bin/env bash
# Bucket de backup (Nearline) + Cloud Scheduler para backup diário automático
set -euo pipefail

PROJECT="${GCP_PROJECT:-$(gcloud config get-value project 2>/dev/null)}"
REGION="${GCP_REGION:-southamerica-east1}"
SERVICE_NAME="${GCP_SERVICE:-andrade-isencoes}"
BUCKET="${GCS_BACKUP_BUCKET:-andrade-backups}"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-90}"
SCHEDULE="${BACKUP_CRON_SCHEDULE:-0 6 * * *}" # 03:00 BRT (UTC-3)

if [[ -z "$PROJECT" || "$PROJECT" == "(unset)" ]]; then
  echo "Defina o projeto: export GCP_PROJECT=seu-projeto-gcp"
  exit 1
fi

echo "→ Projeto: $PROJECT | Região: $REGION | Backup: gs://${BUCKET}"
gcloud config set project "$PROJECT"
gcloud services enable \
  storage.googleapis.com \
  cloudscheduler.googleapis.com \
  run.googleapis.com \
  secretmanager.googleapis.com \
  --quiet

PROJECT_NUMBER="$(gcloud projects describe "$PROJECT" --format='value(projectNumber)')"
RUN_SA="${PROJECT_NUMBER}-compute@developer.gserviceaccount.com"

if ! gcloud storage buckets describe "gs://${BUCKET}" --project "$PROJECT" &>/dev/null; then
  gcloud storage buckets create "gs://${BUCKET}" \
    --project="$PROJECT" \
    --location="$REGION" \
    --default-storage-class=NEARLINE \
    --uniform-bucket-level-access
  echo "✓ Bucket de backup criado: gs://${BUCKET} (Nearline)"
else
  echo "✓ Bucket já existe: gs://${BUCKET}"
fi

# Lifecycle: remove backups antigos
cat > /tmp/andrade-backup-lifecycle.json <<EOF
{
  "rule": [
    {
      "action": { "type": "Delete" },
      "condition": {
        "age": ${RETENTION_DAYS},
        "matchesPrefix": ["daily/"]
      }
    }
  ]
}
EOF
gcloud storage buckets update "gs://${BUCKET}" --lifecycle-file=/tmp/andrade-backup-lifecycle.json --quiet
rm -f /tmp/andrade-backup-lifecycle.json

gcloud storage buckets add-iam-policy-binding "gs://${BUCKET}" \
  --member="serviceAccount:${RUN_SA}" \
  --role=roles/storage.objectAdmin \
  --quiet

# Secret para Cloud Scheduler autenticar no endpoint de backup
if ! gcloud secrets describe BACKUP_CRON_SECRET --project "$PROJECT" &>/dev/null; then
  SECRET_VALUE="$(openssl rand -hex 32)"
  printf '%s' "$SECRET_VALUE" | gcloud secrets create BACKUP_CRON_SECRET \
    --project="$PROJECT" \
    --data-file=- \
    --replication-policy=automatic
  echo "✓ Secret BACKUP_CRON_SECRET criado"
else
  echo "✓ Secret BACKUP_CRON_SECRET já existe"
fi

gcloud secrets add-iam-policy-binding BACKUP_CRON_SECRET \
  --project="$PROJECT" \
  --member="serviceAccount:${RUN_SA}" \
  --role=roles/secretmanager.secretAccessor \
  --quiet 2>/dev/null || true

# Timeout maior no Cloud Run para backups grandes
gcloud run services update "$SERVICE_NAME" \
  --region="$REGION" \
  --timeout=900 \
  --update-env-vars "GCS_BACKUP_BUCKET=${BUCKET},BACKUP_RETENTION_DAYS=${RETENTION_DAYS}" \
  --update-secrets "BACKUP_CRON_SECRET=BACKUP_CRON_SECRET:latest" \
  --quiet 2>/dev/null || echo "⚠ Atualize o Cloud Run manualmente com GCS_BACKUP_BUCKET e BACKUP_CRON_SECRET"

SERVICE_URL="$(gcloud run services describe "$SERVICE_NAME" --region="$REGION" --format='value(status.url)')"
JOB_NAME="andrade-daily-backup"
SCHEDULER_SA="andrade-backup-scheduler@${PROJECT}.iam.gserviceaccount.com"

if ! gcloud iam service-accounts describe "$SCHEDULER_SA" --project="$PROJECT" &>/dev/null; then
  gcloud iam service-accounts create andrade-backup-scheduler \
    --display-name="Andrade backup scheduler" \
    --project="$PROJECT"
fi

# Scheduler precisa invocar Cloud Run (allUsers já está no serviço; auth via header secreto)
if gcloud scheduler jobs describe "$JOB_NAME" --location="$REGION" --project="$PROJECT" &>/dev/null; then
  gcloud scheduler jobs update http "$JOB_NAME" \
    --location="$REGION" \
    --schedule="$SCHEDULE" \
    --uri="${SERVICE_URL}/api/admin/backup/run" \
    --http-method=POST \
    --headers="Content-Type=application/json,X-Backup-Secret=$(gcloud secrets versions access latest --secret=BACKUP_CRON_SECRET --project=$PROJECT)" \
    --message-body='{"trigger":"scheduler"}' \
    --time-zone="America/Sao_Paulo" \
    --quiet
  echo "✓ Cloud Scheduler atualizado: $JOB_NAME"
else
  gcloud scheduler jobs create http "$JOB_NAME" \
    --location="$REGION" \
    --schedule="$SCHEDULE" \
    --uri="${SERVICE_URL}/api/admin/backup/run" \
    --http-method=POST \
    --headers="Content-Type=application/json,X-Backup-Secret=$(gcloud secrets versions access latest --secret=BACKUP_CRON_SECRET --project=$PROJECT)" \
    --message-body='{"trigger":"scheduler"}' \
    --time-zone="America/Sao_Paulo" \
    --quiet
  echo "✓ Cloud Scheduler criado: $JOB_NAME ($SCHEDULE, America/Sao_Paulo)"
fi

echo ""
echo "✓ Backup automático configurado"
echo "  Bucket: gs://${BUCKET}"
echo "  Retenção: ${RETENTION_DAYS} dias"
echo "  Endpoint: POST ${SERVICE_URL}/api/admin/backup/run"
echo "  Manual: npm run backup:daily"
echo ""
echo "Próximo passo: inclua no deploy:"
echo "  GCS_BACKUP_BUCKET=${BUCKET}"
echo "  BACKUP_RETENTION_DAYS=${RETENTION_DAYS}"
echo "  BACKUP_CRON_SECRET (Secret Manager)"
