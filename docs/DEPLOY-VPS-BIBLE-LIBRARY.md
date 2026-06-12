# Deploy completo — Bible Library na VPS

**Domínio:** `mediamrkt.online`  
**Stack:** Ubuntu 22.04/24.04 · Nginx · PHP 8.3 · MySQL 8 · Node.js 20 · Supervisor  
**Projeto:** Laravel 13 + Filament 4

> Execute os comandos **na VPS**, conectado via SSH como root ou usuário com `sudo`.

### Status atual (já concluído)

- [x] Projeto no GitHub
- [x] Pasta do projeto na VPS em `/var/www/bible-library`

**Comece pela [seção 2](#2-confirmar-projeto-na-vps)** e siga em ordem até o fim.

---

## Índice

1. [Antes de começar](#1-antes-de-começar)
2. [Confirmar projeto na VPS](#2-confirmar-projeto-na-vps)
3. [Preparar o servidor (Ubuntu)](#3-preparar-o-servidor-ubuntu)
4. [Instalar PHP 8.3 e extensões](#4-instalar-php-83-e-extensões)
5. [Instalar Composer, Node.js e MySQL](#5-instalar-composer-nodejs-e-mysql)
6. [Criar banco de dados](#6-criar-banco-de-dados)
7. [Configurar o projeto Laravel](#7-configurar-o-projeto-laravel)
8. [Configurar Nginx + SSL](#8-configurar-nginx--ssl)
9. [Queue Worker (Supervisor)](#9-queue-worker-supervisor)
10. [Criar usuário admin](#10-criar-usuário-admin)
11. [Firewall e permissões finais](#11-firewall-e-permissões-finais)
12. [Verificação pós-deploy](#12-verificação-pós-deploy)
13. [Atualizar o projeto depois](#13-atualizar-o-projeto-depois)
14. [URLs importantes](#14-urls-importantes)
15. [Solução de problemas](#15-solução-de-problemas)

---

## 1. Antes de começar

### O que você precisa ter em mãos

| Item | Exemplo |
|------|---------|
| IP da VPS | `72.61.222.108` |
| Acesso SSH | `ssh root@72.61.222.108` |
| Domínio | `mediamrkt.online` |
| E-mail para SSL | `profitminer369@gmail.com` |

### DNS (painel do domínio)

Crie estes registros **antes** do Certbot:

| Tipo | Nome | Valor | TTL |
|------|------|-------|-----|
| A | `@` | `72.61.222.108` | 300 |
| A | `www` | `72.61.222.108` | 300 |

Aguarde a propagação (5–30 minutos). Teste:

```bash
ping mediamrkt.online
```

---

## 2. Confirmar projeto na VPS

Conecte na VPS:

```bash
ssh root@72.61.222.108
```

Entre na pasta do projeto:

```bash
cd /var/www/bible-library
```

Confirme que os arquivos estão corretos:

```bash
ls -la
ls composer.json artisan .env.example docs/DEPLOY-VPS-BIBLE-LIBRARY.md
```

Deve existir `composer.json`, `artisan` e a pasta `app/`.

### Sincronizar com o GitHub (última versão)

Antes de instalar dependências, puxe o código mais recente:

```bash
cd /var/www/bible-library
git status
git remote -v
git pull origin main
```

Se `git pull` pedir credenciais do GitHub, use um **Personal Access Token** como senha.

> Se a pasta **não** for um clone Git (foi copiada manualmente), inicialize o remoto:
>
> ```bash
> cd /var/www/bible-library
> git init
> git remote add origin https://github.com/SEU_USUARIO/bible-library.git
> git fetch origin
> git checkout -b main origin/main
> ```

### Atenção ao `.env`

- Se **não existir** `.env` → crie na [seção 7](#7-configurar-o-projeto-laravel) com `cp .env.example .env`
- Se **já existir** `.env` copiado do PC local → **substitua** pelo `.env` de produção da seção 7 (o local usa SQLite e `APP_DEBUG=true`)

---

## 3. Preparar o servidor (Ubuntu)

Na VPS (já conectado):

Atualize o sistema e instale pacotes base:

```bash
apt update && apt upgrade -y
apt install -y software-properties-common curl wget git unzip zip ufw fail2ban
timedatectl set-timezone America/Sao_Paulo
```

---

## 4. Instalar PHP 8.3 e extensões

```bash
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y \
  php8.3-fpm \
  php8.3-cli \
  php8.3-common \
  php8.3-mysql \
  php8.3-mbstring \
  php8.3-xml \
  php8.3-curl \
  php8.3-zip \
  php8.3-gd \
  php8.3-bcmath \
  php8.3-intl \
  php8.3-readline \
  php8.3-tokenizer \
  php8.3-fileinfo
```

Confirme a versão:

```bash
php -v
```

Deve mostrar **PHP 8.3.x**.

Ajuste limites do PHP (upload de PDFs/áudios):

```bash
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/8.3/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.3/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 120/' /etc/php/8.3/fpm/php.ini

systemctl restart php8.3-fpm
systemctl enable php8.3-fpm
```

---

## 5. Instalar Composer, Node.js e MySQL

### Composer

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
composer --version
```

### Node.js 20

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
node -v
npm -v
```

### MySQL 8

```bash
apt install -y mysql-server
systemctl enable mysql
systemctl start mysql
mysql --version
```

Segurança básica do MySQL (responda às perguntas; defina senha forte para root):

```bash
mysql_secure_installation
```

---

## 6. Criar banco de dados

Substitua `SUA_SENHA_MYSQL_FORTE` por uma senha real:

```bash
mysql -u root -p <<'EOF'
CREATE DATABASE bible_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bible_library'@'localhost' IDENTIFIED BY 'Viniroot333+';
GRANT ALL PRIVILEGES ON bible_library.* TO 'bible_library'@'localhost';
FLUSH PRIVILEGES;
EOF
```

Teste a conexão:

```bash
mysql -u bible_library -p bible_library -e "SELECT 1;"
```

---

## 7. Configurar o projeto Laravel

```bash
cd /var/www/bible-library
```

### Permissões iniciais

```bash
chown -R www-data:www-data /var/www/bible-library
chmod -R 755 /var/www/bible-library
chmod -R 775 /var/www/bible-library/storage
chmod -R 775 /var/www/bible-library/bootstrap/cache
```

### Criar arquivo .env de produção

```bash
cp .env.example .env
nano .env
```

Cole e ajuste **todo** o conteúdo abaixo (troque senhas):

```env
APP_NAME="Biblioteca Bíblica Digital"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://mediamrkt.online

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
DB_DATABASE=bible_library
DB_USERNAME=bible_library
DB_PASSWORD=Viniroot333+

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
MAIL_FROM_ADDRESS="soporte@mediamrkt.online"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"
```

Salve: `Ctrl+O`, Enter, `Ctrl+X`.

### Instalar dependências e build

```bash
cd /var/www/bible-library

composer install --no-dev --optimize-autoloader --no-interaction

npm ci
npm run build

php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
```

### Seeders essenciais (sem usuários demo)

```bash
php artisan db:seed --class=PlanSeeder --force
php artisan db:seed --class=CategorySeeder --force
php artisan db:seed --class=SettingSeeder --force
php artisan db:seed --class=ProductSeeder --force
```

> **Não** rode `php artisan db:seed` completo em produção — ele cria usuários demo com senha `password`.

### Seeders de conteúdo (livros, bônus e áudios)

**Obrigatório** para os clientes verem materiais em `/mi-biblioteca/libros` e `/mi-biblioteca/bonos`.  
Esses seeders **não** fazem parte do `DatabaseSeeder` padrão — sem eles o banco fica só com planos/categorias vazias.

```bash
cd /var/www/bible-library

php artisan db:seed --class=LibroSeeder --force
php artisan db:seed --class=BonusPdfSeeder --force
php artisan db:seed --class=AudioSeeder --force
```

Confirme que os registros foram criados:

```bash
php artisan tinker --execute="echo 'Livros: '.App\Models\Material::where('type', App\Enums\MaterialType::Libro)->count().PHP_EOL;"
php artisan tinker --execute="echo 'Bônus: '.App\Models\Material::where('type', App\Enums\MaterialType::Bonus)->count().PHP_EOL;"
```

Resultado esperado:

| Tipo | Quantidade |
|------|------------|
| Livros | **7** |
| Bônus | **5** |

### PDFs e capas (via Git)

Os arquivos devem ficar nestas pastas **dentro do projeto**:

```
storage/app/private/pdfs/bonuses/
  bonus1-mandamentos.pdf
  bonus2-milagres.pdf
  bonus3-historias.pdf
  bonus4-devocional.pdf
  bonus5-manual.pdf

storage/app/public/covers/
  libros/          → el-pentateuco.jpg, los-4-evangelicos.jpg, etc.
  bonuses/         → img-bonus-1.jpg, img-bonus-2.jpg, etc.
  audios/          → capas dos áudios (opcional)
```

Os nomes dos PDFs de bônus precisam bater com o `BonusPdfSeeder`. As capas usam o nome base + extensão (`.jpg`, `.png` ou `.webp`).

**No PC Windows** — copie seus arquivos para essas pastas e envie ao GitHub:

```powershell
cd "C:\Users\T-GAMER\OneDrive\Área de Trabalho\BIBLE_LIBRARY"

git add storage/app/private/pdfs storage/app/public/covers
git status
git commit -m "Adiciona PDFs e capas dos materiais"
git push origin main
```

**Na VPS** — puxe e ajuste permissões:

```bash
cd /var/www/bible-library
git pull origin main
chown -R www-data:www-data storage
chmod -R 775 storage
php artisan storage:link
```

> Repositório **privado** recomendado — os PDFs são conteúdo pago.  
> Se o `git push` reclamar de arquivo grande (>100 MB), use [Git LFS](https://git-lfs.com/) ou envie só esse arquivo via SCP.

### Otimizar Laravel para produção

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize
```

### Permissões finais do storage

```bash
chown -R www-data:www-data /var/www/bible-library/storage
chown -R www-data:www-data /var/www/bible-library/bootstrap/cache
chmod -R 775 /var/www/bible-library/storage
chmod -R 775 /var/www/bible-library/bootstrap/cache
```

---

## 8. Configurar Nginx + SSL

### Instalar Nginx e Certbot

```bash
apt install -y nginx certbot python3-certbot-nginx
systemctl enable nginx
systemctl start nginx
```

### Criar site

```bash
nano /etc/nginx/sites-available/bible-library
```

Cole:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name mediamrkt.online www.mediamrkt.online;
    root /var/www/bible-library/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Ativar o site e remover default:

```bash
ln -sf /etc/nginx/sites-available/bible-library /etc/nginx/sites-enabled/bible-library
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx
```

### Gerar certificado SSL (HTTPS)

Substitua o e-mail:

```bash
certbot --nginx -d mediamrkt.online -d www.mediamrkt.online --non-interactive --agree-tos -m profitminer369@gmail.com --redirect
```

Renovação automática (já vem com certbot, confirme):

```bash
certbot renew --dry-run
```

---

## 9. Queue Worker (Supervisor)

O WhatsApp e outros jobs usam fila (`QUEUE_CONNECTION=database`). Sem o worker, nada processa.

```bash
apt install -y supervisor
systemctl enable supervisor
systemctl start supervisor
```

Criar configuração:

```bash
nano /etc/supervisor/conf.d/bible-library-worker.conf
```

Cole:

```ini
[program:bible-library-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/bible-library/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/bible-library/storage/logs/worker.log
stopwaitsecs=3600
```

Ativar:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start bible-library-worker:*
supervisorctl status
```

---

## 10. Criar usuário admin

```bash
cd /var/www/bible-library
php artisan make:filament-user
```

Preencha:

- **Name:** Administrador
- **Email:** seu email real
- **Password:** senha forte (mínimo 12 caracteres)

Depois, no MySQL, marque como admin:

```bash
mysql -u bible_library -p bible_library -e "UPDATE users SET is_admin = 1 WHERE email = 'profitminer369@gmail.com';"
```

Acesse o painel: **https://mediamrkt.online/admin**

---

## 11. Firewall e permissões finais

```bash
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable
ufw status
```

---

## 12. Verificação pós-deploy

Execute na VPS:

```bash
cd /var/www/bible-library

php artisan about
php artisan migrate:status
supervisorctl status bible-library-worker:*
curl -I https://mediamrkt.online
curl -I https://mediamrkt.online/admin
curl -I https://mediamrkt.online/login
```

Checklist manual no navegador:

- [ ] Site abre em `https://mediamrkt.online`
- [ ] Login em `/login` funciona
- [ ] Admin em `/admin` funciona
- [ ] Área de membros `/mi-biblioteca` funciona após login
- [ ] Upload de PDF no admin funciona
- [ ] SSL válido (cadeado verde)

---

## 13. Atualizar o projeto depois

Salve este script na VPS:

```bash
nano /var/www/bible-library/deploy.sh
```

Conteúdo:

```bash
#!/bin/bash
set -e

cd /var/www/bible-library

php artisan down || true

git pull origin main

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan filament:optimize

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

supervisorctl restart bible-library-worker:*

php artisan up

echo "Deploy concluído!"
```

Permissão de execução:

```bash
chmod +x /var/www/bible-library/deploy.sh
```

Fluxo completo de atualização:

**No PC** (após alterações locais):

```powershell
cd "C:\Users\T-GAMER\OneDrive\Área de Trabalho\BIBLE_LIBRARY"
git add .
git commit -m "Descreva o que mudou"
git push origin main
```

**Na VPS**:

```bash
/var/www/bible-library/deploy.sh
```

---

## 14. URLs importantes

| Função | URL |
|--------|-----|
| Site público | https://mediamrkt.online |
| Login membros | https://mediamrkt.online/login |
| Área de membros | https://mediamrkt.online/mi-biblioteca |
| Painel admin | https://mediamrkt.online/admin |
| Webhook Hotmart | https://mediamrkt.online/api/webhooks/hotmart |
| Webhook genérico | https://mediamrkt.online/api/webhooks/generic |

Configure Hotmart e integrações em: **Admin → Integrações**

---

## 15. Solução de problemas

### Erro 502 Bad Gateway

```bash
systemctl status php8.3-fpm
systemctl restart php8.3-fpm
nginx -t
systemctl reload nginx
```

### Erro 500 — permissão

```bash
cd /var/www/bible-library
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
tail -n 50 storage/logs/laravel.log
```

### CSS/JS quebrado

```bash
cd /var/www/bible-library
npm ci
npm run build
php artisan view:clear
php artisan view:cache
```

### Livros ou bônus não aparecem para o cliente

Causa mais comum: seeders de conteúdo não foram executados.

```bash
cd /var/www/bible-library
php artisan db:seed --class=LibroSeeder --force
php artisan db:seed --class=BonusPdfSeeder --force
php artisan tinker --execute="echo 'Livros: '.App\Models\Material::where('type', App\Enums\MaterialType::Libro)->count().' | Bônus: '.App\Models\Material::where('type', App\Enums\MaterialType::Bonus)->count().PHP_EOL;"
```

O cliente também precisa de **pelo menos um plano** atribuído no admin (ex.: **Acceso Vitalicio**).  
Permissão de admin **não** substitui plano para a área de membros — marque os planos em **Usuários → Planos atribuídos**.

### Fila não processa (WhatsApp não envia)

```bash
supervisorctl status
supervisorctl restart bible-library-worker:*
tail -f /var/www/bible-library/storage/logs/worker.log
php artisan queue:failed
```

### Limpar cache após mudança no .env

```bash
cd /var/www/bible-library
php artisan config:clear
php artisan cache:clear
php artisan config:cache
supervisorctl restart bible-library-worker:*
```

---

## Próximo passo

Depois que o site estiver no ar e testado, instale a Evolution API seguindo:

**`docs/DEPLOY-VPS-EVOLUTION-API.md`**
