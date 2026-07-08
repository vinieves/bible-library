#!/usr/bin/env bash
#
# Provisionamento inicial completo - Bible Library
# Uso:
#   sudo bash first-upload.sh
#
# O script foi feito para uma VPS Ubuntu "crua".
# Ele instala:
# - Nginx
# - PHP 8.3 + extensoes
# - Composer
# - Node.js 20
# - MySQL
# - Supervisor
# - Certbot
# - Aplicacao Laravel Bible Library
#
# Observacoes:
# - O dominio precisa apontar para a VPS antes do SSL funcionar.
# - O repositorio informado e publico; se ficar privado, ajuste o clone.
# - O arquivo JSON da Biblia deve estar no repo ou ser enviado depois.

set -Eeuo pipefail

DOMAIN="mediamrkt.online"
WWW_DOMAIN="www.mediamrkt.online"
APP_NAME="Biblioteca Biblica Digital"
APP_DIR="/var/www/bible-library"
REPO_URL="https://github.com/vinieves/bible-library.git"
REPO_BRANCH="main"
APP_URL="https://${DOMAIN}"
PHP_VERSION="8.3"
NODE_MAJOR="20"
MYSQL_DB="bible_library"
MYSQL_USER="bible_library"
TIMEZONE="America/Sao_Paulo"
CERTBOT_EMAIL_DEFAULT="profitminer369@gmail.com"
SUPERVISOR_PROGRAM="bible-library-worker"
RUN_OPTIONAL_SEEDERS="1"
CREATE_ADMIN_USER="1"
INSTALL_EVOLUTION="1"
EVOLUTION_DIR="/opt/evolution-api"
EVOLUTION_DOMAIN="wpp.${DOMAIN}"
EVOLUTION_SERVER_URL="https://${EVOLUTION_DOMAIN}"
EVOLUTION_INSTANCE="biblioteca"

log() {
    echo "[first-upload] $*"
}

warn() {
    echo "[first-upload] AVISO: $*" >&2
}

fail() {
    echo "[first-upload] ERRO: $*" >&2
    exit 1
}

cleanup_on_error() {
    local exit_code=$?
    warn "Falhou na linha ${BASH_LINENO[0]} com codigo ${exit_code}."
    warn "Revise a saida acima, corrija o problema e rode o script novamente."
    exit "$exit_code"
}

trap cleanup_on_error ERR

require_root() {
    if [[ "${EUID}" -ne 0 ]]; then
        fail "Execute como root: sudo bash first-upload.sh"
    fi
}

command_exists() {
    command -v "$1" >/dev/null 2>&1
}

prompt_if_empty() {
    local var_name="$1"
    local prompt_text="$2"
    local secret="${3:-0}"
    local current_value="${!var_name:-}"

    if [[ -n "$current_value" ]]; then
        return 0
    fi

    if [[ "$secret" == "1" ]]; then
        read -r -s -p "$prompt_text: " current_value
        echo
    else
        read -r -p "$prompt_text: " current_value
    fi

    [[ -n "$current_value" ]] || fail "Valor obrigatorio nao informado: $var_name"
    printf -v "$var_name" '%s' "$current_value"
}

random_string() {
    openssl rand -base64 24 | tr -d '\n'
}

escape_php_single_quotes() {
    printf '%s' "$1" | sed "s/'/'\\\\''/g"
}

public_ip() {
    local ip
    ip="$(curl -4 -fsS https://api.ipify.org 2>/dev/null || true)"
    if [[ -z "$ip" ]]; then
        ip="$(hostname -I 2>/dev/null | awk '{print $1}')"
    fi
    printf '%s' "$ip"
}

resolved_ip() {
    local host="$1"
    getent ahostsv4 "$host" 2>/dev/null | awk 'NR==1{print $1}'
}

