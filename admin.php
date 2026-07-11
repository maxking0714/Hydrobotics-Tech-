<?php
session_start();
require_once 'admin_background.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

// Handle admin actions: delete_user, delete_post, export_log
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'delete_user' && !empty($_POST['username'])) {
        $deleted = deleteUserByUsername($_POST['username']);
        $msg = $deleted ? 'User deleted.' : 'User not found.';
    }
    if ($action === 'delete_post' && !empty($_POST['post_id'])) {
        $deleted = deletePostById($_POST['post_id']);
        $msg = $deleted ? 'Post deleted.' : 'Post not found.';
    }
    if ($action === 'resolve_help' && !empty($_POST['request_id'])) {
        $resolved = resolveHelpRequest($_POST['request_id'], 'assisted');
        $msg = $resolved ? 'Help request marked as assisted.' : 'Help request not found.';
    }
}
$users = loadUsers();
$posts = loadPosts();
$logs = getAdminLogLines(1000);
$helpRequests = loadHelpRequests();
$pendingRequests = array_values(array_filter($helpRequests, fn($req) => isset($req['status']) && $req['status'] === 'pending'));
?>
<?php
$page_title = 'Admin Log - HYDROBOTICS';
include 'header.php';
?>

    <div class="page">
        <div class="top">
            <div>
                <h1>Admin Tools</h1>
                <p>Viewing all registration and post events recorded by HYDROBOTICS.</p>
            </div>
            <a class="btn" href="dashboard.php">Back to Dashboard</a>
        </div>
        <?php if (count($pendingRequests) > 0): ?>
            <div class="alert-banner">
                You have <?php echo count($pendingRequests); ?> pending user help request<?php echo count($pendingRequests) === 1 ? '' : 's'; ?>. Review them below and mark assisted when resolved.
            </div>
        <?php endif; ?>

        <div class="summary">
            <div class="card">
                <h3>Total Users</h3>
                <p><?php echo count($users); ?></p>
            </div>
            <div class="card">
                <h3>Total Feed Posts</h3>
                <p><?php echo count($posts); ?></p>
            </div>
            <div class="card">
                <h3>Logged Events</h3>
                <p><?php echo count($logs); ?></p>
            </div>
        </div>
        <div class="card" style="margin-top:1.5rem;">
            <h3>Pending Help Requests</h3>
            <?php if (count($pendingRequests) === 0): ?>
                <p>No pending user support requests at the moment.</p>
            <?php else: ?>
                <?php foreach ($pendingRequests as $request): ?>
                    <div class="log-item" style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
                        <div>
                            <strong><?php echo htmlspecialchars($request['user']); ?></strong>
                            <div><?php echo htmlspecialchars($request['message']); ?></div>
                            <div style="font-size:0.85rem; color:#F50505;">Requested <?php echo htmlspecialchars($request['created_at']); ?></div>
                        </div>
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="action" value="resolve_help">
                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                            <button class="btn" type="submit">Mark Assisted</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-top:1.25rem;">
            <div class="card">
                <h3>Users</h3>
                <?php if (count($users) === 0): ?>
                    <p>No registered users yet.</p>
                <?php else: ?>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr><th style="text-align:left; padding:8px;">Username</th><th style="text-align:left; padding:8px">Role</th><th style="padding:8px;">Action</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr style="border-top:1px solid #eef6ff;">
                                <td style="padding:8px"><?php echo htmlspecialchars($u['username']); ?></td>
                                <td style="padding:8px"><?php echo htmlspecialchars($u['role'] ?? 'inventor'); ?></td>
                                <td style="padding:8px;text-align:center;">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete user <?php echo htmlspecialchars($u['username']); ?>?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($u['username']); ?>">
                                        <button type="submit" style="background:#ff6b6b;border:none;padding:8px 10px;color:white;border-radius:8px;cursor:pointer;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Feed Posts</h3>
                <?php if (count($posts) === 0): ?>
                    <p>No posts yet.</p>
                <?php else: ?>
                    <table style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr><th style="text-align:left;padding:8px">Author</th><th style="text-align:left;padding:8px">Title</th><th style="padding:8px">Action</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach (array_reverse($posts) as $p): ?>
                            <tr style="border-top:1px solid #eef6ff;">
                                <td style="padding:8px"><?php echo htmlspecialchars($p['author'] ?? ''); ?></td>
                                <td style="padding:8px"><?php echo htmlspecialchars($p['title'] ?? ''); ?></td>
                                <td style="padding:8px;text-align:center;">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this post?');">
                                        <input type="hidden" name="action" value="delete_post">
                                        <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($p['id']); ?>">
                                        <button type="submit" style="background:#ff6b6b;border:none;padding:8px 10px;color:white;border-radius:8px;cursor:pointer;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top:1rem;" class="card">
            <h3>Admin Tools</h3>
            <form method="POST" style="margin-bottom:0.75rem; display:inline;">
                <input type="hidden" name="action" value="export_log">
                <button class="btn" type="submit">Export Log</button>
            </form>
            <div class="log-list" style="margin-top:0.85rem;">
                <?php if (count($logs) === 0): ?>
                    <p>No admin log entries found yet.</p>
                <?php else: ?>
                    <?php foreach ($logs as $logEntry): ?>
                        <div class="log-item"><?php echo htmlspecialchars($logEntry); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <p class="note">All registrations, posts, and admin deletes are recorded in <code>admin_log.txt</code>.</p>
        </div>
    </div>
    <?php if (count($pendingRequests) > 0): ?>
        <script>
            window.addEventListener('load', function() {
                alert('HYDROBOTICS Admin: You have <?php echo count($pendingRequests); ?> pending support request<?php echo count($pendingRequests) === 1 ? '' : 's'; ?>.');
            });
        </script>
    <?php endif; ?>
    <?php include 'footer.php'; ?>

