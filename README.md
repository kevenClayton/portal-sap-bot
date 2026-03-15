# Bot de Captura de Cargas SAP — Portal ArcelorMittal

Sistema de automação para consulta e captura de cargas no portal logístico SAP Fiori da ArcelorMittal.

## Estrutura

- **Backend + Painel (monólito)**: Laravel + Vue 3 + Vite + Tailwind
- **Worker**: Python (Playwright + Requests)

```
portal-sap-bot/
├── app/                    # Laravel
├── resources/
│   ├── js/                 # Vue 3 SPA (Dashboard, Config, Cargas, Execuções, Logs)
│   └── views/
├── routes/api.php          # API (painel + rotas worker)
├── worker/                 # Worker Python
│   ├── api_client.py      # Cliente da API Laravel
│   ├── login.py            # Login SAP (Playwright)
│   ├── sap_client.py       # OData (cargas, CSRF, aceitar)
│   ├── rule_engine.py      # Regras de filtro
│   ├── accept_service.py  # Ciclo: buscar → regras → aceitar
│   ├── worker.py           # Loop principal
│   └── requirements.txt
└── README.md
```

## Requisitos

- PHP 8.2+
- Composer
- Node 18+
- Python 3.10+
- Banco (SQLite/MySQL/PostgreSQL)
- Playwright (navegador para login SAP)

## Instalação

### 1. Laravel (backend + painel)

```bash
cd portal-sap-bot
cp .env.example .env
php artisan key:generate
# Configurar DB no .env:
# - SQLite: DB_CONNECTION=sqlite (e criar database/database.sqlite se não existir)
# - MySQL: DB_CONNECTION=mysql, DB_DATABASE=portal_sap_bot, DB_USERNAME=..., DB_PASSWORD=...
php artisan migrate
php artisan db:seed
```

Definir token do worker no `.env`:

```env
WORKER_API_TOKEN=seu-token-secreto
```

### 2. Frontend (Vue)

```bash
npm install
npm run build
# ou para desenvolvimento: npm run dev
```

### 3. Worker Python

```bash
cd worker
python -m venv venv
venv\Scripts\activate   # Windows
# source venv/bin/activate  # Linux/macOS
pip install -r requirements.txt
playwright install chromium
cp .env.example .env
# Editar .env: BOT_API_BASE, WORKER_API_TOKEN, SAP_BASE_URL, SAP_USER, SAP_PASSWORD
```

## Uso

1. **Laravel**: `php artisan serve` (ou apontar o documento raiz para `public/`).
2. **Painel**: acessar `http://localhost:8000` (ou a URL do app).  
   - Dashboard: status do bot, métricas, Iniciar/Parar/Reiniciar.  
   - Configuração: criar/editar bot e parâmetros (origem, destino, peso mínimo, distância, tipo veículo, intervalo).  
   - Cargas: listar analisadas/capturadas.  
   - Execuções: histórico de ciclos.  
   - Logs: eventos do worker.
3. **Worker**: no diretório `worker`, com o venv ativado e `.env` configurado:
   ```bash
   python worker.py
   ```
   O worker consulta a API (bot ativo + parâmetros), faz login no SAP, busca cargas OData, aplica as regras e aceita as que passam, registrando cargas e logs na API.

## API

- **Painel** (sem auth na instalação padrão; em produção use `auth:sanctum`):
  - `GET /api/dashboard` — métricas e status do bot  
  - `GET/POST/PUT/DELETE /api/bots`  
  - `POST /api/bots/{id}/iniciar`, `.../parar`, `.../reiniciar`  
  - `GET/POST/PUT/DELETE /api/bots/{id}/parametros` (e `parametros/{id}`)  
  - `GET /api/cargas`, `/api/cargas/analisadas`, `/api/cargas/capturadas`  
  - `GET /api/execucoes`, `GET /api/logs`

- **Worker** (header `Authorization: Bearer {WORKER_API_TOKEN}` ou `X-Worker-Token: {WORKER_API_TOKEN}`):
  - `GET /api/worker/bot` — config do bot ativo e parâmetros  
  - `POST /api/worker/cargas` — registrar carga  
  - `POST /api/worker/execucoes` — iniciar execução  
  - `PATCH /api/worker/execucoes/{id}` — atualizar execução  
  - `POST /api/worker/logs` — registrar log  

## Segurança

- Usar **WORKER_API_TOKEN** forte e único.
- Em produção: habilitar autenticação no painel (Sanctum), HTTPS e restringir acesso ao worker (rede/VM).
- Credenciais SAP: manter em variáveis de ambiente (`.env` do worker), nunca no código.

## Personalização SAP

- **Login** (`worker/login.py`): ajustar seletores ao portal real (campos de usuário/senha e botão de submit).
- **OData** (`worker/sap_client.py`): endpoint e filtros já seguem o PRD (`RequestForQuotationCollection`, `LifecycleStatus eq '02'`); ajustar base URL e paths se o ambiente for diferente.
- **Aceitar carga**: o método `accept_carga` é um placeholder; implementar o endpoint e payload reais conforme documentação do portal (ação de aceitação da cotação).

## Roadmap (PRD)

- Fase 1: login SAP, consulta cargas, filtros, aceitação automática ✅ (estrutura pronta; aceitar carga a ajustar ao portal real)
- Fase 2: painel web, relatórios, logs ✅
- Fase 3: múltiplos clientes, multi portal, inteligência de preço
