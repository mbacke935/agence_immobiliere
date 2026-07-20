<?php
// index.php
require_once 'config/bootstrap.php';
include_once 'includes/header.php';

// Initialisation des variables de filtrage
$type = isset($_GET['type']) ? $_GET['type'] : '';
$prix_max = isset($_GET['prix_max']) ? $_GET['prix_max'] : '';
$superficie_min = isset($_GET['superficie_min']) ? $_GET['superficie_min'] : '';

// Construction de la requête SQL dynamique
$sql = "SELECT b.*, i.chemin_fichier 
        FROM BIENS b 
        LEFT JOIN IMAGES i ON b.id_bien = i.id_bien AND i.is_principal = 1 
        WHERE 1=1";

$params = [];

if (in_array($type, ['Appartement', 'Villa'], true)) {
    $sql .= " AND b.type = :type";
    $params['type'] = $type;
}
if ($prix_max !== '' && filter_var($prix_max, FILTER_VALIDATE_FLOAT) !== false && (float) $prix_max >= 0) {
    $sql .= " AND b.prix_mensuel <= :prix_max";
    $params['prix_max'] = $prix_max;
}
if ($superficie_min !== '' && filter_var($superficie_min, FILTER_VALIDATE_FLOAT) !== false && (float) $superficie_min >= 0) {
    $sql .= " AND b.superficie >= :superficie_min";
    $params['superficie_min'] = $superficie_min;
}

$sql .= " ORDER BY b.id_bien DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $biens = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Erreur de récupération des biens : ' . $e->getMessage());
    $biens = [];
}
?>

<!-- Hero premium -->
<section class="hero-premium">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="eyebrow mb-2">Agence immobilière de prestige</div>
                <h1 class="fw-bold mb-3">Trouvez la résidence qui vous ressemble</h1>
                <p class="mb-0">Des villas et appartements sélectionnés avec soin, accompagnés d'un service commercial dédié pour chaque location.</p>
            </div>
        </div>
    </div>
</section>

<main class="container my-5">
    <!-- Barre de Recherche & Filtres -->
    <div class="card search-card p-4 mb-5">
        <h4 class="mb-3 fw-bold text-dark"><i class="bi bi-search me-2"></i>Affiner votre recherche</h4>
        <form method="GET" action="index.php" class="row g-3">
            <div class="col-md-4">
                <label for="type" class="form-label small fw-bold">Type de bien</label>
                <select name="type" id="type" class="form-select">
                    <option value="">Tous les types</option>
                    <option value="Appartement" <?php echo $type === 'Appartement' ? 'selected' : ''; ?>>Appartement</option>
                    <option value="Villa" <?php echo $type === 'Villa' ? 'selected' : ''; ?>>Villa</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="prix_max" class="form-label small fw-bold">Budget max (FCFA / mois)</label>
                <input type="number" name="prix_max" id="prix_max" class="form-control" placeholder="Ex: 500000" value="<?php echo htmlspecialchars($prix_max); ?>">
            </div>
            <div class="col-md-3">
                <label for="superficie_min" class="form-label small fw-bold">Superficie min (m²)</label>
                <input type="number" name="superficie_min" id="superficie_min" class="form-control" placeholder="Ex: 100" value="<?php echo htmlspecialchars($superficie_min); ?>">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 py-2">Filtrer</button>
            </div>
        </form>
    </div>

    <!-- Grille d'affichage des Biens -->
    <h3 class="fw-bold text-dark mb-4">Nos Offres Disponibles</h3>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php if (count($biens) > 0): ?>
            <?php foreach ($biens as $bien): ?>
                <div class="col">
                    <div class="card h-100 property-card position-relative">
                        <!-- Badge de type -->
                        <span class="badge-type <?php echo $bien['type'] === 'Villa' ? 'bg-success' : 'bg-primary'; ?> text-white">
                            <?php echo htmlspecialchars($bien['type']); ?>
                        </span>

                        <!-- Image de couverture corrigée -->
                        <?php
                            if (!empty($bien['chemin_fichier'])) {
                                $image_url = "uploads/" . rawurlencode($bien['chemin_fichier']);
                            } else {
                                $image_url = "https://via.placeholder.com/600x400?text=Aucune+Image";
                            }
                        ?>
                        <img src="<?php echo $image_url; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($bien['titre']); ?>" style="height: 220px; object-fit: cover;">

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold text-dark mb-1"><?php echo htmlspecialchars($bien['titre']); ?></h5>
                            <p class="text-muted small mb-2"><i class="bi bi-geo-alt-fill text-danger"></i> <?php echo htmlspecialchars($bien['adresse']); ?></p>

                            <div class="d-flex justify-content-between mb-3 text-secondary small">
                                <span><i class="bi bi-rulers me-1"></i><strong><?php echo htmlspecialchars($bien['superficie']); ?></strong> m²</span>
                                <span><i class="bi bi-door-closed me-1"></i><strong><?php echo htmlspecialchars($bien['nb_chambres']); ?></strong> Ch.</span>
                                <span><i class="bi bi-house-door me-1"></i><strong><?php echo htmlspecialchars($bien['nb_pieces']); ?></strong> Pièces</span>
                            </div>

                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <span class="fs-5 price-tag"><?php echo number_format($bien['prix_mensuel'], 0, ',', ' '); ?> <small class="fs-6 text-muted">FCFA/mois</small></span>
                                <a href="detail_bien.php?id=<?php echo $bien['id_bien']; ?>" class="btn btn-outline-dark btn-sm rounded-pill px-3">Voir Détails</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 w-100 text-center py-5">
                <p class="fs-5 text-muted">Aucun bien ne correspond à vos critères de recherche.</p>
                <a href="index.php" class="btn btn-sm btn-link">Réinitialiser les filtres</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
