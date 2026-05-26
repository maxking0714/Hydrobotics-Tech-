<?php
require_once 'admin_background.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$role = $_SESSION['role'] ?? 'inventor';
$profile = getUserProfile($user);
$posts = loadPosts();
$userPosts = array_reverse(array_filter($posts, function ($post) use ($user) {
    return isset($post['author']) && $post['author'] === $user;
}));

$error = '';
$success = '';

// Handle posting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'post') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $allow_share = isset($_POST['allow_share']) ? 1 : 0;

        if ($title === '' || $content === '') {
            $error = 'Please provide both a title and content for your post.';
        } else {
            $posts[] = [
                'id' => uniqid('post_', true),
                'author' => $user,
                'role' => $role,
                'title' => $title,
                'content' => $content,
                'likes' => [],
                'comments' => [],
                'allow_share' => $allow_share,
                'shares' => 0,
                'reposts' => 0,
                'repost_of' => null,
                'shared_from' => null,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            savePosts($posts);
            appendAdminLog("POST author={$user} title=" . substr($title, 0, 50));
            $success = 'Post published to your account and feed!';
            header('Location: account.php');
            exit;
        }
    } elseif ($action === 'delete_post') {
        $postId = $_POST['post_id'] ?? '';
        $posts = array_filter($posts, function ($post) use ($postId) {
            return !($post['id'] === $postId);
        });
        savePosts(array_values($posts));
        appendAdminLog("DELETE_POST user={$user} post_id={$postId}");
        header('Location: account.php');
        exit;
    }
}

// Reload posts to ensure fresh data
$posts = loadPosts();
$userPosts = array_reverse(array_filter($posts, function ($post) use ($user) {
    return isset($post['author']) && $post['author'] === $user;
}));

$totalLikes = 0;
foreach ($userPosts as $post) {
    $totalLikes += count($post['likes'] ?? []);
}

