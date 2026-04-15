"""
Motor de regras: avalia se uma carga atende aos parâmetros configurados.
Critérios opcionais: só entram na avaliação se estiverem preenchidos.
Cidades permitidas em listas; regras extras por cidade (origem ou destino) com peso/valor.
"""
import re


def atende_regras(carga, parametros):
    """
    carga: dict com keys como SourceLocationCity, DestinationLocationCity,
            GrossWeightValue, TotalDistance, LifecycleStatus, ZZTRUCK, etc.
    parametros: dict vindo da API (listas de cidades, regras, peso_min/max, etc.)

    Retorna True se a carga atende a todos os filtros que o painel preencheu.
    """
    if not parametros:
        return True

    co = _lista_normalizada(
        parametros.get("cidades_origem_aceitas") or parametros.get("cidades_origem")
    )
    if co:
        if not _cidade_em_lista(carga.get("SourceLocationCity"), co):
            return False

    cd = _lista_normalizada(
        parametros.get("cidades_destino_aceitas") or parametros.get("cidades_destino")
    )
    if cd:
        if not _cidade_em_lista(carga.get("DestinationLocationCity"), cd):
            return False

    for regra in _lista_regras(parametros.get("regras")):
        if not _regra_cidade_aplicavel(regra, carga):
            continue
        if not _regra_limites_ok(regra, carga):
            return False

    peso_min = parametros.get("peso_min")
    if _limite_positivo(peso_min):
        peso = _num(carga.get("GrossWeightValue"))
        if peso is None or peso < float(peso_min):
            return False

    peso_max = parametros.get("peso_max")
    if _limite_positivo(peso_max):
        peso = _num(carga.get("GrossWeightValue"))
        if peso is None or peso > float(peso_max):
            return False

    dist_max = parametros.get("distancia_max")
    if _limite_positivo(dist_max):
        dist = _num(carga.get("TotalDistance"))
        if dist is not None and dist > float(dist_max):
            return False

    dist_min = parametros.get("distancia_min")
    if _limite_positivo(dist_min):
        dist = _num(carga.get("TotalDistance"))
        if dist is None or dist < float(dist_min):
            return False

    tipo = parametros.get("tipo_veiculo")
    if tipo:
        if tipo == "ZZTRUCK" and not _truthy(carga.get("ZZTRUCK")):
            return False
        if tipo == "ZZBITRUCK" and not _truthy(carga.get("ZZBITRUCK")):
            return False
        if tipo == "ZZCARRETA" and not _truthy(carga.get("ZZCARRETA")):
            return False
        if tipo == "ZZRODOTREM" and not _truthy(carga.get("ZZRODOTREM")):
            return False

    return True


def _lista_regras(v):
    if v is None:
        return []
    if isinstance(v, list):
        return [x for x in v if isinstance(x, dict)]
    return []


def _regra_cidade_aplicavel(regra, carga):
    cidade_regra = (regra.get("cidade") or "").strip().upper()
    if not cidade_regra:
        return False
    aplica = (regra.get("aplica_a") or "").strip().lower()
    if aplica == "origem":
        cidade_carga = (carga.get("SourceLocationCity") or "").strip().upper()
    elif aplica == "destino":
        cidade_carga = (carga.get("DestinationLocationCity") or "").strip().upper()
    else:
        return False
    return cidade_carga == cidade_regra


def _regra_limites_ok(regra, carga):
    peso = _num(carga.get("GrossWeightValue"))
    valor = _valor_carga(carga)

    pmn = regra.get("peso_min_kg")
    if _limite_numerico_definido(pmn):
        if peso is None or peso < float(pmn):
            return False

    pmx = regra.get("peso_max_kg")
    if _limite_numerico_definido(pmx):
        if peso is None or peso > float(pmx):
            return False

    vmi = regra.get("valor_carga_min")
    if _limite_numerico_definido(vmi):
        if valor is None or valor < float(vmi):
            return False

    vma = regra.get("valor_carga_max")
    if _limite_numerico_definido(vma):
        if valor is None or valor > float(vma):
            return False

    return True


def _valor_carga(carga):
    for key in (
        "LowestPriceProposal",
        "PriceLimit",
        "TotalAmount",
        "FreightValue",
        "TargetAmount",
        "Amount",
    ):
        v = _num(carga.get(key))
        if v is not None:
            return v
    return None


def _limite_numerico_definido(v):
    if v is None or v == "":
        return False
    try:
        float(v)
        return True
    except (TypeError, ValueError):
        return False


def _limite_positivo(v):
    if v is None or v == "":
        return False
    try:
        return float(v) > 0
    except (TypeError, ValueError):
        return False


def _lista_normalizada(v):
    if v is None:
        return []
    if isinstance(v, list):
        out = []
        for x in v:
            if x is None:
                continue
            s = str(x).strip()
            if s:
                out.append(s.upper())
        return out
    if isinstance(v, str):
        return [p.strip().upper() for p in re.split(r"[\n,;]+", v) if p.strip()]
    return []


def _cidade_em_lista(cidade_carga, lista):
    c = (cidade_carga or "").strip().upper()
    return c in lista


def _num(v):
    if v is None:
        return None
    try:
        return float(v)
    except (TypeError, ValueError):
        return None


def _truthy(v):
    if v is None:
        return False
    if isinstance(v, bool):
        return v
    if isinstance(v, str):
        return v.strip().upper() in ("1", "TRUE", "X", "S", "SIM", "YES")
    return bool(v)


def modo_teste_ativo(parametros):
    if not parametros:
        return False
    v = parametros.get("modo_teste")
    if v is True:
        return True
    if v in (1, "1"):
        return True
    if isinstance(v, str) and v.strip().lower() in ("true", "yes", "sim", "on"):
        return True
    return False
