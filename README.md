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

### 计费规则

- **费率单位**：元/分钟
- **默认费率**：0.50 元/分钟
- **初始余额**：100.00 元
- **计费方式**：双方同时扣费
- **计费精度**：每秒实时计费

### 计费逻辑详解

#### 1. 会话创建阶段

```
用户A创建会话 → 自动加入会话 → 等待用户B加入
用户B接受邀请 → 双方都已加入 → 会话激活 → 开始计费
```

**关键点**：
- 会话发起人创建会话时自动加入（`joined_users: [userA]`）
- 被邀请人接受邀请后加入（`joined_users: [userA, userB]`）
- 当 `joined_users` 数量达到 2 时，会话状态 `active` 设为 `true`

#### 2. 计费计算公式

```php
// 单次计费金额
$charge = ($elapsed_seconds / 60) * $rate;

// 示例：
// 费率 0.50 元/分钟，实际使用 30 秒
$charge = (30 / 60) * 0.50 = 0.25 元
```

#### 3. 实时扣费流程

```php
// 每秒执行一次检查
if ($elapsed >= 1) {
    // 计算当前时间段费用
    $charge = ($elapsed / 60) * $session['rate'];
    
    // 检查双方余额
    if ($user1Balance < $charge || $user2Balance < $charge) {
        // 余额不足，结束会话
    }
    
    // 双方同时扣费
    self::$userBalances[$session['user1_id']] -= $charge;
    self::$userBalances[$session['user2_id']] -= $charge;
    
    // 实时通知用户余额变动
}
```

#### 4. 余额检查机制

- **预检查**：在加入会话时检查用户余额是否至少满足 1 分钟的费率
- **实时检查**：每秒计费前检查双方余额是否足够支付当前时间段费用
- **自动结束**：任一方余额不足时，自动结束会话并结算

#### 5. 会话结束结算

```php
// 计算总时长
$duration = time() - $session['start_time'];

// 计算总费用（按实际时长重新计算）
$totalCharge = ($duration / 60) * $session['rate'];

// 从双方账户扣除总费用
self::$userBalances[$session['user1_id']] -= $totalCharge;
self::$userBalances[$session['user2_id']] -= $totalCharge;
```

#### 6. 费用示例

| 时长 | 费率 (0.5元/分钟) | 费用 (单方) | 费用 (双方) |
|------|------------------|-------------|-------------|
| 30秒  | 0.50 元/分钟     | 0.25 元     | 0.50 元     |
| 1分钟  | 0.50 元/分钟     | 0.50 元     | 1.00 元     |
| 5分钟  | 0.50 元/分钟     | 2.50 元     | 5.00 元     |
| 10分钟 | 0.50 元/分钟     | 5.00 元     | 10.00 元    |

### 前端计费显示

客户端实时显示：
- 当前余额
- 会话时长（分:秒）
- 累计费用（单方）
- 余额不足警告（余额 < 10 元时卡片变红）

## 技术栈

- PHP 7.0+
- Workerman 4.0+
- WebSocket
- HTML5 + CSS3 + JavaScript

## 许可证

MIT License
