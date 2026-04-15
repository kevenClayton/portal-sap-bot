"""
Login no portal SAP Fiori / BIG-IP (my.policy) e captura de cookies + sessão.
Domínio (ex.: BMS) no campo input_3 (ou equivalente) antes do utilizador/senha.
"""
from __future__ import annotations

import time
import traceback
from typing import Optional
from playwright.sync_api import Error as PlaywrightError
from playwright.sync_api import sync_playwright

from urllib.parse import urljoin

from config import SAP_BASE_URL, SAP_CLIENT, SAP_DOMAIN, SAP_LOGIN_URL, SAP_PASSWORD, SAP_USER
from api_client import post_log
from sap_client import cookie_jar_from_playwright, fetch_csrf_token
from terminal_log import log_tecnico, term, term_exc


def _detalhe_excecao_playwright(exc: Exception) -> str:
    """Texto para log ficheiro: mensagem + traceback."""
    lines = [repr(exc), str(exc), ""]
    lines.append(traceback.format_exc())
    return "\n".join(lines)


def _csrf_via_inpage_fetch(page, sap_client: str) -> Optional[str]:
    """
    fetch() no contexto da página Fiori — envia os mesmos cookies que a UI (evita 401 sem MYSAPSSO2 no jar).
    """
    try:
        term("CSRF: fetch() no documento (Fiori) com sap-client =", sap_client or "(omitido)")
        result = page.evaluate(
            """async ({ sapClient }) => {
              const u = new URL('/sap/opu/odata/SCMTMS/TENDERING/', window.location.origin);
              u.searchParams.set('$format', 'json');
              if (sapClient) u.searchParams.set('sap-client', sapClient);
              const r = await fetch(u.toString(), {
                method: 'GET',
                credentials: 'include',
                headers: {
                  'X-CSRF-Token': 'Fetch',
                  'Accept': 'application/json',
                },
              });
              let textStart = '';
              try { textStart = (await r.text()).slice(0, 800); } catch (e) { textStart = String(e); }
              const token = r.headers.get('X-CSRF-Token') || r.headers.get('x-csrf-token') || '';
              return {
                status: r.status,
                token: token,
                url: r.url,
                textStart: textStart,
              };
            }""",
            {"sapClient": (sap_client or "").strip()},
        )
        st = int(result.get("status") or 0)
        tok = (result.get("token") or "").strip()
        term("CSRF in-page fetch: HTTP", st, "url", (result.get("url") or "")[:120])
        log_tecnico(
            "CSRF in-page fetch",
            f"status={st}\nurl={result.get('url')}\ntoken_len={len(tok)}\n"
            f"corpo_inicio={result.get('textStart') or ''}\n",
        )
        if st != 200:
            term("CSRF in-page: corpo (início):", (result.get("textStart") or "")[:200].replace("\n", " "))
            return None
        if not tok or tok.lower() in ("fetch", "required", "none"):
            return None
        term("CSRF OK via fetch() na página,", len(tok), "chars")
        return tok
    except Exception as e:
        term_exc("CSRF in-page fetch excepção:", e)
        log_tecnico("CSRF in-page fetch excepção", _detalhe_excecao_playwright(e))
        return None


def _csrf_fetch_playwright(page, odata_url: str, user_agent: str, referer: str, sap_client: str):
    """
    Mesmo armazenamento de cookies que o browser — contorna falhas do requests+jar
    em alguns portais corporativos.
    """
    try:
        params = {"$format": "json"}
        if (sap_client or "").strip():
            params["sap-client"] = sap_client.strip()
        term("CSRF fallback Playwright APIRequest GET…", odata_url[:72], "params", params)
        resp = page.request.get(
            odata_url,
            params=params,
            headers={
                "X-CSRF-Token": "Fetch",
                "Accept": "application/json",
                "User-Agent": user_agent or "Bot/1.0",
                "Referer": (referer or SAP_BASE_URL).strip()[:2048],
            },
            timeout=60000,
        )
        h = resp.headers
        token = h.get("x-csrf-token") or h.get("X-CSRF-Token")
        term("CSRF Playwright HTTP", resp.status, "token cabeçalho vazio?", not bool(token))
        if resp.status != 200:
            return None
        if not token or str(token).strip().lower() in ("fetch", "required", "none"):
            return None
        term("CSRF OK via Playwright request,", len(str(token)), "chars")
        return str(token).strip()
    except Exception as e:
        term_exc("CSRF Playwright excepção:", e)
        return None


