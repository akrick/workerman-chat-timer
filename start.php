<?php
use Workerman\Worker;
use Chat\ChatServer;

// 检查 PHP 版本
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    die("PHP 7.0 or higher is required. Current version: " . PHP_VERSION . "\n");
}

// 加载 Composer 自动加载
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    die("Please run 'composer install' first\n");
}

// 创建 WebSocket Worker
$worker = new Worker('websocket://0.0.0.0:2346');

// 设置 Worker 名称
$worker->name = 'ChatServer';

// 设置进程数量
$worker->count = 4;

// 重启间隔时间
$worker->reloadable = true;

// Worker 启动时的回调
$worker->onWorkerStart = function() {
    echo "Worker started\n";
    
    // 启动计费定时器，每秒执行一次
    \Workerman\Timer::add(1, function() {
        ChatServer::chargeForChat();
    });
    
    // 每60秒清理一次无效会话
    \Workerman\Timer::add(60, function() {
        ChatServer::cleanupInactiveSessions();
    });
};

// 接收客户端消息
$worker->onMessage = [ChatServer::class, 'onMessage'];

// 客户端连接时
$worker->onConnect = [ChatServer::class, 'onConnect'];

// 客户端断开时
$worker->onClose = [ChatServer::class, 'onClose'];

// 错误处理
$worker->onError = function($connection, $code, $msg) {
    echo "Error: code=$code msg=$msg\n";
};

// 运行 Worker
Worker::runAll();
