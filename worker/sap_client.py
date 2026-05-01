"""
Cliente HTTP para chamadas OData ao SAP (cookies de sessão + CSRF).
Aceite de carga: POST multipart/mixed para .../TENDERING/$batch (Fiori: QuotationCollection + ResponseCode).
"""
from __future__ import annotations

import json
import re
import uuid
from typing import Optional

import requests
from requests.cookies import RequestsCookieJar
from urllib.parse import urljoin

from api_client import post_log
from terminal_log import log_tecnico, term
from config import (
    SAP_BASE_URL,
    SAP_CLIENT,
    SAP_ODATA_EXPAND,
)

# Aceite de carga: multipart Fiori em SCMTMS/TENDERING (captura portal Arcelor; fixo, sem .env).
_BATCH_INNER_POST_PATH = "QuotationCollection"
_BATCH_RESPONSE_CODE = "AP"
_BATCH_ACCEPT_LANGUAGE = "pt"


def _referer_header(referer: Optional[str]) -> str:
    r = (referer or "").strip()
    return r if r else (SAP_BASE_URL.rstrip("/") + "/")


def _sap_client_param(sap_client: Optional[str]) -> Optional[str]:
    """String vazia ou None → usa SAP_CLIENT do .env."""
    if sap_client is not None and str(sap_client).strip():
        return str(sap_client).strip()
    return (SAP_CLIENT or "").strip() or None


def _merge_odata_params(base: dict, sap_client: Optional[str]) -> dict:
    out = dict(base)
    sc = _sap_client_param(sap_client)
    if sc:
        out["sap-client"] = sc
    return out


def cookie_jar_from_playwright(cookie_list: list) -> RequestsCookieJar:
    """
    Converte cookies do Playwright para RequestsCookieJar com domain/path.
    Um dict simples name→value falha quando o servidor define Domain/Path específicos.
    """
    jar = RequestsCookieJar()
    for c in cookie_list:
        name = c.get("name")
        value = c.get("value")
        if not name:
            continue
        domain = (c.get("domain") or "").strip() or None
        path = c.get("path") or "/"
        jar.set(name, value, domain=domain, path=path)
    return jar


def playwright_cookies_as_dict(cookie_list: Optional[list]) -> dict:
    """name→value para fallback em requests quando o RequestsCookieJar não replica bem o browser."""
    if not cookie_list:
        return {}
    out: dict = {}
    for c in cookie_list:
        n = c.get("name")
        if n:
            out[n] = c.get("value") if c.get("value") is not None else ""
    return out


def _csrf_token_from_response(r: requests.Response):
    t = r.headers.get("X-CSRF-Token") or r.headers.get("x-csrf-token")
    if not t or str(t).strip().lower() in ("fetch", "required", "none"):
        return None
    return str(t).strip()


def fetch_csrf_token(
    cookies,
    user_agent=None,
    log_failures: bool = True,
    cookie_list: Optional[list] = None,
    referer: Optional[str] = None,
    sap_client: Optional[str] = None,
):
    """Obtém o token CSRF via GET com header X-CSRF-Token: Fetch."""
    url = urljoin(SAP_BASE_URL + "/", "sap/opu/odata/SCMTMS/TENDERING/")
    params = _merge_odata_params({"$format": "json"}, sap_client)
    headers = {
        "X-CSRF-Token": "Fetch",
        "Accept": "application/json",
        "User-Agent": user_agent or "Bot/1.0",
        "Referer": _referer_header(referer),
    }
    last_r = None
    attempts = []
    for label, cook in (
        ("cookie_jar", cookies),
        ("cookie_dict", playwright_cookies_as_dict(cookie_list) if cookie_list else None),
    ):
        if cook is None:
            continue
        if isinstance(cook, dict) and not cook:
            term("CSRF tentativa", label, "— dict vazio, a ignorar")
            continue
        term("CSRF GET", label, "→", url[:80], "params", str(params)[:80])
        r = requests.get(
            url,
            params=params,
            cookies=cook,
            headers=headers,
            timeout=30,
            allow_redirects=True,
        )
        last_r = r
        attempts.append((label, r))
        hdr = r.headers.get("X-CSRF-Token") or r.headers.get("x-csrf-token") or ""
        term("CSRF resposta", label, "HTTP", r.status_code, "X-CSRF-Token=", (hdr[:24] + "…") if len(str(hdr)) > 24 else hdr)
        if r.status_code != 200:
            term("CSRF corpo (início):", (r.text or "")[:200].replace("\n", " "))
            continue
        token = _csrf_token_from_response(r)
        if token:
            term("CSRF OK via", label, "token", len(token), "chars")
            return token

    if log_failures and last_r is not None:
        body = (last_r.text or "")[:1200]
        post_log(
            "error",
            "erro_api",
            f"CSRF OData HTTP {last_r.status_code} — ver contexto técnico.",
            contexto={
                "url": last_r.url[:500],
                "referer": headers.get("Referer", "")[:400],
                "sap_client_param": params.get("sap-client", ""),
                "corpo_inicio": body,
            },
        )
    term("CSRF falhou após tentativas:", len(attempts))
    return None


