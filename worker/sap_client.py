"""
Cliente HTTP para chamadas OData ao SAP (usando cookies e CSRF).
"""
import requests
from urllib.parse import urljoin

from config import SAP_BASE_URL
from api_client import post_log


def fetch_csrf_token(cookies, user_agent=None):
    """Obtém o token CSRF via GET com header X-CSRF-Token: Fetch."""
    url = urljoin(SAP_BASE_URL, "/sap/opu/odata/SCMTMS/TENDERING/")
    headers = {
        "X-CSRF-Token": "Fetch",
        "Accept": "application/json",
        "User-Agent": user_agent or "Bot/1.0",
    }
    r = requests.get(url, cookies=cookies, headers=headers, timeout=30)
    token = r.headers.get("X-CSRF-Token")
    return token


def get_cargas(cookies, csrf_token, top=100, skip=0, lifecycle_status="02", user_agent=None):
    """
    Consulta RequestForQuotationCollection.
    lifecycle_status '02' = cargas abertas.
    """
    path = "/sap/opu/odata/SCMTMS/TENDERING/RequestForQuotationCollection"
    params = {
        "$top": top,
        "$skip": skip,
        "$filter": f"LifecycleStatus eq '{lifecycle_status}'",
        "$format": "json",
    }
    url = urljoin(SAP_BASE_URL, path)
    headers = {
        "X-CSRF-Token": csrf_token,
        "Accept": "application/json",
        "Content-Type": "application/json",
        "User-Agent": user_agent or "Bot/1.0",
    }
    r = requests.get(url, params=params, cookies=cookies, headers=headers, timeout=30)
    if r.status_code != 200:
        post_log("error", "erro_api", f"OData status {r.status_code}", contexto={"response": r.text[:500]})
        r.raise_for_status()
    return r.json()


def accept_carga(cookies, csrf_token, rfq_uuid, payload=None, user_agent=None):
    """
    Aceita uma carga (endpoint real depende do portal; aqui é um placeholder).
    Em produção, usar o método correto (PATCH/POST) conforme documentação SAP.
    """
    # Exemplo: PATCH ou POST na entidade ou ação de aceitação
    path = f"/sap/opu/odata/SCMTMS/TENDERING/RequestForQuotationCollection('{rfq_uuid}')"
    url = urljoin(SAP_BASE_URL, path)
    headers = {
        "X-CSRF-Token": csrf_token,
        "Accept": "application/json",
        "Content-Type": "application/json",
        "User-Agent": user_agent or "Bot/1.0",
    }
    # Ação de aceitar pode ser POST em .../Accept ou PATCH no entity
    # Ajustar conforme API real do portal
    r = requests.post(url, json=payload or {}, cookies=cookies, headers=headers, timeout=15)
    if r.status_code not in (200, 201, 204):
        post_log("error", "erro_api", f"Aceitar carga {r.status_code}", contexto={"rfq_uuid": rfq_uuid, "response": r.text[:300]})
        r.raise_for_status()
    return r.json() if r.content else {}
