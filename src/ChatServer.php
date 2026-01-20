<?php

namespace Chat;

class ChatServer
{
    // 存储用户连接 => user_id
    private static $connections = [];
    
    // 存储会话信息: session_id => [user1_id => conn1, user2_id => conn2, start_time => timestamp, rate => rate_per_minute]
    private static $sessions = [];
    
    // 存储用户会话映射: user_id => session_id
    private static $userSessions = [];
    
    // 存储用户余额: user_id => balance
    private static $userBalances = [];
    
    // 存储会话对应的连接: session_id => [user1_conn, user2_conn]
    private static $sessionConnections = [];

    // 存储在线用户列表
    private static $onlineUsers = [];
    
    // 存储聊天记录: session_id => [[from_user_id, message, timestamp], ...]
    private static $chatHistory = [];
    
    // 存储用户昵称: user_id => nickname
    private static $userNicknames = [];
    
    // 存储用户头像: user_id => avatar_url
    private static $userAvatars = [];

    /**
     * 连接建立时的处理
     */
    public static function onConnect($connection)
    {
        echo "[" . date('Y-m-d H:i:s') . "] 新连接建立，当前连接数: " . count(self::$connections) . "\n";
    }

    /**
     * 接收消息处理
     */
    public static function onMessage($connection, $data)
    {
        $data = json_decode($data, true);
        
        if (!$data || !isset($data['type'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '无效的消息格式'
            ]));
            return;
        }

