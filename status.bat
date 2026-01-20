@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo ========================================
echo Workerman 服务器状态
echo ========================================
echo.

php start.php status

pause
