<?php
require_once 'admin_background.php';
session_start();

$error = '';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

function redirectToDashboard($username, $role) {
    $_SESSION['user'] = $username;
    $_SESSION['role'] = $role;
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($action === 'register') {
        $role = $_POST['role'] ?? 'inventor';
        // Prevent creating admin accounts through public registration
        $allowed = ['inventor', 'company', 'person'];
        if (!in_array($role, $allowed, true)) {
            $role = 'inventor';
        }
        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password to register.';
        } elseif (trim($_POST['phone'] ?? '') === '') {
            $error = 'Please enter your cellphone number for verification.';
        } else {
            $users = loadUsers();
            foreach ($users as $existingUser) {
                if (strtolower($existingUser['username']) === strtolower($username)) {
                    $error = 'That username is already registered. Choose a different one.';
                    break;
                }
            }
            if ($error === '') {
                $gender = $_POST['gender'] ?? 'not-specified';
                $age = trim($_POST['age'] ?? '');
                $location = trim($_POST['location'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $showGender = isset($_POST['show_gender']) ? 1 : 0;
                $showAge = isset($_POST['show_age']) ? 1 : 0;
                $showLocation = isset($_POST['show_location']) ? 1 : 0;
                
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                }

                if ($error === '') {
                    $users[] = [
                        'username' => $username,
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                        'created_at' => date('Y-m-d H:i:s'),
                        'gender' => $gender,
                        'age' => $age ? (int)$age : null,
                        'location' => $location,
                        'phone' => $phone,
                        'email' => $email,
                        'phone_verified' => 0,
                        'show_gender' => $showGender,
                        'show_age' => $showAge,
                        'show_location' => $showLocation,
                        'followers' => [],
                        'following' => [],
                    ];
                    saveUsers($users);
                    appendAdminLog("REGISTER username={$username} role={$role} location={$location} phone={$phone} email={$email}");
                    $_SESSION['verification_notice'] = "Verification sent to {$phone}.";
                    redirectToDashboard($username, $role);
                }
            }
        }
    }

    if ($action === 'login') {
        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password to login.';
        } else {
            $users = loadUsers();
            $found = false;
            foreach ($users as $existingUser) {
                if (strtolower($existingUser['username']) === strtolower($username) && password_verify($password, $existingUser['password'])) {
                    $found = true;
                    $role = $existingUser['role'] ?? 'inventor';
                    redirectToDashboard($existingUser['username'], $role);
                }
            }
            if (!$found) {
                $error = 'Login failed. Check your username and password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login / Register - HYDROBOTICS</title>
    <link rel="stylesheet" href="css/theme.css?v=2">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f7fb; color: #102a43; }
        .page { max-width: 1040px; margin: 0 auto; padding: 2rem; }
        .header { text-align: center; margin-bottom: 2rem; }
        .card { background: white; border-radius: 20px; box-shadow: 0 20px 50px rgba(16, 49, 86, 0.08); overflow: hidden; display: grid; grid-template-columns: 1fr 1fr; }
        .panel { padding: 2rem; }
        .panel h2 { margin-bottom: 1rem; color: #0d243a; }
        .panel p { margin-bottom: 1.5rem; line-height: 1.7; color: #334e68; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #0d243a; }
        input, select { width: 100%; padding: 0.9rem 1rem; margin-bottom: 1rem; border: 1px solid #dfe7ef; border-radius: 14px; }
        button { width: 100%; padding: 0.95rem 1rem; border: none; color: white; background: #00a8e8; border-radius: 14px; font-weight: 700; cursor: pointer; }
        button:hover { opacity: 0.95; }
        .error { background: #ffe3e3; color: #9d2b2b; border-radius: 14px; padding: 1rem; margin-bottom: 1rem; }
        .note { color: #627d98; font-size: 0.95rem; }
        .links { margin-top: 1.5rem; }
        .links a { color: #00a8e8; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        @media (max-width: 900px) { .card { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>Login or Register</h1>
            <p>Use HYDROBOTICS to access your dashboard, post to the feed, and join the inventor community.</p>
        </div>
        <div class="card">
            <div class="panel">
                <h2>Login</h2>
                <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" action="login.php">
                    <input type="hidden" name="action" value="login">
                    <label for="login-username">Username</label>
                    <input id="login-username" type="text" name="username" required>
                    <label for="login-password">Password</label>
                    <input id="login-password" type="password" name="password" required>
                    <button type="submit">Login</button>
                </form>
            </div>
            <div class="panel" style="background: #f6fbff;">
                <h2>Register</h2>
                <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form method="POST" action="login.php">
                    <input type="hidden" name="action" value="register">
                    <label for="register-username">Username</label>
                    <input id="register-username" type="text" name="username" required>
                    <label for="register-password">Password</label>
                    <input id="register-password" type="password" name="password" required>
                    <label for="register-email">Email (optional)</label>
                    <input id="register-email" type="email" name="email" placeholder="you@example.com">
                    <label for="role">Account Type</label>
                    <select id="role" name="role">
                        <option value="inventor">Inventor</option>
                        <option value="company">Company</option>
                        <option value="person">Person</option>
                    </select>
                    <label for="gender">Gender (Optional)</label>
                    <select id="gender" name="gender">
                        <option value="not-specified">Not specified</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                    <label for="age">Age (Optional)</label>
                    <input id="age" type="number" name="age" min="13" max="120" placeholder="Your age">
                    <label for="phone">Cellphone Number</label>
                    <input id="phone" type="tel" name="phone" placeholder="e.g. +1234567890" required>
                    <label for="location">Location/City (Optional)</label>
                    <div style="display:flex; gap:0.75rem; align-items:center; margin-bottom:0.75rem;">
                        <input id="location" type="text" name="location" placeholder="e.g. New York, San Francisco" style="flex:1;">
                        <button type="button" id="use-gps" style="padding:0.9rem 1rem; border:none; background:#00a8e8; color:white; border-radius:12px; cursor:pointer;">Use GPS</button>
                    </div>
                    <label style="margin-bottom: 1rem;">
                        <input type="checkbox" name="show_gender" value="1" style="width: auto; margin-right: 0.5rem;">
                        Show my gender in my profile
                    </label>
                    <label style="margin-bottom: 1rem;">
                        <input type="checkbox" name="show_age" value="1" style="width: auto; margin-right: 0.5rem;">
                        Show my age in my profile
                    </label>
                    <label style="margin-bottom: 1rem;">
                        <input type="checkbox" name="show_location" value="1" style="width: auto; margin-right: 0.5rem;">
                        Show my location in my profile
                    </label>
                    <button type="submit">Register</button>
                </form>
                <p class="note">All registrations are recorded in the HYDROBOTICS admin log.</p>
            </div>
        </div>
        <div class="links">
            <a href="HOMEPAGE.PHP">Return to Home</a>
        </div>
        <script>
            document.getElementById('use-gps').addEventListener('click', function() {
                var button = this;
                if (!navigator.geolocation) {
                    alert('Geolocation is not supported by your browser.');
                    return;
                }
                button.disabled = true;
                button.textContent = 'Detecting...';

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        var lat = position.coords.latitude;
                        var lon = position.coords.longitude;
                        var apiUrl = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lon);

                        fetch(apiUrl)
                            .then(function(response) {
                                return response.json();
                            })
                            .then(function(data) {
                                var address = data.address || {};
                                var locationText = address.city || address.town || address.village || address.state || address.country || (lat + ', ' + lon);
                                document.getElementById('location').value = locationText;
                            })
                            .catch(function() {
                                document.getElementById('location').value = lat + ', ' + lon;
                            })
                            .finally(function() {
                                button.disabled = false;
                                button.textContent = 'Use GPS';
                            });
                    },
                    function(error) {
                        alert('Unable to use GPS: ' + (error.message || 'Permission denied'));
                        button.disabled = false;
                        button.textContent = 'Use GPS';
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            });
        </script>
    </div>
<?php include 'ai_chatbot_widget.php'; ?>
</body>
</html>
