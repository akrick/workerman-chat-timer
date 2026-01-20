@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo [提示] 重启服务器...
php start.php restart

if errorlevel 1 (
    echo [错误] 服务器重启失败
    pause
    exit /b 1
)

echo [成功] 服务器已重启
pause
