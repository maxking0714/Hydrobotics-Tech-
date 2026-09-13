<?php
require_once 'admin_background.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$users = loadUsers();
$messages = loadMessages();
$selectedContact = $_GET['contact'] ?? '';

// Handle sending a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $recipient = trim($_POST['recipient'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    
    if ($recipient && $messageText && $recipient !== $user) {
        $messages[] = [
            'id' => uniqid('msg_', true),
            'sender' => $user,
            'recipient' => $recipient,
            'message' => $messageText,
            'created_at' => date('Y-m-d H:i:s'),
            'read' => false,
        ];
        saveMessages($messages);
        appendAdminLog("CHAT_MESSAGE from={$user} to={$recipient}");
        header('Location: chat.php?contact=' . urlencode($recipient));
        exit;
    }
}

// Build conversation list
$conversations = [];
$conversationMap = [];
foreach ($messages as $msg) {
    if ($msg['sender'] === $user || $msg['recipient'] === $user) {
        $otherUser = $msg['sender'] === $user ? $msg['recipient'] : $msg['sender'];
        if (!isset($conversationMap[$otherUser])) {
            $conversationMap[$otherUser] = [
                'other_user' => $otherUser,
                'last_message' => $msg['message'],
                'last_time' => $msg['created_at'],
                'unread' => ($msg['recipient'] === $user && !($msg['read'] ?? false)) ? 1 : 0,
            ];
        } else {
            $conversationMap[$otherUser]['last_time'] = $msg['created_at'];
            if ($msg['recipient'] === $user && !($msg['read'] ?? false)) {
                $conversationMap[$otherUser]['unread']++;
            }
        }
    }
}
$conversations = array_values($conversationMap);
usort($conversations, function($a, $b) {
    return strtotime($b['last_time']) - strtotime($a['last_time']);
});

