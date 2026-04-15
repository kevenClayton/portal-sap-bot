"""
Loop principal do worker: verifica se o bot está ativo, faz login, e executa ciclos.
"""
import sys
import time
import traceback
from datetime import datetime

from config import DEFAULT_INTERVAL, PLAYWRIGHT_HEADLESS, SAP_USER, SAP_PASSWORD
from api_client import get_bot, post_log, post_execucao, patch_execucao
from login import login as sap_login
from accept_service import run_ciclo
from terminal_log import clear_terminal_mirror_bot_id, set_terminal_mirror_bot_id, term, term_exc

# Detalhes técnicos só na consola (stderr), via `term`.
WORKER_BUILD = "2026-04-03-csrf-playwright+fallback"


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
    term("Arranque worker", WORKER_BUILD, "headless Playwright =", PLAYWRIGHT_HEADLESS)
    post_log("info", "login", "Robô ligado ao servidor.")

    while True:
        try:
            term("A pedir configuração do bot à API…")
            cfg = get_bot()
        except Exception as e:
            term_exc("Erro GET /api/worker/bot:", e)
            post_log("error", "erro_api", f"Falha ao obter config: {e}")
            time.sleep(DEFAULT_INTERVAL)
            continue

        if not cfg.get("ativo") or not cfg.get("bot"):
            term(
                "Bot inativo ou sem dados — a aguardar",
                DEFAULT_INTERVAL,
                "s (use Iniciar no site quando estiver pronto)",
            )
            time.sleep(DEFAULT_INTERVAL)
            continue

        bot = cfg["bot"]
        parametros = cfg.get("parametros") or {}
        bot_id = bot.get("id")
        intervalo = parametros.get("intervalo_busca") or bot.get("intervalo") or DEFAULT_INTERVAL
        horario_inicio = parametros.get("horario_inicio") or bot.get("horario_inicio")
        horario_fim = parametros.get("horario_fim") or bot.get("horario_fim")

        if not dentro_horario(horario_inicio, horario_fim):
            term("Fora do horário permitido", horario_inicio, "–", horario_fim, "— pausa 60s")
            time.sleep(60)
            continue

        portal_user = (parametros.get("portal_usuario") or "").strip()
        portal_pass = (parametros.get("portal_senha") or "").strip()
        cred_user = portal_user or SAP_USER
        cred_pass = portal_pass or SAP_PASSWORD
        if not cred_user or not cred_pass:
            term("AVISO: sem credenciais (parâmetros portal ou .env SAP_USER)")
            post_log(
                "warning",
                "login",
                "Credenciais do portal em falta. Configure-as na área de configuração do robô.",
            )
            time.sleep(intervalo)
            continue

        set_terminal_mirror_bot_id(bot_id)
        try:
            term(
                "Ciclo bot_id =",
                bot_id,
                "intervalo =",
                intervalo,
                "s",
            )
            term("Início de sessão SAP (Playwright)…")
            try:
                sessao = sap_login(headless=PLAYWRIGHT_HEADLESS, user=cred_user, password=cred_pass)
            except Exception as e:
                term_exc("Login SAP falhou:", e)
                post_log(
                    "error",
                    "erro_auth",
                    f"{type(e).__name__}: {e}",
                    bot_id=bot_id,
                    contexto={"traceback": traceback.format_exc()[-4000:]},
                )
                time.sleep(intervalo)
                continue

            cookies = sessao["cookies"]
            user_agent = sessao.get("user_agent", "")
            term("Sessão SAP OK; CSRF presente =", bool(sessao.get("csrf_token")))
            post_log("info", "login", "Ligação ao portal concluída.", bot_id=bot_id)

            inicio = datetime.utcnow().isoformat() + "Z"
            try:
                term("Criar registo de execução na API…")
                execucao = post_execucao(bot_id, inicio=inicio)
                execucao_id = execucao["id"]
                term("Execução id =", execucao_id)
            except Exception as e:
                term_exc("post_execucao:", e)
                post_log("error", "erro_api", f"Falha ao criar execução: {e}", bot_id=bot_id)
                time.sleep(intervalo)
                continue

            try:
                term("run_ciclo: analisar cargas e regras…")
                analisadas, capturadas, ignoradas, simuladas = run_ciclo(
                    cookies,
                    user_agent,
                    parametros,
                    bot_id,
                    csrf_token=sessao.get("csrf_token"),
                    cookie_list=sessao.get("cookie_list"),
                    sap_referer=sessao.get("sap_referer"),
                    sap_client=sessao.get("sap_client"),
                )
            except Exception as e:
                term_exc("run_ciclo excepção:", e)
                post_log("error", "erro_api", str(e), bot_id=bot_id)
                patch_execucao(
                    execucao_id,
                    status="erro",
                    mensagem_erro=str(e),
                    fim_execucao=datetime.utcnow().isoformat() + "Z",
                )
                time.sleep(intervalo)
                continue

            term(
                "Ciclo concluído: analisadas =",
                analisadas,
                "capturadas =",
                capturadas,
                "ignoradas =",
                ignoradas,
                "simuladas =",
                simuladas,
            )

            try:
                patch_execucao(
                    execucao_id,
                    cargas_analisadas=analisadas,
                    cargas_capturadas=capturadas,
                    cargas_ignoradas=ignoradas,
                    cargas_simuladas=simuladas,
                    status="concluida",
                    fim_execucao=datetime.utcnow().isoformat() + "Z",
                )
            except Exception as e:
                term_exc("patch_execucao:", e)
                post_log("error", "erro_api", f"Falha ao atualizar execução: {e}", bot_id=bot_id)

            term("Pausa até próximo ciclo:", intervalo, "s")
            time.sleep(intervalo)
        finally:
            clear_terminal_mirror_bot_id()


if __name__ == "__main__":
    main()
