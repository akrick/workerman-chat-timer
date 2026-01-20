@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo [提示] 停止服务器...
php start.php stop

if errorlevel 1 (
    echo [错误] 服务器停止失败
    pause
    exit /b 1
)

echo [成功] 服务器已停止
pause
