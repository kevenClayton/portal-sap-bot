# Publicar no Portainer

## 1) Criar os ficheiros de ambiente

No Portainer (Stack), crie estes ficheiros ao lado do compose:

- `deploy/.env.portainer` (copiar de `deploy/.env.portainer.example`)
- `deploy/.env.worker.portainer` (copiar de `deploy/.env.worker.portainer.example`)

Defina o mesmo valor para `WORKER_API_TOKEN` nos dois ficheiros.

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
