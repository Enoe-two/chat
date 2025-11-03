<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pseudo'])) {
    $pseudo = trim($_POST['pseudo']);
    
    if (strlen($pseudo) >= 3 && strlen($pseudo) <= 20) {
        $db = getDB();
        
        try {
            $stmt = $db->prepare("INSERT INTO users (pseudo, ip) VALUES (?, ?)");
            $stmt->execute([$pseudo, $_SERVER['REMOTE_ADDR']]);
            $userId = $db->lastInsertId();
            
            $_SESSION['user_id'] = $userId;
            $_SESSION['pseudo'] = $pseudo;
            
            logAction($userId, 'LOGIN', 'Connexion réussie');
            
            header('Location: chat.php');
            exit;
        } catch (PDOException $e) {
            // L'utilisateur existe déjà, on le récupère
            $stmt = $db->prepare("SELECT id FROM users WHERE pseudo = ?");
            $stmt->execute([$pseudo]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['pseudo'] = $pseudo;
                
                // Mise à jour de l'IP et dernière activité
                $stmt = $db->prepare("UPDATE users SET ip = ?, last_activity = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$_SERVER['REMOTE_ADDR'], $user['id']]);
                
                logAction($user['id'], 'LOGIN', 'Reconnexion');
                
                header('Location: chat.php');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Chat</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .admin-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .admin-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .admin-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>💬 Chat App</h1>
        <form method="POST">
            <div class="form-group">
                <label for="pseudo">Choisissez votre pseudo</label>
                <input type="text" id="pseudo" name="pseudo" required minlength="3" maxlength="20" placeholder="Votre pseudo...">
            </div>
            <button type="submit">Se connecter</button>
        </form>
        <div class="admin-link">
            <a href="admin.php">Accès administrateur</a>
        </div>
    </div>
</body>
</html>