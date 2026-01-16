@echo off
echo ==========================================
echo   SUBIENDO CAMBIOS A GITHUB (PUSH)
echo ==========================================
echo.

set /p msg="Introduce el mensaje del commit (sin comillas): "
if "%msg%"=="" set msg="Actualizacion automatica"

echo.
echo 1. Agregando archivos (git add .)...
git add .

echo.
echo 2. Creando commit...
git commit -m "%msg%"

echo.
echo 3. Subiendo a GitHub (git push origin main)...
git push origin main

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR CRITICO] Error al ejecutar comandos GIT.
    echo.
    echo Si ves "git no se reconoce", es porque NO esta instalado.
    echo.
    echo SOLUCION:
    echo 1. Descarga: https://git-scm.com/download/win
    echo 2. Instala Git for Windows.
    echo 3. Intenta de nuevo.
) else (
    echo.
    echo [EXITO] Cambios subidos correctamente.
)
echo.
pause
