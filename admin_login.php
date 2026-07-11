<?php
require_once 'admin_background.php';
session_start();

$error = '';

// Admin login requires the fixed account hydro1234 with password 1234.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($code !== 'hydro1234') {
        $error = 'Invalid admin code. Use hydro1234.';
        appendAdminLog("ADMIN_LOGIN_FAILED code={$code} reason=invalid_admin_code");
    } elseif ($password !== '1234') {
        $error = 'Incorrect admin password.';
        appendAdminLog("ADMIN_LOGIN_FAILED code={$code} reason=bad_password");
    } else {
        // Successful admin login -- set session and redirect
        $_SESSION['user'] = $code;
        $_SESSION['role'] = 'admin';
        appendAdminLog("ADMIN_LOGIN_SUCCESS code={$code}");
        header('Location: admin.php');
        exit;
    }
}
?>
<?php
$page_title = 'Admin Login - HYDROBOTICS';
include 'header.php';
?>

    <div class="page">
        <div class="card">
            <h2>Administrator Login</h2>
            <p class="note">Enter the exact admin code <strong>hydro1234</strong> and password <strong>1234</strong>.</p>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="admin_login.php">
                <label for="code">HYDRO Code</label>
                <input id="code" name="code" type="text" placeholder="HydroXXXX" required>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="Password" required>
                <button type="submit">Sign in as Admin</button>
            </form>
        </div>
    </div>
<?php include 'footer.php'; ?>

