<?php
/**
 * HYDROBOTICS shared admin/data helper utilities.
 */

function getDataDir() {
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function getDataFile($name) {
    return getDataDir() . '/' . $name;
}

function loadJsonData($filename) {
    $path = getDataFile($filename);
    if (!file_exists($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT));
    }
    $content = file_get_contents($path);
    if ($content === false) {
        return [];
    }
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function saveJsonData($filename, $data) {
    $path = getDataFile($filename);
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
}

function loadUsers() {
    return loadJsonData('users.json');
}

function saveUsers(array $users) {
    saveJsonData('users.json', $users);
}

function deleteUserByUsername($username) {
    $users = loadUsers();
    $new = [];
    $deleted = false;
    foreach ($users as $u) {
        if (isset($u['username']) && strcasecmp($u['username'], $username) === 0) {
            $deleted = true;
            continue;
        }
        $new[] = $u;
    }
    if ($deleted) {
        saveUsers($new);
        appendAdminLog("DELETE_USER username={$username}");
    }
    return $deleted;
}

function followUser($follower, $target) {
    if ($follower === $target) {
        return false;
    }
    $users = loadUsers();
    $updated = false;
    foreach ($users as &$u) {
        if (isset($u['username']) && strcasecmp($u['username'], $target) === 0) {
            if (!isset($u['followers']) || !is_array($u['followers'])) {
                $u['followers'] = [];
            }
            if (!in_array($follower, $u['followers'], true)) {
                $u['followers'][] = $follower;
                $updated = true;
            }
        }
        if (isset($u['username']) && strcasecmp($u['username'], $follower) === 0) {
            if (!isset($u['following']) || !is_array($u['following'])) {
                $u['following'] = [];
            }
            if (!in_array($target, $u['following'], true)) {
                $u['following'][] = $target;
                $updated = true;
            }
        }
    }
    if ($updated) {
        saveUsers($users);
        appendAdminLog("FOLLOW from={$follower} to={$target}");
    }
    return $updated;
}

function unfollowUser($follower, $target) {
    $users = loadUsers();
    $updated = false;
    foreach ($users as &$u) {
        if (isset($u['username']) && strcasecmp($u['username'], $target) === 0 && isset($u['followers']) && is_array($u['followers'])) {
            $newFollowers = array_values(array_filter($u['followers'], fn($name) => $name !== $follower));
            if (count($newFollowers) !== count($u['followers'])) {
                $u['followers'] = $newFollowers;
                $updated = true;
            }
        }
        if (isset($u['username']) && strcasecmp($u['username'], $follower) === 0 && isset($u['following']) && is_array($u['following'])) {
            $newFollowing = array_values(array_filter($u['following'], fn($name) => $name !== $target));
            if (count($newFollowing) !== count($u['following'])) {
                $u['following'] = $newFollowing;
                $updated = true;
            }
        }
    }
    if ($updated) {
        saveUsers($users);
        appendAdminLog("UNFOLLOW from={$follower} to={$target}");
    }
    return $updated;
}

function userIsFollowing($follower, $target) {
    $profile = getUserProfile($follower);
    if (!$profile || empty($profile['following']) || !is_array($profile['following'])) {
        return false;
    }
    return in_array($target, $profile['following'], true);
}

function loadHelpRequests() {
    return loadJsonData('help_requests.json');
}

function saveHelpRequests(array $requests) {
    saveJsonData('help_requests.json', $requests);
}

function addHelpRequest($username, $message) {
    $requests = loadHelpRequests();
    $requests[] = [
        'id' => uniqid('help_', true),
        'user' => $username,
        'message' => $message,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];
    saveHelpRequests($requests);
    appendAdminLog("HELP_REQUEST user={$username}");
    return true;
}

function resolveHelpRequest($requestId, $result = 'assisted') {
    $requests = loadHelpRequests();
    $updated = false;
    foreach ($requests as &$req) {
        if (isset($req['id']) && $req['id'] === $requestId) {
            $req['status'] = $result;
            $req['updated_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    if ($updated) {
        saveHelpRequests($requests);
        appendAdminLog("HELP_REQUEST_RESOLVED id={$requestId} result={$result}");
    }
    return $updated;
}

function loadPosts() {
    return loadJsonData('posts.json');
}

function savePosts(array $posts) {
    saveJsonData('posts.json', $posts);
}

function loadMessages() {
    return loadJsonData('messages.json');
}

function saveMessages(array $messages) {
    saveJsonData('messages.json', $messages);
}

function loadVideos() {
    return loadJsonData('videos.json');
}

function saveVideos(array $videos) {
    saveJsonData('videos.json', $videos);
}

function loadChats() {
    return loadJsonData('chats.json');
}

function saveChats(array $chats) {
    saveJsonData('chats.json', $chats);
}

function appendAdminLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $line = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(__DIR__ . '/admin_log.txt', $line, FILE_APPEND | LOCK_EX);
}

function deletePostById($postId) {
    $posts = loadPosts();
    $new = [];
    $deleted = false;
    foreach ($posts as $p) {
        if (isset($p['id']) && $p['id'] === $postId) {
            // attempt to remove uploaded image file if present
            if (!empty($p['image'])) {
                $path = __DIR__ . '/data/uploads/' . $p['image'];
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
            $deleted = true;
            continue;
        }
        $new[] = $p;
    }
    if ($deleted) {
        savePosts($new);
        appendAdminLog("DELETE_POST id={$postId}");
    }
    return $deleted;
}

function getAdminLogLines($limit = 1000) {
    $path = __DIR__ . '/admin_log.txt';
    if (!file_exists($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($limit !== null) {
        return array_slice($lines, -1 * $limit, $limit);
    }
    return $lines;
}

function exportAdminLog() {
    $path = __DIR__ . '/admin_log.txt';
    if (!file_exists($path)) return '';
    return file_get_contents($path);
}

function getUserProfile($username) {
    $users = loadUsers();
    foreach ($users as $u) {
        if (isset($u['username']) && strcasecmp($u['username'], $username) === 0) {
            return $u;
        }
    }
    return null;
}

function updateUserProfile($username, $updates) {
    $users = loadUsers();
    $updated = false;
    foreach ($users as &$u) {
        if (isset($u['username']) && strcasecmp($u['username'], $username) === 0) {
            foreach ($updates as $key => $value) {
                $u[$key] = $value;
            }
            $updated = true;
            break;
        }
    }
    if ($updated) {
        saveUsers($users);
    }
    return $updated;
}

function getUserDisplayInfo($username) {
    $profile = getUserProfile($username);
    if (!$profile) return ['username' => $username];
    
    $display = ['username' => $profile['username']];
    
    if (isset($profile['show_gender']) && $profile['show_gender'] && isset($profile['gender'])) {
        $display['gender'] = $profile['gender'];
    }
    if (isset($profile['show_age']) && $profile['show_age'] && isset($profile['age'])) {
        $display['age'] = $profile['age'];
    }
    if (isset($profile['show_location']) && $profile['show_location'] && isset($profile['location'])) {
        $display['location'] = $profile['location'];
    }
    
    $display['role'] = $profile['role'] ?? 'inventor';
    
    return $display;
}
