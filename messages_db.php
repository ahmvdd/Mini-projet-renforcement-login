<?php
$servername = "localhost";
$username = "root"; // Remplacez par votre identifiant MySQL
$password = ""; // Remplacez par votre mot de passe MySQL

// Connexion au serveur MySQL
$conn = new mysqli($servername, $username, $password);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Création de la base de données
$sql = "CREATE DATABASE messages_db";
if ($conn->query($sql) === TRUE) {
    echo "Base de données créée avec succès";
} else {
    echo "Erreur lors de la création de la base de données : " . $conn->error;
}

// Sélectionner la base et créer une table
$conn->select_db("messages_db");
$table_sql = "CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($table_sql) === TRUE) {
    echo "Table 'messages' créée avec succès";
} else {
    echo "Erreur lors de la création de la table : " . $conn->error;
}

$conn->close();
?>
