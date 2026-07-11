<?php
require_once 'admin_background.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user'];
$profile = getUserProfile($username);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $updates = [];
    $updates['gender'] = $_POST['gender'] ?? 'not-specified';
    $updates['age'] = trim($_POST['age'] ?? '') ? (int)trim($_POST['age']) : null;
    $updates['location'] = trim($_POST['location'] ?? '');
    $updates['email'] = trim($_POST['email'] ?? '');
    $updates['phone'] = trim($_POST['phone'] ?? '');
    $updates['facebook'] = trim($_POST['facebook'] ?? '');
    $updates['whatsapp'] = trim($_POST['whatsapp'] ?? '');
    $updates['instagram'] = trim($_POST['instagram'] ?? '');
    $updates['show_gender'] = isset($_POST['show_gender']) ? 1 : 0;
    $updates['show_age'] = isset($_POST['show_age']) ? 1 : 0;
    $updates['show_location'] = isset($_POST['show_location']) ? 1 : 0;
    $isError = false;
    
    if ($updates['email'] !== '' && !filter_var($updates['email'], FILTER_VALIDATE_EMAIL)) {
        $message = 'Enter a valid email address.';
        $isError = true;
    } elseif ($updates['whatsapp'] !== '' && !preg_match('/^[\d+\-\s()]+$/', $updates['whatsapp'])) {
        $message = 'Enter a valid WhatsApp number.';
        $isError = true;
    } else {
        if (updateUserProfile($username, $updates)) {
            $message = 'Profile updated successfully!';
            $profile = getUserProfile($username);
            appendAdminLog("PROFILE_UPDATE username={$username}");
        } else {
            $message = 'Failed to update profile.';
            $isError = true;
        }
    }
}
?>
<?php
$page_title = 'Settings - HYDROBOTICS';
include 'header.php';
?>

    <div class="page">
        <div class="top">
            <div>
                <h1>Account Settings</h1>
                <p>Manage your profile and privacy preferences</p>
            </div>
            <a class="back-link" href="dashboard.php">Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="<?php echo !empty($isError) ? 'error' : 'success'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="profile-info">
                <strong>Username:</strong> <?php echo htmlspecialchars($username); ?><br>
                <strong>Account Type:</strong> <?php echo htmlspecialchars($profile['role'] ?? 'Inventor'); ?><br>
                <strong>Member Since:</strong> <?php echo isset($profile['created_at']) ? date('M d, Y', strtotime($profile['created_at'])) : 'Unknown'; ?>
            </div>

            <h2>Profile Information</h2>
            <form method="POST" action="settings.php">
                <input type="hidden" name="action" value="update_profile">
                
                <label for="gender">Gender</label>
                <select id="gender" name="gender">
                    <option value="not-specified" <?php echo ($profile['gender'] ?? 'not-specified') === 'not-specified' ? 'selected' : ''; ?>>Not specified</option>
                    <option value="male" <?php echo ($profile['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo ($profile['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?php echo ($profile['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>

                <label for="age">Age</label>
                <input id="age" type="number" name="age" min="13" max="120" placeholder="Your age" value="<?php echo htmlspecialchars($profile['age'] ?? ''); ?>">

                <label for="location">Location / City</label>
                <input id="location" type="text" name="location" placeholder="e.g. New York, San Francisco" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>">

                <label for="email">Email</label>
                <input id="email" type="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>">

                <label for="phone">Cellphone Number</label>
                <input id="phone" type="tel" name="phone" placeholder="e.g. +1234567890" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">

                <label for="facebook">Facebook Profile URL</label>
                <input id="facebook" type="text" name="facebook" placeholder="https://facebook.com/yourprofile" value="<?php echo htmlspecialchars($profile['facebook'] ?? ''); ?>">

                <label for="whatsapp">WhatsApp Number</label>
                <input id="whatsapp" type="text" name="whatsapp" placeholder="e.g. +1234567890" value="<?php echo htmlspecialchars($profile['whatsapp'] ?? ''); ?>">

                <label for="instagram">Instagram Profile URL</label>
                <input id="instagram" type="text" name="instagram" placeholder="https://instagram.com/yourprofile" value="<?php echo htmlspecialchars($profile['instagram'] ?? ''); ?>">

                <h2 style="margin-top: 2rem; margin-bottom: 1rem;">Privacy Settings</h2>
                <p style="color: #627d98; margin-bottom: 1rem;">Choose what information you want to show on your profile</p>

                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="show_gender" name="show_gender" value="1" <?php echo ($profile['show_gender'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="show_gender">Show my gender on my profile</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="show_age" name="show_age" value="1" <?php echo ($profile['show_age'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="show_age">Show my age on my profile</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="show_location" name="show_location" value="1" <?php echo ($profile['show_location'] ?? 0) ? 'checked' : ''; ?>>
                        <label for="show_location">Show my location on my profile (helps find local posts)</label>
                    </div>
                </div>

                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>
    </div>
<?php include 'footer.php'; ?>
