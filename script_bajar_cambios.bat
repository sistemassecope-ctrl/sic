@echo off
echo ==========================================
echo   BAJANDO CAMBIOS DE GITHUB (PULL)
echo ==========================================
echo.
echo Ejecutando: git pull origin main
echo.

git pull origin main

    echo.
    echo [ERROR CRITICO] "git" no se reconoce.
    echo.
    echo CAUSA: Git for Windows no esta instalado o no esta en el PATH.
    echo SOLUCION:
    echo 1. Descarga Git aqui: https://git-scm.com/download/win
    echo 2. Instalalo (Siguiente > Siguiente > Siguiente...)
    echo 3. Cierra y vuelve a abrir este script.
    echo.
    echo Mientras tanto, no puedes sincronizar directamente.
    pause
    exit
) else (
    echo.
    echo [EXITO] Cambios descargados correctamente.
)
echo.
pause
