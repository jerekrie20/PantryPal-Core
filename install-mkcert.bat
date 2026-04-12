@echo off
setlocal enabledelayedexpansion

echo ============================================================
echo   PantryPal Core - local SSL Setup (mkcert)
echo ============================================================
echo.

REM 1. Check for mkcert
where mkcert >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [!] mkcert not found in PATH.
    echo.
    echo Attempting to install via Chocolatey...
    where choco >nul 2>nul
    if !ERRORLEVEL! neq 0 (
        echo [!] Chocolatey not found either. Please install mkcert manually:
        echo     https://github.com/FiloSottile/mkcert#installation
        pause
        exit /b 1
    )
    choco install mkcert -y
    if !ERRORLEVEL! neq 0 (
        echo [!] Failed to install mkcert via Chocolatey.
        pause
        exit /b 1
    )
    REM Refresh path for current session
    set "PATH=%PATH%;%ALLUSERSPROFILE%\chocolatey\bin"
)

REM 2. Install CA
echo [*] Installing local CA into system trust store...
mkcert -install
if %ERRORLEVEL% neq 0 (
    echo [!] Failed to install CA. Try running this script as Administrator.
    pause
    exit /b 1
)

REM 3. Create SSL directory
if not exist ssl (
    echo [*] Creating ssl directory...
    mkdir ssl
)
cd ssl

REM 4. Generate certificate
echo [*] Generating certificates for localhost, 127.0.0.1, and pantrypal.local...
echo     (Note: this matches the 'localhost+3.pem' expectation in vite.config.js)
mkcert localhost 127.0.0.1 ::1 pantrypal.local
if %ERRORLEVEL% neq 0 (
    echo [!] Failed to generate certificates.
    pause
    exit /b 1
)

REM 5. Copy Root CA for Vite's server.https.ca
echo [*] Extracting Root CA certificate...
for /f "usebackq tokens=*" %%i in (`mkcert -CAROOT`) do set CAROOT=%%i
if exist "%CAROOT%\rootCA.pem" (
    copy "%CAROOT%\rootCA.pem" rootCA.crt >nul
    echo [*] Copied rootCA.pem to ssl/rootCA.crt
) else if exist "%CAROOT%\rootCA.crt" (
    copy "%CAROOT%\rootCA.crt" rootCA.crt >nul
    echo [*] Copied rootCA.crt to ssl/rootCA.crt
) else (
    echo [!] Warning: Could not find Root CA in %CAROOT%.
    echo     You may need to manually copy your Root CA to ssl/rootCA.crt
)

echo.
echo ============================================================
echo   SUCCESS! local SSL is configured.
echo ============================================================
echo.
echo 1. Add this to your C:\Windows\System32\drivers\etc\hosts:
echo    127.0.0.1 pantrypal.local
echo.
echo 2. Restart your development server:
echo    npm run dev
echo.
pause
