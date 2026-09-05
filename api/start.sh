#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"
export PYTHONUNBUFFERED=1
echo "[start] API directory: $(pwd)"

# Use the Windows virtualenv layout when running from Git Bash, and the
# POSIX layout on Linux/macOS.  A venv copied from another machine can have
# the wrong interpreter, so recreate it when its Python cannot be started.
if command -v python3 >/dev/null 2>&1 && python3 --version >/dev/null 2>&1; then
    SYSTEM_PYTHON=python3
else
    SYSTEM_PYTHON=python
fi

VENV_PYTHON=""
if [[ -f venv/Scripts/python.exe ]]; then
    VENV_PYTHON=venv/Scripts/python.exe
elif [[ -x venv/bin/python ]] && venv/bin/python --version >/dev/null 2>&1; then
    VENV_PYTHON=venv/bin/python
fi

if [[ -z "$VENV_PYTHON" ]]; then
    echo "[start] Creating a fresh virtual environment..."
    "$SYSTEM_PYTHON" -m venv --clear venv
    if [[ -f venv/Scripts/python.exe ]]; then
        VENV_PYTHON=venv/Scripts/python.exe
    else
        VENV_PYTHON=venv/bin/python
    fi
fi

echo "[start] Python: $VENV_PYTHON"
echo "[start] Checking Python dependencies..."
"$VENV_PYTHON" -m pip install --disable-pip-version-check -q -r requirements.txt

echo "[start] Checking the Keras catalog index..."
"$VENV_PYTHON" index_catalog.py

echo "[start] Starting API at http://127.0.0.1:8000"
exec "$VENV_PYTHON" -m uvicorn main:app --host 127.0.0.1 --port 8000 --log-level info
