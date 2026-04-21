FROM mcr.microsoft.com/playwright/python:v1.49.0-jammy

WORKDIR /app/worker

COPY worker/requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

COPY worker/ ./

CMD ["python", "worker.py"]
