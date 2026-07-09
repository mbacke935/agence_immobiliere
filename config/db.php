<?php
// config/db.php

$host = 'localhost';
$dbname = 'agence_immobiliere';
$username = 'root';
$password = ''; // À modifier si tu as un mot de passe sur ton MySQL local (ex: MAMP/Mac ou configuration personnalisée)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Erreur critique de connexion à la base de données : " . $e->getMessage());
}
?>