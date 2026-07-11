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
<?php
$page_title = 'Chat - HYDROBOTICS';
include 'header.php';
?>

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
                    <?php $contactProfile = getUserProfile($conv['other_user']); ?>
                    <a href="chat.php?contact=<?php echo urlencode($conv['other_user']); ?>" style="text-decoration: none;">
                        <div class="conversation-item <?php echo $selectedContact === $conv['other_user'] ? 'active' : ''; ?>" style="display: flex; gap: 1rem; align-items: center;">
                            <?php 
                                $profilePicPath = '';
                                if (!empty($contactProfile['profile_picture'])) {
                                    $profilePicPath = 'data/profile_pictures/' . htmlspecialchars($contactProfile['profile_picture']);
                                }
                            ?>
                            <?php if ($profilePicPath && file_exists($profilePicPath)): ?>
                                <img src="<?php echo $profilePicPath; ?>" alt="<?php echo htmlspecialchars($conv['other_user']); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">
                            <?php else: ?>
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #00a8e8, #0d3b66); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.9rem; flex-shrink: 0;">
                                    <?php echo htmlspecialchars(strtoupper(substr($conv['other_user'], 0, 2))); ?>
                                </div>
                            <?php endif; ?>
                            <div style="flex: 1; min-width: 0;">
                                <span class="conversation-name"><?php echo htmlspecialchars($conv['other_user']); ?></span>
                                <span class="conversation-preview"><?php echo htmlspecialchars(mb_strimwidth($conv['last_message'], 0, 50, '...')); ?></span>
                                <span class="conversation-time"><?php echo date('M d, H:i', strtotime($conv['last_time'])); ?></span>
                            </div>
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
                                <?php $msgSenderProfile = getUserProfile($msg['sender']); ?>
                                <div class="message <?php echo $msg['sender'] === $user ? 'sent' : 'received'; ?>" style="display: flex; gap: 0.5rem; <?php echo $msg['sender'] === $user ? 'justify-content: flex-end;' : ''; ?>">
                                    <?php if ($msg['sender'] !== $user): ?>
                                        <?php 
                                            $profilePicPath = '';
                                            if (!empty($msgSenderProfile['profile_picture'])) {
                                                $profilePicPath = 'data/profile_pictures/' . htmlspecialchars($msgSenderProfile['profile_picture']);
                                            }
                                        ?>
                                        <?php if ($profilePicPath && file_exists($profilePicPath)): ?>
                                            <img src="<?php echo $profilePicPath; ?>" alt="<?php echo htmlspecialchars($msg['sender']); ?>" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; margin-top: 0.25rem;">
                                        <?php else: ?>
                                            <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #00a8e8, #0d3b66); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.8rem; flex-shrink: 0;">
                                                <?php echo htmlspecialchars(strtoupper(substr($msg['sender'], 0, 1))); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
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
<?php include 'footer.php'; ?>

