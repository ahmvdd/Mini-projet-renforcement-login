<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getDbConnection();
$currentUserId = $_SESSION['user_id'];
$currentUsername = getCurrentUsername();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['message'])) {
        try {
            $message = sanitize($_POST['message']);
            
            $stmt = $conn->prepare("INSERT INTO messages (sender_name, message, user_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $currentUsername, $message, $currentUserId);
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur d'envoi : " . $stmt->error);
            }
            
            $stmt->close();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }
    
    if (isset($_POST['update_profile'])) {
        try {
            $new_username = sanitize($_POST['username']);
            $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
            
            if ($password) {
                $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssi", $new_username, $password, $currentUserId);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->bind_param("si", $new_username, $currentUserId);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Erreur de mise à jour : " . $stmt->error);
            }
            
            $_SESSION['username'] = $new_username;
            $_SESSION['success'] = "Profil mis à jour avec succès";
            $stmt->close();
            redirect($_SERVER['PHP_SELF']);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }
}

// Récupération des messages
$messages = [];
$stmt = $conn->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY sent_at DESC LIMIT 50");
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Application</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-bg: #f8f9fa;
            --light-text: #212529;
            --light-card: #ffffff;
            --light-border: #e0e0e0;
            --dark-bg: #121212;
            --dark-card: #1e1e1e;
            --dark-text: #f8f9fa;
            --dark-border: #333333;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--light-bg);
            color: var(--light-text);
            line-height: 1.6;
            transition: var(--transition);
        }
        
        body[data-theme="dark"] {
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-border);
        }
        
        body[data-theme="dark"] .chat-header {
            border-bottom-color: var(--dark-border);
        }
        
        .chat-header h1 {
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .theme-toggle {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--light-text);
            transition: var(--transition);
        }
        
        body[data-theme="dark"] .theme-toggle {
            color: var(--dark-text);
        }
        
        .profile-menu {
            position: relative;
            display: inline-block;
        }
        
        .profile-btn {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--light-text);
            transition: var(--transition);
        }
        
        body[data-theme="dark"] .profile-btn {
            color: var(--dark-text);
        }
        
        .profile-btn i {
            font-size: 1.2rem;
        }
        
        .profile-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            background-color: var(--light-card);
            border: 1px solid var(--light-border);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 200px;
            z-index: 100;
            display: none;
            transition: var(--transition);
        }
        
        body[data-theme="dark"] .profile-dropdown {
            background-color: var(--dark-card);
            border-color: var(--dark-border);
        }
        
        .profile-menu:hover .profile-dropdown {
            display: block;
        }
        
        .profile-dropdown a {
            display: block;
            padding: 10px 15px;
            text-decoration: none;
            color: var(--light-text);
            transition: var(--transition);
        }
        
        body[data-theme="dark"] .profile-dropdown a {
            color: var(--dark-text);
        }
        
        .profile-dropdown a:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }
        
        .profile-dropdown .divider {
            height: 1px;
            background-color: var(--light-border);
            margin: 5px 0;
        }
        
        body[data-theme="dark"] .profile-dropdown .divider {
            background-color: var(--dark-border);
        }
        
        .chat-box {
            background-color: var(--light-card);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            height: 500px;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
        }
        
        body[data-theme="dark"] .chat-box {
            background-color: var(--dark-card);
        }
        
        .messages-container {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: var(--light-bg);
            transition: var(--transition);
        }
        
        body[data-theme="dark"] .messages-container {
            background-color: var(--dark-bg);
        }
        
        .message {
            margin-bottom: 15px;
            max-width: 70%;
            padding: 12px 15px;
            border-radius: var(--border-radius);
            position: relative;
            word-wrap: break-word;
            transition: var(--transition);
        }
        
        .message-sent {
            background-color: var(--primary-color);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 0;
        }
        
        .message-received {
            background-color: #e9ecef;
            color: var(--light-text);
            margin-right: auto;
            border-bottom-left-radius: 0;
        }
        
        body[data-theme="dark"] .message-received {
            background-color: #2d2d2d;
            color: var(--dark-text);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .message-time {
            opacity: 0.8;
            font-size: 12px;
        }
        
        .chat-form {
            display: flex;
            padding: 15px;
            background-color: var(--light-card);
            border-top: 1px solid var(--light-border);
            transition: var(--transition);
        }
        
        body[data-theme="dark"] .chat-form {
            background-color: var(--dark-card);
            border-top-color: var(--dark-border);
        }
        
        .message-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid var(--light-border);
            border-radius: var(--border-radius);
            resize: none;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: var(--transition);
            background-color: var(--light-card);
            color: var(--light-text);
        }
        
        body[data-theme="dark"] .message-input {
            border-color: var(--dark-border);
            background-color: var(--dark-card);
            color: var(--dark-text);
        }
        
        .message-input:focus {
            border-color: var(--primary-color);
        }
        
        .send-btn {
            margin-left: 10px;
            padding: 0 25px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .send-btn:hover {
            background-color: var(--secondary-color);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: var(--light-card);
            padding: 25px;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            transition: var(--transition);
        }
        
        body[data-theme="dark"] .modal-content {
            background-color: var(--dark-card);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 1.5rem;
            color: var(--primary-color);
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--light-text);
        }
        
        body[data-theme="dark"] .close-btn {
            color: var(--dark-text);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--light-border);
            border-radius: var(--border-radius);
            background-color: var(--light-card);
            color: var(--light-text);
            transition: var(--transition);
        }
        
        body[data-theme="dark"] .form-control {
            border-color: var(--dark-border);
            background-color: var(--dark-card);
            color: var(--dark-text);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        body[data-theme="dark"] .alert-success {
            background-color: #1a3a22;
            color: #c3e6cb;
            border-color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        body[data-theme="dark"] .alert-error {
            background-color: #3a1a22;
            color: #f5c6cb;
            border-color: #721c24;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message {
            animation: fadeIn 0.3s ease-out;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        body[data-theme="dark"] ::-webkit-scrollbar-track {
            background: #2d2d2d;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        body[data-theme="dark"] ::-webkit-scrollbar-thumb {
            background: #555;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .chat-box {
                height: calc(100vh - 120px);
            }
            
            .message {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="chat-header">
            <h1>Messagerie</h1>
            <div class="header-actions">
                <button class="theme-toggle" id="themeToggle">
                    <i class="fas fa-moon"></i>
                </button>
                
                <div class="profile-menu">
                    <button class="profile-btn">
                        <i class="fas fa-user-circle"></i>
                        <span><?= htmlspecialchars($currentUsername) ?></span>
                    </button>
                    <div class="profile-dropdown">
                        <a href="#" id="editProfileBtn"><i class="fas fa-user-edit"></i> Modifier profil</a>
                        <a href="#" id="changePasswordBtn"><i class="fas fa-key"></i> Changer mot de passe</a>
                        <div class="divider"></div>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="chat-box">
            <div class="messages-container" id="chat-messages">
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?= ($msg['sender_name'] === $currentUsername) ? 'message-sent' : 'message-received' ?>">
                        <div class="message-header">
                            <span class="sender-name"><?= htmlspecialchars($msg['sender_name']) ?></span>
                            <span class="message-time"><?= date('d/m/Y H:i', strtotime($msg['sent_at'])) ?></span>
                        </div>
                        <div class="message-content">
                            <?= htmlspecialchars($msg['message']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <form id="chat-form" method="POST" class="chat-form">
                <textarea name="message" class="message-input" placeholder="Écrivez votre message ici..." rows="1" required></textarea>
                <button type="submit" class="send-btn">Envoyer</button>
            </form>
        </div>
    </div>
    
    <!-- Modal de modification de profil -->
    <div class="modal" id="profileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Modifier le profil</h3>
                <button class="close-btn" id="closeProfileModal">&times;</button>
            </div>
            <form method="POST" id="profileForm">
                <input type="hidden" name="update_profile" value="1">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($currentUsername) ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                    <input type="password" class="form-control" id="password" name="password">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelProfileBtn">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Gestion du thème
        const themeToggle = document.getElementById('themeToggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        
        document.documentElement.setAttribute('data-theme', currentTheme);
        
        themeToggle.addEventListener('click', () => {
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            const icon = themeToggle.querySelector('i');
            icon.className = newTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        });
        
        const themeIcon = themeToggle.querySelector('i');
        if (currentTheme === 'dark') {
            themeIcon.className = 'fas fa-sun';
        }
        
        // Gestion du modal de profil
        const profileModal = document.getElementById('profileModal');
        const editProfileBtn = document.getElementById('editProfileBtn');
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const closeProfileModal = document.getElementById('closeProfileModal');
        const cancelProfileBtn = document.getElementById('cancelProfileBtn');
        
        editProfileBtn.addEventListener('click', (e) => {
            e.preventDefault();
            profileModal.style.display = 'flex';
        });
        
        changePasswordBtn.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('password').focus();
            profileModal.style.display = 'flex';
        });
        
        closeProfileModal.addEventListener('click', () => {
            profileModal.style.display = 'none';
        });
        
        cancelProfileBtn.addEventListener('click', () => {
            profileModal.style.display = 'none';
        });
        
        window.addEventListener('click', (e) => {
            if (e.target === profileModal) {
                profileModal.style.display = 'none';
            }
        });
        
        // Auto-resize textarea
        const textarea = document.querySelector('.message-input');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        
        // Scroll to bottom of messages
        function scrollToBottom() {
            const messagesContainer = document.getElementById('chat-messages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Refresh chat messages
        function refreshChat() {
            fetch('get_messages.php')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('chat-messages').innerHTML = data;
                    scrollToBottom();
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Handle form submission with AJAX
        document.getElementById('chat-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(() => {
                this.reset();
                textarea.style.height = 'auto';
                refreshChat();
            })
            .catch(error => console.error('Error:', error));
        });
        
        // Initial setup
        scrollToBottom();
        setInterval(refreshChat, 3000);
    </script>
</body>
</html>