write_nginx_config() {
    cat > /etc/nginx/sites-available/bible-library <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} ${WWW_DOMAIN};
    root ${APP_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;
    client_max_body_size 2048M;

    location ~ \.mjs$ {
        default_type application/javascript;
        add_header Cache-Control "public, immutable";
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 600;
        fastcgi_send_timeout 600;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
}

write_supervisor_config() {
    cat > "/etc/supervisor/conf.d/${SUPERVISOR_PROGRAM}.conf" <<EOF
[program:${SUPERVISOR_PROGRAM}]
process_name=%(program_name)s_%(process_num)02d
command=php ${APP_DIR}/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=120
directory=${APP_DIR}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=${APP_DIR}/storage/logs/worker.log
stopwaitsecs=3600
EOF
}

write_env_file() {
    cat > "${APP_DIR}/.env" <<EOF
APP_NAME="${APP_NAME}"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=${APP_URL}

APP_TIMEZONE=UTC
APP_DISPLAY_TIMEZONE=${TIMEZONE}

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${MYSQL_DB}
DB_USERNAME=${MYSQL_USER}
DB_PASSWORD=${MYSQL_PASSWORD}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="${SUPPORT_EMAIL}"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="\${APP_NAME}"

BIBLE_DATA_PATH=${APP_DIR}/biblia_catolica_73_es_exp_compacto.json
EOF
}

write_evolution_compose() {
    mkdir -p "${EVOLUTION_DIR}"
    cat > "${EVOLUTION_DIR}/docker-compose.yml" <<EOF
services:
  evolution-api:
    container_name: evolution_api
    image: evoapicloud/evolution-api:v2.3.7
    restart: always
    depends_on:
      evolution-postgres:
        condition: service_healthy
      evolution-redis:
        condition: service_started
    ports:
      - "127.0.0.1:8080:8080"
    env_file:
      - .env
    volumes:
      - evolution_instances:/evolution/instances
    networks:
      - evolution-net

  evolution-postgres:
    container_name: evolution_postgres
    image: postgres:15-alpine
    restart: always
    environment:
      POSTGRES_DB: \${POSTGRES_DATABASE}
      POSTGRES_USER: \${POSTGRES_USERNAME}
      POSTGRES_PASSWORD: \${POSTGRES_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U \${POSTGRES_USERNAME} -d \${POSTGRES_DATABASE}"]
      interval: 5s
      timeout: 5s
      retries: 10
    networks:
      - evolution-net

  evolution-redis:
    container_name: evolution_redis
    image: redis:7-alpine
    restart: always
    command: redis-server --appendonly yes
    volumes:
      - evolution_redis:/data
    networks:
      - evolution-net

volumes:
  evolution_instances:
  postgres_data:
  evolution_redis:

networks:
  evolution-net:
    driver: bridge
EOF
}

write_evolution_env() {
    cat > "${EVOLUTION_DIR}/.env" <<EOF
SERVER_NAME=evolution
SERVER_TYPE=http
SERVER_PORT=8080
SERVER_URL=${EVOLUTION_SERVER_URL}

AUTHENTICATION_API_KEY=${EVOLUTION_API_KEY}
AUTHENTICATION_EXPOSE_IN_FETCH_INSTANCES=true

POSTGRES_DATABASE=evolution
POSTGRES_USERNAME=evolution_user
POSTGRES_PASSWORD=${EVOLUTION_POSTGRES_PASSWORD}

DATABASE_ENABLED=true
DATABASE_PROVIDER=postgresql
DATABASE_CONNECTION_URI=postgresql://evolution_user:${EVOLUTION_POSTGRES_PASSWORD}@evolution-postgres:5432/evolution
DATABASE_CONNECTION_CLIENT_NAME=evolution
DATABASE_SAVE_DATA_INSTANCE=true
DATABASE_SAVE_DATA_NEW_MESSAGE=false
DATABASE_SAVE_MESSAGE_UPDATE=false
DATABASE_SAVE_DATA_CONTACTS=false
DATABASE_SAVE_DATA_CHATS=false
DATABASE_SAVE_DATA_LABELS=false
DATABASE_SAVE_DATA_HISTORIC=false

CACHE_REDIS_ENABLED=true
CACHE_REDIS_URI=redis://evolution-redis:6379/0
CACHE_REDIS_PREFIX_KEY=evolution
CACHE_LOCAL_ENABLED=false

DEL_INSTANCE=false
CONFIG_SESSION_PHONE_CLIENT=Evolution API
CONFIG_SESSION_PHONE_NAME=Chrome

WEBSOCKET_ENABLED=true
NODE_OPTIONS=--dns-result-order=ipv4first

LOG_LEVEL=ERROR
LOG_COLOR=true
LOG_BAILEYS=error

WEBHOOK_GLOBAL_ENABLED=false
EOF

    chmod 600 "${EVOLUTION_DIR}/.env"
}

write_evolution_nginx_config() {
    cat > /etc/nginx/sites-available/evolution-api <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name ${EVOLUTION_DOMAIN};

    client_max_body_size 50M;

    location /socket.io/ {
        proxy_pass http://127.0.0.1:8080/socket.io/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection \$connection_upgrade;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 86400s;
        proxy_buffering off;
    }

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection \$connection_upgrade;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_read_timeout 300s;
        proxy_connect_timeout 300s;
    }
}
EOF
}

