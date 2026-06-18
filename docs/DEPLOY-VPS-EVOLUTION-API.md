# Deploy completo — Evolution API na VPS

**Domínio Evolution:** `wpp.mediamrkt.online`  
**Domínio Bible Library:** `mediamrkt.online` (já instalado antes)  
**Stack:** Docker · Docker Compose · PostgreSQL · Redis · Nginx · Certbot

> Instale a Evolution **depois** que o Bible Library já estiver funcionando.  
> Execute os comandos **na VPS**, salvo quando indicado o contrário.

### Configuração validada em produção

- Imagem: **`evoapicloud/evolution-api:v2.3.7`** (não use `atendai/evolution-api:v2.1.1`)
- `.env` com `WEBSOCKET_ENABLED=true` e **sem** `CONFIG_SESSION_PHONE_VERSION`
- Instância `biblioteca` + manager em `https://wpp.mediamrkt.online/manager`

---

## Índice

1. [Antes de começar](#1-antes-de-começar)
2. [DNS do subdomínio](#2-dns-do-subdomínio)
3. [Instalar Docker](#3-instalar-docker)
4. [Criar estrutura da Evolution](#4-criar-estrutura-da-evolution)
5. [Arquivo docker-compose.yml](#5-arquivo-docker-composeyml)
6. [Arquivo .env da Evolution](#6-arquivo-env-da-evolution)
7. [Subir os containers](#7-subir-os-containers)
8. [Nginx + SSL para wpp.mediamrkt.online](#8-nginx--ssl-para-wppmediamrktonline)
9. [Criar instância WhatsApp](#9-criar-instância-whatsapp)
10. [Conectar no Bible Library](#10-conectar-no-bible-library)
11. [Testar envio de mensagem](#11-testar-envio-de-mensagem)
12. [Atualizar a Evolution depois](#12-atualizar-a-evolution-depois)
13. [Backup da sessão WhatsApp](#13-backup-da-sessão-whatsapp)
14. [Solução de problemas](#14-solução-de-problemas)

---

## 1. Antes de começar

### Pré-requisitos

- [ ] Bible Library rodando em `https://mediamrkt.online`
- [ ] Acesso SSH root ou sudo na VPS
- [ ] Pelo menos **2 GB RAM** livres (Evolution + Postgres + Redis)
- [ ] Portas 80 e 443 abertas (Nginx já instalado)

### O que será instalado

| Serviço | Função |
|---------|--------|
| evolution-api | API WhatsApp (porta interna 8080) |
| evolution-postgres | Banco de dados da Evolution |
| evolution-redis | Cache/sessões |
| Nginx | Proxy HTTPS para `wpp.mediamrkt.online` |

A Evolution **não** ficará exposta diretamente na internet — só via Nginx com HTTPS.

---

## 2. DNS do subdomínio

No painel do domínio `mediamrkt.online`, crie:

| Tipo | Nome | Valor | TTL |
|------|------|-------|-----|
| A | `wpp` | IP_DA_VPS | 300 |

Aguarde propagação e teste:

```bash
ping wpp.mediamrkt.online
```

---

## 3. Instalar Docker

Conecte na VPS:

```bash
ssh root@72.61.222.108
```

Instale Docker oficial:

```bash
apt update
apt install -y ca-certificates curl gnupg
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
docker --version
docker compose version
```

---

## 4. Criar estrutura da Evolution

```bash
mkdir -p /opt/evolution-api
cd /opt/evolution-api
```

---

## 5. Arquivo docker-compose.yml

Crie o arquivo:

```bash
nano /opt/evolution-api/docker-compose.yml
```

Cole **todo** o conteúdo:

```yaml
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
      POSTGRES_DB: ${POSTGRES_DATABASE}
      POSTGRES_USER: ${POSTGRES_USERNAME}
      POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USERNAME} -d ${POSTGRES_DATABASE}"]
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
```

Salve: `Ctrl+O`, Enter, `Ctrl+X`.

---

## 6. Arquivo .env da Evolution

Gere uma API Key forte:

```bash
openssl rand -hex 32
```

Anote o resultado. Exemplo: `a1b2c3d4e5f6...` — use o **seu** valor real abaixo.

Gere senha do Postgres:

```bash
openssl rand -hex 24
```

Crie o `.env`:

```bash
nano /opt/evolution-api/.env
```

Cole e **substitua todos os valores marcados**:

```env
# ===========================================
# SERVIDOR
# ===========================================
SERVER_NAME=evolution
SERVER_TYPE=http
SERVER_PORT=8080
SERVER_URL=https://wpp.mediamrkt.online

# ===========================================
# AUTENTICAÇÃO (cole a API Key gerada acima)
# ===========================================
AUTHENTICATION_API_KEY=COLE_SUA_API_KEY_AQUI
AUTHENTICATION_EXPOSE_IN_FETCH_INSTANCES=true

# ===========================================
# BANCO POSTGRES (interno Docker)
# ===========================================
POSTGRES_DATABASE=evolution
POSTGRES_USERNAME=evolution_user
POSTGRES_PASSWORD=COLE_SUA_SENHA_POSTGRES_AQUI

DATABASE_ENABLED=true
DATABASE_PROVIDER=postgresql
DATABASE_CONNECTION_URI=postgresql://evolution_user:COLE_SUA_SENHA_POSTGRES_AQUI@evolution-postgres:5432/evolution
DATABASE_CONNECTION_CLIENT_NAME=evolution
DATABASE_SAVE_DATA_INSTANCE=true
DATABASE_SAVE_DATA_NEW_MESSAGE=false
DATABASE_SAVE_MESSAGE_UPDATE=false
DATABASE_SAVE_DATA_CONTACTS=false
DATABASE_SAVE_DATA_CHATS=false
DATABASE_SAVE_DATA_LABELS=false
DATABASE_SAVE_DATA_HISTORIC=false

# ===========================================
# REDIS (interno Docker)
# ===========================================
CACHE_REDIS_ENABLED=true
CACHE_REDIS_URI=redis://evolution-redis:6379/0
CACHE_REDIS_PREFIX_KEY=evolution
CACHE_LOCAL_ENABLED=false

# ===========================================
# SESSÃO WHATSAPP
# ===========================================
DEL_INSTANCE=false
CONFIG_SESSION_PHONE_CLIENT=Evolution API
CONFIG_SESSION_PHONE_NAME=Chrome
# NÃO defina CONFIG_SESSION_PHONE_VERSION — versão fixa trava o QR

# ===========================================
# WEBSOCKET (manager / QR em tempo real)
# ===========================================
WEBSOCKET_ENABLED=true
NODE_OPTIONS=--dns-result-order=ipv4first

# ===========================================
# LOGS
# ===========================================
LOG_LEVEL=ERROR
LOG_COLOR=true
LOG_BAILEYS=error

# ===========================================
# WEBHOOK GLOBAL (desligado — Bible Library chama a API diretamente)
# ===========================================
WEBHOOK_GLOBAL_ENABLED=false
```

> **Importante:** a senha em `POSTGRES_PASSWORD` e em `DATABASE_CONNECTION_URI` deve ser **a mesma**.

Salve: `Ctrl+O`, Enter, `Ctrl+X`.

Proteja o arquivo:

```bash
chmod 600 /opt/evolution-api/.env
```

---

## 7. Subir os containers

```bash
cd /opt/evolution-api
docker compose pull
docker compose up -d
```

Verifique se tudo subiu:

```bash
docker compose ps
docker logs evolution_api --tail 50
```

Teste localmente na VPS:

```bash
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080
```

Deve retornar `200` ou `404` (API respondendo — não `000` ou `502`).

---

## 8. Nginx + SSL para wpp.mediamrkt.online

### WebSocket global (manager + QR em tempo real)

```bash
nano /etc/nginx/nginx.conf
```

Dentro do bloco `http {`, **no topo**, adicione:

```nginx
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}
```

### Criar configuração Nginx

```bash
nano /etc/nginx/sites-available/evolution-api
```

Cole:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name wpp.mediamrkt.online;

    client_max_body_size 50M;

    location /socket.io/ {
        proxy_pass http://127.0.0.1:8080/socket.io/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 86400s;
        proxy_buffering off;
    }

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection $connection_upgrade;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300s;
        proxy_connect_timeout 300s;
    }
}
```

Ativar:

```bash
ln -sf /etc/nginx/sites-available/evolution-api /etc/nginx/sites-enabled/evolution-api
nginx -t
systemctl reload nginx
```

### Gerar SSL

Substitua o e-mail:

```bash
certbot --nginx -d wpp.mediamrkt.online --non-interactive --agree-tos -m profitminer369@gmail.com --redirect
```

Teste externo:

```bash
curl -I https://wpp.mediamrkt.online
```

---

## 9. Criar instância WhatsApp

Substitua `COLE_SUA_API_KEY_AQUI` pela mesma chave do `.env`.

### 9.1 Criar a instância

```bash
curl -X POST "https://wpp.mediamrkt.online/instance/create" \
  -H "Content-Type: application/json" \
  -H "apikey: COLE_SUA_API_KEY_AQUI" \
  -d '{
    "instanceName": "biblioteca",
    "integration": "WHATSAPP-BAILEYS",
    "qrcode": true
  }'
```

### 9.2 Conectar o WhatsApp (escolha um método)

> Se a instância **já foi criada** pelo `curl` acima, **não crie de novo** no manager — só conecte a `biblioteca` existente.

#### Método C — Manager web (recomendado após v2.3.7)

1. Abra: **https://wpp.mediamrkt.online/manager**
2. Selecione a instância **`biblioteca`**
3. Clique para conectar e escaneie o QR no celular (expira em ~60s)

Funciona com `evoapicloud/evolution-api:v2.3.7` + `WEBSOCKET_ENABLED=true` + Nginx com WebSocket (seção 8).

#### Método A — Código de pareamento (alternativa sem QR)

Substitua `5511999999999` pelo seu WhatsApp com DDI (só dígitos):

```bash
curl -s -X GET "https://wpp.mediamrkt.online/instance/connect/biblioteca?number=5511999999999" \
  -H "apikey: COLE_SUA_API_KEY_AQUI" | python3 -m json.tool
```

Na resposta, copie o **`pairingCode`** (ex.: `ABCD-EFGH`).

No celular:
1. WhatsApp → **Aparelhos conectados**
2. **Conectar com número de telefone**
3. Digite o código `pairingCode`

#### Método B — QR Code no navegador (recomendado se o manager estiver em branco)

Na VPS:

```bash
API_KEY="COLE_SUA_API_KEY_AQUI"

curl -s -X GET "https://wpp.mediamrkt.online/instance/connect/biblioteca" \
  -H "apikey: $API_KEY" \
  -o /tmp/qr-response.json

python3 << 'EOF'
import json, base64, re

def find_base64(obj):
    if isinstance(obj, dict):
        for k, v in obj.items():
            if k == "base64" and isinstance(v, str) and "base64" in v:
                return v
            found = find_base64(v)
            if found:
                return found
    elif isinstance(obj, list):
        for item in obj:
            found = find_base64(item)
            if found:
                return found
    return None

with open("/tmp/qr-response.json") as f:
    data = json.load(f)

b64 = find_base64(data)
if not b64:
    print("ERRO: campo base64 não encontrado. Veja: cat /tmp/qr-response.json")
    raise SystemExit(1)

if not b64.startswith("data:image"):
    b64 = "data:image/png;base64," + b64

raw = re.sub(r"^data:image/[^;]+;base64,", "", b64)
png = base64.b64decode(raw)

if not png.startswith(b"\x89PNG\r\n\x1a\n"):
    print("ERRO: arquivo não é PNG válido — JSON pode estar incompleto ou expirado")
    raise SystemExit(1)

open("/tmp/qrcode.png", "wb").write(png)

html = f"""<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>QR WhatsApp</title></head>
<body style="display:flex;justify-content:center;align-items:center;min-height:100vh;background:#111;">
<img src="{b64}" alt="QR Code" style="max-width:400px;background:#fff;padding:16px;border-radius:12px;">
</body></html>"""
open("/tmp/qrcode.html", "w").write(html)
print("OK: /tmp/qrcode.png e /tmp/qrcode.html")
EOF
```

Baixe no **PowerShell do Windows** (não na VPS):

```powershell
ssh-keygen -R 72.61.222.108
scp root@72.61.222.108:/tmp/qrcode.html C:\Users\T-GAMER\Desktop\qrcode-whatsapp.html
```

Abra `qrcode-whatsapp.html` no Chrome e escaneie. O QR expira em ~60s — se expirar, repita o `curl` + script na VPS.

> Use o Método B só se o manager (Método C) não exibir o QR — veja seção 14.

### 9.3 Ver logs se algo falhar

```bash
docker logs evolution_api --tail 100
cat /tmp/qr-response.json | python3 -m json.tool | head -40
file /tmp/qrcode.png
```

`file /tmp/qrcode.png` deve mostrar: **PNG image data**

### 9.4 Conectar no celular (QR)

1. Abra o **WhatsApp** no celular
2. Vá em **Aparelhos conectados → Conectar aparelho**
3. Escaneie o QR Code (HTML ou PNG)

### 9.5 Confirmar conexão

```bash
curl -X GET "https://wpp.mediamrkt.online/instance/connectionState/biblioteca" \
  -H "apikey: COLE_SUA_API_KEY_AQUI"
```

Estado esperado: `"state": "open"` (conectado).

---

## 10. Conectar no Bible Library

Acesse o painel admin:

```
https://mediamrkt.online/admin
```

Vá em **Integrações → Evolution API (WhatsApp)** e preencha:

| Campo | Valor |
|-------|-------|
| Enviar WhatsApp automático | **Ativado** |
| URL base Evolution API | `https://wpp.mediamrkt.online` |
| Nome da instância | `biblioteca` |
| API Key | mesma `AUTHENTICATION_API_KEY` do `.env` |

Salve.

### URLs de webhook (Hotmart)

Configure na Hotmart quando for usar produção:

```
https://mediamrkt.online/api/webhooks/hotmart
```

O **hottok** da Hotmart vai no painel **Admin → Integrações**.

**Eventos recomendados na Hotmart** (todos suportados pelo Bible Library para WhatsApp/regras):

`PURCHASE_APPROVED`, `PURCHASE_COMPLETE`, `PURCHASE_CANCELED`, `PURCHASE_BILLET_PRINTED`, `PURCHASE_PROTEST`, `PURCHASE_REFUNDED`, `PURCHASE_CHARGEBACK`, `PURCHASE_EXPIRED`, `PURCHASE_DELAYED`, `PURCHASE_OUT_OF_SHOPPING_CART`

Referência completa dos campos do payload: `docs/dochotmart.txt`

---

## 11. Testar envio de mensagem

### 11.1 Pelo painel admin (recomendado)

1. Admin → **Integrações**
2. Preencha **Telefone de teste** (ex.: `5511999999999` — só dígitos, com DDI)
3. Clique em **Enviar teste WhatsApp**

### 11.2 Pelo curl direto na Evolution

```bash
curl -X POST "https://wpp.mediamrkt.online/message/sendText/biblioteca" \
  -H "Content-Type: application/json" \
  -H "apikey: COLE_SUA_API_KEY_AQUI" \
  -d '{
    "number": "5511999999999",
    "text": "Teste Evolution + Bible Library OK"
  }'
```

### 11.3 Confirmar queue worker do Laravel

O Bible Library envia WhatsApp via fila. Confirme que o worker está rodando:

```bash
supervisorctl status bible-library-worker:*
```

Se não estiver:

```bash
supervisorctl restart bible-library-worker:*
tail -f /var/www/bible-library/storage/logs/worker.log
```

---

## 12. Atualizar a Evolution depois

Atualizar a Evolution **não** afeta o Bible Library. Com volumes persistentes, a sessão WhatsApp costuma se manter.

```bash
cd /opt/evolution-api
docker compose pull
docker compose up -d
docker compose ps
docker logs evolution_api --tail 30
```

Verificar se ainda está conectada:

```bash
curl -X GET "https://wpp.mediamrkt.online/instance/connectionState/biblioteca" \
  -H "apikey: COLE_SUA_API_KEY_AQUI"
```

Se `"state"` não for `"open"`, reconecte:

```bash
curl -X GET "https://wpp.mediamrkt.online/instance/connect/biblioteca" \
  -H "apikey: COLE_SUA_API_KEY_AQUI"
```

E escaneie o QR Code novamente.

---

## 13. Backup da sessão WhatsApp

Faça backup regular dos volumes Docker:

```bash
mkdir -p /root/backups/evolution
docker run --rm \
  -v evolution-api_evolution_instances:/data \
  -v /root/backups/evolution:/backup \
  alpine tar czf /backup/evolution_instances_$(date +%Y%m%d_%H%M%S).tar.gz -C /data .
```

Backup do Postgres:

```bash
docker exec evolution_postgres pg_dump -U evolution_user evolution > /root/backups/evolution/postgres_$(date +%Y%m%d_%H%M%S).sql
```

Restaurar instâncias (se necessário):

```bash
docker compose -f /opt/evolution-api/docker-compose.yml down
docker run --rm \
  -v evolution-api_evolution_instances:/data \
  -v /root/backups/evolution:/backup \
  alpine sh -c "cd /data && tar xzf /backup/NOME_DO_ARQUIVO.tar.gz"
docker compose -f /opt/evolution-api/docker-compose.yml up -d
```

---

## 14. Solução de problemas

### Container não sobe

```bash
cd /opt/evolution-api
docker compose logs evolution-api
docker compose logs evolution-postgres
docker compose logs evolution-redis
```

### Erro de banco / Postgres

```bash
docker exec -it evolution_postgres psql -U evolution_user -d evolution -c "\dt"
```

Recriar do zero ( **apaga sessão WhatsApp** ):

```bash
cd /opt/evolution-api
docker compose down -v
docker compose up -d
```

### API retorna 401 Unauthorized

- Confirme que o header `apikey` é igual a `AUTHENTICATION_API_KEY` no `.env`
- Reinicie após mudar `.env`:

```bash
cd /opt/evolution-api
docker compose up -d --force-recreate evolution-api
```

### WhatsApp desconectou

```bash
curl -X GET "https://wpp.mediamrkt.online/instance/connectionState/biblioteca" \
  -H "apikey: COLE_SUA_API_KEY_AQUI"

curl -X GET "https://wpp.mediamrkt.online/instance/connect/biblioteca" \
  -H "apikey: COLE_SUA_API_KEY_AQUI"
```

Escaneie o QR Code novamente no celular.

### Bible Library não envia WhatsApp

1. Integrações → WhatsApp ativado?
2. URL, instância e API Key corretos?
3. Worker rodando?

```bash
supervisorctl status
tail -n 100 /var/www/bible-library/storage/logs/laravel.log
tail -n 100 /var/www/bible-library/storage/logs/worker.log
php artisan queue:failed
```

Reprocessar jobs falhos:

```bash
cd /var/www/bible-library
php artisan queue:retry all
```

### QR não gera (`count: 0`, `state: connecting`, manager preto)

Sintomas dos seus logs:
- `GET /instance/connect` → `{"count": 0}`
- `connectionState` → `"state": "connecting"` para sempre
- Imagem `atendai/evolution-api:v2.1.1` (desatualizada)

**Correção completa** — rode na VPS:

```bash
cd /opt/evolution-api
API_KEY="COLE_SUA_API_KEY_AQUI"

# 1) Apagar instância travada
curl -s -X DELETE "https://wpp.mediamrkt.online/instance/delete/biblioteca" \
  -H "apikey: $API_KEY"

# 2) Atualizar imagem no docker-compose.yml para:
#    image: evoapicloud/evolution-api:v2.3.7
nano docker-compose.yml

# 3) No .env — confirme SERVER_URL e remova CONFIG_SESSION_PHONE_VERSION se existir
nano .env
# Adicione se não tiver:
# WEBSOCKET_ENABLED=true
# NODE_OPTIONS=--dns-result-order=ipv4first

# 4) Subir versão nova
docker compose down
docker compose pull
docker compose up -d
sleep 15
docker logs evolution_api --tail 30

# 5) Criar instância do zero
curl -s -X POST "https://wpp.mediamrkt.online/instance/create" \
  -H "Content-Type: application/json" \
  -H "apikey: $API_KEY" \
  -d '{"instanceName":"biblioteca","integration":"WHATSAPP-BAILEYS","qrcode":true}' \
  | python3 -m json.tool | head -30

# 6) Se não vier base64 no passo 5, force connect em até 10s:
curl -s -X GET "https://wpp.mediamrkt.online/instance/connect/biblioteca" \
  -H "apikey: $API_KEY" | python3 -m json.tool | head -30
```

A resposta **deve** conter `base64` ou `qrcode.base64`. Se vier só `count: 0`, veja `docker logs evolution_api --tail 100`.

### Reiniciar tudo da Evolution

```bash
cd /opt/evolution-api
docker compose restart
```

### Parar a Evolution (manutenção)

```bash
cd /opt/evolution-api
docker compose stop
```

### Iniciar novamente

```bash
cd /opt/evolution-api
docker compose start
```

---

## Resumo da arquitetura final

```
Internet
   │
   ├── mediamrkt.online ──────► Nginx ──► PHP/Laravel (Bible Library)
   │                                      └── Supervisor (queue worker)
   │
   └── wpp.mediamrkt.online ──► Nginx ──► Docker Evolution API :8080
                                              ├── PostgreSQL
                                              ├── Redis
                                              └── Volume (sessão WhatsApp)
```

| Serviço | Atualizar | Sessão WhatsApp |
|---------|-----------|-----------------|
| Bible Library | `deploy.sh` ou git pull | Não afeta |
| Evolution API | `docker compose pull && up -d` | Mantém com volume |
| Nginx / SSL | `certbot renew` | Não afeta |

---

## Checklist final

- [ ] DNS `wpp.mediamrkt.online` apontando para a VPS
- [ ] Docker containers rodando (`docker compose ps`)
- [ ] HTTPS em `https://wpp.mediamrkt.online`
- [ ] Instância `biblioteca` criada
- [ ] WhatsApp conectado (`state: open`)
- [ ] Integrações configuradas no admin do Bible Library
- [ ] Teste WhatsApp enviado com sucesso
- [ ] Queue worker do Laravel ativo
- [ ] Backup dos volumes configurado
