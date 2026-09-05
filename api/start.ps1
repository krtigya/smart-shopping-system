$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot
$env:PYTHONUNBUFFERED = '1'

$python = Join-Path $PSScriptRoot 'venv\Scripts\python.exe'
if (-not (Test-Path $python)) {
    Write-Host '[start] Creating Windows virtual environment...'
    & python -m venv --clear (Join-Path $PSScriptRoot 'venv')
}

if (-not (Test-Path $python)) {
    throw 'Python was not found. Install Python 3.10+ and enable it in PATH.'
}

Write-Host "[start] Python: $python"
Write-Host '[start] Checking Python dependencies...'
& $python -m pip install --disable-pip-version-check -q -r (Join-Path $PSScriptRoot 'requirements.txt')

Write-Host '[start] Checking the Keras catalog index...'
& $python (Join-Path $PSScriptRoot 'index_catalog.py')

Write-Host '[start] Starting API at http://127.0.0.1:8000'
& $python -m uvicorn main:app --host 127.0.0.1 --port 8000 --log-level info
