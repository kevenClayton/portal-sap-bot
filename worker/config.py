"""
Configuração do worker (variáveis de ambiente ou .env).
"""
import os
from pathlib import Path
from urllib.parse import urljoin, urlparse

from dotenv import load_dotenv

# Carrega worker/.env (senão BOT_API_BASE no ficheiro é ignorado e usa-se o default)
load_dotenv(Path(__file__).resolve().parent / ".env")

# URL base do Laravel, sem barra final (ex.: http://127.0.0.1:8000 ou http://portal-sap-bot.test)
API_BASE = (os.getenv("BOT_API_BASE", "http://localhost:8000") or "http://localhost:8000").rstrip("/")
# Token para rotas /api/worker/* (mesmo valor que WORKER_API_TOKEN no .env do Laravel)
WORKER_TOKEN = (os.getenv("WORKER_API_TOKEN", "") or "").strip()

# Portal SAP — host usado em OData (sem path de login)
SAP_BASE_URL = os.getenv("SAP_BASE_URL", "https://portallogistica.arcelormittal.com.br").rstrip("/")
# Mandatório em muitos gateways SAP: mesmo valor que na URL Fiori (?sap-client=310)
SAP_CLIENT = (os.getenv("SAP_CLIENT", "310") or "").strip()


def _resolve_sap_login_url() -> str:
    """
    URL do formulário de logon (BIG-IP / APM costuma ser .../my.policy).
    Se SAP_LOGIN_URL estiver vazio ou for só a raiz do host (path vazio), usa my.policy.
    """
    raw = os.getenv("SAP_LOGIN_URL", "").strip()
    if not raw:
        return urljoin(SAP_BASE_URL + "/", "my.policy")
    parsed = urlparse(raw)
    path = (parsed.path or "").strip("/")
    if path == "":
        return urljoin(SAP_BASE_URL + "/", "my.policy")
    return raw.rstrip("/")


SAP_LOGIN_URL = _resolve_sap_login_url()
# Domínio no formulário (ex.: BMS) — campo input_3 no portal Arcelor
SAP_DOMAIN = os.getenv("SAP_DOMAIN", "BMS").strip()
# Opcional: $expand na listagem OData (ex.: RequestToNotes)
SAP_ODATA_EXPAND = os.getenv("SAP_ODATA_EXPAND", "").strip()

SAP_USER = os.getenv("SAP_USER", "")
SAP_PASSWORD = os.getenv("SAP_PASSWORD", "")

# Intervalo padrão entre ciclos (segundos)
DEFAULT_INTERVAL = int(os.getenv("BOT_INTERVAL", "60"))

# Playwright: HEADLESS=0 / false / no → janela visível (debug). Por defeito 1 = sem janela.
_headless_env = (os.getenv("HEADLESS", "1") or "1").strip().lower()
PLAYWRIGHT_HEADLESS = _headless_env not in ("0", "false", "no", "off")
