@echo off
chcp 65001 >nul
echo ========================================
echo Workerman 计时聊天服务器
echo ========================================
echo.

cd /d "%~dp0"

if not exist "vendor" (
    echo [错误] vendor 目录不存在，请先运行: composer install
    pause
    exit /b 1
)

echo [提示] 启动服务器...
echo [提示] 按 Ctrl+C 停止服务器
echo.

php start.php start

if errorlevel 1 (
    echo.
    echo [错误] 服务器启动失败
    pause
    exit /b 1
)

pause
