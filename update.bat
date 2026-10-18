@echo off
setlocal EnableExtensions
title Himma - Update
cd /d "%~dp0"

rem ==================================================================
rem  Himma - get the latest version from GitHub
rem
rem  Double-click this file: it downloads the newest changes (master)
rem  and then starts the system with start-local.bat.
rem  Your data stays: database\database.sqlite, .env and .env.local
rem  are not touched.
rem ==================================================================

echo.
echo  ===============================================
echo             Himma - Update
echo  ===============================================
echo.

rem ---- Find git: PATH, then Laragon, GitHub Desktop, Git for Windows ----
set "GIT="
for /f "delims=" %%G in ('where git.exe 2^>nul') do if not defined GIT set "GIT=%%G"
if not defined GIT if exist "C:\laragon\bin\git\bin\git.exe" set "GIT=C:\laragon\bin\git\bin\git.exe"
if not defined GIT for /d %%D in ("%LOCALAPPDATA%\GitHubDesktop\app-*") do if exist "%%~D\resources\app\git\cmd\git.exe" set "GIT=%%~D\resources\app\git\cmd\git.exe"
if not defined GIT if exist "%ProgramFiles%\Git\cmd\git.exe" set "GIT=%ProgramFiles%\Git\cmd\git.exe"
if not defined GIT goto :nogit
if not exist ".git" goto :nogitfolder

rem Everything below runs as one block: git may replace this very file
rem while updating, and cmd has already read the whole block by then.
(
  echo  [1/3] Dropping local cache and log changes...
  "%GIT%" checkout -- storage 2>nul

  echo  [2/3] Switching to master...
  "%GIT%" checkout master
  if errorlevel 1 (
    echo.
    echo  Could not switch to master - take a screenshot of this window.
    echo.
    pause
    exit /b 1
  )

  echo  [3/3] Downloading the latest version...
  "%GIT%" pull --ff-only origin master
  if errorlevel 1 (
    echo.
    echo  The update did not finish - take a screenshot of this window.
    echo.
    pause
    exit /b 1
  )

  echo.
  echo  Up to date. Starting the system...
  echo.
  call "%~dp0start-local.bat"
  exit /b 0
)

:nogit
echo  Git was not found on this computer.
echo  Install GitHub Desktop or Git for Windows, then run this file again.
goto :end

:nogitfolder
echo  This folder is not a git copy of the project - it was probably downloaded as a ZIP.
echo  Download it again with GitHub Desktop or "git clone", or download the new ZIP from GitHub.
goto :end

:end
echo.
pause