def _log_safe(nivel, evento, mensagem=None, **kwargs):
    try:
        post_log(nivel, evento, mensagem, **kwargs)
    except Exception:
        pass


# Possíveis campos de domínio (portais Arcelor / F5 / SAP)
# select#input_3 primeiro — evita confusão com input homónimo; Arcelor usa credentials_input_select
DOMAIN_SELECTORS = [
    "select#input_3",
    'select[name="domain"]',
    "select.credentials_input_select",
    "#input_3",
    "select[name='input_3']",
    "input[name='input_3']",
    "input[name='domain']",
    "select#domain",
    "#domain",
    "input[name*='domain']",
    "select[name*='domain']",
]

USER_SELECTORS = [
    'input[name="username"]',
    "input#username",
    'input[name="user"]',
    'input[name="j_username"]',
    "#j_username",
    'input[name="sap-user"]',
    "#USERNAME_FIELD-inner",
    'input[id*="USERNAME_FIELD"]',
    'input[id*="userName"]',
    'input[id*="UserName"]',
    'input[aria-label*="user"]',
    'input[placeholder*="utilizador"]',
    'input[placeholder*="Utilizador"]',
    'input[placeholder*="user"]',
]

PASS_SELECTORS = [
    'input[name="password"]',
    "input#password",
    'input[type="password"]',
    "#PASSWORD_FIELD-inner",
    'input[id*="PASSWORD_FIELD"]',
    'input[id*="password"]',
    'input[id*="Password"]',
]

SUBMIT_SELECTORS = [
    'button[type="submit"]',
    'input[type="submit"]',
    'button[name="login"]',
    "#logon_continuation",
    ".logon-button",
    'button:has-text("Log On")',
    'button:has-text("Sign In")',
    'button:has-text("Entrar")',
    'button:has-text("Iniciar")',
    'input[value*="Log"]',
    'button[title*="Log"]',
]


def _resolve_login_surface(page, max_attempts: int = 6, delay_s: float = 0.5):
    """
    BIG-IP/F5: o formulário de logon costuma estar num iframe; locators na Page só vêem o frame principal.
    """
    for _ in range(max_attempts):
        time.sleep(delay_s)
        ordered = [page.main_frame] + [f for f in page.frames if f != page.main_frame]
        for fr in ordered:
            try:
                loc = fr.locator("input[type='password']")
                if loc.count() == 0:
                    continue
                loc.first.wait_for(state="visible", timeout=8000)
                term(
                    "Formulário de login: campo password visível no frame",
                    "secundário (iframe)" if fr != page.main_frame else "principal",
                )
                return fr
            except Exception:
                continue
    term("Formulário de login: nenhum iframe com password; a usar frame principal")
    return page.main_frame


def _password_still_visible_anywhere(page) -> bool:
    for fr in [page.main_frame] + [f for f in page.frames if f != page.main_frame]:
        try:
            loc = fr.locator('input[type="password"]')
            if loc.count() == 0:
                continue
            if loc.first.is_visible(timeout=600):
                return True
        except Exception:
            continue
    return False


def _first_matching_locator(root, selectors, timeout_ms: int = 4000):
    for sel in selectors:
        try:
            loc = root.locator(sel).first
            loc.wait_for(state="visible", timeout=timeout_ms)
            return loc
        except Exception:
            continue
    return None


