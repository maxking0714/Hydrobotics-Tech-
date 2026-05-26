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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login - HYDROBOTICS</title>
    <link rel="stylesheet" href="css/theme.css?v=2">
    <style>
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f8ff; color: #102a43; }
        .page { max-width: 480px; margin: 6rem auto; padding: 2rem; }
        .card { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 10px 30px rgba(15,23,42,0.06); }
        label { display:block; margin-top:0.75rem; color:#0d243a; font-weight:600; }
        input { width:100%; padding:0.85rem 1rem; border:1px solid #e6eef6; border-radius:10px; margin-top:0.35rem; }
        button { margin-top:1rem; width:100%; padding:0.9rem; border:none; background:#0d6efd; color:white; border-radius:10px; font-weight:700; cursor:pointer; }
        .error { background:#ffe3e3; color:#9d2b2b; padding:0.9rem; border-radius:8px; margin-top:0.75rem; }
        .note { color:#627d98; margin-top:0.75rem; font-size:0.95rem; }
    </style>
</head>
<body>
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
<?php include 'ai_chatbot_widget.php'; ?>
</body>
</html>
