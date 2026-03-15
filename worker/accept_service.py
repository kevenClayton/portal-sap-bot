"""
Serviço de aceitação de carga: orquestra regras + chamada SAP + registro na API.
"""
import time
from sap_client import get_cargas, fetch_csrf_token, accept_carga
from rule_engine import atende_regras
from api_client import post_carga, post_log


def run_ciclo(cookies, user_agent, parametros, bot_id):
    """
    Executa um ciclo: busca cargas, aplica regras, aceita as que passam, registra tudo.
    Retorna (cargas_analisadas, cargas_capturadas, cargas_ignoradas).
    """
    csrf = fetch_csrf_token(cookies, user_agent)
    if not csrf:
        post_log("error", "erro_api", "CSRF token não obtido", bot_id=bot_id)
        return 0, 0, 0

    try:
        data = get_cargas(cookies, csrf, top=100, skip=0, user_agent=user_agent)
    except Exception as e:
        post_log("error", "erro_api", str(e), bot_id=bot_id)
        return 0, 0, 0

    d = data.get("d", data)
    entries = d.get("results", d) if isinstance(d, dict) else d
    if not entries or not isinstance(entries, list):
        entries = []
    if not entries:
        return 0, 0, 0

    analisadas = 0
    capturadas = 0
    ignoradas = 0

    for item in entries:
        analisadas += 1
        rfq_uuid = item.get("RequestForQuotationUUID")
        rfq_id = item.get("RequestForQuotationID")
        origem = item.get("SourceLocationCity")
        destino = item.get("DestinationLocationCity")
        peso = item.get("GrossWeightValue")
        distancia = item.get("TotalDistance")

        if not atende_regras(item, parametros or {}):
            ignoradas += 1
            post_carga({
                "rfq_uuid": rfq_uuid,
                "rfq_id": rfq_id,
                "origem": origem,
                "destino": destino,
                "peso": peso,
                "distancia": distancia,
                "status": "ignorada",
                "dados_json": item,
            })
            continue

        t0 = time.time()
        try:
            accept_carga(cookies, csrf, rfq_uuid, user_agent=user_agent)
            tempo_ms = int((time.time() - t0) * 1000)
            capturadas += 1
            post_carga({
                "rfq_uuid": rfq_uuid,
                "rfq_id": rfq_id,
                "origem": origem,
                "destino": destino,
                "peso": peso,
                "distancia": distancia,
                "status": "capturada",
                "dados_json": item,
                "tempo_resposta_ms": tempo_ms,
            })
            post_log("info", "carga_aceita", f"RFQ {rfq_id} aceita", bot_id=bot_id, contexto={"rfq_uuid": rfq_uuid})
        except Exception as e:
            ignoradas += 1
            post_carga({
                "rfq_uuid": rfq_uuid,
                "rfq_id": rfq_id,
                "origem": origem,
                "destino": destino,
                "peso": peso,
                "distancia": distancia,
                "status": "erro",
                "dados_json": item,
            })
            post_log("error", "erro_api", str(e), bot_id=bot_id, contexto={"rfq_uuid": rfq_uuid})

    return analisadas, capturadas, ignoradas
