<?php
session_start();
include "../config/database.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login' || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sessions = [];
    
    // 1. Fetch sessions
    $sql = "SELECT s.id, s.competition_id, s.is_voting_done, s.created_at,
                   c.title, c.registration_fee, c.target_audience, c.date_range,
                   (SELECT GROUP_CONCAT(cat.name SEPARATOR ', ') FROM competition_categories cc JOIN categories cat ON cc.category_id = cat.id WHERE cc.competition_id = c.id) as category
            FROM debate_sessions s
            LEFT JOIN competitions c ON s.competition_id = c.id
            WHERE s.user_id = ?
            ORDER BY s.created_at DESC";
            
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $sess_id = $row['id'];
            
            $comp = null;
            if ($row['competition_id']) {
                $comp = [
                    'id' => (int)$row['competition_id'],
                    'title' => $row['title'],
                    'registration_fee' => (int)$row['registration_fee'],
                    'target_audience' => $row['target_audience'],
                    'date_range' => $row['date_range'],
                    'category' => $row['category']
                ];
            }
            
            $sessions[$sess_id] = [
                'id' => $sess_id,
                'competition' => $comp,
                'messages' => [],
                'isVotingDone' => (bool)$row['is_voting_done'],
                'createdAt' => $row['created_at']
            ];
        }
        mysqli_stmt_close($stmt);
    }
    
    // 2. Fetch all messages for these sessions
    if (!empty($sessions)) {
        $sess_ids = array_keys($sessions);
        $placeholders = implode(',', array_fill(0, count($sess_ids), '?'));
        
        $msg_sql = "SELECT session_id, sender, message_text, created_at 
                    FROM debate_messages 
                    WHERE session_id IN ($placeholders)
                    ORDER BY id ASC";
                    
        $msg_stmt = mysqli_prepare($koneksi, $msg_sql);
        if ($msg_stmt) {
            $types = str_repeat('s', count($sess_ids));
            mysqli_stmt_bind_param($msg_stmt, $types, ...$sess_ids);
            mysqli_stmt_execute($msg_stmt);
            $msg_res = mysqli_stmt_get_result($msg_stmt);
            while ($msg_row = mysqli_fetch_assoc($msg_res)) {
                $s_id = $msg_row['session_id'];
                
                $time = date('H:i', strtotime($msg_row['created_at']));
                
                $sessions[$s_id]['messages'][] = [
                    'sender' => $msg_row['sender'],
                    'text' => $msg_row['message_text'],
                    'time' => $time
                ];
            }
            mysqli_stmt_close($msg_stmt);
        }
    }
    
    echo json_encode(['success' => true, 'sessions' => array_values($sessions)]);
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    if ($action === 'create_session') {
        $id = $data['id'] ?? '';
        $comp_id = isset($data['competition_id']) ? (int)$data['competition_id'] : null;
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'Session ID is required']);
            exit();
        }
        
        $sql = "INSERT INTO debate_sessions (id, user_id, competition_id) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sii", $id, $user_id, $comp_id);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if ($success) {
                $initial_msg = $data['initial_message'] ?? '';
                if (!empty($initial_msg)) {
                    $sender = $data['initial_sender'] ?? 'AMBIS';
                    $msg_sql = "INSERT INTO debate_messages (session_id, sender, message_text) VALUES (?, ?, ?)";
                    $msg_stmt = mysqli_prepare($koneksi, $msg_sql);
                    if ($msg_stmt) {
                        mysqli_stmt_bind_param($msg_stmt, "sss", $id, $sender, $initial_msg);
                        mysqli_stmt_execute($msg_stmt);
                        mysqli_stmt_close($msg_stmt);
                    }
                }
                echo json_encode(['success' => true]);
                exit();
            }
        }
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit();
    }
    
    if ($action === 'save_message') {
        $sess_id = $data['session_id'] ?? '';
        $sender = $data['sender'] ?? '';
        $text = $data['text'] ?? '';
        
        if (empty($sess_id) || empty($sender) || empty($text)) {
            echo json_encode(['success' => false, 'error' => 'Missing fields']);
            exit();
        }
        
        $sql = "INSERT INTO debate_messages (session_id, sender, message_text) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $sess_id, $sender, $text);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            if ($success) {
                echo json_encode(['success' => true]);
                exit();
            }
        }
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit();
    }
    
    if ($action === 'update_voting_status') {
        $sess_id = $data['session_id'] ?? '';
        $is_voting_done = isset($data['isVotingDone']) ? (int)$data['isVotingDone'] : 0;
        
        if (empty($sess_id)) {
            echo json_encode(['success' => false, 'error' => 'Session ID is required']);
            exit();
        }
        
        $sql = "UPDATE debate_sessions SET is_voting_done = ? WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isi", $is_voting_done, $sess_id, $user_id);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            if ($success) {
                echo json_encode(['success' => true]);
                exit();
            }
        }
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit();
    }
    
    if ($action === 'delete_session') {
        $sess_id = $data['session_id'] ?? '';
        if (empty($sess_id)) {
            echo json_encode(['success' => false, 'error' => 'Session ID is required']);
            exit();
        }
        
        $sql = "DELETE FROM debate_sessions WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $sess_id, $user_id);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            if ($success) {
                echo json_encode(['success' => true]);
                exit();
            }
        }
        echo json_encode(['success' => false, 'error' => 'Database error']);
        exit();
    }
    
    if ($action === 'reset_session') {
        $sess_id = $data['session_id'] ?? '';
        $initial_msg = $data['initial_message'] ?? '';
        $initial_sender = $data['initial_sender'] ?? 'AMBIS';
        
        if (empty($sess_id)) {
            echo json_encode(['success' => false, 'error' => 'Session ID is required']);
            exit();
        }
        
        $sql = "UPDATE debate_sessions SET is_voting_done = 0 WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $sess_id, $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        $sql = "DELETE FROM debate_messages WHERE session_id = ?";
        $stmt = mysqli_prepare($koneksi, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $sess_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        
        if (!empty($initial_msg)) {
            $msg_sql = "INSERT INTO debate_messages (session_id, sender, message_text) VALUES (?, ?, ?)";
            $msg_stmt = mysqli_prepare($koneksi, $msg_sql);
            if ($msg_stmt) {
                mysqli_stmt_bind_param($msg_stmt, "sss", $sess_id, $initial_sender, $initial_msg);
                mysqli_stmt_execute($msg_stmt);
                mysqli_stmt_close($msg_stmt);
            }
        }
        
        echo json_encode(['success' => true]);
        exit();
    }
}
