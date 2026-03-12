#!/usr/bin/env bash

set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"

echo "[deploy-bot] app dir: ${APP_DIR}"
echo "[deploy-bot] branch : ${DEPLOY_BRANCH}"

cd "${APP_DIR}"

if ! command -v git >/dev/null 2>&1; then
  echo "[deploy-bot] git tidak ditemukan." >&2
  exit 1
fi

if ! command -v php >/dev/null 2>&1; then
  echo "[deploy-bot] php tidak ditemukan." >&2
  exit 1
fi

if ! command -v composer >/dev/null 2>&1; then
  echo "[deploy-bot] composer tidak ditemukan." >&2
  exit 1
fi

if ! command -v npm >/dev/null 2>&1; then
  echo "[deploy-bot] npm tidak ditemukan." >&2
  exit 1
fi

if ! command -v pm2 >/dev/null 2>&1; then
  echo "[deploy-bot] pm2 tidak ditemukan. Install dulu dengan: npm i -g pm2" >&2
  exit 1
fi

if ! git diff --quiet --ignore-submodules HEAD -- || ! git diff --cached --quiet --ignore-submodules; then
  echo "[deploy-bot] ada perubahan tracked yang belum committed di VPS. Deploy dihentikan supaya aman." >&2
  exit 1
fi

git fetch origin "${DEPLOY_BRANCH}"

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
if [[ "${CURRENT_BRANCH}" != "${DEPLOY_BRANCH}" ]]; then
  git checkout "${DEPLOY_BRANCH}"
fi

git pull --ff-only origin "${DEPLOY_BRANCH}"

composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize

pm2 startOrReload ecosystem.config.cjs --only lyva-bot --update-env
pm2 save

echo "[deploy-bot] deploy selesai."
