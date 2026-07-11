<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'HYDROBOTICS - Smart Tech. Reliable Service.'); ?></title>
    <link rel="stylesheet" href="css/theme.css">
</head>
<body>
    <nav>
        <div class="logo"> <a> HYDROBOTICS </a> </div>
        <ul class="nav-links">
            <li><a href="about.php">About</a></li>
            <li><a href="faq.php">FAQ</a></li>
            <li><a href="feed.php">Social Feed</a></li>
            <li><a href="login.php">Login</a></li>
        </ul>
    </nav>

    <button id="menu-toggle" class="menu-toggle">☰</button>
    <div id="floating-menu" class="floating-menu">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="faq.php">FAQ</a>
        <a href="feed.php">Social Feed</a>
        <a href="login.php">Login</a>
    </div>
