@echo off
setlocal EnableExtensions
title Himma - Local Server
cd /d "%~dp0"

rem ==================================================================
rem  Himma - one-click local run (no Laragon / MySQL needed)
rem
rem  Double-click this file: it prepares everything and opens the
rem  system in your browser. Keep this window open while you use it;
rem  close it to stop the server.
rem
rem  - PHP 8.3+: from PATH, Laragon or C:\php. If there is none, PHP
rem    and Composer are downloaded once to %LOCALAPPDATA%\Himma
rem  - Database: database\database.sqlite (created automatically)
rem  - Settings: .env.local (your normal .env is NOT touched)
rem ==================================================================

set "APP_ENV=local"
set "PORT=8000"
set "TOOLS=%LOCALAPPDATA%\Himma"
set "PHP_ZIP=php-8.3-nts-Win32-vs16-x64-latest.zip"

echo.
echo  ===============================================
echo             Himma - Local Server
echo  ===============================================
echo.

rem ---- 1) Find PHP 8.3+: PATH, Laragon, C:\php, then our own copy ---
set "PHP="
for /f "delims=" %%P in ('where php.exe 2^>nul') do call :tryphp "%%P"
for /d %%D in (C:\laragon\bin\php\php-8*) do call :tryphp "%%~D\php.exe"
call :tryphp "C:\php\php.exe"
call :tryphp "%TOOLS%\php\php.exe"
if not defined PHP call :getphp
if not defined PHP goto :nophp
for %%P in ("%PHP%") do set "PHPDIR=%%~dpP"
echo  [1/5] PHP found: %PHP%

rem Enable any required PHP extension that is not already loaded
set "MISSING="
for %%X in (pdo_sqlite sqlite3 mbstring openssl fileinfo zip) do (
  "%PHP%" -m 2>nul | findstr /i /b /c:"%%X" >nul || call set "MISSING=%%MISSING%% -d extension=%%X"
)
set "PHPX="
if defined MISSING set PHPX=-d extension_dir="%PHPDIR%ext"%MISSING%

rem ---- 2) PHP dependencies (vendor folder) ----------------------------
rem Installed again whenever composer.lock changed since the last install
"%PHP%" -r "exit(is_file('vendor/autoload.php') && is_file('vendor/.installed-lock') && file_get_contents('composer.lock') === file_get_contents('vendor/.installed-lock') ? 0 : 1);"
if not errorlevel 1 goto :deps_ok
echo  [2/5] Installing dependencies (first run or after an update, may take a few minutes)...
call :getcomposer
if not defined COMPOSER_PHAR goto :nocomposer
"%PHP%" %PHPX% "%COMPOSER_PHAR%" install --no-interaction --prefer-dist
if errorlevel 1 goto :fail
if not exist "vendor\autoload.php" goto :fail
copy /y "composer.lock" "vendor\.installed-lock" >nul
:deps_ok
echo  [2/5] Dependencies OK

rem ---- 3) Local settings, folders and database -----------------------
if not exist ".env.local" (
  > ".env.local" (
    echo APP_NAME="Himma"
    echo APP_ENV=local
    echo APP_KEY=
    echo APP_DEBUG=true
    echo APP_URL=http://127.0.0.1:%PORT%
    echo.
    echo DB_CONNECTION=sqlite
    echo.
    echo SESSION_DRIVER=file
    echo CACHE_DRIVER=file
    echo QUEUE_CONNECTION=sync
    echo MAIL_MAILER=log
  )
)
for %%D in (storage\app\public storage\framework\cache\data storage\framework\sessions storage\framework\views storage\logs bootstrap\cache) do (
  if not exist "%%D" mkdir "%%D"
)
if not exist "database\database.sqlite" type nul > "database\database.sqlite"

"%PHP%" %PHPX% artisan config:clear >nul 2>nul
"%PHP%" %PHPX% artisan view:clear >nul 2>nul
rem Make uploaded photos (storage\app\public) reachable at /storage
if exist "public\storage\.gitignore" (
  del "public\storage\.gitignore"
  rmdir "public\storage" 2>nul
)
if not exist "public\storage" "%PHP%" %PHPX% artisan storage:link >nul 2>nul
findstr /b /c:"APP_KEY=base64:" ".env.local" >nul || "%PHP%" %PHPX% artisan key:generate --force || goto :fail
echo  [3/5] Settings OK

rem ---- 4) Database tables + demo data --------------------------------
"%PHP%" %PHPX% artisan migrate --force || goto :fail
"%PHP%" %PHPX% artisan db:seed --force || goto :fail
echo  [4/5] Database OK

rem ---- 5) Start the server and open the browser ----------------------
:findport
netstat -an | findstr /r /c:"127.0.0.1:%PORT% .*LISTENING" /c:"0.0.0.0:%PORT% .*LISTENING" >nul
if not errorlevel 1 (
  set /a PORT+=1
  goto :findport
)

set "URL=http://127.0.0.1:%PORT%"
echo  [5/5] Starting server at %URL%
echo.
echo  -----------------------------------------------
echo   Open:     %URL%
echo   Admin:    admin@demo.com    / Admin@12345
echo   Trainer:  trainer@demo.com  / Trainer@12345
echo   Trainee:  trainee@demo.com  / Trainee@12345
echo   Trainee:  rami@demo.com     / Trainee@12345  (5 weeks of check-ins)
echo.
echo   Keep this window open. Close it to stop.
echo  -----------------------------------------------
echo.