def get_cargas(
    cookies,
    csrf_token,
    top=100,
    skip=0,
    lifecycle_status="02",
    user_agent=None,
    cookie_list: Optional[list] = None,
    referer: Optional[str] = None,
    sap_client: Optional[str] = None,
):
    """
    Consulta RequestForQuotationCollection.
    lifecycle_status '02' = em aberto.
    """
    path = "sap/opu/odata/SCMTMS/TENDERING/RequestForQuotationCollection"
    params = _merge_odata_params(
        {
            "$top": top,
            "$skip": skip,
            "$filter": f"LifecycleStatus eq '{lifecycle_status}'",
            "$format": "json",
        },
        sap_client,
    )
    if SAP_ODATA_EXPAND:
        params["$expand"] = SAP_ODATA_EXPAND
    url = urljoin(SAP_BASE_URL + "/", path)
    headers = {
        "X-CSRF-Token": csrf_token or "",
        "Accept": "application/json",
        "Content-Type": "application/json",
        "User-Agent": user_agent or "Bot/1.0",
        "Referer": _referer_header(referer),
    }
    term("OData RequestForQuotationCollection GET (cookie_jar)…")
    r = requests.get(url, params=params, cookies=cookies, headers=headers, timeout=30)
    term("OData lista cargas HTTP", r.status_code, "bytes", len(r.content or b""))
    if r.status_code != 200 and cookie_list:
        term("OData lista cargas retry com cookie_dict…")
        r = requests.get(
            url,
            params=params,
            cookies=playwright_cookies_as_dict(cookie_list),
            headers=headers,
            timeout=30,
        )
        term("OData lista cargas (dict) HTTP", r.status_code)
    if r.status_code != 200:
        post_log(
            "error",
            "erro_api",
            f"Lista cargas OData HTTP {r.status_code} — ver contexto técnico.",
            contexto={
                "url": r.url[:500],
                "corpo_inicio": (r.text or "")[:1200],
            },
        )
        r.raise_for_status()
    data = r.json()
    d = data.get("d", data) if isinstance(data, dict) else data
    ent = d.get("results", d) if isinstance(d, dict) else d
    n = len(ent) if isinstance(ent, list) else ("?" if ent else 0)
    term("OData lista cargas: entradas =", n)
    return data


def _accept_batch_json_body(rfq_uuid: str) -> tuple[str, bytes]:
    """JSON e bytes UTF-8 do corpo da parte HTTP interna (Content-Length = len(bytes))."""
    uid = str(rfq_uuid).strip()
    payload_obj = {"RequestForQuotationUUID": uid, "ResponseCode": _BATCH_RESPONSE_CODE}
    payload = json.dumps(payload_obj, separators=(",", ":"))
    return payload, payload.encode("utf-8")


def _batch_inner_http_block(post_target: str, csrf_token: str, payload_bytes: bytes) -> str:
    """
    Cabeçalhos da sub-requisição HTTP dentro do multipart (alinhado à captura Fiori).
    CSRF e DataServiceVersion 2.0 repetidos na parte interna.
    """
    content_length = len(payload_bytes)
    payload = payload_bytes.decode("utf-8")
    token_line = (csrf_token or "").strip()
    return (
        f"POST {post_target} HTTP/1.1\r\n"
        f"sap-contextid-accept: header\r\n"
        f"Accept: application/json\r\n"
        f"Accept-Language: {_BATCH_ACCEPT_LANGUAGE}\r\n"
        f"DataServiceVersion: 2.0\r\n"
        f"MaxDataServiceVersion: 2.0\r\n"
        f"x-csrf-token: {token_line}\r\n"
        f"Content-Type: application/json\r\n"
        f"Content-Length: {content_length}\r\n"
        f"\r\n"
        f"{payload}\r\n"
    )


