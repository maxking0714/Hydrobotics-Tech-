<?php
require_once 'admin_background.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$action = $data['action'];
$user = $_SESSION['user'] ?? 'Guest';

if ($action === 'request_help') {
    $message = trim($data['message'] ?? '');
    if ($message === '') {
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty.']);
        exit;
    }
    $created = addHelpRequest($user, $message);
    echo json_encode(['success' => (bool)$created]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);
