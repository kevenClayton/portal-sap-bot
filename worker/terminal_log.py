"""
Registo na consola (stderr) e, durante o ciclo do worker, espelho para o painel (post_log evento terminal).
Também grava blocos técnicos em worker/worker_tecnico.log (não enviar passwords).
"""
from __future__ import annotations

import os
import sys
import threading
import traceback
from datetime import datetime
from pathlib import Path
from typing import Optional

_LOG_PATH = Path(__file__).resolve().parent / "worker_tecnico.log"

# Durante login/ciclo SAP, o worker define o bot para espelhar cada linha no terminal web.
_MIRROR_BOT_ID: Optional[int] = None
_MENSAGEM_MAX = 62000


def set_terminal_mirror_bot_id(bot_id: Optional[int]) -> None:
    global _MIRROR_BOT_ID
    _MIRROR_BOT_ID = int(bot_id) if bot_id is not None else None


def clear_terminal_mirror_bot_id() -> None:
    global _MIRROR_BOT_ID
    _MIRROR_BOT_ID = None


def _mirror_terminal_enabled() -> bool:
    v = (os.getenv("WORKER_MIRROR_TERMINAL", "1") or "1").strip().lower()
    return v not in ("0", "false", "no", "off")


def _mirror_to_panel_sync(linha_completa: str, nivel: str) -> None:
    if not _mirror_terminal_enabled() or _MIRROR_BOT_ID is None:
        return
    texto = linha_completa if len(linha_completa) <= _MENSAGEM_MAX else linha_completa[:_MENSAGEM_MAX] + "…"
    if nivel not in ("info", "warning", "error"):
        nivel = "info"
    try:
        from api_client import post_log

        post_log(nivel, "terminal", texto, bot_id=_MIRROR_BOT_ID)
    except Exception:
        pass


def _mirror_to_panel(linha_completa: str, *, nivel: str = "info") -> None:
    """
    Espelha para a API em thread em segundo plano para o pedido HTTP não bloquear
    o login SAP / Playwright (evita terminal web «parado» com robô a trabalhar).
    """
    threading.Thread(
        target=_mirror_to_panel_sync,
        args=(linha_completa, nivel),
        daemon=True,
    ).start()


def term(*parts: object) -> None:
    ts = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    msg = " ".join(str(p) for p in parts)
    linha = f"[robô] {ts} {msg}"
    print(linha, file=sys.stderr, flush=True)
    _mirror_to_panel(linha, nivel="info")


def term_exc(prefix: str, exc: BaseException) -> None:
    ts = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    msg = f"{prefix} {type(exc).__name__}: {exc}"
    linha = f"[robô] {ts} {msg}"
    print(linha, file=sys.stderr, flush=True)
    traceback.print_exc(file=sys.stderr)
    tb = traceback.format_exc()
    bloco = linha + "\n" + tb
    _mirror_to_panel(bloco, nivel="error")


def log_tecnico(titulo: str, corpo: str = "") -> None:
    """
    Acrescenta diagnóstico completo a worker/worker_tecnico.log.
    Não incluir senhas nem tokens completos.
    """
    ts = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    sep = "=" * 72
    try:
        with _LOG_PATH.open("a", encoding="utf-8") as f:
            f.write(f"\n{sep}\n{ts}  {titulo}\n{sep}\n")
            if corpo.strip():
                f.write(corpo.rstrip() + "\n")
            f.flush()
    except OSError as e:
        term("AVISO: não foi possível escrever worker_tecnico.log:", e)