def _build_batch_accept_body(rfq_uuid: str, csrf_token: str) -> tuple[str, str]:
    """
    Monta o corpo multipart/mixed para $batch (changeset + POST QuotationCollection, estilo Fiori).
    Retorna (body_string, boundary_raiz).
    """
    uid = str(rfq_uuid).strip()
    _, payload_bytes = _accept_batch_json_body(uid)
    batch_b = f"batch_{uuid.uuid4().hex}"
    changeset_b = f"changeset_{uuid.uuid4().hex}"
    inner_http = _batch_inner_http_block(_BATCH_INNER_POST_PATH, csrf_token, payload_bytes)
    inner = (
        f"--{changeset_b}\r\n"
        f"Content-Type: application/http\r\n"
        f"Content-Transfer-Encoding: binary\r\n"
        f"\r\n"
        f"{inner_http}"
        f"--{changeset_b}--\r\n"
    )
    body = (
        f"--{batch_b}\r\n"
        f"Content-Type: multipart/mixed; boundary={changeset_b}\r\n"
        f"\r\n"
        f"{inner}"
        f"--{batch_b}--\r\n"
    )
    return body, batch_b


def _batch_inner_http_codes(response_text: str) -> list[int]:
    """Códigos HTTP das partes internas da resposta multipart $batch."""
    if not response_text:
        return []
    return [int(m.group(1)) for m in re.finditer(r"HTTP/\d\.\d\s+(\d+)", response_text)]


def _sap_odata_error_from_body(response_text: str) -> dict:
    """Extrai code/message do XML de erro OData (resposta SAP)."""
    out: dict = {}
    if not response_text:
        return out
    code_match = re.search(r"<code[^>]*>([^<]+)</code>", response_text, re.I)
    if code_match:
        out["sap_error_code"] = code_match.group(1).strip()
    message_match = re.search(r"<message[^>]*>([^<]*)</message>", response_text, re.I)
    if message_match:
        out["sap_error_message"] = message_match.group(1).strip()
    return out


def _truncate_debug_text(text: str, max_chars: int) -> str:
    if not text:
        return ""
    if len(text) <= max_chars:
        return text
    return text[:max_chars] + f"\n… [truncado: {len(text)} caracteres no total]"


def _batch_http_response_ok(response_text: str) -> bool:
    """Avalia códigos HTTP das partes internas da resposta $batch."""
    if not response_text:
        return False
    codes = _batch_inner_http_codes(response_text)
    if codes:
        if any(c >= 400 for c in codes):
            return False
        return any(200 <= c < 300 for c in codes)
    low = response_text.lower()
    if '"error"' in low or "errordetail" in low:
        return False
    return True


