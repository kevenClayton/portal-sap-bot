"""
Configuração do worker (variáveis de ambiente ou .env).
"""
import os

# URL base do Laravel (ex: http://localhost:8000)
API_BASE = os.getenv("BOT_API_BASE", "http://localhost:8000")
# Token para rotas /api/worker/*
WORKER_TOKEN = os.getenv("WORKER_API_TOKEN", "")

# Portal SAP (ajustar para o ambiente real)
SAP_BASE_URL = os.getenv("SAP_BASE_URL", "https://portal-sap.example.com")
SAP_USER = os.getenv("SAP_USER", "")
SAP_PASSWORD = os.getenv("SAP_PASSWORD", "")

# Intervalo padrão entre ciclos (segundos)
DEFAULT_INTERVAL = int(os.getenv("BOT_INTERVAL", "60"))