def _definir_dominio_no_frame(root, dominio: str) -> bool:
    """
    Preenche domínio num único Frame. <select> pode falhar com wait visible (CSS/headless);
    usa attached + select_option e dispara change para o APM/BIG-IP reagir.
    """
    if not dominio:
        return True
    dom = dominio.strip()
    for sel in DOMAIN_SELECTORS:
        try:
            loc = root.locator(sel)
            if loc.count() == 0:
                continue
            el = loc.first
            try:
                el.wait_for(state="visible", timeout=2500)
            except Exception:
                el.wait_for(state="attached", timeout=8000)
            tag = el.evaluate("e => e.tagName.toLowerCase()")
            if tag == "select":
                selected = False
                for kwargs in (
                    {"value": dom, "timeout": 20000},
                    {"value": dom, "timeout": 20000, "force": True},
                    {"label": dom, "timeout": 20000},
                    {"label": dom, "timeout": 20000, "force": True},
                ):
                    try:
                        el.select_option(**kwargs)
                        selected = True
                        break
                    except Exception:
                        continue
                if not selected:
                    applied = el.evaluate(
                        """(e, wanted) => {
                          const w = String(wanted).trim();
                          const opts = [...e.querySelectorAll('option')];
                          const o = opts.find(x => x.value === w)
                            || opts.find(x => x.value.toUpperCase() === w.toUpperCase())
                            || opts.find(x => (x.textContent || '').trim() === w);
                          if (!o) return false;
                          e.value = o.value;
                          e.dispatchEvent(new Event('input', { bubbles: true }));
                          e.dispatchEvent(new Event('change', { bubbles: true }));
                          return true;
                        }""",
                        dom,
                    )
                    if not applied:
                        raise RuntimeError(f"opção «{dom}» não encontrada no <select>")
                el.evaluate(
                    """e => {
                    e.dispatchEvent(new Event('input', { bubbles: true }));
                    e.dispatchEvent(new Event('change', { bubbles: true }));
                }"""
                )
            else:
                el.fill(dom)
                el.evaluate(
                    """el => {
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }"""
                )
            time.sleep(0.4)
            term("Domínio aplicado:", dom, "seletor:", sel, "tag:", tag)
            return True
        except Exception as e:
            term("Domínio falhou seletor", sel, "—", type(e).__name__, str(e)[:100])
            continue
    return False


def _definir_dominio_pagina(page, dominio: str) -> bool:
    """Tenta domínio no frame principal e nos iframes (ordem igual ao resto do login)."""
    if not dominio:
        return True
    ordered = [page.main_frame] + [f for f in page.frames if f != page.main_frame]
    for fr in ordered:
        if _definir_dominio_no_frame(fr, dominio):
            return True
    return False


def _bigip_clicar_nova_sessao(page) -> bool:
    """
    BIG-IP F5: «Your session could not be established» — #newSessionDIV a (href /).
    Procura no frame principal e nos iframes.
    """
    ordered = [page.main_frame] + [f for f in page.frames if f != page.main_frame]
    for fr in ordered:
        div = fr.locator("#newSessionDIV")
        if div.count() == 0:
            continue
        link = fr.locator("#newSessionDIV a").first
        try:
            link.wait_for(state="visible", timeout=5000)
        except Exception:
            try:
                link.wait_for(state="attached", timeout=3000)
            except Exception:
                continue
        nome = "iframe" if fr != page.main_frame else "principal"
        try:
            term("BIG-IP: sessão não encontrada no pedido — a clicar «click here» (nova sessão,", nome + ")")
            link.click(timeout=20000)
            log_tecnico(
                "BIG-IP nova sessão (click here)",
                f"frame={nome}\nURL antes: {page.url}\n",
            )
            return True
        except Exception as e:
            term("BIG-IP: falha ao clicar nova sessão (" + nome + "):", str(e)[:120])
            log_tecnico("BIG-IP clique nova sessão falhou", _detalhe_excecao_playwright(e))
    return False


