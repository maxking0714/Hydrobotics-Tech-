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

// Handle profile picture upload
$pictureUploadError = '';
$pictureUploadSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed, true)) {
            $pictureUploadError = 'Only image files (JPG, PNG, GIF, WebP) are allowed.';
        } else if ($file['size'] > 5242880) { // 5MB limit
            $pictureUploadError = 'File size must be under 5MB.';
        } else {
            $uploadDir = __DIR__ . '/data/profile_pictures';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filename = 'profile_' . $user . '_' . time() . '.' . $ext;
            $filepath = $uploadDir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Delete old picture if exists
                $oldProfile = getUserProfile($user);
                if (!empty($oldProfile['profile_picture'])) {
                    $oldPath = $uploadDir . '/' . $oldProfile['profile_picture'];
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
                
                updateUserProfile($user, ['profile_picture' => $filename]);
                appendAdminLog("PROFILE_PICTURE_UPLOAD user={$user} file={$filename}");
                $pictureUploadSuccess = 'Profile picture updated successfully.';
            } else {
                $pictureUploadError = 'Failed to upload file.';
            }
        }
    } else {
        $pictureUploadError = 'Upload error. Please try again.';
    }
}

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
<?php
$page_title = 'Dashboard - HYDROBOTICS';
include 'header.php';
?>

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

        <?php if ($pictureUploadError): ?>
            <div style="background: #ffe3e3; color: #9d2b2b; padding: 1rem; border-radius: 12px; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($pictureUploadError); ?>
            </div>
        <?php endif; ?>
        <?php if ($pictureUploadSuccess): ?>
            <div style="background: #e6ffed; color: #1f6f34; padding: 1rem; border-radius: 12px; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($pictureUploadSuccess); ?>
            </div>
        <?php endif; ?>

        <div style="background: white; border-radius: 18px; padding: 2rem; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); margin-bottom: 1.5rem; display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: start;">
            <div style="text-align: center;">
                <?php 
                    $profilePicPath = '';
                    $profilePicFile = '';
                    if (!empty($profile['profile_picture'])) {
                        $profilePicPath = 'data/profile_pictures/' . htmlspecialchars($profile['profile_picture']);
                        $profilePicFile = __DIR__ . '/' . $profilePicPath;
                    }
                ?>
                <?php if ($profilePicPath && file_exists($profilePicFile)): ?>
                    <img src="<?php echo $profilePicPath; ?>" alt="<?php echo htmlspecialchars($user); ?>" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #00a8e8; margin-bottom: 1rem;">
                <?php else: ?>
                    <div style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #00a8e8, #0d3b66); display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 2.5rem; margin: 0 auto 1rem; border: 3px solid #00a8e8;">
                        <?php echo htmlspecialchars(strtoupper(substr($user, 0, 2))); ?>
                    </div>
                <?php endif; ?>
                <form id="profile_picture_form" method="POST" enctype="multipart/form-data" style="margin-top: 1rem;">
                    <input type="file" name="profile_picture" accept="image/*" style="display: none;" id="profile_picture_input" onchange="this.form.submit();">
                    <button type="button" style="padding: 0.7rem 1rem; background: #00a8e8; color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600;" onclick="document.getElementById('profile_picture_input').click();">
                        📷 Change Photo
                    </button>
                </form>
                <p style="color: #627d98; font-size: 0.85rem; margin-top: 0.5rem;">Choose a JPG, PNG, GIF, or WebP image (max 5MB). Uploads automatically.</p>
            </div>
            <div>
                <h2 style="margin-top: 0;">Your Profile</h2>
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <strong style="color: #0d243a;">Username:</strong> <?php echo htmlspecialchars($user); ?>
                    </div>
                    <div>
                        <strong style="color: #0d243a;">Account Type:</strong> <?php echo htmlspecialchars($profile['role'] ?? 'inventor'); ?>
                    </div>
                    <div>
                        <strong style="color: #0d243a;">Gender:</strong> <?php echo htmlspecialchars($profile['gender'] === 'not-specified' || !$profile['gender'] ? 'Not specified' : ucfirst($profile['gender'])); ?>
                    </div>
                    <div>
                        <strong style="color: #0d243a;">Age:</strong> <?php echo $profile['age'] ? htmlspecialchars($profile['age']) : 'Not specified'; ?>
                    </div>
                    <div>
                        <strong style="color: #0d243a;">Location:</strong> <?php echo $profile['location'] ? htmlspecialchars($profile['location']) : 'Not specified'; ?>
                    </div>
                </div>
                <p style="color: #627d98; font-size: 0.9rem; margin-top: 1.5rem;">
                    <a href="settings.php" style="color: #00a8e8; text-decoration: none; font-weight: 600;">Edit all profile settings</a>
                </p>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?php echo $stats['users']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Feed Posts</h3>a
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
<?php include 'footer.php'; ?>

