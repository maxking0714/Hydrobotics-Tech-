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
<?php
$page_title = 'Videos - HYDROBOTICS';
include 'header.php';
?>

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
<?php include 'footer.php'; ?>

