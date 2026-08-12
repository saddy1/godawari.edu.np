@echo off
setlocal
cd /d "%~dp0"

echo [1/4] Creating the Python environment...
if not exist ".venv\Scripts\python.exe" (
    py -3 -m venv .venv
    if errorlevel 1 goto :failed
)

echo [2/4] Installing build requirements...
call ".venv\Scripts\activate.bat"
python -m pip install --upgrade pip
if errorlevel 1 goto :failed
python -m pip install -r requirements.txt
if errorlevel 1 goto :failed

echo [3/4] Building HajiriSync.exe...
python -m PyInstaller --clean --noconfirm hajiri_sync.spec
if errorlevel 1 goto :failed

echo [4/4] Copying configuration...
if exist "config.yaml" (
    copy /Y "config.yaml" "dist\config.yaml" >nul
) else (
    copy /Y "config.example.yaml" "dist\config.yaml" >nul
)

echo.
echo Build completed successfully.
echo EXE location: %CD%\dist\HajiriSync.exe
echo Keep config.yaml in the same folder as HajiriSync.exe.
pause
exit /b 0

:failed
echo.
echo Build failed. Review the error shown above.
pause
exit /b 1
