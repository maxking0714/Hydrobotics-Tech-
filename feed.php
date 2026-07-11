<?php
require_once 'admin_background.php';
session_start();

$user = $_SESSION['user'] ?? null;
$role = $_SESSION['role'] ?? null;
$error = '';
$posts = loadPosts();
$users = loadUsers();

// Load current user profile for location-based sorting
$userProfile = $user ? getUserProfile($user) : null;
$userLocation = $userProfile ? ($userProfile['location'] ?? '') : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? 'post';

    if (!$user) {
        $error = 'You must be logged in to use the HYDROBOTICS community features.';
    } elseif ($formType === 'post') {
        $content = trim($_POST['content'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $allowShare = isset($_POST['allow_share']) ? 1 : 0;
        $imageText = trim($_POST['image_text'] ?? '');
        $musicFileName = '';
        $musicTitle = '';
        $mediaFileName = '';
        $mediaType = '';

        $hasMedia = !empty($_FILES['story_media']['name']);
        $hasMusic = !empty($_FILES['background_music']['name']);
        $hasContent = $title !== '' || $content !== '';

        if (!$hasContent && !$hasMedia && !$hasMusic) {
            $error = 'Please provide a title, message, or upload a story media or song.';
        } else {
            $uploadDir = __DIR__ . '/data/uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if ($hasMedia && $_FILES['story_media']['error'] === UPLOAD_ERR_OK) {
                $originalName = $_FILES['story_media']['name'];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                $allowedImages = ['jpg','jpeg','png','gif','webp'];
                $allowedVideos = ['mp4','webm','ogg'];
                if (in_array($ext, $allowedImages, true)) {
                    $mediaType = 'image';
                } elseif (in_array($ext, $allowedVideos, true)) {
                    $mediaType = 'video';
                } else {
                    $error = 'Only image and video files are allowed for stories.';
                }
                if (!$error) {
                    $mediaFileName = uniqid('media_', true) . '.' . $ext;
                    move_uploaded_file($_FILES['story_media']['tmp_name'], $uploadDir . '/' . $mediaFileName);
                }
            }
            if ($hasMusic && $_FILES['background_music']['error'] === UPLOAD_ERR_OK) {
                $musicName = $_FILES['background_music']['name'];
                $musicExt = strtolower(pathinfo($musicName, PATHINFO_EXTENSION));
                $allowedAudio = ['mp3','wav','ogg','m4a','aac'];
                if (in_array($musicExt, $allowedAudio, true)) {
                    $musicTitle = pathinfo($musicName, PATHINFO_FILENAME);
                    $musicFileName = uniqid('audio_', true) . '.' . $musicExt;
                    move_uploaded_file($_FILES['background_music']['tmp_name'], $uploadDir . '/' . $musicFileName);
                } else {
                    $error = 'Only audio files are allowed for background music.';
                }
            }

            if (!$error) {
                $posts[] = [
                    'id' => uniqid('post_', true),
                    'author' => $user,
                    'role' => $role,
                    'title' => $title,
                    'content' => $content,
                    'image_text' => $imageText,
                    'background_music_file' => $musicFileName,
                    'background_music_name' => $musicTitle,
                    'media_file' => $mediaFileName,
                    'media_type' => $mediaType,
                    'likes' => [],
                    'comments' => [],
                    'allow_share' => $allowShare,
                    'shares' => 0,
                    'reposts' => 0,
                    'repost_of' => null,
                    'shared_from' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                savePosts($posts);
                appendAdminLog("POST author={$user} title=" . substr($title, 0, 50) . " allow_share={$allowShare} media_type={$mediaType}");
                header('Location: feed.php');
                exit;
            }
        }
    } elseif ($formType === 'like') {
        $postId = $_POST['post_id'] ?? '';
        foreach ($posts as &$post) {
            if (isset($post['id']) && $post['id'] === $postId) {
                $likes = $post['likes'] ?? [];
                if (!in_array($user, $likes, true)) {
                    $likes[] = $user;
                    appendAdminLog("LIKE user={$user} post={$postId}");
                } else {
                    $likes = array_filter($likes, fn($like) => $like !== $user);
                    appendAdminLog("UNLIKE user={$user} post={$postId}");
                }
                $post['likes'] = array_values($likes);
                break;
            }
        }
        savePosts($posts);
        header('Location: feed.php');
        exit;
    } elseif ($formType === 'comment') {
        $postId = $_POST['post_id'] ?? '';
        $commentText = trim($_POST['comment_text'] ?? '');
        if ($commentText === '') {
            $error = 'Enter a comment before posting.';
        } else {
            foreach ($posts as &$post) {
                if (isset($post['id']) && $post['id'] === $postId) {
                    if (!isset($post['comments']) || !is_array($post['comments'])) {
                        $post['comments'] = [];
                    }
                    $post['comments'][] = [
                        'id' => uniqid('comment_', true),
                        'author' => $user,
                        'text' => $commentText,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    appendAdminLog("COMMENT user={$user} post={$postId}");
                    break;
                }
            }
            savePosts($posts);
            header('Location: feed.php');
            exit;
        }
    } elseif ($formType === 'edit_story') {
        $postId = $_POST['post_id'] ?? '';
        $newTitle = trim($_POST['title'] ?? '');
        $newContent = trim($_POST['content'] ?? '');
        $newImageText = trim($_POST['image_text'] ?? '');
        $updated = false;

        foreach ($posts as &$post) {
            if (isset($post['id'], $post['author']) && $post['id'] === $postId && $post['author'] === $user) {
                $post['title'] = $newTitle;
                $post['content'] = $newContent;
                $post['image_text'] = $newImageText;
                appendAdminLog("EDIT_STORY user={$user} post={$postId}");
                $updated = true;
                break;
            }
        }
        if ($updated) {
            savePosts($posts);
        }
        header('Location: feed.php');
        exit;
    } elseif ($formType === 'follow_user' || $formType === 'unfollow_user') {
        $targetUser = $_POST['target_user'] ?? '';
        if ($targetUser && $targetUser !== $user) {
            if ($formType === 'follow_user') {
                followUser($user, $targetUser);
            } else {
                unfollowUser($user, $targetUser);
            }
        }
        header('Location: feed.php');
        exit;
    } elseif ($formType === 'repost' || $formType === 'share') {
        $postId = $_POST['post_id'] ?? '';
        foreach ($posts as &$post) {
            if (isset($post['id']) && $post['id'] === $postId && !empty($post['allow_share'])) {
                if ($formType === 'repost') {
                    $post['reposts'] = ($post['reposts'] ?? 0) + 1;
                } else {
                    $post['shares'] = ($post['shares'] ?? 0) + 1;
                }
                $newPost = [
                    'id' => uniqid('post_', true),
                    'author' => $user,
                    'role' => $role,
                    'title' => $formType === 'repost' ? '[Repost] ' . $post['title'] : '[Share] ' . $post['title'],
                    'content' => $post['content'],
                    'likes' => [],
                    'comments' => [],
                    'allow_share' => 0,
                    'shares' => 0,
                    'reposts' => 0,
                    'repost_of' => $formType === 'repost' ? $postId : null,
                    'shared_from' => $formType === 'share' ? $postId : null,
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                $posts[] = $newPost;
                appendAdminLog(strtoupper($formType) . " user={$user} post={$postId}");
                break;
            }
        }
        savePosts($posts);
        header('Location: feed.php');
        exit;
    }
}

$sortedPosts = array_reverse($posts);

// Prioritize posts from user's location if they have shared it
if ($userLocation && $userProfile && ($userProfile['show_location'] ?? 0)) {
    $localPosts = [];
    $otherPosts = [];
    
    foreach ($sortedPosts as $post) {
        $postAuthorProfile = getUserProfile($post['author']);
        $postLocation = $postAuthorProfile ? ($postAuthorProfile['location'] ?? '') : '';
        $showPostLocation = $postAuthorProfile ? ($postAuthorProfile['show_location'] ?? 0) : 0;
        
        if ($showPostLocation && $postLocation && strtolower(trim($postLocation)) === strtolower(trim($userLocation))) {
            $localPosts[] = $post;
        } else {
            $otherPosts[] = $post;
        }
    }
    
    $sortedPosts = array_merge($localPosts, $otherPosts);
}
?>
<?php
$page_title = 'HYDROBOTICS Social Feed';
include 'header.php';
?>

    <main class="layout">
        <section class="panel">
            <div class="section-label">Community Feed</div>
            <h2>HYDROBOTICS Activity</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!$user): ?>
                <div class="status-box">
                    <p>Please <a href="login.php">login</a> to create posts and join discussions.</p>
                </div>
            <?php else: ?>
                <form class="status-box" method="POST" action="feed.php" enctype="multipart/form-data">
                    <input type="hidden" name="form_type" value="post">
                    <div class="section-label">Create Story</div>
                    <input type="text" name="title" placeholder="Post title">
                    <textarea id="post-content" name="content" placeholder="Share your latest discovery, idea, or opportunity..."></textarea>
                    <button type="button" class="emoji-toggle-btn" onclick="toggleEmojiPicker()">😊 Add Emoji</button>
                    <div style="display:flex; flex-wrap:wrap; gap:1rem; margin-top:1rem;">
                        <div style="flex:1; min-width:220px;">
                            <label for="story_media">Upload Image or Video</label>
                            <input id="story_media" type="file" name="story_media" accept="image/*,video/*" style="width:100%; padding:0.7rem 0;" />
                        </div>
                        <div style="flex:1; min-width:220px;">
                            <label for="image_text">Text on Image / Video</label>
                            <input id="image_text" type="text" name="image_text" placeholder="Overlay text for your story" style="width:100%;" />
                        </div>
                    </div>
                    <div style="display:flex; flex-wrap:wrap; gap:1rem; margin-top:1rem;">
                        <div style="flex:1; min-width:220px; position:relative;">
                            <label class="section-label" for="background_music_button">Upload Song</label>
                            <input id="background_music" type="file" name="background_music" accept="audio/*" style="display:none;" onchange="updateMusicLabel()" />
                            <button type="button" id="background_music_button" class="btn" onclick="document.getElementById('background_music').click();" style="width:100%; text-align:left;">
                                <span id="background_music_label">Choose audio file...</span>
                            </button>
                            <p style="margin:0.5rem 0 0; color:#627d98; font-size:0.9rem;">One audio file only. MP3, WAV, or other browser-friendly formats.</p>
                        </div>
                    </div>
                    <label style="display:block; margin-bottom: 0.85rem; color:#627d98; font-size:0.85rem; margin-top:1rem;">
                        <input type="checkbox" name="allow_share" value="1" style="margin-right:0.5rem;"> Allow other users to repost or share this post
                    </label>
                    <div class="status-row">
                        <span class="status-pill">Post</span>
                        <span class="status-pill">Story</span>
                        <span class="status-pill">Idea</span>
                        <span class="status-pill">Update</span>
                    </div>
                    <button type="submit" class="btn">Publish Story</button>
                </form>
            <?php endif; ?>

            <?php if (count($sortedPosts) === 0): ?>
                <div class="feed-card">
                    <div class="card-body">No posts yet. Start the conversation by sharing your first update.</div>
                </div>
            <?php else: ?>
                <?php foreach ($sortedPosts as $post): ?>
                    <?php $postAuthorProfile = getUserProfile($post['author']); ?>
                    <?php $displayInfo = getUserDisplayInfo($post['author']); ?>
                    <?php $likedByUser = in_array($user, $post['likes'] ?? [], true); ?>
                    <div class="feed-card">
                        <div class="card-header">
                            <?php 
                                $profilePicPath = '';
                                if (!empty($postAuthorProfile['profile_picture'])) {
                                    $profilePicPath = 'data/profile_pictures/' . htmlspecialchars($postAuthorProfile['profile_picture']);
                                }
                            ?>
                            <?php if ($profilePicPath && file_exists($profilePicPath)): ?>
                                <img src="<?php echo $profilePicPath; ?>" alt="<?php echo htmlspecialchars($post['author']); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #dbe7f0;">
                            <?php else: ?>
                                <div class="avatar"><?php echo htmlspecialchars(substr($post['author'], 0, 2)); ?></div>
                            <?php endif; ?>
                            <div class="author">
                                <strong><?php echo htmlspecialchars($post['author']); ?></strong>
                                <small>
                                    <?php echo htmlspecialchars($post['role'] ?? 'member'); ?>
                                    <?php if (isset($displayInfo['location'])): ?>
                                        · 📍 <?php echo htmlspecialchars($displayInfo['location']); ?>
                                    <?php endif; ?>
                                    <?php if (isset($displayInfo['age'])): ?>
                                        · <span title="Age">🎂 <?php echo htmlspecialchars($displayInfo['age']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($postAuthorProfile['followers'])): ?>
                                        · 👥 <?php echo count($postAuthorProfile['followers']); ?> followers
                                    <?php endif; ?>
                                    · <?php echo htmlspecialchars($post['created_at']); ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            <?php if (!empty($post['media_file'])): ?>
                                <div style="padding:0 1.25rem 1rem;">
                                    <?php if (!empty($post['media_type']) && $post['media_type'] === 'image'): ?>
                                        <div style="position:relative; border-radius:16px; overflow:hidden;">
                                            <img src="data/uploads/<?php echo htmlspecialchars($post['media_file']); ?>" alt="story image" style="width:100%; display:block;">
                                            <?php if (!empty($post['image_text'])): ?>
                                                <div style="position:absolute; left:1rem; bottom:1rem; right:1rem; background:rgba(0,0,0,0.5); color:white; border-radius:12px; padding:0.8rem 1rem; font-size:0.95rem; line-height:1.3; text-align:center;">
                                                    <?php echo nl2br(htmlspecialchars($post['image_text'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif (!empty($post['media_type']) && $post['media_type'] === 'video'): ?>
                                        <div style="position:relative; border-radius:16px; overflow:hidden;">
                                            <video class="post-video" controls playsinline preload="metadata" src="data/uploads/<?php echo htmlspecialchars($post['media_file']); ?>"></video>
                                            <?php if (!empty($post['image_text'])): ?>
                                                <div style="position:absolute; left:1rem; bottom:1rem; right:1rem; background:rgba(0,0,0,0.5); color:white; border-radius:12px; padding:0.8rem 1rem; font-size:0.95rem; line-height:1.3; text-align:center;">
                                                    <?php echo nl2br(htmlspecialchars($post['image_text'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif (!empty($post['image']) && (!isset($post['flagged']) || $post['display_image'])): ?>
                                <div style="padding:0 1.25rem 1rem;">
                                    <img src="data/uploads/<?php echo htmlspecialchars($post['image']); ?>" alt="post image" style="max-width:100%; border-radius:10px;">
                                </div>
                            <?php elseif (!empty($post['image']) && isset($post['flagged']) && !$post['display_image']): ?>
                                <div style="padding:0 1.25rem 1rem; color:#9aa7b8;">[Image removed for review]</div>
                            <?php endif; ?>
                            <?php if (!empty($post['document'])): ?>
                                <div style="padding:0 1.25rem 1rem;">
                                    <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $post['document'])): ?>
                                        <img src="data/uploads/<?php echo htmlspecialchars($post['document']); ?>" alt="document preview" style="max-width:100%; border-radius:10px;">
                                    <?php else: ?>
                                        <a href="data/uploads/<?php echo htmlspecialchars($post['document']); ?>" target="_blank" class="btn">Open Attachment</a>
                                        <?php if (!empty($post['document_label'])): ?>
                                            <div style="margin-top:0.7rem; color:#627d98; font-size:0.95rem;"><?php echo htmlspecialchars($post['document_label']); ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($post['background_music_file']) || !empty($post['background_music_url'])): ?>
                                <div style="padding:0 1.25rem 1rem;">
                                    <div style="margin-bottom:0.5rem; color:#627d98; font-size:0.95rem;">
                                        <?php echo htmlspecialchars($post['background_music_name'] ?: ($post['background_music_title'] ?? 'Background song')); ?>
                                    </div>
                                    <audio controls style="width:100%;">
                                        <?php if (!empty($post['background_music_file'])): ?>
                                            <source src="data/uploads/<?php echo htmlspecialchars($post['background_music_file']); ?>">
                                        <?php else: ?>
                                            <source src="<?php echo htmlspecialchars($post['background_music_url']); ?>">
                                        <?php endif; ?>
                                        Your browser does not support audio playback.
                                    </audio>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-actions" style="display:flex; flex-wrap:wrap; gap:0.75rem; align-items:center;">
                            <form method="POST" action="feed.php" style="margin:0;">
                                <input type="hidden" name="form_type" value="like">
                                <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>">
                                <button class="btn" type="submit" style="padding:0.6rem 1rem; font-size:0.95rem; background: <?php echo $likedByUser ? '#0d3b66' : '#00a8e8'; ?>;">
                                    <?php echo $likedByUser ? 'Unlike' : 'Like'; ?> (<?php echo count($post['likes'] ?? []); ?>)
                                </button>
                            </form>
                            <form method="POST" action="feed.php" style="margin:0;">
                                <input type="hidden" name="form_type" value="<?php echo userIsFollowing($user, $post['author']) ? 'unfollow_user' : 'follow_user'; ?>">
                                <input type="hidden" name="target_user" value="<?php echo htmlspecialchars($post['author']); ?>">
                                <button class="btn" type="submit" style="padding:0.6rem 1rem; font-size:0.95rem; background:#764ba2;">
                                    <?php echo userIsFollowing($user, $post['author']) ? 'Unfollow' : 'Follow'; ?> <?php echo htmlspecialchars($post['author']); ?>
                                </button>
                            </form>
                            <?php if (!empty($post['allow_share'])): ?>
                                <form method="POST" action="feed.php" style="margin:0;">
                                    <input type="hidden" name="form_type" value="repost">
                                    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>">
                                    <button class="btn" type="submit" style="padding:0.6rem 1rem; font-size:0.95rem; background:#43e97b;">Repost</button>
                                </form>
                                <form method="POST" action="feed.php" style="margin:0;">
                                    <input type="hidden" name="form_type" value="share">
                                    <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>">
                                    <button class="btn" type="submit" style="padding:0.6rem 1rem; font-size:0.95rem; background:#f093fb;">Share</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($user === $post['author']): ?>
                                <button class="btn btn-outline" type="button" onclick="toggleEditPost('<?php echo htmlspecialchars($post['id']); ?>')" style="padding:0.6rem 1rem; font-size:0.95rem;">Edit Story</button>
                            <?php endif; ?>
                            <span style="font-weight:700;">💬 <?php echo count($post['comments'] ?? []); ?></span>
                            <span style="font-weight:700;">🔁 <?php echo $post['reposts'] ?? 0; ?></span>
                            <span style="font-weight:700;">📤 <?php echo $post['shares'] ?? 0; ?></span>
                        </div>
                        <div style="padding: 1rem 1.25rem 1.25rem;">
                            <?php if (!empty($post['comments'])): ?>
                                <?php foreach ($post['comments'] as $comment): ?>
                                    <div style="margin-bottom:0.85rem; padding:0.85rem; border-radius:12px; background:#f8fbff;">
                                        <strong><?php echo htmlspecialchars($comment['author']); ?></strong>
                                        <p style="margin:0.35rem 0 0; color:#334e68;"><?php echo nl2br(htmlspecialchars($comment['text'])); ?></p>
                                        <div style="font-size:0.8rem; color:#9aa7b8; margin-top:0.4rem;"><?php echo htmlspecialchars($comment['created_at']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <form method="POST" action="feed.php" style="margin-top:1rem;">
                                <input type="hidden" name="form_type" value="comment">
                                <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>">
                                <textarea name="comment_text" placeholder="Write a comment..." required style="width:100%; border:1px solid #dbe7f0; border-radius:14px; padding:0.9rem; min-height:70px; margin-bottom:0.75rem;"></textarea>
                                <button class="btn" type="submit" style="padding:0.7rem 1rem; font-size:0.95rem;">Comment</button>
                            </form>
                            <?php if ($user === $post['author']): ?>
                                <div id="edit-<?php echo htmlspecialchars($post['id']); ?>" class="edit-story-panel" style="display:none; margin-top:1rem; padding:1rem; border:1px solid #dbe7f0; border-radius:14px; background:#f8fbff;">
                                    <h4 style="margin-top:0;">Edit Story</h4>
                                    <form method="POST" action="feed.php">
                                        <input type="hidden" name="form_type" value="edit_story">
                                        <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($post['id']); ?>">
                                        <label style="display:block; margin-bottom:0.5rem; font-weight:700;">Title</label>
                                        <input type="text" name="title" value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>" style="width:100%; margin-bottom:0.85rem; padding:0.8rem; border:1px solid #dbe7f0; border-radius:12px;">
                                        <label style="display:block; margin-bottom:0.5rem; font-weight:700;">Caption</label>
                                        <textarea name="content" style="width:100%; min-height:90px; margin-bottom:0.85rem; padding:0.8rem; border:1px solid #dbe7f0; border-radius:12px;"><?php echo htmlspecialchars($post['content'] ?? ''); ?></textarea>
                                        <label style="display:block; margin-bottom:0.5rem; font-weight:700;">Text on Image / Video</label>
                                        <input type="text" name="image_text" value="<?php echo htmlspecialchars($post['image_text'] ?? ''); ?>" style="width:100%; margin-bottom:0.85rem; padding:0.8rem; border:1px solid #dbe7f0; border-radius:12px;">
                                        <button type="submit" class="btn" style="padding:0.7rem 1rem;">Save Story</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        <aside class="panel">
            <div class="section-label">Explore</div>
            <h2>Featured Feed</h2>
            <div class="activity-item">
                <strong>Inventor Showcase</strong>
                <p>Discover new devices, prototypes, and product ideas shared by the HYDRO community.</p>
            </div>
            <div class="activity-item">
                <strong>Community Comments</strong>
                <p>Join conversations about tech, equipment, sustainability, and future systems.</p>
            </div>
            <div class="activity-item">
                <strong>Company Hiring</strong>
                <p>Find recruiting posts from companies hiring engineers, designers, and innovators.</p>
            </div>
            <?php if ($user): ?>
                <div class="section-label" style="margin-top: 1.5rem;">Your Location</div>
                <?php if ($userLocation): ?>
                    <div class="activity-item">
                        <strong>📍 <?php echo htmlspecialchars($userLocation); ?></strong>
                        <p style="font-size: 0.9rem; margin: 0.5rem 0 0;">Your local posts are prioritized. <a href="settings.php" style="color: #00a8e8;">Edit location</a></p>
                    </div>
                <?php else: ?>
                    <div class="activity-item">
                        <p style="font-size: 0.9rem;">Add your location in <a href="settings.php" style="color: #00a8e8;">settings</a> to see posts from your area.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="section-label" style="margin-top: 1.5rem;">Quick Actions</div>
            <div style="margin-bottom:0.7rem; display:flex; align-items:center; gap:0.6rem;">
                <input id="autoplay_videos_toggle" type="checkbox" style="width:18px; height:18px;" />
                <label for="autoplay_videos_toggle" style="font-weight:700;">Autoplay videos (muted)</label>
            </div>
            <a class="small-link" href="dashboard.php">View your dashboard</a><br>
            <a class="small-link" href="HOMEPAGE.PHP">Back to homepage</a>
        </aside>
    </main>

    <div class="emoji-picker-overlay" id="emoji-overlay" onclick="closeEmojiPicker()"></div>
    <div class="emoji-picker" id="emoji-picker">
        <div class="emoji-picker-header">
            <h3>Select Emoji</h3>
            <button class="emoji-picker-close" onclick="closeEmojiPicker()">✕</button>
        </div>
        <div class="emoji-grid">
            <?php 
            $allEmojis = ['😀','😂','😍','🤔','😎','🤩','😉','😁','😈','🤓','😇','🤠','🥳','😜','😝','😋','😗','😚','😘','😍','😭','😢','😡','😤','😠','😲','😟','😪','🤐','😬','😈','🤡','👿','😶','🤑','😳','❤️','🧡','💛','💚','💙','💜','🖤','🎥','📹','📷','📸','🎬','🎞️','🎦','👁️','🔭','🎤','🎧','🎵','🎶','🎼','🎹','🎸','🥁','🎺','🎻','🚀','🛸','🛰️','🌌','⭐','✨','🌟','💫','⚡','🔥','💥','💢','💯','🎯','🎲','🎰','🧩','🎮','🕹️','🎳','🎯','🎪','🎨','🖼️','🎭','🎬','🎪','🎨','🖌️','🖍️','📝','📄','📃','📑','📊','📈','📉','💼','📁','📂','🗂️','🗃️','🗳️','🗄️','📢','📣','📯','🔔','🔕','📻','📺','📷','📹','🎥','🎞️','📽️','🎬','📖','📕','📗','📘','📙','📚','📓','📔','📒','📑','🧷','🧹','🧺','🧻','🧼','🧽','🧯','🛒','🚗','🚕','🚙','🚌','🚎','🏎️','🚓','🚑','🚒','🚐','🛻','🚚','🚛','🚜','🏍️','🏎️','🛵','🐛','🐝','🐞','🐜','🐢','🐍','🦗','🦟','🦠','🐢','🐙','🦑','🦐','🦞','🦀','🐡','🐠','🐟','🐬','🐳','🐋','🦈','🐊','🐅','🐆','🐘','🦛','🦏','🐪','🐫','🦒','🦓','🦍','🦧','🐒','🐔','🐓','🐦','🐤','🐣','🐥','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🦓','🦌','🦍','🐒','🦧','🐒','🦠','🧫','💐','🌹','🥀','🌺','🌸','🌼','🌻','🌞','🌝','🌛','🌜','🌚','🌕','🌖','🌗','🌘','🌑','🌒','⭐','🌟','✨','⚡','☄️','💥','🔥','🌪️','🌈','☀️','🌤️','⛅','🌥️','☁️','🌦️','🌧️','⛈️','🌩️','🌨️','❄️','☃️','⛄','🌬️','💨','💧','💦','☔','🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥑','🍆','🍅','🌶️','🌽','🥒','🥬','🥦','🧄','🧅','🍄','🥜','🌰','💮','🎀','🎁','⚽','⚾','🥎','🎾','🏐','🏀','🏈','🏉','🎱','🪀','🎳','🏓','🏸','🏒','🏑','🥍','🏏','🥅','⛳','⛸️','🎣','🎽','🎿','⛷️','🏂','🪂','🛷','🛹','🛼','🛴','🏄','🏊','🤽','🏇','🚣','🚤','🛶','⛵','🛵','🛩️','🛸','💺','⛽','🚧','🚥','🚦','🛑'];
            foreach ($allEmojis as $emoji): ?>
                <button type="button" class="emoji-item" onclick="selectEmoji('<?php echo $emoji; ?>')"><?php echo $emoji; ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        var emojiPickerActive = false;

        function toggleEmojiPicker() {
            var picker = document.getElementById('emoji-picker');
            var overlay = document.getElementById('emoji-overlay');
            emojiPickerActive = !emojiPickerActive;
            if (emojiPickerActive) {
                picker.classList.add('active');
                overlay.classList.add('active');
            } else {
                picker.classList.remove('active');
                overlay.classList.remove('active');
            }
        }

        function closeEmojiPicker() {
            var picker = document.getElementById('emoji-picker');
            var overlay = document.getElementById('emoji-overlay');
            picker.classList.remove('active');
            overlay.classList.remove('active');
            emojiPickerActive = false;
        }

        function toggleEditPost(postId) {
            var panel = document.getElementById('edit-' + postId);
            if (!panel) return;
            panel.style.display = panel.style.display === 'block' ? 'none' : 'block';
        }

        function selectEmoji(emoji) {
            var field = document.getElementById('post-content');
            if (field) {
                field.value += emoji;
                field.focus();
            }
        }

        function insertEmoji(fieldId, emoji) {
            var field = document.getElementById(fieldId);
            if (!field) return;
            field.value += emoji;
            field.focus();
        }

        function updateMusicLabel() {
            var input = document.getElementById('background_music');
            var label = document.getElementById('background_music_label');
            if (!input || !label) return;
            if (input.files.length > 0) {
                label.textContent = input.files[0].name;
            } else {
                label.textContent = 'Choose audio file...';
            }
        }

        // Autoplay videos toggle (stored in localStorage)
        function applyAutoplaySetting(enabled) {
            var videos = document.querySelectorAll('.post-video');
            videos.forEach(function(v) {
                try {
                    if (enabled) {
                        v.muted = true;
                        v.autoplay = true;
                        v.play().catch(function(){});
                    } else {
                        v.autoplay = false;
                        // don't force-unmute — respect user control, but pause autoplay
                        v.pause();
                    }
                } catch (e) {}
            });
        }

        document.addEventListener('DOMContentLoaded', function(){
            var checkbox = document.getElementById('autoplay_videos_toggle');
            var stored = localStorage.getItem('hydro_autoplay_videos');
            var enabled = stored === '1';
            if (checkbox) {
                checkbox.checked = enabled;
                checkbox.addEventListener('change', function(){
                    var val = checkbox.checked ? '1' : '0';
                    localStorage.setItem('hydro_autoplay_videos', val);
                    applyAutoplaySetting(checkbox.checked);
                });
            }
            applyAutoplaySetting(enabled);
        });
    </script>
<?php include 'footer.php'; ?>

