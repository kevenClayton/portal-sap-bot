# Publicar no Portainer

## Variáveis obrigatórias (checklist)

### `deploy/.env.portainer` (Laravel)

| Variável | O que pôr |
|----------|-----------|
| `APP_URL` | URL **pública** do painel (`https://teu-dominio.com`), **sem** barra no fim. Não uses `http://localhost:8000` em produção atrás de proxy. |
| `APP_KEY` | Na primeira subida pode ficar **vazio** — o entrypoint gera. Se já tiveres uma chave `base64:...`, podes colar. |
| `DB_*` | MySQL acessível **a partir do container** (host, porta, base, user, password). |
| `MYSQL_ATTR_SSL_CA` | Só se o MySQL exigir SSL: caminho para o ficheiro CA **dentro** da imagem (muitas vezes vazio). |
| `WORKER_API_TOKEN` | Token longo e aleatório; **igual** ao do worker. Ex.: `openssl rand -hex 32`. |
| `SANCTUM_STATEFUL_DOMAINS` | Lista de **hostnames** do browser (sem `https://`), separados por vírgula. Tem de incluir o host de `APP_URL` (ex. `bot.meudominio.com`). |
| `MAIL_*` | O compose passa `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME`, `MAIL_URL` ao container. Sem isto no ambiente da stack, o Laravel fica com SMTP por defeito (`127.0.0.1:2525`) e **não envia**. Laravel 12: use `MAIL_SCHEME` (porta 587 → deixe vazio; 465 → `smtps`). `MAIL_ENCRYPTION` antigo **não** é lido pelo `config/mail.php` deste projeto. |
| E-mails de carga | No painel, preencher **emails_notificacao** nos parâmetros do bot. |

### `deploy/.env.worker.portainer` (Python)

| Variável | O que pôr |
|----------|-----------|
| `BOT_API_BASE` | Com Docker Compose deste repo: **`http://app:8000`** (nome do serviço + porta interna). |
| `WORKER_API_TOKEN` | **Exatamente** o mesmo que `WORKER_API_TOKEN` no Laravel. |
| `SAP_*` | Credenciais e parâmetros do portal SAP, como no `worker/.env.example`. |

## 1) Variáveis no Portainer (sem ficheiro no Git)

O `docker-compose.portainer.yml` **não** usa `env_file` — o clone do Git não traz `.env.portainer` (e não deve, por segurança).

Na stack, em **Environment variables** (ou modo avançado), cola **todas** as linhas:

- do exemplo Laravel: `deploy/.env.portainer.example`
- e as do worker: `deploy/.env.worker.portainer.example` (no mesmo bloco; chaves repetidas como `WORKER_API_TOKEN` têm de ser **iguais**)

Ou manténs `deploy/.env.portainer` só na tua máquina como rascunho e copias/colas para o Portainer.

## 2) Criar Stack pelo Git

1. Portainer -> `Stacks` -> `Add stack`
2. Método: `Repository`
3. Repository URL: `https://github.com/kevenClayton/portal-sap-bot`
4. Repository reference: `refs/heads/master`
5. Compose path: `deploy/docker-compose.portainer.yml`
6. Deploy the stack

## 3) Primeiro arranque

- O serviço `app` cria `.env` se não existir, gera `APP_KEY` (quando vazio), roda migrations e sobe servidor Laravel.
- O serviço `queue` processa a fila em background.
- O serviço `worker` executa o bot Python com Playwright.

## 4) Observações

- `APP_PORT` controla a porta publicada do Laravel (default `8000`).
- Se usar domínio/HTTPS, coloque um proxy reverso à frente (Nginx, Traefik ou Cloudflare Tunnel).
- Se quiser banco externo (MySQL), altere as variáveis `DB_*` em `.env.portainer`.
