<?php
// Démarrer la session en premier
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Remplace par ton mot de passe si nécessaire
define('DB_NAME', 'messages_db');

// Établir la connexion
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Erreur de connexion : " . $conn->connect_error);
    }
    
    return $conn;
}

// Sécurité contre les injections XSS
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour obtenir le nom d'utilisateur actuel
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

// Fonction de redirection
function redirect($url) {
    header("Location: $url");
    exit();
}
?>