$followers = $profile['followers'] ?? [];
$following = $profile['following'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($user); ?> Account - HYDROBOTICS</title>
    <link rel="stylesheet" href="css/theme.css?v=2">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef5fb; color: #102a43; }
        .page { max-width: 1180px; margin: 0 auto; padding: 2rem; }
        .nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; }
        .nav h1 { margin: 0; }
        .nav a, .nav button { display: inline-block; padding: 0.85rem 1.3rem; background: #00a8e8; color: white; text-decoration: none; border-radius: 12px; border: none; cursor: pointer; font-weight: 600; }
        .nav a:hover, .nav button:hover { background: #0099d5; }
        .profile-header { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; background: white; padding: 2rem; border-radius: 18px; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); margin-bottom: 2rem; }
        .profile-info h2 { margin-top: 0; }
        .stat-row { display: flex; gap: 2rem; margin: 1rem 0; }
        .stat { background: #f0f7ff; padding: 1rem; border-radius: 12px; text-align: center; flex: 1; }
        .stat strong { display: block; font-size: 1.5rem; color: #00a8e8; }
        .stat span { display: block; color: #627d98; font-size: 0.9rem; }
        .panel { background: white; border-radius: 18px; padding: 2rem; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); margin-bottom: 2rem; }
        .panel h2 { margin-top: 0; }
        form { display: flex; flex-direction: column; gap: 1rem; }
        label { font-weight: 600; }
        input[type="text"], input[type="number"], textarea, select {
            padding: 0.85rem 1rem;
            border: 1px solid #d4e0f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
        }
        textarea { resize: vertical; min-height: 120px; }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #00a8e8;
            box-shadow: 0 0 0 3px rgba(0, 168, 232, 0.1);
        }
        .checkbox-group { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-group input { width: auto; }
        .btn { padding: 0.9rem 1.5rem; background: #00a8e8; color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #0099d5; }
        .btn-danger { background: #ff6b6b; }
        .btn-danger:hover { background: #e85555; }
        .error { background: #ffe6e6; border-left: 4px solid #ff6b6b; padding: 1rem; border-radius: 8px; color: #c92a2a; margin-bottom: 1rem; }
        .success { background: #e6ffe6; border-left: 4px solid #51cf66; padding: 1rem; border-radius: 8px; color: #2b8a3e; margin-bottom: 1rem; }
        .post-item { padding: 1.5rem; border: 1px solid #e6eef6; border-radius: 12px; margin-bottom: 1rem; background: #f9fbff; }
        .post-item h3 { margin: 0 0 0.5rem; }
        .post-meta { font-size: 0.85rem; color: #627d98; margin-bottom: 1rem; }
        .post-content { margin: 1rem 0; }
        .post-stats { display: flex; gap: 1.5rem; font-size: 0.9rem; color: #627d98; margin: 1rem 0; }
        .post-actions { display: flex; gap: 0.75rem; }
        .post-actions form { flex-direction: row; margin: 0; }
        .post-actions button { padding: 0.6rem 1rem; font-size: 0.9rem; }
        .empty { text-align: center; color: #627d98; padding: 2rem; }
        @media (max-width: 800px) {
            .profile-header { grid-template-columns: 1fr; }
            .stat-row { flex-direction: column; gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="nav">
            <h1><?php echo htmlspecialchars($user); ?>'s Account</h1>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="feed.php">📰 Feed</a>
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="settings.php">⚙️ Settings</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-info">
                <h2>Profile</h2>
                <div>
                    <strong>Username:</strong> <?php echo htmlspecialchars($user); ?>
                </div>
                <div style="margin-top: 0.75rem;">
                    <strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($role)); ?>
                </div>
                <div style="margin-top: 0.75rem;">
                    <strong>Location:</strong> <?php echo $profile['location'] ? htmlspecialchars($profile['location']) : 'Not specified'; ?>
                </div>
                <div style="margin-top: 0.75rem;">
                    <strong>Phone:</strong> <?php echo $profile['phone'] ? htmlspecialchars($profile['phone']) : 'Not specified'; ?>
                </div>
                <div style="margin-top: 0.75rem;">
                    <strong>Email:</strong> <?php echo $profile['email'] ? '<a href="mailto:' . htmlspecialchars($profile['email']) . '">' . htmlspecialchars($profile['email']) . '</a>' : 'Not specified'; ?>
                </div>
                <?php if (!empty($profile['facebook']) || !empty($profile['whatsapp']) || !empty($profile['instagram'])): ?>
                    <div style="margin-top: 1rem;">
                        <strong>Social Profiles</strong>
                        <div style="display:flex; flex-wrap:wrap; gap:0.75rem; margin-top:0.5rem;">
                            <?php if (!empty($profile['facebook'])): ?>
                                <a href="<?php echo htmlspecialchars($profile['facebook']); ?>" target="_blank" style="color:#0d3b66; text-decoration:none; padding:0.45rem 0.8rem; border:1px solid #d4e0f0; border-radius:10px; background:#eef7ff;">Facebook</a>
                            <?php endif; ?>
                            <?php if (!empty($profile['whatsapp'])): ?>
                                <a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/[^\d+]/', '', $profile['whatsapp'])); ?>" target="_blank" style="color:#0d3b66; text-decoration:none; padding:0.45rem 0.8rem; border:1px solid #d4e0f0; border-radius:10px; background:#e7fff5;">WhatsApp</a>
                            <?php endif; ?>
                            <?php if (!empty($profile['instagram'])): ?>
                                <a href="<?php echo htmlspecialchars($profile['instagram']); ?>" target="_blank" style="color:#0d3b66; text-decoration:none; padding:0.45rem 0.8rem; border:1px solid #d4e0f0; border-radius:10px; background:#fff0f8;">Instagram</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <h2>Stats</h2>
                <div class="stat-row">
                    <div class="stat">
                        <strong><?php echo count($userPosts); ?></strong>
                        <span>Posts</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo count($followers); ?></strong>
                        <span>Followers</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo count($following); ?></strong>
                        <span>Following</span>
                    </div>
                    <div class="stat">
                        <strong><?php echo $totalLikes; ?></strong>
                        <span>Total Likes</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <h2>Create a Post</h2>
            <p>Share your thoughts and content with the HYDROBOTICS community.</p>
            <form method="POST">
                <input type="hidden" name="action" value="post">
                <div>
                    <label for="title">Post Title</label>
                    <input id="title" type="text" name="title" placeholder="Enter a catchy title..." required>
                </div>
                <div>
                    <label for="content">Content</label>
                    <textarea id="content" name="content" placeholder="What's on your mind?" required></textarea>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="allow_share" name="allow_share" value="1">
                    <label for="allow_share" style="margin: 0;">Allow others to share and repost this content</label>
                </div>
                <button type="submit" class="btn">📤 Publish Post</button>
            </form>
        </div>

        <div class="panel">
            <h2>Your Posts (<?php echo count($userPosts); ?>)</h2>
            <?php if (count($userPosts) === 0): ?>
                <div class="empty">
                    <p>You haven't posted anything yet. Create your first post above!</p>
                </div>
            <?php else: ?>
                <?php foreach ($userPosts as $post): ?>
                    <div class="post-item">
                        <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                        <div class="post-meta">
                            Posted on <?php echo date('M d, Y \a\t h:i A', strtotime($post['created_at'])); ?>
                        </div>
                        <div class="post-content">
                            <?php echo htmlspecialchars($post['content']); ?>
                        </div>
                        <div class="post-stats">
                            <span>❤️ <?php echo count($post['likes'] ?? []); ?> likes</span>
                            <span>💬 <?php echo count($post['comments'] ?? []); ?> comments</span>
                            <?php if (!empty($post['allow_share'])): ?>
                                <span>🔄 <?php echo ($post['reposts'] ?? 0); ?> reposts</span>
                                <span>📤 <?php echo ($post['shares'] ?? 0); ?> shares</span>
                            <?php endif; ?>
                        </div>
                        <div class="post-actions">
                            <a href="feed.php" class="btn" style="text-decoration: none; display: inline-block; padding: 0.6rem 1rem; font-size: 0.9rem;">👁️ View on Feed</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_post">
                                <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this post?');">🗑️ Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php include 'ai_chatbot_widget.php'; ?>
</body>
</html>
