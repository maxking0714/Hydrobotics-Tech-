<?php
require_once 'admin_background.php';
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$role = $_SESSION['role'] ?? 'inventor';
$users = loadUsers();
$videos = loadVideos();
$error = '';

// Handle video upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $videoUrl = trim($_POST['video_url'] ?? '');
    $videoType = $_POST['video_type'] ?? 'short'; // 'short' or 'long'
    
    if (!$title) {
        $error = 'Please enter a video title.';
    } elseif (!$videoUrl) {
        $error = 'Please provide a video URL or embed code.';
    } else {
        $videos[] = [
            'id' => uniqid('vid_', true),
            'author' => $user,
            'role' => $role,
            'title' => $title,
            'description' => $description,
            'video_url' => $videoUrl,
            'video_type' => $videoType,
            'created_at' => date('Y-m-d H:i:s'),
            'views' => 0,
        ];
        saveVideos($videos);
        appendAdminLog("VIDEO_UPLOAD author={$user} title=" . substr($title, 0, 50) . " type={$videoType}");
        header('Location: videos.php');
        exit;
    }
}

// Sort videos by newest first
$sortedVideos = array_reverse($videos);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Videos - HYDROBOTICS</title>
    <link rel="stylesheet" href="css/theme.css?v=2">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #eef5fb; color: #102a43; }
        .topbar { background: #0d1b2a; color: #e0f7ff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; padding: 1rem 2rem; }
        .topbar .brand { font-size: 1.4rem; font-weight: 700; letter-spacing: 1px; }
        .topbar nav a { color: #e0f7ff; text-decoration: none; margin-left: 1.25rem; }
        .topbar nav a:hover { text-decoration: underline; }
        .layout { display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; padding: 2rem; max-width: 1270px; margin: 0 auto; }
        .panel { background: white; border-radius: 18px; padding: 1.5rem; box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08); }
        .panel h2 { margin-top: 0; }
        .video-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .video-card { border: 1px solid #dbe7f0; border-radius: 14px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
        .video-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1); }
        .video-thumbnail { width: 100%; height: 180px; background: #0d1b2a; display: flex; align-items: center; justify-content: center; color: #e0f7ff; font-size: 3rem; cursor: pointer; position: relative; }
        .video-badge { position: absolute; top: 10px; right: 10px; background: rgba(0, 0, 0, 0.7); color: white; padding: 0.4rem 0.7rem; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        .video-play { position: absolute; width: 60px; height: 60px; background: rgba(255, 255, 255, 0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; cursor: pointer; transition: background 0.2s; }
        .video-play:hover { background: white; }
        .video-info { padding: 1rem; }
        .video-title { font-weight: 600; margin-bottom: 0.5rem; display: -webkit-box; -webkit-line-clamp: 2; line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .video-meta { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: #627d98; }
        .video-author { font-weight: 600; }
        .upload-form { background: #f8fbff; border: 1px solid #dbe7f0; border-radius: 14px; padding: 1.25rem; margin-bottom: 1.5rem; }
        .upload-form h3 { margin-top: 0; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.4rem; font-weight: 600; color: #0d243a; }
        input, textarea, select { width: 100%; padding: 0.75rem 1rem; border: 1px solid #cfdce5; border-radius: 10px; font-size: 0.95rem; }
        textarea { resize: vertical; min-height: 80px; }
        .btn { display: inline-block; padding: 0.85rem 1.2rem; border-radius: 10px; background: #00a8e8; color: white; border: none; text-decoration: none; font-weight: 700; cursor: pointer; }
        .btn:hover { opacity: 0.95; }
        .error { background: #ffe3e3; color: #9d2b2b; border-radius: 10px; padding: 0.9rem; margin-bottom: 1rem; }
        .empty-state { text-align: center; padding: 2rem; color: #627d98; }
        .video-modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); align-items: center; justify-content: center; z-index: 200; padding: 1rem; }
        .video-modal.active { display: flex; }
        .video-player { background: #000; border-radius: 12px; max-width: 800px; width: 100%; }
        .video-player-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #0d1b2a; border-radius: 12px 12px 0 0; color: white; }
        .close-btn { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; }
        .video-iframe { width: 100%; height: 450px; border: none; border-radius: 0 0 12px 12px; }
        @media (max-width: 980px) { .layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">🎬 HYDROBOTICS Videos</div>
        <nav>
            <a href="feed.php">Feed</a>
            <a href="chat.php">Chat</a>
            <a href="dashboard.php">Dashboard</a>
        </nav>
    </header>
    <main class="layout">
        <section style="grid-column: 1 / -1;">
            <div class="panel">
                <h2>Video Library</h2>
                <?php if ($error): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="upload-form">
                    <h3>📹 Upload Your Video</h3>
                    <form method="POST" action="videos.php">
                        <div class="form-group">
                            <label for="title">Video Title *</label>
                            <input id="title" type="text" name="title" placeholder="Enter video title" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" placeholder="What's your video about?"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="video_url">Video URL or Embed Link *</label>
                            <input id="video_url" type="text" name="video_url" placeholder="e.g. https://youtube.com/watch?v=... or embed URL" required>
                        </div>
                        <div class="form-group">
                            <label for="video_type">Video Type</label>
                            <select id="video_type" name="video_type">
                                <option value="short">Short Video (under 2 min)</option>
                                <option value="long">Long Video (2+ min)</option>
                                <option value="tutorial">Tutorial</option>
                                <option value="demo">Product Demo</option>
                            </select>
                        </div>
                        <button type="submit" class="btn">Upload Video</button>
                    </form>
                </div>

                <?php if (count($sortedVideos) === 0): ?>
                    <div class="empty-state">
                        <p>No videos yet. Be the first to share!</p>
                    </div>
                <?php else: ?>
                    <div class="video-grid">
                        <?php foreach ($sortedVideos as $video): ?>
                            <div class="video-card">
                                <div class="video-thumbnail" onclick="playVideo('<?php echo htmlspecialchars(addslashes($video['video_url'])); ?>', '<?php echo htmlspecialchars(addslashes($video['title'])); ?>')">
                                    <div class="video-badge"><?php echo htmlspecialchars(ucfirst($video['video_type'])); ?></div>
                                    <div class="video-play">▶</div>
                                </div>
                                <div class="video-info">
                                    <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                                    <div class="video-meta">
                                        <span class="video-author"><?php echo htmlspecialchars($video['author']); ?></span>
                                        <span>👁 <?php echo $video['views']; ?></span>
                                    </div>
                                    <div style="font-size: 0.8rem; color: #9aa7b8; margin-top: 0.5rem;">
                                        <?php echo date('M d, Y', strtotime($video['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <div id="video-modal" class="video-modal">
        <div class="video-player">
            <div class="video-player-header">
                <span id="video-player-title" style="font-weight: 600;"></span>
                <button class="close-btn" onclick="closeVideo()">✕</button>
            </div>
            <iframe id="video-iframe" class="video-iframe" src="" allowfullscreen></iframe>
        </div>
    </div>

    <script>
        function playVideo(url, title) {
            const modal = document.getElementById('video-modal');
            const iframe = document.getElementById('video-iframe');
            const titleEl = document.getElementById('video-player-title');
            
            // Check if it's a YouTube URL and convert to embed
            let embedUrl = url;
            if (url.includes('youtube.com') || url.includes('youtu.be')) {
                const videoId = url.includes('watch?v=') 
                    ? url.split('v=')[1].split('&')[0] 
                    : url.split('youtu.be/')[1].split('?')[0];
                embedUrl = 'https://www.youtube.com/embed/' + videoId;
            } else if (url.includes('vimeo.com')) {
                const videoId = url.split('/').pop();
                embedUrl = 'https://player.vimeo.com/video/' + videoId;
            }
            
            iframe.src = embedUrl;
            titleEl.textContent = title;
            modal.classList.add('active');
        }

        function closeVideo() {
            document.getElementById('video-modal').classList.remove('active');
            document.getElementById('video-iframe').src = '';
        }

        // Close modal on background click
        document.getElementById('video-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideo();
            }
        });
    </script>
<?php include 'ai_chatbot_widget.php'; ?>
</body>
</html>