def _resolver_bigip_sessao_perdida(page, max_rodadas: int = 6) -> None:
    """Enquanto aparecer o ecrã de sessão BIG-IP, clicar em «click here» e aguardar carga."""
    for n in range(max_rodadas):
        if not _bigip_clicar_nova_sessao(page):
            return
        term("BIG-IP: aguardar navegação após nova sessão (rodada", n + 1, ")")
        try:
            page.wait_for_load_state("domcontentloaded", timeout=90000)
        except Exception as e:
            term("BIG-IP: wait domcontentloaded:", str(e)[:100])
        time.sleep(1.0)
        try:
            page.wait_for_load_state("networkidle", timeout=20000)
        except Exception:
            pass
        time.sleep(0.5)


def _find_login_fields(root):
    """Localiza campos de utilizador e senha (vários layouts SAP / Fiori / BIG-IP)."""
    user_loc = _first_matching_locator(root, USER_SELECTORS, timeout_ms=12000)
    pass_loc = _first_matching_locator(root, PASS_SELECTORS, timeout_ms=12000)
    if user_loc and not pass_loc:
        pass_loc = _first_matching_locator(root, PASS_SELECTORS, timeout_ms=5000)
    if pass_loc and not user_loc:
        user_loc = _first_matching_locator(root, USER_SELECTORS, timeout_ms=5000)
    return user_loc, pass_loc


