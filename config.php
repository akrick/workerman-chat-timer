<?php
/**
 * 聊天服务器配置文件
 */

return [
    // WebSocket 配置
    'websocket' => [
        'host' => '0.0.0.0',
        'port' => 2346,
        'count' => 4,  // 进程数量
    ],

    // 用户配置
    'user' => [
        'default_balance' => 100.00,  // 默认余额
        'min_balance' => 10.00,       // 余额警告阈值
    ],

    // 会话配置
    'session' => [
        'default_rate' => 0.50,        // 默认费率（元/分钟）
        'charge_interval' => 1,       // 计费间隔（秒）
        'max_chat_history' => 100,   // 最大聊天记录数
        'cleanup_interval' => 60,     // 清理间隔（秒）
        'session_timeout' => 3600,    // 会话超时时间（秒）
    ],

    // 聊天记录配置
    'chat' => [
        'history_retention' => 86400, // 聊天记录保留时间（秒）
        'max_message_length' => 5000, // 最大消息长度
    ],

    // 日志配置
    'log' => [
        'enabled' => true,
        'level' => 'info',
    ],
];
