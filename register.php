<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $conn = getDbConnection();
        
        // Vérifier si le nom d'utilisateur existe déjà
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result(); // Store the result to get the row count
        
        if ($stmt->num_rows > 0) {
            $error = "Ce nom d'utilisateur est déjà pris.";
        } else {
            // Hacher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insérer le nouvel utilisateur
            $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);
            if ($stmt->execute()) {
                redirect("login.php"); // Redirige vers la page de connexion
            } else {
                $error = "Erreur lors de l'inscription.";
            }
        }
    } catch (Exception $e) {
        $error = "Erreur de base de données : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --error-color: #f72585;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .register-container {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .register-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header h2 {
            font-weight: 600;
            font-size: 28px;
        }
        
        .register-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            outline: none;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            color: var(--error-color);
            background-color: rgba(247, 37, 133, 0.1);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--dark-color);
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .login-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .password-strength {
            margin-top: 5px;
            height: 5px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            background: var(--error-color);
            transition: width 0.3s;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 480px) {
            .register-container {
                border-radius: 12px;
            }
            
            .register-header {
                padding: 20px;
            }
            
            .register-form {
                padding: 20px;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const strengthBar = document.getElementById('password-strength-bar');
            
            if (passwordInput && strengthBar) {
                passwordInput.addEventListener('input', function() {
                    const password = passwordInput.value;
                    let strength = 0;
                    
                    // Longueur minimale
                    if (password.length >= 8) strength += 1;
                    
                    // Contient des chiffres
                    if (/\d/.test(password)) strength += 1;
                    
                    // Contient des lettres minuscules et majuscules
                    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
                    
                    // Contient des caractères spéciaux
                    if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
                    
                    // Mise à jour de la barre de force
                    const width = strength * 25;
                    strengthBar.style.width = width + '%';
                    
                    // Changement de couleur selon la force
                    if (strength <= 1) {
                        strengthBar.style.backgroundColor = 'var(--error-color)';
                    } else if (strength <= 3) {
                        strengthBar.style.backgroundColor = 'var(--accent-color)';
                    } else {
                        strengthBar.style.backgroundColor = 'var(--success-color)';
                    }
                });
            }
        });
    </script>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2>Inscription</h2>
        </div>
        <div class="register-form">
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                    <div class="password-strength">
                        <div id="password-strength-bar" class="password-strength-bar"></div>
                    </div>
                </div>
                <button type="submit" class="btn">S'inscrire</button>
            </form>
            
            <div class="login-link">
                Déjà inscrit ? <a href="login.php">Connectez-vous</a>
            </div>
        </div>
    </div>
</body>
</html>