start "" /b powershell -NoProfile -WindowStyle Hidden -Command "Start-Sleep -Seconds 2; Start-Process '%URL%'"

pushd public
"%PHP%" %PHPX% -S 127.0.0.1:%PORT% "%~dp0vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"
popd
goto :end

:nophp
echo.
echo  PHP 8.3 or newer was not found, and downloading it did not work.
echo  Check the internet connection and run this file again. Or download PHP 8.3
echo  ("VS16 x64 Non Thread Safe" zip) from https://windows.php.net/download
echo  and unzip it to C:\php - this file finds it there.
echo  If PHP is there but does not start, install the Visual C++ runtime:
echo  https://aka.ms/vs/17/release/vc_redist.x64.exe
goto :end

:nocomposer
echo.
echo  Composer was not found and could not be downloaded, and the "vendor" folder needs it.
echo  Check the internet connection and run this file again,
echo  or install Composer from https://getcomposer.org
goto :end

:fail
echo.
echo  Something went wrong - see the message above.

:end
echo.
pause
exit /b


rem ==================================================================
rem  Helpers (called with "call :name")
rem ==================================================================

rem Use %1 as PHP when it exists, runs, and is 8.3 or newer
:tryphp
if defined PHP exit /b 0
if not exist "%~1" exit /b 0
"%~1" -r "exit(version_compare(PHP_VERSION, '8.3.0', '<') ? 1 : 0);" >nul 2>nul
if not errorlevel 1 set "PHP=%~1"
exit /b 0

rem Download PHP 8.3 (official Windows build) to %TOOLS%\php
:getphp
echo  [1/5] PHP 8.3 was not found on this computer - downloading it (about 32 MB, first time only)...
if not exist "%TOOLS%" mkdir "%TOOLS%"
if exist "%TOOLS%\php" rmdir /s /q "%TOOLS%\php"
mkdir "%TOOLS%\php"
call :download "%TOOLS%\php.zip" "https://windows.php.net/downloads/releases/latest/%PHP_ZIP%" "https://www.php.net/~windows/releases/latest/%PHP_ZIP%"
if not exist "%TOOLS%\php.zip" exit /b 1
call :unzip "%TOOLS%\php.zip" "%TOOLS%\php"
del "%TOOLS%\php.zip" >nul 2>nul
if not exist "%TOOLS%\php\php.exe" exit /b 1
rem PHP writes its own php.ini, so a folder name in any language works
"%TOOLS%\php\php.exe" -n -r "$d = dirname(PHP_BINARY); $q = chr(34); $ini = ['extension_dir = ' . $q . $d . '\ext' . $q]; foreach (['curl', 'fileinfo', 'mbstring', 'openssl', 'pdo_sqlite', 'sqlite3', 'zip'] as $x) { $ini[] = 'extension = ' . $x; } $ini[] = 'upload_max_filesize = 8M'; $ini[] = 'post_max_size = 40M'; file_put_contents($d . '\php.ini', implode(PHP_EOL, $ini) . PHP_EOL);"
call :tryphp "%TOOLS%\php\php.exe"
exit /b 0

rem Find composer.phar (ours, Composer-Setup's, Laragon's) or download it
:getcomposer
set "COMPOSER_PHAR="
if exist "%TOOLS%\composer.phar" set "COMPOSER_PHAR=%TOOLS%\composer.phar"
if not defined COMPOSER_PHAR for /f "delims=" %%C in ('where composer 2^>nul') do if not defined COMPOSER_PHAR if exist "%%~dpCcomposer.phar" set "COMPOSER_PHAR=%%~dpCcomposer.phar"
if not defined COMPOSER_PHAR if exist "C:\laragon\bin\composer\composer.phar" set "COMPOSER_PHAR=C:\laragon\bin\composer\composer.phar"
if defined COMPOSER_PHAR exit /b 0
echo  Downloading Composer (first time only)...
if not exist "%TOOLS%" mkdir "%TOOLS%"
call :download "%TOOLS%\composer.phar" "https://getcomposer.org/download/latest-stable/composer.phar" "https://getcomposer.org/composer-stable.phar"
if exist "%TOOLS%\composer.phar" set "COMPOSER_PHAR=%TOOLS%\composer.phar"
exit /b 0

rem Save the first address that works (%2, then %3) to the file %1 (no file when both fail)
:download
if exist "%~1" del "%~1"
for %%U in (%2 %3) do (
  if not exist "%~1" if exist "%SystemRoot%\System32\curl.exe" (
    "%SystemRoot%\System32\curl.exe" -fL -# --retry 2 -o "%~1" %%U
    if errorlevel 1 if exist "%~1" del "%~1"
  )
  if not exist "%~1" (
    powershell -NoProfile -ExecutionPolicy Bypass -Command "$ProgressPreference = 'SilentlyContinue'; [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12; Invoke-WebRequest -UseBasicParsing -Uri '%%~U' -OutFile '%~1'" 2>nul
    if errorlevel 1 if exist "%~1" del "%~1"
  )
)
exit /b 0

rem Unzip %1 into the folder %2 (Windows tar, or PowerShell on older Windows)
:unzip
"%SystemRoot%\System32\tar.exe" -xf "%~1" -C "%~2" 2>nul
if not errorlevel 1 exit /b 0
powershell -NoProfile -ExecutionPolicy Bypass -Command "Expand-Archive -LiteralPath '%~1' -DestinationPath '%~2' -Force"
exit /b 0