// Get messages for selected contact
$chatMessages = [];
if ($selectedContact) {
    $chatMessages = array_filter($messages, function($msg) use ($user, $selectedContact) {
        return ($msg['sender'] === $user && $msg['recipient'] === $selectedContact) ||
               ($msg['sender'] === $selectedContact && $msg['recipient'] === $user);
    });
    usort($chatMessages, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Chat - HYDROBOTICS</title>
    <link rel="stylesheet" href="css/theme.css?v=2">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef5fb; color: #102a43; }
        .topbar { background: #0d1b2a; color: #e0f7ff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; padding: 1rem 2rem; }
        .topbar .brand { font-size: 1.4rem; font-weight: 700; letter-spacing: 1px; }
        .topbar nav a { color: #e0f7ff; text-decoration: none; margin-left: 1.25rem; }
        .topbar nav a:hover { text-decoration: underline; }
        .layout { display: grid; grid-template-columns: 320px 1fr; gap: 1.5rem; padding: 2rem; max-width: 1270px; margin: 0 auto; min-height: calc(100vh - 80px); }
        .panel { background: white; border-radius: 18px; padding: 1.5rem; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); }
        .panel h2 { margin-top: 0; }
        .conversation-item { padding: 1rem; border-radius: 12px; cursor: pointer; border: 1px solid #dbe7f0; margin-bottom: 0.75rem; transition: background 0.2s; }
        .conversation-item:hover { background: #f0f6ff; }
        .conversation-item.active { background: #d9edff; border-color: #00a8e8; }
        .conversation-name { font-weight: 600; display: block; }
        .conversation-preview { font-size: 0.85rem; color: #627d98; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .conversation-time { font-size: 0.8rem; color: #9aa7b8; }
        .chat-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 2px solid #dbe7f0; margin-bottom: 1rem; }
        .chat-container { display: flex; flex-direction: column; height: 100%; }
        .messages-area { flex: 1; overflow-y: auto; margin-bottom: 1rem; padding-right: 0.5rem; }
        .messages-area::-webkit-scrollbar { width: 6px; }
        .messages-area::-webkit-scrollbar-track { background: #f0f6ff; border-radius: 3px; }
        .messages-area::-webkit-scrollbar-thumb { background: #00a8e8; border-radius: 3px; }
        .message { margin-bottom: 0.75rem; display: flex; }
        .message.sent { justify-content: flex-end; }
        .message.received { justify-content: flex-start; }
        .message-bubble { max-width: 75%; padding: 0.85rem 1rem; border-radius: 12px; word-wrap: break-word; }
        .message.sent .message-bubble { background: #00a8e8; color: white; border-bottom-right-radius: 4px; }
        .message.received .message-bubble { background: #f0f6ff; color: #102a43; border-bottom-left-radius: 4px; }
        .message-time { font-size: 0.75rem; color: #9aa7b8; margin-top: 0.25rem; }
        .message-form { display: grid; gap: 0.75rem; }
        .message-form textarea { width: 100%; padding: 0.85rem 1rem; border: 1px solid #dbe7f0; border-radius: 14px; resize: vertical; min-height: 80px; font-family: inherit; }
        .btn { display: inline-block; padding: 0.9rem 1.4rem; border-radius: 14px; background: #00a8e8; color: white; border: none; text-decoration: none; font-weight: 700; cursor: pointer; }
        .btn:hover { opacity: 0.95; }
        .empty-state { text-align: center; padding: 2rem; color: #627d98; }
        .empty-state p { margin: 0.5rem 0; }
        @media (max-width: 980px) { .layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">💬 HYDROBOTICS Chat</div>
        <nav>
            <a href="feed.php">Feed</a>
            <a href="videos.php">Videos</a>
            <a href="dashboard.php">Dashboard</a>
        </nav>
    </header>
    <main class="layout">
        <section class="panel">
            <h2>Conversations</h2>
            <?php if (count($conversations) === 0): ?>
                <div class="empty-state">
                    <p>No conversations yet</p>
                    <p style="font-size: 0.9rem;">Start a chat by selecting someone from the feed or dashboard</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <a href="chat.php?contact=<?php echo urlencode($conv['other_user']); ?>" style="text-decoration: none;">
                        <div class="conversation-item <?php echo $selectedContact === $conv['other_user'] ? 'active' : ''; ?>">
                            <span class="conversation-name"><?php echo htmlspecialchars($conv['other_user']); ?></span>
                            <span class="conversation-preview"><?php echo htmlspecialchars(mb_strimwidth($conv['last_message'], 0, 50, '...')); ?></span>
                            <span class="conversation-time"><?php echo date('M d, H:i', strtotime($conv['last_time'])); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <section class="panel">
            <?php if ($selectedContact): ?>
                <div class="chat-header">
                    <h2 style="margin: 0;">Chat with <?php echo htmlspecialchars($selectedContact); ?></h2>
                    <a href="chat.php" style="color: #00a8e8; text-decoration: none; font-weight: 600;">✕</a>
                </div>
                <div class="chat-container">
                    <div class="messages-area">
                        <?php if (count($chatMessages) === 0): ?>
                            <div class="empty-state" style="padding: 1rem;">
                                <p>No messages yet. Start the conversation!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($chatMessages as $msg): ?>
                                <div class="message <?php echo $msg['sender'] === $user ? 'sent' : 'received'; ?>">
                                    <div>
                                        <div class="message-bubble"><?php echo htmlspecialchars($msg['message']); ?></div>
                                        <div class="message-time" style="text-align: <?php echo $msg['sender'] === $user ? 'right' : 'left'; ?>;">
                                            <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="chat.php" class="message-form">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="recipient" value="<?php echo htmlspecialchars($selectedContact); ?>">
                        <textarea name="message" placeholder="Type your message..." required></textarea>
                        <button type="submit" class="btn">Send</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h2>Select a conversation to chat</h2>
                    <p>Choose someone from the left to continue your chat.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script>
        // Auto-scroll to latest message
        const messagesArea = document.querySelector('.messages-area');
        if (messagesArea) {
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }
    </script>
<?php include 'ai_chatbot_widget.php'; ?>
</body>
</html>
