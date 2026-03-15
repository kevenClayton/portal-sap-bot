"""
Cliente HTTP para comunicação com a API Laravel (rotas do worker).
"""
import requests
from config import API_BASE, WORKER_TOKEN


def headers():
    return {
        "Authorization": f"Bearer {WORKER_TOKEN}",
        "X-Worker-Token": WORKER_TOKEN,
        "Accept": "application/json",
        "Content-Type": "application/json",
    }


def get_bot():
    """Obtém configuração do bot ativo e parâmetros."""
    r = requests.get(f"{API_BASE}/api/worker/bot", headers=headers(), timeout=30)
    r.raise_for_status()
    return r.json()


def post_carga(data):
    """Registra uma carga (analisada, capturada, ignorada ou erro)."""
    r = requests.post(f"{API_BASE}/api/worker/cargas", json=data, headers=headers(), timeout=10)
    r.raise_for_status()
    return r.json()


def post_execucao(bot_id, inicio=None, cargas_analisadas=0, cargas_capturadas=0, cargas_ignoradas=0):
    """Inicia uma nova execução."""
    data = {
        "bot_id": bot_id,
        "inicio_execucao": inicio,
        "cargas_analisadas": cargas_analisadas,
        "cargas_capturadas": cargas_capturadas,
        "cargas_ignoradas": cargas_ignoradas,
    }
    r = requests.post(f"{API_BASE}/api/worker/execucoes", json=data, headers=headers(), timeout=10)
    r.raise_for_status()
    return r.json()


def patch_execucao(execucao_id, **kwargs):
    """Atualiza execução (fim, contagens, status)."""
    r = requests.patch(
        f"{API_BASE}/api/worker/execucoes/{execucao_id}",
        json=kwargs,
        headers=headers(),
        timeout=10,
    )
    r.raise_for_status()
    return r.json()


def post_log(nivel, evento, mensagem=None, bot_id=None, contexto=None):
    """Registra log no sistema."""
    data = {
        "nivel": nivel,
        "evento": evento,
        "mensagem": mensagem,
        "bot_id": bot_id,
        "contexto": contexto or {},
    }
    r = requests.post(f"{API_BASE}/api/worker/logs", json=data, headers=headers(), timeout=5)
    r.raise_for_status()
    return r.json()
