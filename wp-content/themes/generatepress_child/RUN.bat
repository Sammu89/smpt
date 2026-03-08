@echo off
setlocal EnableExtensions EnableDelayedExpansion

set "SCRIPT_DIR=%~dp0"
for %%I in ("%SCRIPT_DIR%") do set "SCRIPT_DIR=%%~fI"
pushd "%SCRIPT_DIR%" >nul 2>&1
if errorlevel 1 (
  echo ERROR: Cannot access script directory.
  exit /b 1
)

for /f "delims=" %%I in ('git -C "%SCRIPT_DIR%" rev-parse --show-toplevel 2^>nul') do set "REPO_DIR=%%I"
if not defined REPO_DIR (
  echo ERROR: Could not locate the git repository root from: %SCRIPT_DIR%
  popd >nul
  exit /b 1
)

set "REMOTE=origin"
set "BRANCH=main"

:MENU
cls
echo ========================================
echo  SMPT Git Sync
echo ========================================
echo  Repo   : %REPO_DIR%
echo  Remote : %REMOTE%/%BRANCH%
echo.
echo  [1] Pull latest from GitHub
echo  [2] Commit and push local changes
echo  [0] Exit
echo.
set "CHOICE="
set /p CHOICE="Select an option: "

if "%CHOICE%"=="1" goto PULL
if "%CHOICE%"=="2" goto PUSH
if "%CHOICE%"=="0" goto END

echo.
echo Invalid option.
pause
goto MENU

:PULL
echo.
echo This will replace local files with %REMOTE%/%BRANCH%.
echo Any local changes will be stashed first.
echo.
call :GIT_SAFE status --short
if errorlevel 1 goto GIT_ERROR
echo.
set "PULL_CONFIRM="
set /p PULL_CONFIRM="Continue with pull-and-replace? (y/N): "
if /i not "%PULL_CONFIRM%"=="Y" (
  echo Pull cancelled.
  pause
  goto MENU
)

call :HAS_LOCAL_CHANGES
if "%HAS_CHANGES%"=="1" (
  for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format \"yyyy-MM-dd HH:mm:ss\""' ) do set "STASH_MESSAGE=Auto stash before pull %%I"
  echo Stashing local changes: %STASH_MESSAGE%
  call :GIT_SAFE stash push --include-untracked -m "%STASH_MESSAGE%"
  if errorlevel 1 goto GIT_ERROR
)

echo Fetching latest changes...
call :GIT_SAFE fetch %REMOTE% %BRANCH%
if errorlevel 1 goto GIT_ERROR

echo Replacing local branch with %REMOTE%/%BRANCH% ...
call :GIT_SAFE reset --hard %REMOTE%/%BRANCH%
if errorlevel 1 goto GIT_ERROR
call :GIT_SAFE clean -fd
if errorlevel 1 goto GIT_ERROR

echo.
echo Local branch now matches %REMOTE%/%BRANCH%.
pause
goto MENU

:PUSH
echo.
echo Current status:
call :GIT_SAFE status --short
if errorlevel 1 goto GIT_ERROR

call :GIT_SAFE add .
if errorlevel 1 goto GIT_ERROR

call :GIT_SAFE diff --cached --quiet
if not errorlevel 1 (
  echo Nothing staged. Nothing to push.
  pause
  goto MENU
)

for /f %%I in ('powershell -NoProfile -Command "Get-Date -Format \"yyyy-MM-dd HH:mm:ss\""' ) do set "COMMIT_MESSAGE=Auto sync %%I"
echo Using commit message: %COMMIT_MESSAGE%

call :GIT_SAFE commit -m "%COMMIT_MESSAGE%"
if errorlevel 1 goto GIT_ERROR

echo.
echo Pushing to %REMOTE%/%BRANCH% ...
call :GIT_SAFE push -u %REMOTE% %BRANCH%
if errorlevel 1 goto GIT_ERROR

echo Push completed.
pause
goto MENU

:HAS_LOCAL_CHANGES
set "HAS_CHANGES=0"
call :GIT_SAFE diff --quiet
if errorlevel 1 set "HAS_CHANGES=1"
call :GIT_SAFE diff --cached --quiet
if errorlevel 1 set "HAS_CHANGES=1"
for /f "delims=" %%I in ('git -C "%REPO_DIR%" ls-files --others --exclude-standard') do set "HAS_CHANGES=1"
exit /b 0

:GIT_SAFE
set "LD_LIBRARY_PATH="
git -C "%REPO_DIR%" %*
exit /b %ERRORLEVEL%

:GIT_ERROR
echo.
echo A git command failed.
pause
goto MENU

:END
popd >nul
echo.
echo Goodbye.
exit /b 0
