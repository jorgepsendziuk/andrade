#!/usr/bin/env bash
# Deploy completo: Cloud Run + Firebase Hosting
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

echo "═══ 1/2 Cloud Run ═══"
bash scripts/deploy-cloudrun.sh

echo ""
echo "═══ 2/2 Firebase Hosting ═══"
PROJECT="${GCP_PROJECT:-smart-tractor-257319}"
# sitemap.xml é gerado dinamicamente pelo Cloud Run (lastmod por artigo)
npx --yes firebase-tools deploy --only hosting --project "$PROJECT" --non-interactive

echo ""
echo "✓ Deploy completo (Cloud Run + Firebase Hosting)"
