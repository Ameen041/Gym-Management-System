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
rem  - Database: database\database.sqlite (created automatically)
rem  - Settings: .env.local (your normal .env is NOT touched)
rem ==================================================================

set "APP_ENV=local"
set "PORT=8000"

echo.
echo  ===============================================
echo             Himma - Local Server
echo  ===============================================
echo.

rem ---- 1) Find PHP: PATH first, then Laragon --------------------------
set "PHP="
for /f "delims=" %%P in ('where php.exe 2^>nul') do if not defined PHP set "PHP=%%P"
if not defined PHP for /d %%D in ("C:\laragon\bin\php\php-8*") do set "PHP=%%~D\php.exe"
if not defined PHP goto :nophp
if not exist "%PHP%" goto :nophp
for %%P in ("%PHP%") do set "PHPDIR=%%~dpP"
echo  [1/5] PHP found: %PHP%
"%PHP%" -r "exit(version_compare(PHP_VERSION, '8.2.0', '<') ? 1 : 0);" || goto :oldphp

rem Enable any required PHP extension that is not already loaded
set "MISSING="
for %%X in (pdo_sqlite sqlite3 mbstring openssl fileinfo) do (
  "%PHP%" -m 2>nul | findstr /i /b /c:"%%X" >nul || call set "MISSING=%%MISSING%% -d extension=%%X"
)
set "PHPX="
if defined MISSING set PHPX=-d extension_dir="%PHPDIR%ext"%MISSING%

rem ---- 2) PHP dependencies (vendor folder) ----------------------------
if exist "vendor\autoload.php" goto :deps_ok
echo  [2/5] Installing dependencies (first run only, may take a few minutes)...
where composer >nul 2>nul
if not errorlevel 1 (
  call composer install --no-interaction --prefer-dist
  goto :deps_check
)
if exist "C:\laragon\bin\composer\composer.phar" (
  "%PHP%" %PHPX% "C:\laragon\bin\composer\composer.phar" install --no-interaction --prefer-dist
  goto :deps_check
)
goto :nocomposer
:deps_check
if not exist "vendor\autoload.php" goto :fail
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

:oldphp
for /f "delims=" %%V in ('call "%PHP%" -r "echo PHP_VERSION;"') do set "PHPVER=%%V"
echo  This project needs PHP 8.2 or newer, but found PHP %PHPVER%.
echo  In Laragon: right-click the tray icon, PHP, Version, and pick 8.2 or newer
echo  (or download PHP 8.2+ from https://windows.php.net/download and add it to PATH).
goto :end

:nophp
echo  PHP was not found.
echo  Install PHP 8 (or Laragon), or add php.exe to your PATH, then run this file again.
goto :end

:nocomposer
echo  Composer was not found, and the "vendor" folder is missing.
echo  Install Composer from https://getcomposer.org then run this file again.
goto :end

:fail
echo.
echo  Something went wrong - see the message above.

:end
echo.
pause
