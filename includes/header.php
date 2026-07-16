<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// On force le préfixe relatif selon le dossier où se trouve le script
$current_dir = basename(dirname($_SERVER['SCRIPT_NAME']));
$prefix = ($current_dir === 'auth' || $current_dir === 'client' || $current_dir === 'commercial') ? '../' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ImmoAgence — Résidences d'exception</title>
    <!-- Polices premium -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Styles personnalisés -->
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo $prefix; ?>index.php"><i class="bi bi-building me-2"></i>ImmoAgence</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $prefix; ?>index.php">Accueil</a>
                </li>
                
                <?php if (isset($_SESSION['role'])): ?>
                    <?php if ($_SESSION['role'] === 'Commercial'): ?>
                        <!-- Liens spécifiques au Commercial -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $prefix; ?>commercial/gestion_biens.php">Gestion Biens</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $prefix; ?>commercial/gestion_reservations.php">Suivi Réservations</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $prefix; ?>commercial/gestion_clients.php">Gestion Comptes</a>
                        </li>
                    <?php elseif ($_SESSION['role'] === 'Client'): ?>
                        <!-- Liens spécifiques au Client -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $prefix; ?>client/favoris.php">Mes Favoris</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $prefix; ?>client/mes_reservations.php">Mes Réservations</a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            
            <div class="d-flex align-items-center">
                <?php if (isset($_SESSION['id_user'])): ?>
                    <span class="navbar-text text-white me-3">
                        Bonjour, <strong><?php echo htmlspecialchars($_SESSION['prenom']); ?></strong> (<?php echo htmlspecialchars($_SESSION['role']); ?>)
                    </span>
                    <a href="<?php echo $prefix; ?>auth/deconnexion.php" class="btn btn-outline-danger btn-sm">Déconnexion</a>
                <?php else: ?>
                    <a href="<?php echo $prefix; ?>auth/connexion.php" class="btn btn-outline-light btn-sm me-2">Connexion</a>
                    <a href="<?php echo $prefix; ?>auth/inscription.php" class="btn btn-primary btn-sm">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>