        switch ($data['type']) {
            case 'login':
                self::handleLogin($connection, $data);
                break;
            case 'register':
                self::handleRegister($connection, $data);
                break;
            case 'update_profile':
                self::handleUpdateProfile($connection, $data);
                break;
            case 'get_online_users':
                self::handleGetOnlineUsers($connection, $data);
                break;
            case 'create_session':
                self::handleCreateSession($connection, $data);
                break;
            case 'join_session':
                self::handleJoinSession($connection, $data);
                break;
            case 'chat':
                self::handleChat($connection, $data);
                break;
            case 'end_session':
                self::handleEndSession($connection, $data);
                break;
            case 'check_balance':
                self::handleCheckBalance($connection, $data);
                break;
            case 'recharge':
                self::handleRecharge($connection, $data);
                break;
            case 'get_chat_history':
                self::handleGetChatHistory($connection, $data);
                break;
            case 'typing':
                self::handleTyping($connection, $data);
                break;
            case 'session_status':
                self::handleSessionStatus($connection, $data);
                break;
            default:
                $connection->send(json_encode([
                    'type' => 'error',
                    'message' => '未知的消息类型'
                ]));
        }
    }

    /**
     * 连接关闭处理
     */
    public static function onClose($connection)
    {
        // 查找并移除用户连接
        if ($userId = array_search($connection, self::$connections)) {
            unset(self::$connections[$userId]);
            unset(self::$onlineUsers[$userId]);
            
            // 广播用户离线
            self::broadcastUserStatus($userId, false);
            
            // 如果用户在会话中，结束会话
            if (isset(self::$userSessions[$userId])) {
                self::endSessionByUser($userId);
            }
        }
        
        echo "[" . date('Y-m-d H:i:s') . "] 连接关闭，当前在线用户: " . count(self::$connections) . "\n";
    }

    /**
     * 处理用户注册
     */
    private static function handleRegister($connection, $data)
    {
        if (!isset($data['user_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少user_id参数'
            ]));
            return;
        }

        $userId = $data['user_id'];
        $nickname = isset($data['nickname']) ? $data['nickname'] : $userId;
        $avatar = isset($data['avatar']) ? $data['avatar'] : '';

        // 初始化用户信息
        self::$userBalances[$userId] = 100.00; // 默认100元
        self::$userNicknames[$userId] = $nickname;
        self::$userAvatars[$userId] = $avatar;

        $connection->send(json_encode([
            'type' => 'register_success',
            'user_id' => $userId,
            'nickname' => $nickname,
            'balance' => self::$userBalances[$userId]
        ]));

        echo "用户 {$userId} 注册成功\n";
    }

    /**
     * 处理用户登录
     */
    private static function handleLogin($connection, $data)
    {
        if (!isset($data['user_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少user_id参数'
            ]));
            return;
        }

        $userId = $data['user_id'];
        
        // 检查是否已登录
        if (isset(self::$connections[$userId])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '用户已在别处登录'
            ]));
            return;
        }

        self::$connections[$userId] = $connection;
        self::$onlineUsers[$userId] = time();

        // 初始化用户余额（如果不存在）
        if (!isset(self::$userBalances[$userId])) {
            self::$userBalances[$userId] = 100.00; // 默认100元
            self::$userNicknames[$userId] = $userId;
            self::$userAvatars[$userId] = '';
        }

        $connection->send(json_encode([
            'type' => 'login_success',
            'user_id' => $userId,
            'nickname' => self::$userNicknames[$userId],
            'avatar' => self::$userAvatars[$userId],
            'balance' => self::$userBalances[$userId],
            'online_count' => count(self::$connections)
        ]));

        // 广播用户上线
        self::broadcastUserStatus($userId, true);
        
        // 发送在线用户列表
        self::sendOnlineUsers($connection);

        echo "用户 {$userId} (昵称: " . self::$userNicknames[$userId] . ") 登录成功\n";
    }

    /**
     * 处理更新用户资料
     */
    private static function handleUpdateProfile($connection, $data)
    {
        if (!isset($data['user_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少user_id参数'
            ]));
            return;
        }

        $userId = $data['user_id'];

        if (isset($data['nickname'])) {
            self::$userNicknames[$userId] = $data['nickname'];
        }

        if (isset($data['avatar'])) {
            self::$userAvatars[$userId] = $data['avatar'];
        }

        $connection->send(json_encode([
            'type' => 'profile_updated',
            'user_id' => $userId,
            'nickname' => self::$userNicknames[$userId],
            'avatar' => self::$userAvatars[$userId]
        ]));

        echo "用户 {$userId} 更新资料\n";
    }

    /**
     * 处理获取在线用户列表
     */
    private static function handleGetOnlineUsers($connection, $data)
    {
        self::sendOnlineUsers($connection);
    }

    /**
     * 发送在线用户列表
     */
    private static function sendOnlineUsers($connection)
    {
        $users = [];
        foreach (self::$onlineUsers as $userId => $loginTime) {
            $users[] = [
                'user_id' => $userId,
                'nickname' => self::$userNicknames[$userId] ?? $userId,
                'avatar' => self::$userAvatars[$userId] ?? '',
                'login_time' => $loginTime
            ];
        }

        $connection->send(json_encode([
            'type' => 'online_users',
            'users' => $users,
            'count' => count($users)
        ]));
    }

    /**
     * 广播用户状态变更
     */
    private static function broadcastUserStatus($userId, $online)
    {
        $message = json_encode([
            'type' => 'user_status',
            'user_id' => $userId,
            'nickname' => self::$userNicknames[$userId] ?? $userId,
            'avatar' => self::$userAvatars[$userId] ?? '',
            'online' => $online,
            'online_count' => count(self::$connections)
        ]);

        foreach (self::$connections as $conn) {
            $conn->send($message);
        }
    }

    /**
     * 处理创建会话
     */
    private static function handleCreateSession($connection, $data)
    {
        if (!isset($data['user_id']) || !isset($data['target_user_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少必要参数'
            ]));
            return;
        }

        $userId = $data['user_id'];
        $targetUserId = $data['target_user_id'];

        // 不能与自己聊天
        if ($userId == $targetUserId) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '不能与自己创建会话'
            ]));
            return;
        }

        // 检查目标用户是否在线
        if (!isset(self::$connections[$targetUserId])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '目标用户不在线'
            ]));
            return;
        }

        // 检查是否已有活跃会话
        if (isset(self::$userSessions[$userId]) || isset(self::$userSessions[$targetUserId])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '用户已在会话中'
            ]));
            return;
        }

        // 创建会话
        $sessionId = uniqid('session_', true);
        $rate = isset($data['rate']) ? floatval($data['rate']) : 0.50; // 默认0.50元/分钟

        self::$sessions[$sessionId] = [
            'user1_id' => $userId,
            'user2_id' => $targetUserId,
            'start_time' => time(),
            'rate' => $rate,
            'active' => false,
            'last_charge_time' => time(),
            'joined_users' => [$userId]  // 发起人自动加入
        ];

        self::$sessionConnections[$sessionId] = [
            'user1' => self::$connections[$userId],
            'user2' => self::$connections[$targetUserId]
        ];

        self::$chatHistory[$sessionId] = [];

        // 获取用户信息
        $inviterName = self::$userNicknames[$userId] ?? $userId;
        $targetName = self::$userNicknames[$targetUserId] ?? $targetUserId;

        // 通知目标用户
        $targetConnection = self::$connections[$targetUserId];
        $targetConnection->send(json_encode([
            'type' => 'session_invite',
            'session_id' => $sessionId,
            'inviter_id' => $userId,
            'inviter_nickname' => $inviterName,
            'inviter_avatar' => self::$userAvatars[$userId] ?? '',
            'rate' => $rate
        ]));

        $connection->send(json_encode([
            'type' => 'session_created',
            'session_id' => $sessionId,
            'target_user_id' => $targetUserId,
            'target_nickname' => $targetName,
            'target_avatar' => self::$userAvatars[$targetUserId] ?? '',
            'rate' => $rate
        ]));

        echo "会话 {$sessionId} 已创建: {$inviterName} -> {$targetName}，费率: {$rate}元/分钟\n";
    }

    /**
     * 处理加入会话
     */
    private static function handleJoinSession($connection, $data)
    {
        if (!isset($data['user_id']) || !isset($data['session_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少必要参数'
            ]));
            return;
        }

        $userId = $data['user_id'];
        $sessionId = $data['session_id'];

        if (!isset(self::$sessions[$sessionId])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '会话不存在'
            ]));
            return;
        }

        $session = &self::$sessions[$sessionId];
        
        // 检查用户是否在会话中
        if ($session['user1_id'] != $userId && $session['user2_id'] != $userId) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '您不是此会话的参与者'
            ]));
            return;
        }

        // 检查是否已加入
        if (isset($session['joined_users']) && in_array($userId, $session['joined_users'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '您已在会话中'
            ]));
            return;
        }

        // 检查余额是否足够
        if (self::$userBalances[$userId] < $session['rate']) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '余额不足，请先充值'
            ]));
            return;
        }

        // 标记用户已加入
        if (!isset($session['joined_users'])) {
            $session['joined_users'] = [];
        }
        
        $session['joined_users'][] = $userId;

        // 如果双方都已加入，激活会话
        if (count($session['joined_users']) == 2) {
            $session['active'] = true;
            self::$userSessions[$session['user1_id']] = $sessionId;
            self::$userSessions[$session['user2_id']] = $sessionId;
            
            $userName1 = self::$userNicknames[$session['user1_id']] ?? $session['user1_id'];
            $userName2 = self::$userNicknames[$session['user2_id']] ?? $session['user2_id'];
            
            // 通知双方会话开始
            foreach (self::$sessionConnections[$sessionId] as $conn) {
                $conn->send(json_encode([
                    'type' => 'session_started',
                    'session_id' => $sessionId,
                    'rate' => $session['rate'],
                    'user1_nickname' => $userName1,
                    'user2_nickname' => $userName2
                ]));
            }
            
            echo "会话 {$sessionId} 已激活: {$userName1} <-> {$userName2}，开始计费\n";
        } else {
            $waitingCount = 2 - count($session['joined_users']);
            $connection->send(json_encode([
                'type' => 'session_joined',
                'session_id' => $sessionId,
                'message' => "已加入会话，等待对方加入（还需 {$waitingCount} 人）"
            ]));
        }
    }

    /**
     * 处理聊天消息
     */
    private static function handleChat($connection, $data)
    {
        if (!isset($data['user_id']) || !isset($data['message'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少必要参数'
            ]));
            return;
        }

        $userId = $data['user_id'];
        $message = trim($data['message']);

        if (empty($message)) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '消息不能为空'
            ]));
            return;
        }

        // 检查用户是否在任何活跃会话中
        if (!isset(self::$userSessions[$userId])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '您不在任何活跃会话中'
            ]));
            return;
        }

        $sessionId = self::$userSessions[$userId];
        $session = &self::$sessions[$sessionId];

        // 检查会话是否激活
        if (!$session['active']) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '会话未激活，请等待对方加入'
            ]));
            return;
        }

        // 获取对方用户ID
        $targetUserId = ($userId == $session['user1_id']) ? $session['user2_id'] : $session['user1_id'];

        // 保存聊天记录
        self::$chatHistory[$sessionId][] = [
            'from_user_id' => $userId,
            'message' => $message,
            'timestamp' => time()
        ];

        // 限制聊天记录数量（最多保存100条）
        if (count(self::$chatHistory[$sessionId]) > 100) {
            array_shift(self::$chatHistory[$sessionId]);
        }

        // 获取发送者信息
        $senderNickname = self::$userNicknames[$userId] ?? $userId;
        $senderAvatar = self::$userAvatars[$userId] ?? '';

        // 转发消息给对方
        $targetConnection = self::$sessionConnections[$sessionId][$userId == $session['user1_id'] ? 'user2' : 'user1'];
        $targetConnection->send(json_encode([
            'type' => 'chat_message',
            'from_user_id' => $userId,
            'from_nickname' => $senderNickname,
            'from_avatar' => $senderAvatar,
            'message' => $message,
            'timestamp' => time()
        ]));

        // 回复发送方
        $connection->send(json_encode([
            'type' => 'message_sent',
            'timestamp' => time()
        ]));
    }

    /**
     * 处理输入状态
     */
    private static function handleTyping($connection, $data)
    {
        if (!isset($data['user_id'])) {
            return;
        }

        $userId = $data['user_id'];

        if (!isset(self::$userSessions[$userId])) {
            return;
        }

        $sessionId = self::$userSessions[$userId];
        $session = &self::$sessions[$sessionId];

        if (!$session['active']) {
            return;
        }

        // 获取对方用户ID
        $targetUserId = ($userId == $session['user1_id']) ? $session['user2_id'] : $session['user1_id'];

        // 通知对方正在输入
        $targetConnection = self::$sessionConnections[$sessionId][$userId == $session['user1_id'] ? 'user2' : 'user1'];
        $targetConnection->send(json_encode([
            'type' => 'typing',
            'user_id' => $userId,
            'is_typing' => isset($data['is_typing']) ? $data['is_typing'] : true
        ]));
    }

    /**
     * 处理获取聊天记录
     */
    private static function handleGetChatHistory($connection, $data)
    {
        if (!isset($data['user_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少user_id参数'
            ]));
            return;
        }

        $userId = $data['user_id'];

        if (!isset(self::$userSessions[$userId])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '您不在任何活跃会话中'
            ]));
            return;
        }

        $sessionId = self::$userSessions[$userId];
        $history = isset(self::$chatHistory[$sessionId]) ? self::$chatHistory[$sessionId] : [];

        $connection->send(json_encode([
            'type' => 'chat_history',
            'session_id' => $sessionId,
            'history' => $history
        ]));
    }

    /**
     * 处理获取会话状态
     */
    private static function handleSessionStatus($connection, $data)
    {
        if (!isset($data['user_id'])) {
            return;
        }

        $userId = $data['user_id'];

        if (!isset(self::$userSessions[$userId])) {
            $connection->send(json_encode([
                'type' => 'session_status',
                'in_session' => false
            ]));
            return;
        }

        $sessionId = self::$userSessions[$userId];
        $session = self::$sessions[$sessionId];

        $duration = time() - $session['start_time'];
        $charge = ($duration / 60) * $session['rate'];

        $connection->send(json_encode([
            'type' => 'session_status',
            'in_session' => true,
            'session_id' => $sessionId,
            'duration' => $duration,
            'rate' => $session['rate'],
            'charge' => round($charge, 2),
            'user1_nickname' => self::$userNicknames[$session['user1_id']] ?? $session['user1_id'],
            'user2_nickname' => self::$userNicknames[$session['user2_id']] ?? $session['user2_id']
        ]));
    }

    /**
     * 处理结束会话
     */
    private static function handleEndSession($connection, $data)
    {
        if (!isset($data['user_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少user_id参数'
            ]));
            return;
        }

        self::endSessionByUser($data['user_id']);
    }

    /**
     * 通过用户ID结束会话
     */
    private static function endSessionByUser($userId)
    {
        if (!isset(self::$userSessions[$userId])) {
            return;
        }

        $sessionId = self::$userSessions[$userId];
        $session = &self::$sessions[$sessionId];

        // 计算最终费用
        $duration = time() - $session['start_time'];
        $totalCharge = ($duration / 60) * $session['rate'];

        // 从双方账户扣除
        self::$userBalances[$session['user1_id']] -= $totalCharge;
        self::$userBalances[$session['user2_id']] -= $totalCharge;

        $userName1 = self::$userNicknames[$session['user1_id']] ?? $session['user1_id'];
        $userName2 = self::$userNicknames[$session['user2_id']] ?? $session['user2_id'];

        // 通知双方
        foreach (self::$sessionConnections[$sessionId] as $conn) {
            $conn->send(json_encode([
                'type' => 'session_ended',
                'session_id' => $sessionId,
                'duration' => $duration,
                'charge' => round($totalCharge, 2),
                'user1_nickname' => $userName1,
                'user2_nickname' => $userName2
            ]));
        }

        echo "会话 {$sessionId} 已结束 ({$userName1} <-> {$userName2})，时长: {$duration}秒，总费用: " . round($totalCharge, 2) . "元\n";

        // 清理会话数据
        unset(self::$userSessions[$session['user1_id']]);
        unset(self::$userSessions[$session['user2_id']]);
        unset(self::$sessions[$sessionId]);
        unset(self::$sessionConnections[$sessionId]);
        // 保留聊天记录用于历史查询
    }

    /**
     * 定时计费函数
     */
    public static function chargeForChat()
    {
        foreach (self::$sessions as $sessionId => &$session) {
            if (!$session['active']) {
                continue;
            }

            $elapsed = time() - $session['last_charge_time'];
            
            if ($elapsed >= 1) { // 至少间隔1秒才计费
                $charge = ($elapsed / 60) * $session['rate'];
                
                // 检查双方余额
                $user1Balance = self::$userBalances[$session['user1_id']];
                $user2Balance = self::$userBalances[$session['user2_id']];
                
                if ($user1Balance < $charge || $user2Balance < $charge) {
                    // 余额不足，结束会话
                    foreach (self::$sessionConnections[$sessionId] as $conn) {
                        $conn->send(json_encode([
                            'type' => 'session_ended',
                            'reason' => 'balance_insufficient',
                            'message' => '余额不足，会话已结束'
                        ]));
                    }
                    self::endSessionByUser($session['user1_id']);
                    continue;
                }
                
                // 扣费
                self::$userBalances[$session['user1_id']] -= $charge;
                self::$userBalances[$session['user2_id']] -= $charge;
                $session['last_charge_time'] = time();
                
                // 通知双方余额变动
                foreach (self::$sessionConnections[$sessionId] as $userId => $conn) {
                    $conn->send(json_encode([
                        'type' => 'balance_update',
                        'session_id' => $sessionId,
                        'charge' => round($charge, 2),
                        'balance' => round(self::$userBalances[$userId == 'user1' ? $session['user1_id'] : $session['user2_id']], 2)
                    ]));
                }
            }
        }
    }

    /**
     * 处理查询余额
     */
    private static function handleCheckBalance($connection, $data)
    {
        if (!isset($data['user_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少user_id参数'
            ]));
            return;
        }

        $userId = $data['user_id'];
        $balance = isset(self::$userBalances[$userId]) ? self::$userBalances[$userId] : 0;

        $connection->send(json_encode([
            'type' => 'balance_info',
            'user_id' => $userId,
            'balance' => round($balance, 2)
        ]));
    }

    /**
     * 处理充值
     */
    private static function handleRecharge($connection, $data)
    {
        if (!isset($data['user_id']) || !isset($data['amount'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '缺少必要参数'
            ]));
            return;
        }

        $userId = $data['user_id'];
        $amount = floatval($data['amount']);

        if ($amount <= 0) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => '充值金额必须大于0'
            ]));
            return;
        }

        if (!isset(self::$userBalances[$userId])) {
            self::$userBalances[$userId] = 0;
        }

        self::$userBalances[$userId] += $amount;

        $connection->send(json_encode([
            'type' => 'recharge_success',
            'amount' => $amount,
            'new_balance' => round(self::$userBalances[$userId], 2)
        ]));

        echo "[" . date('Y-m-d H:i:s') . "] 用户 {$userId} 充值 {$amount} 元，当前余额: " . round(self::$userBalances[$userId], 2) . " 元\n";
    }

    /**
     * 清理无效会话
     * 定时清理超过1小时未激活或连接断开的会话
     */
    public static function cleanupInactiveSessions()
    {
        $now = time();
        $cleaned = 0;

        foreach (self::$sessions as $sessionId => $session) {
            // 检查会话是否超过1小时未激活
            if (!$session['active'] && ($now - $session['start_time']) > 3600) {
                self::removeSessionData($sessionId);
                $cleaned++;
                echo "[" . date('Y-m-d H:i:s') . "] 清理无效会话: {$sessionId}\n";
                continue;
            }

            // 检查会话的连接是否有效
            if ($session['active']) {
                $user1Online = isset(self::$connections[$session['user1_id']]);
                $user2Online = isset(self::$connections[$session['user2_id']]);

                // 如果双方都不在线，结束会话
                if (!$user1Online && !$user2Online) {
                    echo "[" . date('Y-m-d H:i:s') . "] 双方离线，结束会话: {$sessionId}\n";
                    self::endSessionByUser($session['user1_id']);
                    $cleaned++;
                }
            }
        }

        // 清理过期的聊天记录（超过24小时）
        foreach (self::$chatHistory as $sessionId => $history) {
            if (isset($history[0]) && ($now - $history[0]['timestamp']) > 86400) {
                self::$chatHistory[$sessionId] = array_slice($history, -50); // 保留最近50条
                echo "[" . date('Y-m-d H:i:s') . "] 清理过期聊天记录: {$sessionId}\n";
            }
        }

        if ($cleaned > 0) {
            echo "[" . date('Y-m-d H:i:s') . "] 清理完成，共清理 {$cleaned} 个会话\n";
        }
    }

    /**
     * 移除会话数据
     */
    private static function removeSessionData($sessionId)
    {
        if (isset(self::$sessions[$sessionId])) {
            $session = self::$sessions[$sessionId];
            unset(self::$userSessions[$session['user1_id']]);
            unset(self::$userSessions[$session['user2_id']]);
            unset(self::$sessions[$sessionId]);
            unset(self::$sessionConnections[$sessionId]);
            unset(self::$chatHistory[$sessionId]);
        }
    }
}
