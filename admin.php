<?php
require_once 'config.php';

$isAdmin = $_SESSION['is_admin'] ?? false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_password'])) {
    if ($_POST['admin_password'] === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        $isAdmin = true;
    }
}

if (isset($_GET['logout'])) {
    $_SESSION['is_admin'] = false;
    header('Location: admin.php');
    exit;
}

if (!$isAdmin) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Admin Login</title>
        <style>
            body { font-family: Arial; display: flex; justify-content: center; align-items: center; height: 100vh; background: #667eea; }
            .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
            input { padding: 10px; width: 250px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
            button { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>Administration</h2>
            <form method="POST">
                <input type="password" name="admin_password" placeholder="Mot de passe admin" required>
                <button type="submit">Connexion</button>
            </form>
            <p style="margin-top: 20px; text-align: center;"><a href="index.php">Retour</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$db = getDB();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f5f5f5; }
        .header { background: #667eea; color: white; padding: 20px; display: flex; justify-content: space-between; }
        .container { padding: 20px; }
        .section { background: white; margin: 20px 0; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f8f8; font-weight: 600; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; }
        .stat-card h3 { font-size: 2em; margin-bottom: 5px; }
        .logout-btn { padding: 10px 20px; background: rgba(255,255,255,0.2); border: none; border-radius: 5px; color: white; cursor: pointer; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🛡️ Panel d'Administration</h1>
        <button class="logout-btn" onclick="location.href='?logout=1'">Déconnexion Admin</button>
    </div>
    
    <div class="container">
        <div class="stat-grid">
            <?php
            $totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $onlineUsers = $db->query("SELECT COUNT(*) FROM users WHERE datetime(last_activity) > datetime('now', '-5 minutes')")->fetchColumn();
            $totalMessages = $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
            $totalPM = $db->query("SELECT COUNT(*) FROM private_messages")->fetchColumn();
            ?>
            <div class="stat-card">
                <h3><?php echo $totalUsers; ?></h3>
                <p>Utilisateurs Total</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $onlineUsers; ?></h3>
                <p>En Ligne</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $totalMessages; ?></h3>
                <p>Messages Publics</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $totalPM; ?></h3>
                <p>Messages Privés</p>
            </div>
        </div>
        
        <div class="section">
            <h2>👥 Utilisateurs et IPs</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Pseudo</th>
                    <th>Adresse IP</th>
                    <th>Créé le</th>
                    <th>Dernière activité</th>
                </tr>
                <?php
                $users = $db->query("SELECT * FROM users ORDER BY last_activity DESC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['pseudo']); ?></td>
                    <td><?php echo htmlspecialchars($user['ip']); ?></td>
                    <td><?php echo $user['created_at']; ?></td>
                    <td><?php echo $user['last_activity']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="section">
            <h2>📋 Logs d'activité</h2>
            <table>
                <tr>
                    <th>Date/Heure</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>IP</th>
                    <th>Détails</th>
                </tr>
                <?php
                $logs = $db->query("SELECT l.*, u.pseudo FROM logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo $log['created_at']; ?></td>
                    <td><?php echo htmlspecialchars($log['pseudo'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                    <td><?php echo htmlspecialchars($log['ip']); ?></td>
                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="section">
            <h2>💬 Messages Publics Récents</h2>
            <table>
                <tr>
                    <th>Date/Heure</th>
                    <th>Utilisateur</th>
                    <th>Message</th>
                </tr>
                <?php
                $messages = $db->query("SELECT m.*, u.pseudo FROM messages m JOIN users u ON m.user_id = u.id ORDER BY m.created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($messages as $msg): ?>
                <tr>
                    <td><?php echo $msg['created_at']; ?></td>
                    <td><?php echo htmlspecialchars($msg['pseudo']); ?></td>
                    <td><?php echo htmlspecialchars($msg['message']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="section">
            <h2>📨 Messages Privés Récents</h2>
            <table>
                <tr>
                    <th>Date/Heure</th>
                    <th>De</th>
                    <th>À</th>
                    <th>Message</th>
                    <th>Lu</th>
                </tr>
                <?php
                $pms = $db->query("SELECT pm.*, u1.pseudo as from_pseudo, u2.pseudo as to_pseudo 
                                  FROM private_messages pm 
                                  JOIN users u1 ON pm.from_user_id = u1.id 
                                  JOIN users u2 ON pm.to_user_id = u2.id 
                                  ORDER BY pm.created_at DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($pms as $pm): ?>
                <tr>
                    <td><?php echo $pm['created_at']; ?></td>
                    <td><?php echo htmlspecialchars($pm['from_pseudo']); ?></td>
                    <td><?php echo htmlspecialchars($pm['to_pseudo']); ?></td>
                    <td><?php echo htmlspecialchars($pm['message']); ?></td>
                    <td><?php echo $pm['is_read'] ? '✓' : '✗'; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>