def login(headless=True, user=None, password=None):
    """
    Abre SAP_LOGIN_URL (my.policy por defeito), define domínio, credenciais e submete.
    Falha com excepção se o formulário não for encontrado ou o login não alterar a página.
    """
    u = (user or "").strip() or SAP_USER
    pwd = (password or "").strip() or SAP_PASSWORD

    term("Playwright: headless =", headless, "utilizador =", u[:3] + "…" if len(u) > 3 else u)
    term("Abrir página de login:", SAP_LOGIN_URL)

    with sync_playwright() as pw:
        browser = pw.chromium.launch(headless=headless)
        context = browser.new_context()
        page = context.new_page()

        try:
            page.goto(SAP_LOGIN_URL, wait_until="domcontentloaded", timeout=90000)
            term("Navegação login: URL actual =", page.url[:100])
            time.sleep(0.8)
            try:
                page.wait_for_load_state("networkidle", timeout=15000)
            except Exception:
                pass

            _resolver_bigip_sessao_perdida(page)

            surface = _resolve_login_surface(page)
            dom_ok = False
            if SAP_DOMAIN:
                term("Definir domínio SAP_DOMAIN =", SAP_DOMAIN)
                dom_ok = _definir_dominio_pagina(page, SAP_DOMAIN)
                term("Domínio preenchido:", dom_ok)
            if SAP_DOMAIN and not dom_ok:
                _log_safe(
                    "warning",
                    "login",
                    "O domínio do portal não foi preenchido automaticamente; a tentar continuar o início de sessão.",
                )

            user_loc, pass_loc = _find_login_fields(surface)
            if not user_loc or not pass_loc:
                term("ERRO: utilizador/senha não localizados no formulário")
                raise RuntimeError(
                    "O ecrã de início de sessão do portal não foi reconhecido. Contacte o suporte informático."
                )

            term("Preencher utilizador e senha")
            user_loc.fill(u)
            pass_loc.fill(pwd)
            time.sleep(0.2)

            submitted = False
            for sel in SUBMIT_SELECTORS:
                try:
                    btn = surface.locator(sel).first
                    btn.wait_for(state="visible", timeout=2500)
                    term("Clicar botão submit seletor:", sel[:60])
                    btn.click(timeout=8000)
                    submitted = True
                    break
                except Exception:
                    continue
            if not submitted:
                term("Submit: Enter no campo senha")
                pass_loc.press("Enter")

            try:
                page.wait_for_load_state("networkidle", timeout=90000)
            except Exception:
                page.wait_for_load_state("domcontentloaded", timeout=30000)
            time.sleep(1.0)
            term("Após submit: URL =", page.url[:120])

            _resolver_bigip_sessao_perdida(page)

            # Ainda na página de logon com senha visível = falhou (qualquer frame)
            try:
                if _password_still_visible_anywhere(page):
                    err = page.locator(".error, .errortext, [class*='error'], .loginError").first
                    msg = ""
                    try:
                        if err.count():
                            msg = err.inner_text(timeout=1000)[:200]
                    except Exception:
                        pass
                    term("Portal ainda mostra campo senha; mensagem:", (msg or "")[:160])
                    raise RuntimeError(
                        msg.strip()
                        or "Início de sessão recusado. Verifique utilizador, senha e domínio, ou contacte o suporte."
                    )
            except RuntimeError:
                raise
            except Exception:
                pass

            term("Login aparentemente OK; sessão OData TM (cookies + CSRF)")
            portal_url_apos_login = page.url
            term("URL do portal após login (Referer OData):", portal_url_apos_login[:180])

            user_agent = page.evaluate("() => navigator.userAgent")
            term("User-Agent:", (user_agent or "")[:80])

            odata_entry = urljoin(SAP_BASE_URL + "/", "sap/opu/odata/SCMTMS/TENDERING/")
            sap_client_val = (SAP_CLIENT or "").strip()

            # 1) fetch() no documento Fiori — mesmos cookies HttpOnly da UI; evita page.goto OData (chrome-error / ERR_INVALID_AUTH)
            csrf = _csrf_via_inpage_fetch(page, sap_client_val)

            # 2) Playwright APIRequest com Referer = app Fiori + sap-client
            if not csrf:
                try:
                    params = {"$format": "json"}
                    if sap_client_val:
                        params["sap-client"] = sap_client_val
                    resp = page.request.get(
                        odata_entry,
                        params=params,
                        headers={
                            "X-CSRF-Token": "Fetch",
                            "Accept": "application/json",
                            "User-Agent": user_agent or "Bot/1.0",
                            "Referer": portal_url_apos_login[:2048],
                        },
                        timeout=60000,
                    )
                    hdrs = dict(resp.headers)
                    tok = hdrs.get("x-csrf-token") or hdrs.get("X-CSRF-Token") or ""
                    term("APIRequest OData (sem goto): HTTP", resp.status, (resp.url or "")[:120])
                    log_tecnico(
                        "APIRequest OData CSRF",
                        f"status={resp.status}\nurl={resp.url}\n"
                        f"X-CSRF-Token (início): {tok[:48]}\n"
                        f"corpo_inicio={(resp.text() or '')[:800]}",
                    )
                    if resp.status == 200 and tok and str(tok).strip().lower() not in ("fetch", "required", "none"):
                        csrf = str(tok).strip()
                        term("CSRF OK via APIRequest,", len(csrf), "chars")
                except Exception as e:
                    log_tecnico("APIRequest OData excepção", _detalhe_excecao_playwright(e))
                    term_exc("APIRequest OData:", e)

            # 3) Se a aba ficou em chrome-error (tentativa antiga), voltar à Fiori e repetir fetch
            if not csrf and ("chrome-error" in page.url or "chromewebdata" in page.url):
                term("A recuperar página Fiori após chrome-error…")
                try:
                    page.goto(portal_url_apos_login, wait_until="domcontentloaded", timeout=90000)
                    time.sleep(1.0)
                    csrf = _csrf_via_inpage_fetch(page, sap_client_val)
                except Exception as e:
                    log_tecnico("goto recuperação Fiori", _detalhe_excecao_playwright(e))
                    term_exc("goto recuperação Fiori:", e)

            # 4) Último recurso: page.goto OData (pode falhar com ERR_INVALID_AUTH_CREDENTIALS)
            odata_nav_ok = False
            if not csrf:
                odata_goto = odata_entry.rstrip("/") + "?$format=json"
                if sap_client_val:
                    odata_goto += "&sap-client=" + sap_client_val
                try:
                    page.goto(odata_goto, wait_until="domcontentloaded", timeout=60000)
                    odata_nav_ok = True
                    term("page.goto OData OK — URL:", page.url[:150])
                except Exception as e:
                    log_tecnico("page.goto OData (opcional) falhou", _detalhe_excecao_playwright(e))
                    term_exc("page.goto OData opcional falhou:", e)
                    if isinstance(e, PlaywrightError) and "ERR_INVALID_AUTH_CREDENTIALS" in str(e):
                        term(
                            "Nota: ERR_INVALID_AUTH_CREDENTIALS no goto é frequente; CSRF deve vir do fetch/APIRequest/requests.",
                        )

            if odata_nav_ok:
                time.sleep(1.0)
                try:
                    page.wait_for_load_state("networkidle", timeout=20000)
                except Exception:
                    pass
                final = page.url.lower()
                term("URL após goto OData:", page.url[:120])
                if "my.policy" in final or "/saml" in final or "f5-oauth" in final:
                    term("Redireccionamento SSO detectado (my.policy/saml/f5-oauth); sessão inválida para TM")
                    raise RuntimeError(
                        "A sessão expirou ou o portal pediu novo início de sessão. Tente de novo ou contacte o suporte informático."
                    )

            cookie_list = context.cookies()
            nomes = sorted({c.get("name", "") for c in cookie_list if c.get("name")})
            term("Cookies no contexto:", len(cookie_list), "nomes:", ", ".join(nomes[:20]) + ("…" if len(nomes) > 20 else ""))
            cookie_jar = cookie_jar_from_playwright(cookie_list)

            if not csrf:
                term("CSRF: requests (jar + dict) com Referer Fiori e sap-client…")
                csrf = fetch_csrf_token(
                    cookie_jar,
                    user_agent,
                    log_failures=True,
                    cookie_list=cookie_list,
                    referer=portal_url_apos_login,
                    sap_client=sap_client_val,
                )
            if not csrf:
                csrf = _csrf_fetch_playwright(
                    page, odata_entry, user_agent, portal_url_apos_login, sap_client_val
                )

            if not csrf:
                ctx = {
                    "portal_url_apos_login": portal_url_apos_login[:500],
                    "sap_client": sap_client_val or "(vazio)",
                    "odata_entry": odata_entry,
                    "cookies_nomes": ", ".join(nomes[:60]),
                    "url_navegador": page.url[:500],
                    "nota": "401 Logon Error costuma indicar falta de sessão SAP no pedido OData; confirme SAP_CLIENT e permissão TM Tendering.",
                }
                log_tecnico("Falha CSRF após todas as tentativas", str(ctx))
                term("CSRF em falta — cookies:", ", ".join(nomes[:40]))
                msg = (
                    "CSRF OData indisponível (HTTP 401 ou sem token). "
                    "Verifique SAP_CLIENT no .env, permissões e contexto no painel (detalhes técnicos)."
                )
                _log_safe("error", "erro_auth", msg, contexto=ctx)
                raise RuntimeError(msg)

            term("Login concluído: fechar browser e devolver sessão")
            browser.close()
            return {
                "cookies": cookie_jar,
                "cookie_list": cookie_list,
                "user_agent": user_agent,
                "csrf_token": csrf,
                "sap_referer": portal_url_apos_login,
                "sap_client": sap_client_val,
            }
        except Exception as e:
            browser.close()
            term_exc("Falha no login:", e)
            _log_safe(
                "error",
                "erro_auth",
                f"{type(e).__name__}: {e}",
                contexto={"traceback": traceback.format_exc()[-4000:]},
            )
            raise

