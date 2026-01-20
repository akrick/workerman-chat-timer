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
├── client.html           # 聊天客户端
├── src/
│   └── ChatServer.php   # 聊天服务器逻辑
├── start.bat            # Windows 启动脚本
├── stop.bat             # Windows 停止脚本
├── restart.bat          # Windows 重启脚本
├── status.bat           # Windows 状态查看脚本
├── start_daemon.bat     # Windows 守护进程启动
└── vendor/              # Composer 依赖
```

## 计费说明

- 费率单位：元/分钟
- 默认费率：0.50 元/分钟
- 初始余额：100.00 元
- 双方同时扣费
- 余额不足自动结束会话

## 技术栈

- PHP 7.0+
- Workerman 4.0+
- WebSocket
- HTML5 + CSS3 + JavaScript

## 许可证

MIT License
