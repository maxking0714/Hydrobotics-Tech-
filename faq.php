<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FAQ - HYDROBOTICS</title>
    <link rel="stylesheet" href="css/theme.css?v=2">
    <style>
        body { margin: 0; background: #f4f8ff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #102a43; }
        .page { max-width: 980px; margin: 0 auto; padding: 2rem; }
        h1 { margin-bottom: 0.5rem; }
        .question { margin-top: 1.5rem; }
        .question h2 { font-size: 1.2rem; margin-bottom: 0.5rem; }
        .question p { margin: 0; line-height: 1.75; color: #334e68; }
        .nav { margin-bottom: 2rem; }
        .nav a { color: #00a8e8; text-decoration: none; margin-right: 1rem; }
        .nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="page">
        <div class="nav">
            <a href="HOMEPAGE.PHP">Home</a>
            <a href="about.php">About</a>
            <a href="login.php">Login</a>
            <a href="feed.php">Social Feed</a>
        </div>
        <h1>Frequently Asked Questions</h1>
        <div class="question">
            <h2>How do I register?</h2>
            <p>Go to the Login page, choose Register, enter a username and password, select an account type, and submit. Your registration is saved and recorded in the admin log.</p>
        </div>
        <div class="question">
            <h2>Can I post updates and recruiting messages?</h2>
            <p>Yes. After logging in, visit the HYDROBOTICS Social feed to create posts, share ideas, and connect with companies or inventors.</p>
        </div>
        <div class="question">
            <h2>What information is recorded?</h2>
            <p>The system records each registration and each feed post in the admin log file. That log is visible to administrators on the Admin page.</p>
        </div>
        <div class="question">
            <h2>How do I access my dashboard?</h2>
            <p>After logging in, the dashboard page displays your profile, session details, and a summary of your posts and community activity.</p>
        </div>
    </div>
<?php include 'ai_chatbot_widget.php'; ?>
</body>
</html>
