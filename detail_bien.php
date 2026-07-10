<?php
// detail_bien.php
require_once 'config/db.php';
include_once 'includes/header.php';

$id_bien = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_bien <= 0) {
    header('Location: index.php');
    exit();
}

try {
    // 1. Récupération des détails du bien
    $stmt = $pdo->prepare("SELECT * FROM BIENS WHERE id_bien = :id_bien");
    $stmt->execute(['id_bien' => $id_bien]);
    $bien = $stmt->fetch();

    if (!$bien) {
        die("<div class='container my-5 alert alert-danger'>Ce bien immobilier n'existe pas ou a été supprimé.</div>");
    }

    // 2. Récupération de toutes les images du bien
    $stmtImages = $pdo->prepare("SELECT * FROM IMAGES WHERE id_bien = :id_bien ORDER BY is_principal DESC");
    $stmtImages->execute(['id_bien' => $id_bien]);
    $images = $stmtImages->fetchAll();

    // 3. Vérification si le bien est déjà en favori (uniquement si le client est connecté)
    $is_favori = false;
    if (isset($_SESSION['id_user']) && $_SESSION['role'] === 'Client') {
        $stmtFav = $pdo->prepare("SELECT 1 FROM FAVORIS WHERE id_client = :id_client AND id_bien = :id_bien");
        $stmtFav->execute([
            'id_client' => $_SESSION['id_user'],
            'id_bien' => $id_bien
        ]);
        if ($stmtFav->fetch()) {
            $is_favori = true;
        }
    }
} catch (PDOException $e) {
    die("Erreur lors du chargement des données : " . $e->getMessage());
}
?>

<main class="container my-5">
    <div class="row g-4">
        <!-- Colonne Gauche : Galerie d'images (Carrousel) -->
        <div class="col-lg-7">
            <?php if (count($images) > 0): ?>
                <div id="carouselBien" class="carousel slide carousel-detail shadow-sm bg-white p-2 rounded-3" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $img): ?>
                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                <!-- Chemin de l'image corrigé ici -->
                                <img src="uploads/<?php echo htmlspecialchars($img['chemin_fichier']); ?>" class="d-block w-100" alt="Image de l'offre" style="height: 450px; object-fit: cover; border-radius: 6px;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselBien" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Précédent</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselBien" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Suivant</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <img src="https://via.placeholder.com/800x500?text=Aucune+Image+Disponible" class="img-fluid rounded-3 shadow-sm" alt="Pas d'image">
            <?php endif; ?>
        </div>

        <!-- Colonne Droite : Informations, Caractéristiques & Actions -->
        <div class="col-lg-5">
            <div class="card p-4 border-0 shadow-sm h-100 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge <?php echo $bien['type'] === 'Villa' ? 'bg-success' : 'bg-primary'; ?> px-3 py-2 rounded-pill fs-6">
                        <?php echo htmlspecialchars($bien['type']); ?>
                    </span>
                    
                    <!-- Bouton Favoris dynamique -->
                    <?php if (isset($_SESSION['id_user']) && $_SESSION['role'] === 'Client'): ?>
                        <a href="client/favoris.php?id_bien=<?php echo $bien['id_bien']; ?>&action=toggle" class="btn btn-outline-danger btn-favoris rounded-circle p-2">
                            <?php echo $is_favori ? '❤️' : '🤍'; ?>
                        </a>
                    <?php endif; ?>
                </div>

                <h2 class="fw-bold text-dark mt-2 mb-1"><?php echo htmlspecialchars($bien['titre']); ?></h2>
                <p class="text-muted"><i class="bi bi-geo-alt-fill text-danger"></i> <?php echo htmlspecialchars($bien['adresse']); ?></p>
                <hr>

                <h3 class="text-primary fw-bold mb-4"><?php echo number_format($bien['prix_mensuel'], 0, ',', ' '); ?> <span class="fs-6 text-muted">FCFA / mois</span></h3>

                <h5 class="fw-bold text-dark">Caractéristiques du bien</h5>
                <div class="row g-3 my-2 text-center">
                    <div class="col-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="fw-bold text-dark fs-5"><?php echo htmlspecialchars($bien['superficie']); ?></div>
                            <small class="text-muted">m² habitables</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="fw-bold text-dark fs-5"><?php echo htmlspecialchars($bien['nb_chambres']); ?></div>
                            <small class="text-muted">Chambres</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-light rounded-3">
                            <div class="fw-bold text-dark fs-5"><?php echo htmlspecialchars($bien['nb_pieces']); ?></div>
                            <small class="text-muted">Pièces</small>
                        </div>
                    </div>
                </div>

                <h5 class="fw-bold text-dark mt-4">Description</h5>
                <p class="text-secondary mb-4"><?php echo nl2br(htmlspecialchars($bien['description'])); ?></p>

                <!-- Section Action : Réservation -->
                <div class="mt-auto">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'Client'): ?>
                        <a href="client/reservations.php?id_bien=<?php echo $bien['id_bien']; ?>" class="btn btn-primary w-100 py-3 rounded-pill fw-bold">
                            Réserver ce bien maintenant
                        </a>
                    <?php elseif (!isset($_SESSION['role'])): ?>
                        <div class="alert alert-warning text-center small mb-0" role="alert">
                            Veuillez vous <a href="auth/connexion.php" class="alert-link">connecter</a> pour réserver ce bien ou l'ajouter à vos favoris.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center small mb-0" role="alert">
                            Mode Commercial : Consultation de la fiche technique.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>