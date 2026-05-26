<?php
require_once 'admin_background.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$role = $_SESSION['role'] ?? 'inventor';
$users = loadUsers();
$posts = loadPosts();
$profile = getUserProfile($user);
$myPosts = array_filter($posts, function ($post) use ($user) {
    return isset($post['author']) && $post['author'] === $user;
});
$stats = [
    'users' => count($users),
    'posts' => count($posts),
    'my_posts' => count($myPosts),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - HYDROBOTICS</title>
    <link rel="stylesheet" href="css/theme.css?v=2">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef5fb; color: #102a43; }
        .page { max-width: 1180px; margin: 0 auto; padding: 2rem; }
        .top { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
        .top h1 { margin: 0; }
        .welcome { background: white; padding: 1.75rem 2rem; border-radius: 18px; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 1.5rem; }
        .stat-card { background: white; border-radius: 18px; padding: 1.4rem 1.6rem; box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06); }
        .stat-card h3 { margin: 0 0 0.5rem; }
        .panel { background: white; border-radius: 18px; padding: 1.75rem; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); margin-top: 1.75rem; }
        .panel h2 { margin-top: 0; }
        .my-posts { list-style: none; margin: 0; padding: 0; }
        .my-posts li { padding: 1rem 0; border-bottom: 1px solid #e6eef6; }
        .my-posts li:last-child { border-bottom: none; }
        .my-posts strong { display: block; margin-bottom: 0.35rem; }
        .btn { display: inline-block; padding: 0.9rem 1.4rem; border-radius: 14px; background: #00a8e8; color: white; text-decoration: none; font-weight: 700; }
        .logout { border: none; background: #ff6b6b; padding: 0.9rem 1.4rem; border-radius: 14px; color: white; cursor: pointer; }
        a { color: #0d3b66; text-decoration: none; }
        a:hover { text-decoration: underline; }
        @media (max-width: 760px) { .top { flex-direction: column; align-items: stretch; } }
    </style>
</head>
<body>
    <div class="page">
        <div class="top">
            <div>
                <h1>Dashboard</h1>
                <p>Welcome back, <strong><?php echo htmlspecialchars($user); ?></strong> — you are signed in as <strong><?php echo htmlspecialchars($role); ?></strong>.</p>
            </div>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <a href="account.php" class="btn" style="background: #1fa8dd;">👤 My Account</a>
                <a href="settings.php" class="btn" style="background: #1fa8dd;">⚙ Settings</a>
                <form method="POST" style="margin:0;">
                    <button class="logout" name="logout" type="submit">Logout</button>
                </form>
            </div>
        </div>

        <div class="welcome">
            <p>From the dashboard, you can view your account data, access the social feed, and track your posts and the HYDROBOTICS community activity.</p>
        </div>

        <div style="background: white; border-radius: 18px; padding: 1.5rem; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); margin-bottom: 1.5rem;">
            <h2 style="margin-top: 0;">Your Profile</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <strong>Gender:</strong> <?php echo htmlspecialchars($profile['gender'] === 'not-specified' || !$profile['gender'] ? 'Not specified' : ucfirst($profile['gender'])); ?>
                </div>
                <div>
                    <strong>Age:</strong> <?php echo $profile['age'] ? htmlspecialchars($profile['age']) : 'Not specified'; ?>
                </div>
                <div>
                    <strong>Location:</strong> <?php echo $profile['location'] ? htmlspecialchars($profile['location']) : 'Not specified'; ?>
                </div>
            </div>
            <p style="color: #627d98; font-size: 0.9rem; margin-top: 1rem;">
                <a href="settings.php">Edit your profile and privacy settings</a>
            </p>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?php echo $stats['users']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Feed Posts</h3>
                <p><?php echo $stats['posts']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Your Posts</h3>
                <p><?php echo $stats['my_posts']; ?></p>
            </div>
        </div>

        <div class="panel">
            <h2>Your Recent Activity</h2>
            <?php if (count($myPosts) === 0): ?>
                <p>You haven't posted yet. Visit the <a href="feed.php">HYDROBOTICS Social</a> page to share an update.</p>
            <?php else: ?>
                <ul class="my-posts">
                    <?php foreach (array_reverse($myPosts) as $post): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($post['title'] ?? 'Post'); ?></strong>
                            <span><?php echo nl2br(htmlspecialchars($post['content'])); ?></span>
                            <div style="margin-top: 0.75rem; color: #627d98; font-size: 0.95rem;">Posted <?php echo htmlspecialchars($post['created_at']); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if ($role === 'admin'): ?>
            <div class="panel" style="margin-top: 1.5rem;">
                <h2>Admin Tools</h2>
                <p>As an administrator, you can review logs and manage community activity.</p>
                <a class="btn" href="admin.php">Open Admin Log</a>
            </div>
        <?php endif; ?>

        <div class="panel" style="margin-top: 1.5rem;">
            <h2>Quick Links & Navigation</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem;">
                <a href="feed.php" style="text-decoration: none;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 14px; text-align: center; font-weight: 600; transition: transform 0.2s;">
                        📱 Social Feed
                    </div>
                </a>
                <a href="chat.php" style="text-decoration: none;">
                    <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: 14px; text-align: center; font-weight: 600; transition: transform 0.2s;">
                        💬 Chat
                    </div>
                </a>
                <a href="videos.php" style="text-decoration: none;">
                    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 14px; text-align: center; font-weight: 600; transition: transform 0.2s;">
                        🎬 Videos
                    </div>
                </a>
                <a href="settings.php" style="text-decoration: none;">
                    <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 1.5rem; border-radius: 14px; text-align: center; font-weight: 600; transition: transform 0.2s;">
                        ⚙ Settings
                    </div>
                </a>
            </div>
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #dbe7f0;">
                <p><a href="HOMEPAGE.PHP">← Return to Home</a></p>
            </div>
        </div>
    </div>
<?php include 'ai_chatbot_widget.php'; ?>
</body>
</html>