install_base_packages() {
    log "Atualizando sistema e instalando pacotes base..."
    export DEBIAN_FRONTEND=noninteractive
    apt update
    apt upgrade -y
    apt install -y software-properties-common curl wget git unzip zip ufw fail2ban ca-certificates gnupg lsb-release apt-transport-https python3
    timedatectl set-timezone "${TIMEZONE}" || true
}

install_php() {
    log "Instalando PHP ${PHP_VERSION}..."
    add-apt-repository ppa:ondrej/php -y
    apt update
    apt install -y \
        "php${PHP_VERSION}-fpm" \
        "php${PHP_VERSION}-cli" \
        "php${PHP_VERSION}-common" \
        "php${PHP_VERSION}-mysql" \
        "php${PHP_VERSION}-mbstring" \
        "php${PHP_VERSION}-xml" \
        "php${PHP_VERSION}-curl" \
        "php${PHP_VERSION}-zip" \
        "php${PHP_VERSION}-gd" \
        "php${PHP_VERSION}-bcmath" \
        "php${PHP_VERSION}-intl" \
        "php${PHP_VERSION}-readline" \
        "php${PHP_VERSION}-tokenizer" \
        "php${PHP_VERSION}-fileinfo" \
        "php${PHP_VERSION}-sqlite3"

    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 2048M/' "/etc/php/${PHP_VERSION}/fpm/php.ini"
    sed -i 's/^post_max_size = .*/post_max_size = 2048M/' "/etc/php/${PHP_VERSION}/fpm/php.ini"
    sed -i 's/^memory_limit = .*/memory_limit = 512M/' "/etc/php/${PHP_VERSION}/fpm/php.ini"
    sed -i 's/^max_execution_time = .*/max_execution_time = 600/' "/etc/php/${PHP_VERSION}/fpm/php.ini"

    systemctl enable "php${PHP_VERSION}-fpm"
    systemctl restart "php${PHP_VERSION}-fpm"
}

install_composer() {
    if command_exists composer; then
        log "Composer ja instalado."
        return
    fi

    log "Instalando Composer..."
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
}

install_node() {
    log "Instalando Node.js ${NODE_MAJOR}..."
    curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
    apt install -y nodejs
}

install_mysql() {
    log "Instalando MySQL..."
    apt install -y mysql-server
    systemctl enable mysql
    systemctl start mysql
}

install_nginx_and_certbot() {
    log "Instalando Nginx e Certbot..."
    apt install -y nginx certbot python3-certbot-nginx
    systemctl enable nginx
    systemctl start nginx
}

install_supervisor() {
    log "Instalando Supervisor..."
    apt install -y supervisor
    systemctl enable supervisor
    systemctl start supervisor
}

install_docker() {
    if command_exists docker && docker compose version >/dev/null 2>&1; then
        log "Docker e Docker Compose ja instalados."
        return
    fi

    log "Instalando Docker..."
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg

    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
      $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
      tee /etc/apt/sources.list.d/docker.list > /dev/null

    apt update
    apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    systemctl enable docker
    systemctl start docker
}

