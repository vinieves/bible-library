#!/usr/bin/env bash
#
# Deploy / atualização — Bible Library (produção VPS)
# Uso: chmod +x deploy.sh && ./deploy.sh
#
# Opções:
#   ./deploy.sh              → git pull + build completo (padrão)
#   ./deploy.sh --no-pull    → pula git pull (se você já puxou o código)
#   ./deploy.sh --with-seed    → roda seeders essenciais (primeira instalação)
#
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

SKIP_GIT_PULL=0
RUN_SEEDERS=0

for arg in "$@"; do
    case "$arg" in
        --no-pull) SKIP_GIT_PULL=1 ;;
        --with-seed) RUN_SEEDERS=1 ;;
        -h|--help)
            echo "Uso: $0 [--no-pull] [--with-seed]"
            exit 0
            ;;
        *)
            echo "Opção desconhecida: $arg (use --help)"
            exit 1
            ;;
    esac
done

log() { echo "[deploy] $*"; }
warn() { echo "[deploy] AVISO: $*" >&2; }
fail() { echo "[deploy] ERRO: $*" >&2; exit 1; }

log "Diretório: $APP_DIR"

# --- Pré-requisitos ---
command -v php >/dev/null 2>&1 || fail "PHP não encontrado."
command -v composer >/dev/null 2>&1 || fail "Composer não encontrado."
command -v npm >/dev/null 2>&1 || fail "npm não encontrado."
[[ -f artisan ]] || fail "artisan não encontrado — execute na raiz do projeto Laravel."
[[ -f .env ]] || fail "Arquivo .env ausente. Crie a partir de .env.example (veja docs/DEPLOY-VPS-BIBLE-LIBRARY.md)."

# --- Modo manutenção ---
log "Ativando modo manutenção..."
php artisan down || true

# --- Git ---
if [[ "$SKIP_GIT_PULL" -eq 0 ]]; then
    log "Atualizando código (git pull)..."
    git config --global --add safe.directory "$APP_DIR" 2>/dev/null || true
    git fetch origin
    git pull origin main
else
    log "Pulando git pull (--no-pull)."
fi

# --- Dependências PHP ---
log "Composer install..."
composer install --no-dev --optimize-autoloader --no-interaction

# --- Frontend ---
log "npm ci + build..."
npm ci
npm run build

# --- Laravel ---
log "Migrations..."
php artisan migrate --force

log "Storage link..."
php artisan storage:link 2>/dev/null || true

if [[ "$RUN_SEEDERS" -eq 1 ]]; then
    log "Seeders essenciais (--with-seed)..."
    php artisan db:seed --class=PlanSeeder --force
    php artisan db:seed --class=CategorySeeder --force
    php artisan db:seed --class=SettingSeeder --force
    php artisan db:seed --class=ProductSeeder --force
    php artisan db:seed --class=AudioSeeder --force
    warn "Não rode db:seed completo em produção (cria usuários demo)."
fi

log "Limpando e recriando caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize

# --- Permissões ---
log "Ajustando permissões..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

BIBLE_JSON="${BIBLE_DATA_PATH:-$APP_DIR/biblia_catolica_73_es_exp_compacto.json}"
if [[ -f "$BIBLE_JSON" ]]; then
    chown www-data:www-data "$BIBLE_JSON"
    chmod 644 "$BIBLE_JSON"
    log "JSON da Bíblia OK: $BIBLE_JSON"
else
    warn "JSON da Bíblia não encontrado: $BIBLE_JSON"
    warn "Libros (/mi-biblioteca/libros) não funcionará até enviar o arquivo."
    warn "Veja docs/DEPLOY-VPS-BIBLE-LIBRARY.md → Arquivo JSON da Bíblia."
fi

# --- Queue worker ---
if command -v supervisorctl >/dev/null 2>&1; then
    log "Reiniciando queue worker (Supervisor)..."
    supervisorctl restart bible-library-worker:* || warn "Falha ao reiniciar worker — verifique supervisorctl status"
else
    warn "supervisorctl não encontrado — reinicie o worker manualmente se usar filas."
fi

# --- Scheduler (cron) — necessário para notificações push agendadas/recorrentes ---
# Instala, uma única vez e de forma idempotente, o cron do www-data que roda
# `php artisan schedule:run` a cada minuto (dispara push:dispatch-scheduled).
SCHED_USER="www-data"
CRON_LINE="* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
if crontab -u "$SCHED_USER" -l 2>/dev/null | grep -Fq "artisan schedule:run"; then
    log "Cron do scheduler já configurado."
else
    log "Instalando cron do scheduler (schedule:run a cada minuto)..."
    if { crontab -u "$SCHED_USER" -l 2>/dev/null; echo "$CRON_LINE"; } | crontab -u "$SCHED_USER" -; then
        log "Cron instalado para $SCHED_USER."
    else
        warn "Não foi possível instalar o cron automaticamente. Adicione manualmente ao crontab do $SCHED_USER:"
        warn "  $CRON_LINE"
    fi
fi

# Aviso sobre a extensão gmp (obrigatória para gerar/usar chaves VAPID do web push).
if ! php -m | grep -qi '^gmp$'; then
    warn "Extensão PHP 'gmp' ausente — o envio de push (VAPID) NÃO funcionará."
    warn "Instale: apt install -y php8.3-gmp && systemctl restart php8.3-fpm"
fi

# --- Voltar ao ar ---
log "Desativando modo manutenção..."
php artisan up

# --- Verificação rápida ---
log "Verificação pós-deploy..."
php artisan about --only=environment,cache,drivers 2>/dev/null || php artisan about
php artisan migrate:status 2>/dev/null | tail -5 || true

if command -v supervisorctl >/dev/null 2>&1; then
    supervisorctl status bible-library-worker:* 2>/dev/null || true
fi

if [[ -n "${APP_URL:-}" ]]; then
    :
elif grep -q '^APP_URL=' .env 2>/dev/null; then
    APP_URL="$(grep '^APP_URL=' .env | cut -d= -f2- | tr -d '"')"
fi
if [[ -n "${APP_URL:-}" ]]; then
    curl -s -o /dev/null -w "HTTP %{http_code} → %s\n" "$APP_URL" || warn "curl falhou para $APP_URL"
fi

log "Deploy concluído com sucesso."
log "Teste no navegador: /mi-biblioteca · /mi-biblioteca/libros · /admin"
