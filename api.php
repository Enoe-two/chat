<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = getDB();

switch ($action) {
    case 'getUsers':
        $stmt = $db->query("SELECT id, pseudo FROM users WHERE datetime(last_activity) > datetime('now', '-5 minutes') AND id != {$_SESSION['user_id']} ORDER BY pseudo");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    case 'getPublicMessages':
        $stmt = $db->query("SELECT m.*, u.pseudo, CASE WHEN m.user_id = {$_SESSION['user_id']} THEN 1 ELSE 0 END as is_own 
                           FROM messages m 
                           JOIN users u ON m.user_id = u.id 
                           ORDER BY m.created_at DESC LIMIT 50");
        echo json_encode(array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC)));
        break;
        
    case 'getPrivateMessages':
        $userId = intval($_GET['userId'] ?? 0);
        $stmt = $db->prepare("SELECT pm.*, CASE WHEN pm.from_user_id = ? THEN 1 ELSE 0 END as is_own 
                             FROM private_messages pm 
                             WHERE (pm.from_user_id = ? AND pm.to_user_id = ?) OR (pm.from_user_id = ? AND pm.to_user_id = ?)
                             ORDER BY pm.created_at ASC LIMIT 50");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $userId, $userId, $_SESSION['user_id']]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
        
    case 'sendPublic':
        $data = json_decode(file_get_contents('php://input'), true);
        $message = trim($data['message'] ?? '');
        if ($message) {
            $stmt = $db->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $message]);
            logAction($_SESSION['user_id'], 'MESSAGE_PUBLIC', substr($message, 0, 50));
        }
        echo json_encode(['success' => true]);
        break;
        
    case 'sendPrivate':
        $data = json_decode(file_get_contents('php://input'), true);
        $message = trim($data['message'] ?? '');
        $toUserId = intval($data['toUserId'] ?? 0);
        if ($message && $toUserId) {
            $stmt = $db->prepare("INSERT INTO private_messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $toUserId, $message]);
            logAction($_SESSION['user_id'], 'MESSAGE_PRIVATE', "To user $toUserId");
        }
        echo json_encode(['success' => true]);
        break;
}
?>