#!/usr/bin/env bash
# Cria banco/usuario MySQL local conforme o .env do projeto.
# Uso: ./scripts/setup_local_db.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ENV_FILE="$ROOT/.env"

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Arquivo .env nao encontrado. Copie .env.example para .env e ajuste."
    exit 1
fi

# shellcheck disable=SC1090
set -a
source "$ENV_FILE"
set +a

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_DATABASE="${DB_DATABASE:-babapro}"
DB_USERNAME="${DB_USERNAME:-babapro}"
DB_PASSWORD="${DB_PASSWORD:-}"

if [[ -z "$DB_PASSWORD" ]]; then
    echo "DB_PASSWORD vazio no .env."
    exit 1
fi

echo "Configurando MySQL local:"
echo "  banco: $DB_DATABASE"
echo "  usuario: $DB_USERNAME@localhost"
echo ""
read -rsp "Senha do MySQL ROOT (admin local): " MYSQL_ROOT_PASS
echo ""

mysql -u root -p"$MYSQL_ROOT_PASS" -h "$DB_HOST" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';

GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'127.0.0.1';

FLUSH PRIVILEGES;
SQL

echo ""
echo "OK: banco e usuario criados."
echo "Proximo passo (se ainda nao rodou o schema):"
echo "  mysql -u root -p\"***\" -h $DB_HOST $DB_DATABASE < database/schema.sql"
echo "  mysql -u root -p\"***\" -h $DB_HOST $DB_DATABASE < database/seed.sql"
echo "  php scripts/migrate_baba_branding.php"
