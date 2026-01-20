# Workerman 计时收费聊天系统

基于 Workerman 的一对一实时聊天系统，支持按时间计费。

## 功能特性

- 实时 WebSocket 通讯
- 一对一私聊
- 按时间计费（可自定义费率）
- 余额管理（查询、充值）
- 自动扣费（每秒实时扣费）
- 余额不足自动结束会话
- 在线用户列表
- 用户昵称支持
- 聊天记录保存
- 正在输入提示

## 快速开始

### 安装依赖

```bash
composer install
```

### 启动服务器

**Windows：**
```bash
start.bat
```

**Linux/Mac：**
```bash
php start.php start
```

### 使用客户端

打开 `client.html` 文件开始聊天

## 文件说明

```
workman-chat-timer/
├── composer.json          # Composer 配置
├── start.php             # 服务器启动文件
├── config.php            # 配置文件
├── client.html           # 聊天客户端
├── test.html            # WebSocket 测试工具
├── src/
│   └── ChatServer.php   # 聊天服务器逻辑
└── vendor/             # Composer 依赖
```

## 配置

编辑 `config.php` 修改服务器参数：

```php
return [
    'websocket' => [
        'host' => '0.0.0.0',
        'port' => 2346,
    ],
    'user' => [
        'default_balance' => 100.00,
    ],
    'session' => [
        'default_rate' => 0.50,
    ],
];
```

## 计费说明

- 费率单位：元/分钟
- 默认费率：0.50 元/分钟
- 初始余额：100.00 元
- 双方同时扣费
- 余额不足自动结束会话

## 常见问题

### 问题：POSIX 函数未定义

此项目已移除对 POSIX 扩展的依赖，可在 Windows/Linux/Mac 上正常运行。

### 问题：端口被占用

修改 `start.php` 中的端口号：
```php
$worker = new Worker('websocket://0.0.0.0:2347');
```

### 问题：依赖安装失败

使用国内镜像：
```bash
composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
composer install
```

## 技术栈

- PHP 7.0+
- Workerman 4.0+
- WebSocket
- HTML5 + CSS3 + JavaScript

## 许可证

MIT License
