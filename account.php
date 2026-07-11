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
<?php
$page_title = ($user ? htmlspecialchars($user) . "'s Account - HYDROBOTICS" : 'Account - HYDROBOTICS');
include 'header.php';
?>

    <div class="page">
        <div class="nav">
            <h1><?php echo htmlspecialchars($user); ?>'s Account</h1>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="feed.php">📰 Feed</a>
                <a href="dashboard.php">📊 Dashboard</a>
                <a href="settings.php">⚙️ Settings</a>
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
                <div style="text-align: center; margin-bottom: 1.5rem;">
                    <?php 
                        $profilePicPath = '';
                        if (!empty($profile['profile_picture'])) {
                            $profilePicPath = 'data/profile_pictures/' . htmlspecialchars($profile['profile_picture']);
                        }
                    ?>
                    <?php if ($profilePicPath && file_exists($profilePicPath)): ?>
                        <img src="<?php echo $profilePicPath; ?>" alt="<?php echo htmlspecialchars($user); ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #00a8e8;">
                    <?php else: ?>
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #00a8e8, #0d3b66); display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 2rem; margin: 0 auto; border: 3px solid #00a8e8;">
                            <?php echo htmlspecialchars(strtoupper(substr($user, 0, 2))); ?>
                        </div>
                    <?php endif; ?>
                </div>
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
<?php include 'footer.php'; ?>

