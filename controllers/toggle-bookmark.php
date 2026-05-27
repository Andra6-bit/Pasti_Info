<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first to save the competition.']);
    exit();
}

include "../config/database.php";

$competition_id = isset($_POST['competition_id']) ? (int)$_POST['competition_id'] : 0;
if ($competition_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid competition ID.']);
    exit();
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit();
}

$competitionStmt = mysqli_prepare($koneksi, "SELECT id FROM competitions WHERE id = ? LIMIT 1");
if (!$competitionStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to process request.']);
    exit();
}
mysqli_stmt_bind_param($competitionStmt, "i", $competition_id);
mysqli_stmt_execute($competitionStmt);
$competitionResult = mysqli_stmt_get_result($competitionStmt);
$competitionRow = $competitionResult ? mysqli_fetch_assoc($competitionResult) : null;
if (!$competitionRow) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Competition not found.']);
    exit();
}

$checkStmt = mysqli_prepare($koneksi, "SELECT id FROM saved_competitions WHERE user_id = ? AND competition_id = ? LIMIT 1");
if (!$checkStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to process request.']);
    exit();
}

mysqli_stmt_bind_param($checkStmt, "ii", $user_id, $competition_id);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);
$exists = $checkResult && mysqli_num_rows($checkResult) > 0;

if ($exists) {
    $deleteStmt = mysqli_prepare($koneksi, "DELETE FROM saved_competitions WHERE user_id = ? AND competition_id = ?");
    if (!$deleteStmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to remove bookmark.']);
        exit();
    }
    mysqli_stmt_bind_param($deleteStmt, "ii", $user_id, $competition_id);
    mysqli_stmt_execute($deleteStmt);
    echo json_encode(['success' => true, 'action' => 'removed']);
    exit();
}

$insertStmt = mysqli_prepare($koneksi, "INSERT INTO saved_competitions (user_id, competition_id) VALUES (?, ?)");
if (!$insertStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save bookmark.']);
    exit();
}
mysqli_stmt_bind_param($insertStmt, "ii", $user_id, $competition_id);
mysqli_stmt_execute($insertStmt);
if (mysqli_stmt_affected_rows($insertStmt) > 0) {
    echo json_encode(['success' => true, 'action' => 'saved']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save bookmark.']);
}
exit();
?>
