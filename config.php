<?php
session_start();

// Configuration de la base de données SQLite
define('DB_FILE', 'chat.db');

// Mot de passe admin (à changer !)
define('ADMIN_PASSWORD', 'E!g3t93hgx');

// Initialisation de la base de données
function initDB() {
    $db = new PDO('sqlite:' . DB_FILE);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Table des utilisateurs
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pseudo TEXT UNIQUE NOT NULL,
        ip TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Table des messages publics
    $db->exec("CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        message TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
    // Table des messages privés
    $db->exec("CREATE TABLE IF NOT EXISTS private_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        from_user_id INTEGER,
        to_user_id INTEGER,
        message TEXT NOT NULL,
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_user_id) REFERENCES users(id),
        FOREIGN KEY (to_user_id) REFERENCES users(id)
    )");
    
    // Table des logs
    $db->exec("CREATE TABLE IF NOT EXISTS logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        action TEXT NOT NULL,
        ip TEXT,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    
    return $db;
}

function getDB() {
    return new PDO('sqlite:' . DB_FILE);
}

function logAction($userId, $action, $details = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO logs (user_id, action, ip, details) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $_SERVER['REMOTE_ADDR'], $details]);
}

// Initialiser la DB au premier lancement
if (!file_exists(DB_FILE)) {
    initDB();
}
?>
