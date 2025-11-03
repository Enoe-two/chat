<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Mise à jour de la dernière activité
$db = getDB();
$stmt = $db->prepare("UPDATE users SET last_activity = CURRENT_TIMESTAMP WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);

// Déconnexion
if (isset($_GET['logout'])) {
    logAction($_SESSION['user_id'], 'LOGOUT', 'Déconnexion');
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - <?php echo htmlspecialchars($_SESSION['pseudo']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 1.5em;
        }
        
        .header .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            padding: 8px 15px;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        .sidebar {
            width: 250px;
            background: white;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar h2 {
            padding: 15px;
            background: #f8f8f8;
            border-bottom: 1px solid #e0e0e0;
            font-size: 1em;
        }
        
        .users-list {
            flex: 1;
            overflow-y: auto;
        }
        
        .user-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-item:hover {
            background: #f8f8f8;
        }
        
        .user-item.active {
            background: #e8f0fe;
        }
        
        .online-indicator {
            width: 8px;
            height: 8px;
            background: #4caf50;
            border-radius: 50%;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-tabs {
            display: flex;
            background: white;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .tab {
            padding: 15px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            border-bottom-color: #667eea;
            color: #667eea;
            font-weight: 600;
        }
        
        .chat-area {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .message {
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 70%;
            word-wrap: break-word;
        }
        
        .message.public {
            background: #f0f0f0;
            align-self: flex-start;
        }
        
        .message.own {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            align-self: flex-end;
        }
        
        .message .sender {
            font-weight: 600;
            font-size: 0.85em;
            margin-bottom: 5px;
        }
        
        .message .time {
            font-size: 0.75em;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .input-area {
            padding: 15px;
            background: #f8f8f8;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
        }
        
        .input-area input {
            flex: 1;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
        }
        
        .input-area button {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .input-area button:hover {
            transform: scale(1.05);
        }
        
        .private-chat {
            display: none;
        }
        
        .private-chat.active {
            display: flex;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>💬 Chat Application</h1>
        <div class="user-info">
            <span>Connecté en tant que <strong><?php echo htmlspecialchars($_SESSION['pseudo']); ?></strong></span>
            <button class="logout-btn" onclick="location.href='?logout=1'">Déconnexion</button>
        </div>
    </div>
    
    <div class="container">
        <div class="sidebar">
            <h2>👥 Utilisateurs en ligne</h2>
            <div class="users-list" id="usersList"></div>
        </div>
        
        <div class="main-content">
            <div class="chat-tabs">
                <div class="tab active" onclick="switchTab('public')">Chat Public</div>
                <div class="tab" id="privateTab" onclick="switchTab('private')">Messages Privés</div>
            </div>
            
            <div class="chat-area" id="publicChat">
                <div class="messages" id="publicMessages"></div>
                <div class="input-area">
                    <input type="text" id="publicInput" placeholder="Votre message..." onkeypress="if(event.key==='Enter') sendPublicMessage()">
                    <button onclick="sendPublicMessage()">Envoyer</button>
                </div>
            </div>
            
            <div class="chat-area private-chat" id="privateChat">
                <div class="messages" id="privateMessages"></div>
                <div class="input-area">
                    <input type="text" id="privateInput" placeholder="Message privé..." onkeypress="if(event.key==='Enter') sendPrivateMessage()">
                    <button onclick="sendPrivateMessage()">Envoyer</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let currentTab = 'public';
        let selectedUserId = null;
        
        function switchTab(tab) {
            currentTab = tab;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            document.getElementById('publicChat').style.display = tab === 'public' ? 'flex' : 'none';
            document.getElementById('privateChat').style.display = tab === 'private' ? 'flex' : 'none';
        }
        
        function loadUsers() {
            fetch('api.php?action=getUsers')
                .then(r => r.json())
                .then(users => {
                    const list = document.getElementById('usersList');
                    list.innerHTML = users.map(u => `
                        <div class="user-item ${selectedUserId === u.id ? 'active' : ''}" onclick="selectUser(${u.id}, '${u.pseudo}')">
                            <span>${u.pseudo}</span>
                            <span class="online-indicator"></span>
                        </div>
                    `).join('');
                });
        }
        
        function selectUser(userId, pseudo) {
            selectedUserId = userId;
            document.getElementById('privateTab').textContent = `Messages Privés - ${pseudo}`;
            loadUsers();
            switchTab('private');
            loadPrivateMessages();
        }
        
        function loadPublicMessages() {
            fetch('api.php?action=getPublicMessages')
                .then(r => r.json())
                .then(messages => {
                    const container = document.getElementById('publicMessages');
                    container.innerHTML = messages.map(m => `
                        <div class="message ${m.is_own ? 'own' : 'public'}">
                            ${!m.is_own ? `<div class="sender">${m.pseudo}</div>` : ''}
                            <div>${m.message}</div>
                            <div class="time">${m.created_at}</div>
                        </div>
                    `).join('');
                    container.scrollTop = container.scrollHeight;
                });
        }
        
        function loadPrivateMessages() {
            if (!selectedUserId) return;
            fetch(`api.php?action=getPrivateMessages&userId=${selectedUserId}`)
                .then(r => r.json())
                .then(messages => {
                    const container = document.getElementById('privateMessages');
                    container.innerHTML = messages.map(m => `
                        <div class="message ${m.is_own ? 'own' : 'public'}">
                            <div>${m.message}</div>
                            <div class="time">${m.created_at}</div>
                        </div>
                    `).join('');
                    container.scrollTop = container.scrollHeight;
                });
        }
        
        function sendPublicMessage() {
            const input = document.getElementById('publicInput');
            const message = input.value.trim();
            if (!message) return;
            
            fetch('api.php?action=sendPublic', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({message})
            }).then(() => {
                input.value = '';
                loadPublicMessages();
            });
        }
        
        function sendPrivateMessage() {
            if (!selectedUserId) return;
            const input = document.getElementById('privateInput');
            const message = input.value.trim();
            if (!message) return;
            
            fetch('api.php?action=sendPrivate', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({message, toUserId: selectedUserId})
            }).then(() => {
                input.value = '';
                loadPrivateMessages();
            });
        }
        
        // Auto-refresh
        setInterval(() => {
            loadUsers();
            if (currentTab === 'public') {
                loadPublicMessages();
            } else if (selectedUserId) {
                loadPrivateMessages();
            }
        }, 2000);
        
        // Initial load
        loadUsers();
        loadPublicMessages();
    </script>
</body>
</html>
