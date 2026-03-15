"""
Loop principal do worker: verifica se o bot está ativo, faz login, e executa ciclos.
"""
import time
from datetime import datetime

from config import DEFAULT_INTERVAL, SAP_USER, SAP_PASSWORD
from api_client import get_bot, post_log, post_execucao, patch_execucao
from login import login as sap_login
from accept_service import run_ciclo


def dentro_horario(horario_inicio, horario_fim):
    if not horario_inicio and not horario_fim:
        return True
    now = datetime.now().time()
    if horario_inicio and now < datetime.strptime(horario_inicio, "%H:%M").time():
        return False
    if horario_fim and now > datetime.strptime(horario_fim, "%H:%M").time():
        return False
    return True


def main():
    post_log("info", "login", "Worker iniciado")

    while True:
        try:
            cfg = get_bot()
        except Exception as e:
            post_log("error", "erro_api", f"Falha ao obter config: {e}")
            time.sleep(DEFAULT_INTERVAL)
            continue

        if not cfg.get("ativo") or not cfg.get("bot"):
            time.sleep(DEFAULT_INTERVAL)
            continue

        bot = cfg["bot"]
        parametros = cfg.get("parametros") or {}
        bot_id = bot.get("id")
        intervalo = parametros.get("intervalo_busca") or bot.get("intervalo") or DEFAULT_INTERVAL
        horario_inicio = parametros.get("horario_inicio") or bot.get("horario_inicio")
        horario_fim = parametros.get("horario_fim") or bot.get("horario_fim")

        if not dentro_horario(horario_inicio, horario_fim):
            time.sleep(60)
            continue

        if not SAP_USER or not SAP_PASSWORD:
            post_log("warning", "login", "SAP_USER/SAP_PASSWORD não configurados")
            time.sleep(intervalo)
            continue

        try:
            sessao = sap_login(headless=True)
        except Exception as e:
            post_log("error", "erro_auth", str(e), bot_id=bot_id)
            time.sleep(intervalo)
            continue

        cookies = sessao["cookies"]
        user_agent = sessao.get("user_agent", "")
        post_log("info", "login", "Login SAP realizado", bot_id=bot_id)

        inicio = datetime.utcnow().isoformat() + "Z"
        try:
            execucao = post_execucao(bot_id, inicio=inicio)
            execucao_id = execucao["id"]
        except Exception as e:
            post_log("error", "erro_api", f"Falha ao criar execução: {e}", bot_id=bot_id)
            time.sleep(intervalo)
            continue

        try:
            analisadas, capturadas, ignoradas = run_ciclo(cookies, user_agent, parametros, bot_id)
        except Exception as e:
            post_log("error", "erro_api", str(e), bot_id=bot_id)
            patch_execucao(execucao_id, status="erro", mensagem_erro=str(e), fim_execucao=datetime.utcnow().isoformat() + "Z")
            time.sleep(intervalo)
            continue

        try:
            patch_execucao(
                execucao_id,
                cargas_analisadas=analisadas,
                cargas_capturadas=capturadas,
                cargas_ignoradas=ignoradas,
                status="concluida",
                fim_execucao=datetime.utcnow().isoformat() + "Z",
            )
        except Exception as e:
            post_log("error", "erro_api", f"Falha ao atualizar execução: {e}", bot_id=bot_id)

        time.sleep(intervalo)


if __name__ == "__main__":
    main()
