@echo off
chcp 65001 >nul
cd /d "%~dp0"

if not exist "vendor" (
    echo [错误] vendor 目录不存在，请先运行: composer install
    pause
    exit /b 1
)

echo [提示] 以后台方式启动服务器...
php start.php start -d

if errorlevel 1 (
    echo [错误] 服务器启动失败
    pause
    exit /b 1
)

echo [成功] 服务器已在后台运行
echo [提示] 使用 php start.php stop 停止
echo [提示] 使用 php start.php status 查看状态
pause
