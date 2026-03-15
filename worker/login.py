"""
Login no portal SAP e captura de cookies + sessão.
Usa Playwright para simular o navegador e obter cookies válidos.
"""
import time
from playwright.sync_api import sync_playwright

from config import SAP_BASE_URL, SAP_USER, SAP_PASSWORD
from api_client import post_log


def login(headless=True):
    """
    Faz login no portal SAP e retorna um dicionário com:
    - cookies: dict de nome -> valor
    - user_agent: string (opcional para requests)
    """
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=headless)
        context = browser.new_context()
        page = context.new_page()

        try:
            page.goto(SAP_BASE_URL, wait_until="networkidle", timeout=60000)
            time.sleep(1)

            # Ajustar seletores conforme o portal real (exemplo genérico)
            user_input = page.query_selector('input[name="username"], input[id*="user"], input[type="text"]')
            pass_input = page.query_selector('input[name="password"], input[id*="pass"], input[type="password"]')

            if user_input and pass_input:
                user_input.fill(SAP_USER)
                pass_input.fill(SAP_PASSWORD)
                submit = page.query_selector('button[type="submit"], input[type="submit"], [type="submit"]')
                if submit:
                    submit.click()
                else:
                    page.keyboard.press("Enter")
                page.wait_for_load_state("networkidle", timeout=30000)
            else:
                # Portal pode usar SSO ou outro fluxo
                post_log("warning", "login", "Seletores de login não encontrados; verifique SAP_BASE_URL e fluxo.", contexto={"url": SAP_BASE_URL})

            cookies = {c["name"]: c["value"] for c in context.cookies()}
            user_agent = page.evaluate("() => navigator.userAgent")
            browser.close()
            return {"cookies": cookies, "user_agent": user_agent}
        except Exception as e:
            browser.close()
            post_log("error", "erro_auth", str(e), contexto={"url": SAP_BASE_URL})
            raise
