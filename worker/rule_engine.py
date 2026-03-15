"""
Motor de regras: avalia se uma carga atende aos parâmetros configurados.
"""
# Parâmetros vêm do painel (parametros): origem, destino, peso_min, distancia_max, etc.


def atende_regras(carga, parametros):
    """
    carga: dict com keys como SourceLocationCity, DestinationLocationCity,
            GrossWeightValue, TotalDistance, LifecycleStatus, ZZTRUCK, etc.
    parametros: dict com origem, destino, peso_min, distancia_max, tipo_veiculo, etc.

    Retorna True se a carga atende a todos os filtros preenchidos.
    """
    if not parametros:
        return True

    # Origem
    if parametros.get("origem"):
        origem_carga = (carga.get("SourceLocationCity") or "").strip().upper()
        origem_param = (parametros["origem"] or "").strip().upper()
        if origem_param and origem_carga != origem_param:
            return False

    # Destino
    if parametros.get("destino"):
        dest_carga = (carga.get("DestinationLocationCity") or "").strip().upper()
        dest_param = (parametros["destino"] or "").strip().upper()
        if dest_param and dest_carga != dest_param:
            return False

    # Peso mínimo
    peso_min = parametros.get("peso_min")
    if peso_min is not None and peso_min > 0:
        peso = _num(carga.get("GrossWeightValue"))
        if peso is None or peso < peso_min:
            return False

    # Distância máxima
    dist_max = parametros.get("distancia_max")
    if dist_max is not None and dist_max > 0:
        dist = _num(carga.get("TotalDistance"))
        if dist is not None and dist > dist_max:
            return False

    # Distância mínima
    dist_min = parametros.get("distancia_min")
    if dist_min is not None and dist_min > 0:
        dist = _num(carga.get("TotalDistance"))
        if dist is None or dist < dist_min:
            return False

    # Tipo de veículo (campos ZZ*)
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
