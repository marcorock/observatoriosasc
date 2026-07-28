#!/usr/bin/env bash

set -euo pipefail

readonly DEFAULT_PROJECT_DIR="/var/www/projects/observatoriosasc"
readonly DEFAULT_CRON_USER="www-data"
readonly CRON_TARGET="/etc/cron.d/observatoriosasc-ppa"

show_help() {
    cat <<'EOF'
Uso:
  sudo bash install-ppa-sync-cron.sh [diretorio-do-projeto]

Variáveis opcionais:
  PPA_CRON_USER  Usuário do servidor web. Padrão: www-data

Exemplo:
  sudo bash install-ppa-sync-cron.sh /var/www/projects/observatoriosasc

O script instala uma sincronização diária às 04:15 no horário de São Paulo.
EOF
}

if [[ "${1:-}" == "-h" || "${1:-}" == "--help" ]]; then
    show_help
    exit 0
fi

if [[ $# -gt 1 ]]; then
    show_help >&2
    exit 1
fi

if [[ "${EUID}" -ne 0 ]]; then
    echo "Erro: execute este instalador como root usando sudo." >&2
    exit 1
fi

readonly PROJECT_DIR="${1:-$DEFAULT_PROJECT_DIR}"
readonly CRON_USER="${PPA_CRON_USER:-$DEFAULT_CRON_USER}"
readonly PHP_BIN="/usr/bin/php"
readonly SYNC_COMMAND="$PROJECT_DIR/bin/ppa-scheduled-sync.php"
readonly CACHE_DIR="$PROJECT_DIR/storage/cache/ppa/queries"

if [[ ! -d "/etc/cron.d" ]]; then
    echo "Erro: /etc/cron.d não existe. Instale e ative o serviço cron primeiro." >&2
    exit 1
fi

if [[ "$PROJECT_DIR" =~ [[:space:]] ]]; then
    echo "Erro: o caminho do projeto não pode conter espaços ou quebras de linha." >&2
    exit 1
fi

if [[ ! "$CRON_USER" =~ ^[a-z_][a-z0-9_-]*\$?$ ]]; then
    echo "Erro: o nome do usuário do cron possui formato inválido." >&2
    exit 1
fi

if [[ ! -d "$PROJECT_DIR" ]]; then
    echo "Erro: o diretório do projeto não existe: $PROJECT_DIR" >&2
    exit 1
fi

if [[ ! -x "$PHP_BIN" ]]; then
    echo "Erro: PHP não foi encontrado em $PHP_BIN." >&2
    exit 1
fi

if [[ ! -f "$SYNC_COMMAND" ]]; then
    echo "Erro: o comando de sincronização não existe: $SYNC_COMMAND" >&2
    exit 1
fi

if ! getent passwd "$CRON_USER" >/dev/null; then
    echo "Erro: o usuário do cron não existe: $CRON_USER" >&2
    exit 1
fi

if [[ ! -d "$CACHE_DIR" ]]; then
    echo "Erro: o diretório de cache não existe: $CACHE_DIR" >&2
    exit 1
fi

if ! runuser -u "$CRON_USER" -- test -w "$CACHE_DIR"; then
    echo "Erro: $CRON_USER não possui escrita no diretório de cache." >&2
    echo "Ajuste o dono ou grupo de $CACHE_DIR antes de instalar o cron." >&2
    exit 1
fi

if ! runuser -u "$CRON_USER" -- "$PHP_BIN" "$SYNC_COMMAND" --help >/dev/null; then
    echo "Erro: o comando não pôde ser validado como $CRON_USER." >&2
    exit 1
fi

temporary_file="$(mktemp)"
trap 'rm -f "$temporary_file"' EXIT

cat >"$temporary_file" <<EOF
SHELL=/bin/sh
PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
CRON_TZ=America/Sao_Paulo
15 4 * * * $CRON_USER cd $PROJECT_DIR && $PHP_BIN bin/ppa-scheduled-sync.php --all
EOF

install -o root -g root -m 0644 "$temporary_file" "$CRON_TARGET"

echo "Regra instalada com sucesso em $CRON_TARGET"
echo "Usuário: $CRON_USER"
echo "Horário: diariamente às 04:15 (America/Sao_Paulo)"
echo "Comando: cd $PROJECT_DIR && $PHP_BIN bin/ppa-scheduled-sync.php --all"
echo
echo "Conteúdo instalado:"
cat "$CRON_TARGET"
echo
echo "O daemon cron detecta arquivos em /etc/cron.d automaticamente."
echo "Após a primeira execução, confira o histórico no painel administrativo."
