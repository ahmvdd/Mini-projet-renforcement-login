<?php
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php'); // Redirige vers la page de connexion si l'utilisateur n'est pas connecté
}

$conn = getDbConnection();
$currentUserId = $_SESSION['user_id']; // Récupérer l'ID de l'utilisateur connecté

// Préparer et exécuter la requête pour obtenir les messages de l'utilisateur
$stmt = $conn->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY sent_at DESC");
$stmt->bind_param("i", $currentUserId);
$stmt->execute();
$result = $stmt->get_result();

$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close(); // Fermer la déclaration
$conn->close(); // Fermer la connexion à la base de données

// Affichage des messages
foreach ($messages as $msg) {
    echo '<div class="message">';
    echo '<strong>' . htmlspecialchars($msg['sender_name']) . '</strong>: '; // Échapper les caractères spéciaux
    echo htmlspecialchars($msg['message']); // Échapper les caractères spéciaux
    echo '<div class="timestamp">' . date('d/m/Y H:i', strtotime($msg['sent_at'])) . '</div>';
    echo '</div>';
}
?>
