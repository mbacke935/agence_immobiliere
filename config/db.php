<?php
// config/db.php

$envFile = dirname(__DIR__) . '/.env';
$localEnv = is_readable($envFile) ? (parse_ini_file($envFile, false, INI_SCANNER_RAW) ?: []) : [];

$host = getenv('DB_HOST') ?: ($localEnv['DB_HOST'] ?? 'localhost');
$dbname = getenv('DB_NAME') ?: ($localEnv['DB_NAME'] ?? 'agence_immobiliere');
$username = getenv('DB_USER') ?: ($localEnv['DB_USER'] ?? 'root');
$password = getenv('DB_PASSWORD') ?: ($localEnv['DB_PASSWORD'] ?? '');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log('Erreur de connexion à la base de données : ' . $e->getMessage());
    http_response_code(500);
    exit('Le service est temporairement indisponible. Veuillez réessayer plus tard.');
}
