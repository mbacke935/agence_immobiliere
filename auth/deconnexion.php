<?php
// auth/deconnexion.php

// Initialisation ou récupération de la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Libération de toutes les variables de session
$_SESSION = array();

// Destruction du cookie de session si présent
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruction de la session côté serveur
session_destroy();

// Redirection immédiate vers la page d'accueil
header("Location: ../index.php");
exit();
?>