prepare_database() {
    log "Criando banco e usuario MySQL..."
    mysql <<EOF
CREATE DATABASE IF NOT EXISTS \`${MYSQL_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'localhost' IDENTIFIED BY '${MYSQL_PASSWORD}';
ALTER USER '${MYSQL_USER}'@'localhost' IDENTIFIED BY '${MYSQL_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${MYSQL_DB}\`.* TO '${MYSQL_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF
}

clone_or_update_repo() {
    mkdir -p /var/www

    if [[ ! -d "${APP_DIR}/.git" ]]; then
        log "Clonando repositorio..."
        rm -rf "${APP_DIR}"
        git clone --branch "${REPO_BRANCH}" "${REPO_URL}" "${APP_DIR}"
    else
        log "Repositorio ja existe. Atualizando..."
        git -C "${APP_DIR}" fetch origin
        git -C "${APP_DIR}" checkout "${REPO_BRANCH}"
        git -C "${APP_DIR}" pull origin "${REPO_BRANCH}"
    fi
}

prepare_project_permissions() {
    log "Ajustando permissoes iniciais..."
    chown -R www-data:www-data "${APP_DIR}"
    chmod -R 755 "${APP_DIR}"
    mkdir -p "${APP_DIR}/storage/logs" "${APP_DIR}/storage/app/private" "${APP_DIR}/storage/app/public"
    chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
    chown -R www-data:www-data "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"
}

configure_application() {
    log "Configurando .env de producao..."
    if [[ ! -f "${APP_DIR}/.env.example" ]]; then
        fail ".env.example nao encontrado em ${APP_DIR}"
    fi

    write_env_file
    chown root:www-data "${APP_DIR}/.env"
    chmod 640 "${APP_DIR}/.env"

    cd "${APP_DIR}"

    log "Instalando dependencias PHP..."
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction

    log "Instalando dependencias frontend..."
    npm ci
    npm run build

    log "Gerando chave da aplicacao..."
    php artisan key:generate --force

    log "Rodando migrations..."
    php artisan migrate --force

    log "Criando link do storage..."
    php artisan storage:link || true

    log "Rodando seeders obrigatorios..."
    php artisan db:seed --class=PlanSeeder --force
    php artisan db:seed --class=CategorySeeder --force
    php artisan db:seed --class=SettingSeeder --force
    php artisan db:seed --class=ProductSeeder --force

    if [[ "${RUN_OPTIONAL_SEEDERS}" == "1" ]]; then
        log "Rodando seeders recomendados..."
        php artisan db:seed --class=AudioSeeder --force
        if [[ -f "${APP_DIR}/database/seeders/ForumSeeder.php" ]]; then
            php artisan db:seed --class=ForumSeeder --force || warn "ForumSeeder falhou; verifique se o arquivo existe no branch."
        fi
    fi

    log "Otimizando Laravel..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan filament:optimize
}

configure_nginx() {
    log "Escrevendo configuracao do Nginx..."
    write_nginx_config
    ln -sf /etc/nginx/sites-available/bible-library /etc/nginx/sites-enabled/bible-library
    rm -f /etc/nginx/sites-enabled/default

    if grep -n "client_max_body_size" /etc/nginx/nginx.conf >/dev/null 2>&1; then
        sed -i 's/^\s*client_max_body_size .*/    client_max_body_size 2048M;/' /etc/nginx/nginx.conf
    else
        python3 - <<'PY'
from pathlib import Path
path = Path("/etc/nginx/nginx.conf")
text = path.read_text()
needle = "http {\n"
replacement = "http {\n    client_max_body_size 2048M;\n"
if "client_max_body_size 2048M;" not in text:
    text = text.replace(needle, replacement, 1)
path.write_text(text)
PY
    fi

    nginx -t
    systemctl reload nginx
}

configure_nginx_map_for_evolution() {
    if grep -q "map \$http_upgrade \$connection_upgrade" /etc/nginx/nginx.conf; then
        return 0
    fi

    python3 - <<'PY'
from pathlib import Path
path = Path("/etc/nginx/nginx.conf")
text = path.read_text()
needle = "http {\n"
replacement = (
    "http {\n"
    "    map $http_upgrade $connection_upgrade {\n"
    "        default upgrade;\n"
    "        ''      close;\n"
    "    }\n\n"
)
text = text.replace(needle, replacement, 1)
path.write_text(text)
PY
}

configure_ssl_if_possible() {
    local current_ip resolved_domain_ip resolved_www_ip
    current_ip="$(public_ip)"
    resolved_domain_ip="$(resolved_ip "${DOMAIN}")"
    resolved_www_ip="$(resolved_ip "${WWW_DOMAIN}")"

    if [[ -z "${current_ip}" ]]; then
        warn "Nao consegui descobrir o IP publico da VPS. Pulando emissao automatica do SSL."
        return 0
    fi

    if [[ "${resolved_domain_ip}" != "${current_ip}" || "${resolved_www_ip}" != "${current_ip}" ]]; then
        warn "DNS ainda nao aponta corretamente para esta VPS."
        warn "${DOMAIN} -> ${resolved_domain_ip:-sem-resposta}"
        warn "${WWW_DOMAIN} -> ${resolved_www_ip:-sem-resposta}"
        warn "IP da VPS -> ${current_ip}"
        warn "Pulando Certbot por agora. Depois rode manualmente:"
        warn "certbot --nginx -d ${DOMAIN} -d ${WWW_DOMAIN} --non-interactive --agree-tos -m ${CERTBOT_EMAIL} --redirect"
        return 0
    fi

    log "Emitindo certificado SSL..."
    certbot --nginx -d "${DOMAIN}" -d "${WWW_DOMAIN}" --non-interactive --agree-tos -m "${CERTBOT_EMAIL}" --redirect
    certbot renew --dry-run || warn "Teste de renovacao do certbot falhou; revise mais tarde."
}

configure_supervisor() {
    log "Configurando worker da fila..."
    write_supervisor_config
    supervisorctl reread
    supervisorctl update
    supervisorctl restart "${SUPERVISOR_PROGRAM}:"* || supervisorctl start "${SUPERVISOR_PROGRAM}:"*
}

configure_evolution_nginx() {
    log "Configurando Nginx da Evolution..."
    configure_nginx_map_for_evolution
    write_evolution_nginx_config
    ln -sf /etc/nginx/sites-available/evolution-api /etc/nginx/sites-enabled/evolution-api
    nginx -t
    systemctl reload nginx
}

configure_evolution_ssl_if_possible() {
    local current_ip resolved_wpp_ip
    current_ip="$(public_ip)"
    resolved_wpp_ip="$(resolved_ip "${EVOLUTION_DOMAIN}")"

    if [[ -z "${current_ip}" ]]; then
        warn "Nao consegui descobrir o IP publico da VPS. Pulando SSL da Evolution."
        return 0
    fi

    if [[ "${resolved_wpp_ip}" != "${current_ip}" ]]; then
        warn "DNS do subdominio da Evolution ainda nao aponta para esta VPS."
        warn "${EVOLUTION_DOMAIN} -> ${resolved_wpp_ip:-sem-resposta}"
        warn "IP da VPS -> ${current_ip}"
        warn "Pulando Certbot da Evolution por agora."
        return 0
    fi

    certbot --nginx -d "${EVOLUTION_DOMAIN}" --non-interactive --agree-tos -m "${CERTBOT_EMAIL}" --redirect
}

install_evolution_stack() {
    if [[ "${INSTALL_EVOLUTION}" != "1" ]]; then
        warn "Instalacao da Evolution desativada."
        return 0
    fi

    log "Preparando stack Evolution API..."
    install_docker
    write_evolution_compose
    write_evolution_env

    cd "${EVOLUTION_DIR}"
    docker compose pull
    docker compose up -d

    configure_evolution_nginx
    configure_evolution_ssl_if_possible
}

wait_for_evolution() {
    if [[ "${INSTALL_EVOLUTION}" != "1" ]]; then
        return 0
    fi

    log "Aguardando Evolution responder localmente..."
    local tries=0
    local http_code=""
    while true; do
        http_code="$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080 || true)"
        if [[ "${http_code}" == "200" || "${http_code}" == "404" || "${http_code}" == "401" ]]; then
            break
        fi
        tries=$((tries + 1))
        if (( tries >= 30 )); then
            warn "Evolution nao respondeu em tempo. Veja: docker logs evolution_api --tail 100"
            return 0
        fi
        sleep 3
    done
}

create_evolution_instance_if_requested() {
    if [[ "${INSTALL_EVOLUTION}" != "1" ]]; then
        return 0
    fi

    wait_for_evolution

    log "Criando instancia Evolution '${EVOLUTION_INSTANCE}' (ignora se ja existir)..."
    curl -fsS -X POST "http://127.0.0.1:8080/instance/create" \
        -H "Content-Type: application/json" \
        -H "apikey: ${EVOLUTION_API_KEY}" \
        -d "{\"instanceName\":\"${EVOLUTION_INSTANCE}\",\"integration\":\"WHATSAPP-BAILEYS\",\"qrcode\":true}" \
        >/tmp/evolution-instance-create.json 2>/dev/null || true
}

configure_laravel_evolution_settings() {
    if [[ "${INSTALL_EVOLUTION}" != "1" ]]; then
        return 0
    fi

    log "Gravando integracoes da Evolution no Laravel..."
    cd "${APP_DIR}"
    php artisan tinker --execute="
        App\Models\Setting::set('whatsapp_enabled', '1');
        App\Models\Setting::set('evolution_base_url', '${EVOLUTION_SERVER_URL}');
        App\Models\Setting::set('evolution_instance', '${EVOLUTION_INSTANCE}');
        App\Models\Setting::set('evolution_instance_messages', '${EVOLUTION_INSTANCE}');
        App\Models\Setting::set('evolution_instance_flows', '${EVOLUTION_INSTANCE}');
        App\Models\Setting::setEncrypted('evolution_api_key', '${EVOLUTION_API_KEY}');
        App\Models\Setting::setEncrypted('webhook_secret', '${WEBHOOK_SECRET}');
        echo 'Evolution settings OK' . PHP_EOL;
    "
}

configure_firewall() {
    log "Configurando firewall..."
    ufw allow OpenSSH
    ufw allow 'Nginx Full'
    ufw --force enable
}

ensure_bible_json_permissions() {
    local bible_json="${APP_DIR}/biblia_catolica_73_es_exp_compacto.json"

    if [[ -f "${bible_json}" ]]; then
        chown www-data:www-data "${bible_json}"
        chmod 644 "${bible_json}"
        log "JSON da Biblia encontrado."
    else
        warn "JSON da Biblia nao encontrado em ${bible_json}."
        warn "A rota /mi-biblioteca/libros nao funcionara ate o arquivo existir."
    fi
}

create_admin_user_if_requested() {
    if [[ "${CREATE_ADMIN_USER}" != "1" ]]; then
        warn "Criacao automatica de admin desativada."
        return 0
    fi

    prompt_if_empty ADMIN_NAME "Nome do usuario admin"
    prompt_if_empty ADMIN_EMAIL "Email do usuario admin"
    prompt_if_empty ADMIN_PASSWORD "Senha do usuario admin" 1

    local admin_name_escaped admin_email_escaped admin_password_escaped
    admin_name_escaped="$(escape_php_single_quotes "${ADMIN_NAME}")"
    admin_email_escaped="$(escape_php_single_quotes "${ADMIN_EMAIL}")"
    admin_password_escaped="$(escape_php_single_quotes "${ADMIN_PASSWORD}")"

    log "Criando/atualizando usuario admin..."
    cd "${APP_DIR}"
    php artisan tinker --execute="
        \$user = App\Models\User::updateOrCreate(
            ['email' => '${admin_email_escaped}'],
            [
                'name' => '${admin_name_escaped}',
                'password' => Illuminate\Support\Facades\Hash::make('${admin_password_escaped}'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );
        echo 'Admin OK: ' . \$user->email . PHP_EOL;
    "
}

show_summary() {
    log "Resumo final:"
    echo "  Site: ${APP_URL}"
    echo "  Admin: ${APP_URL}/admin"
    echo "  Login: ${APP_URL}/login"
    echo "  App dir: ${APP_DIR}"
    echo "  DB: ${MYSQL_DB}"
    echo "  DB user: ${MYSQL_USER}"
    echo "  Queue worker: ${SUPERVISOR_PROGRAM}"
    echo "  Timezone do servidor: ${TIMEZONE}"
    echo "  Arquivo .env: ${APP_DIR}/.env"
    if [[ "${INSTALL_EVOLUTION}" == "1" ]]; then
        echo "  Evolution: ${EVOLUTION_SERVER_URL}"
        echo "  Evolution instance: ${EVOLUTION_INSTANCE}"
        echo "  Evolution dir: ${EVOLUTION_DIR}"
    fi
    echo
    echo "Credenciais geradas:"
    echo "  MySQL password: ${MYSQL_PASSWORD}"
    if [[ "${CREATE_ADMIN_USER}" == "1" ]]; then
        echo "  Admin email: ${ADMIN_EMAIL}"
    fi
    if [[ "${INSTALL_EVOLUTION}" == "1" ]]; then
        echo "  Evolution API key: ${EVOLUTION_API_KEY}"
        echo "  Evolution Postgres password: ${EVOLUTION_POSTGRES_PASSWORD}"
    fi
    echo
    echo "Checks uteis:"
    echo "  systemctl status nginx php${PHP_VERSION}-fpm mysql supervisor --no-pager"
    echo "  supervisorctl status ${SUPERVISOR_PROGRAM}:*"
    echo "  php ${APP_DIR}/artisan about"
    echo "  php ${APP_DIR}/artisan migrate:status"
    if [[ "${INSTALL_EVOLUTION}" == "1" ]]; then
        echo "  docker compose -f ${EVOLUTION_DIR}/docker-compose.yml ps"
        echo "  curl -H 'apikey: ${EVOLUTION_API_KEY}' ${EVOLUTION_SERVER_URL}/instance/connectionState/${EVOLUTION_INSTANCE}"
    fi
}

main() {
    require_root

    CERTBOT_EMAIL="${CERTBOT_EMAIL:-$CERTBOT_EMAIL_DEFAULT}"
    SUPPORT_EMAIL="${SUPPORT_EMAIL:-soporte@${DOMAIN}}"
    MYSQL_PASSWORD="${MYSQL_PASSWORD:-$(random_string)}"
    EVOLUTION_API_KEY="${EVOLUTION_API_KEY:-$(openssl rand -hex 32)}"
    EVOLUTION_POSTGRES_PASSWORD="${EVOLUTION_POSTGRES_PASSWORD:-$(random_string)}"
    WEBHOOK_SECRET="${WEBHOOK_SECRET:-$(openssl rand -hex 24)}"
    ADMIN_NAME="${ADMIN_NAME:-}"
    ADMIN_EMAIL="${ADMIN_EMAIL:-}"
    ADMIN_PASSWORD="${ADMIN_PASSWORD:-}"

    log "Iniciando provisionamento do ${APP_NAME}..."
    log "Dominio principal: ${DOMAIN}"
    log "Repositorio: ${REPO_URL}"

    install_base_packages
    install_php
    install_composer
    install_node
    install_mysql
    install_nginx_and_certbot
    install_supervisor
    prepare_database
    clone_or_update_repo
    prepare_project_permissions
    configure_application
    configure_nginx
    configure_ssl_if_possible
    ensure_bible_json_permissions
    configure_supervisor
    install_evolution_stack
    create_evolution_instance_if_requested
    configure_laravel_evolution_settings
    configure_firewall
    create_admin_user_if_requested

    cd "${APP_DIR}"
    php artisan up || true
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan filament:optimize

    show_summary
}

main "$@"
