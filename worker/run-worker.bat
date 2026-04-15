@echo off
setlocal
cd /d "%~dp0"

if not exist "venv\Scripts\activate.bat" (
  echo Crie o ambiente virtual primeiro:
  echo   python -m venv venv
  echo   venv\Scripts\activate.bat
  echo   pip install -r requirements.txt
  pause
  exit /b 1
)

call venv\Scripts\activate.bat
python worker.py
set EXITCODE=%ERRORLEVEL%
if not "%EXITCODE%"=="0" (
  echo.
  echo Worker terminou com erro %EXITCODE%.
  pause
)
exit /b %EXITCODE%
