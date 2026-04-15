"""
Serviço de aceitação de carga: orquestra regras + chamada SAP + registro na API.
"""
import time
from sap_client import get_cargas, fetch_csrf_token, accept_carga
from rule_engine import atende_regras, modo_teste_ativo
from api_client import post_carga, post_log
from terminal_log import term, term_exc


def run_ciclo(
    cookies,
    user_agent,
    parametros,
    bot_id,
    csrf_token=None,
    cookie_list=None,
    sap_referer=None,
    sap_client=None,
):
    """
    Executa um ciclo: busca cargas, aplica regras, aceita as que passam, registra tudo.
    Retorna (cargas_analisadas, cargas_capturadas, cargas_ignoradas, cargas_simuladas).
    """
    term("run_ciclo bot_id =", bot_id, "modo teste =", modo_teste_ativo(parametros or {}))
    if (csrf_token or "").strip():
        term("CSRF: a reutilizar token obtido no login")
    csrf = (csrf_token or "").strip() or fetch_csrf_token(
        cookies,
        user_agent,
        cookie_list=cookie_list,
        referer=sap_referer,
        sap_client=sap_client,
        log_failures=True,
    )
    if not csrf:
        post_log(
            "error",
            "erro_api",
            "CSRF no ciclo indisponível — ver contexto técnico.",
            bot_id=bot_id,
            contexto={
                "sap_referer": (sap_referer or "")[:400],
                "sap_client": sap_client or "",
            },
        )
        term("CSRF indisponível; a abortar ciclo")
        return 0, 0, 0, 0

    try:
        data = get_cargas(
            cookies,
            csrf,
            top=1000,
            skip=0,
            user_agent=user_agent,
            cookie_list=cookie_list,
            referer=sap_referer,
            sap_client=sap_client,
        )
    except Exception as e:
        term_exc("get_cargas:", e)
        post_log("error", "erro_api", str(e), bot_id=bot_id)
        return 0, 0, 0, 0

    d = data.get("d", data)
    entries = d.get("results", d) if isinstance(d, dict) else d
    if not entries or not isinstance(entries, list):
        entries = []
    if not entries:
        term("Sem RFQs em aberto na lista OData")
        return 0, 0, 0, 0

    term("A processar", len(entries), "RFQ(s) da lista")
    teste = modo_teste_ativo(parametros or {})
    analisadas = 0
    capturadas = 0
    ignoradas = 0
    simuladas = 0

    for item in entries:
        analisadas += 1
        rfq_uuid = item.get("RequestForQuotationUUID")
        rfq_id = item.get("RequestForQuotationID")
        origem = item.get("SourceLocationCity")
        destino = item.get("DestinationLocationCity")
        peso = item.get("GrossWeightValue")
        distancia = item.get("TotalDistance")

        base_payload = {
            "bot_id": bot_id,
            "rfq_uuid": rfq_uuid,
            "rfq_id": rfq_id,
            "origem": origem,
            "destino": destino,
            "peso": peso,
            "distancia": distancia,
            "dados_json": item,
        }

        if not atende_regras(item, parametros or {}):
            term("RFQ", rfq_id, "ignorada (regras)")
            ignoradas += 1
            post_carga({**base_payload, "status": "ignorada"})
            continue

        if teste:
            term("RFQ", rfq_id, "simulada (modo teste)")
            simuladas += 1
            post_carga({**base_payload, "status": "simulada"})
            post_log(
                "info",
                "carga_simulada",
                f"Modo teste: RFQ {rfq_id} não capturada no SAP",
                bot_id=bot_id,
                contexto={"rfq_uuid": rfq_uuid},
            )
            continue
        t0 = time.time()
        try:
            term("Aceitar RFQ", rfq_id, "uuid", str(rfq_uuid)[:13] + "…")
            accept_carga(
                cookies,
                csrf,
                rfq_uuid,
                user_agent=user_agent,
                cookie_list=cookie_list,
                referer=sap_referer,
                sap_client=sap_client,
            )
            tempo_ms = int((time.time() - t0) * 1000)
            term("RFQ", rfq_id, "aceite em", tempo_ms, "ms")
            capturadas += 1
            post_carga(
                {
                    **base_payload,
                    "status": "capturada",
                    "tempo_resposta_ms": tempo_ms,
                }
            )
            post_log("info", "carga_aceita", f"RFQ {rfq_id} aceita", bot_id=bot_id, contexto={"rfq_uuid": rfq_uuid})
        except Exception as e:
            term_exc(f"Erro ao aceitar RFQ {rfq_id}:", e)
            ignoradas += 1
            post_carga({**base_payload, "status": "erro"})
            post_log("error", "erro_api", str(e), bot_id=bot_id, contexto={"rfq_uuid": rfq_uuid})

    term("run_ciclo fim: analisadas", analisadas, "capturadas", capturadas, "ignoradas", ignoradas, "simuladas", simuladas)
    return analisadas, capturadas, ignoradas, simuladas