def accept_carga(
    cookies,
    csrf_token,
    rfq_uuid,
    payload=None,
    user_agent=None,
    cookie_list: Optional[list] = None,
    referer: Optional[str] = None,
    sap_client: Optional[str] = None,
    rfq_id: Optional[str] = None,
    bot_id: Optional[int] = None,
):
    """
    Aceita uma RFQ via OData $batch (QuotationCollection + ResponseCode, estilo Fiori).
    `payload` é ignorado (mantido por compatibilidade com chamadas antigas).
    """
    if not rfq_uuid:
        raise ValueError("RequestForQuotationUUID vazio")

    batch_url = urljoin(SAP_BASE_URL + "/", "sap/opu/odata/SCMTMS/TENDERING/$batch")
    batch_params = {}
    sc = _sap_client_param(sap_client)
    if sc:
        batch_params["sap-client"] = sc
    body, boundary = _build_batch_accept_body(rfq_uuid, csrf_token or "")
    body_bytes = body.encode("utf-8")

    headers = {
        "Content-Type": f"multipart/mixed; boundary={boundary}",
        "X-CSRF-Token": csrf_token or "",
        "Accept": "multipart/mixed",
        "DataServiceVersion": "2.0",
        "MaxDataServiceVersion": "2.0",
        "User-Agent": user_agent or "Bot/1.0",
        "Referer": _referer_header(referer),
    }

    term(
        "Aceite $batch POST",
        batch_url,
        "params",
        batch_params,
        "uuid",
        str(rfq_uuid)[:36],
        "inner_POST",
        _BATCH_INNER_POST_PATH,
        "ResponseCode",
        _BATCH_RESPONSE_CODE,
    )
    r = requests.post(
        batch_url,
        params=batch_params or None,
        data=body_bytes,
        cookies=cookies,
        headers=headers,
        timeout=60,
    )
    term("$batch resposta HTTP", r.status_code, "corpo", len(r.text or ""), "bytes")
    if r.status_code not in (200, 202) and cookie_list:
        term("$batch retry com cookie_dict…")
        r = requests.post(
            batch_url,
            params=batch_params or None,
            data=body_bytes,
            cookies=playwright_cookies_as_dict(cookie_list),
            headers=headers,
            timeout=60,
        )
        term("$batch (dict) HTTP", r.status_code)

    text = r.text or ""
    inner_codes = _batch_inner_http_codes(text)
    sap_err = _sap_odata_error_from_body(text)

    def _contexto_falha_batch(extra: dict) -> dict:
        base = {
            "rfq_uuid": str(rfq_uuid),
            "rfq_id": rfq_id,
            "batch_url": getattr(r, "url", None) or batch_url,
            "http_status": r.status_code,
            "sap_client_param": sc,
            "batch_inner_post_path": _BATCH_INNER_POST_PATH,
            "batch_response_code": _BATCH_RESPONSE_CODE,
            "inner_http_statuses": inner_codes,
            **sap_err,
        }
        base.update(extra)
        return base

    if r.status_code not in (200, 202):
        corpo_api = _truncate_debug_text(text, 6000)
        log_tecnico(
            "Aceite $batch — HTTP fora de 200/202",
            f"url={getattr(r, 'url', batch_url)}\nstatus={r.status_code}\n"
            f"inner_http_statuses={inner_codes}\n{sap_err}\n\n{_truncate_debug_text(text, 100000)}",
        )
        post_log(
            "error",
            "erro_api",
            "Não foi possível confirmar a aceitação da carga no sistema. Contacte o suporte informático.",
            bot_id=bot_id,
            contexto=_contexto_falha_batch({"batch_body_preview": corpo_api}),
        )
        term(
            "$batch HTTP",
            r.status_code,
            "inner",
            inner_codes,
            "SAP",
            sap_err or "(sem XML de erro)",
            "corpo (início):",
            text[:800].replace("\n", " "),
        )
        r.raise_for_status()

    if not _batch_http_response_ok(text):
        corpo_api = _truncate_debug_text(text, 6000)
        log_tecnico(
            "Aceite $batch — corpo multipart com falha (ex.: 404 na parte interna)",
            f"url={getattr(r, 'url', batch_url)}\nstatus={r.status_code}\n"
            f"inner_http_statuses={inner_codes}\n{sap_err}\n\n{_truncate_debug_text(text, 100000)}",
        )
        post_log(
            "error",
            "erro_api",
            "O sistema não confirmou a aceitação da carga. Contacte o suporte informático.",
            bot_id=bot_id,
            contexto=_contexto_falha_batch({"batch_body_preview": corpo_api}),
        )
        term(
            "$batch multipart com falha — HTTP externo",
            r.status_code,
            "partes internas",
            inner_codes,
            "SAP OData",
            sap_err or "(sem XML de erro)",
            "corpo (início):",
            text[:1200].replace("\n", " "),
        )
        detalhe_sap = ""
        if sap_err.get("sap_error_code") or sap_err.get("sap_error_message"):
            detalhe_sap = (
                f"SAP: {sap_err.get('sap_error_code', '')} {sap_err.get('sap_error_message', '')}"
            ).strip()
        partes_msg = [
            "A aceitação da carga não foi confirmada pelo sistema.",
            f"HTTP {r.status_code}, partes internas {inner_codes}.",
        ]
        if detalhe_sap:
            partes_msg.append(detalhe_sap)
        partes_msg.append("Ver worker_tecnico.log para o corpo completo do $batch.")
        raise RuntimeError(" ".join(partes_msg))

    term("Aceite RFQ OK uuid", str(rfq_uuid)[:36])
    return {"raw": text[:2000]} if text else {}
