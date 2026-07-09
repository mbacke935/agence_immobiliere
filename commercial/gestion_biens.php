<?php
// commercial/gestion_biens.php
require_once '../config/db.php';
include_once '../includes/header.php';

// Sécurisation : uniquement pour les commerciaux connectés
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Commercial') {
    header('Location: ../auth/connexion.php');
    exit();
}

$erreur = '';
$succes = '';

// --- 1. TRAITEMENT DE L'AJOUT D'UN BIEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_bien'])) {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $adresse = trim($_POST['adresse']);
    $prix_mensuel = (float)$_POST['prix_mensuel'];
    $superficie = (float)$_POST['superficie'];
    $nb_chambres = (int)$_POST['nb_chambres'];
    $nb_pieces = (int)$_POST['nb_pieces'];
    $type = $_POST['type']; // 'Appartement' ou 'Villa'

    if (empty($titre) || empty($adresse) || $prix_mensuel <= 0 || $superficie <= 0 || empty($type)) {
        $erreur = "Veuillez remplir correctement tous les champs obligatoires.";
    } else {
        try {
            $pdo->beginTransaction();

            // Insertion du bien
            $sql = "INSERT INTO BIENS (titre, description, adresse, prix_mensuel, superficie, nb_chambres, nb_pieces, type) 
                    VALUES (:titre, :description, :adresse, :prix_mensuel, :superficie, :nb_chambres, :nb_pieces, :type)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'titre' => $titre,
                'description' => $description,
                'adresse' => $adresse,
                'prix_mensuel' => $prix_mensuel,
                'superficie' => $superficie,
                'nb_chambres' => $nb_chambres,
                'nb_pieces' => $nb_pieces,
                'type' => $type
            ]);

            $id_bien = $pdo->lastInsertId();

            // Liaison d'une image par défaut pour la démonstration (évite la gestion complexe d'upload de fichiers)
            $image_defaut = ($type === 'Villa') ? 'villa_default.jpg' : 'appartement_default.jpg';
            $sqlImg = "INSERT INTO IMAGES (chemin_fichier, is_principal, id_bien) VALUES (:chemin, 1, :id_bien)";
            $stmtImg = $pdo->prepare($sqlImg);
            $stmtImg->execute([
                'chemin' => $image_defaut,
                'id_bien' => $id_bien
            ]);

            $pdo->commit();
            $succes = "Le bien immobilier a été ajouté avec succès !";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erreur = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}

// --- 2. RÉCUPÉRATION DE TOUS LES BIENS ---
try {
    $stmt = $pdo->query("SELECT * FROM BIENS ORDER BY id_bien DESC");
    $biens = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur de récupération des données : " . $e->getMessage());
}
?>

<main class="container my-5">
    <div class="row g-4">
        <!-- Colonne Gauche : Formulaire d'Ajout -->
        <div class="col-lg-4">
            <div class="card p-4 border-0 shadow-sm bg-white rounded-3">
                <h4 class="fw-bold text-dark mb-3">Ajouter un bien</h4>
                
                <?php if (!empty($erreur)): ?>
                    <div class="alert alert-danger small" role="alert"><?php echo htmlspecialchars($erreur); ?></div>
                <?php endif; ?>
                <?php if (!empty($succes)): ?>
                    <div class="alert alert-success small" role="alert"><?php echo htmlspecialchars($succes); ?></div>
                <?php endif; ?>

                <form method="POST" action="gestion_biens.php">
                    <input type="hidden" name="ajouter_bien" value="1">
                    
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-secondary">Titre de l'annonce *</label>
                        <input type="text" name="titre" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-secondary">Type de bien *</label>
                        <select name="type" class="form-select form-select-sm" required>
                            <option value="Appartement">Appartement</option>
                            <option value="Villa">Villa</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-secondary">Adresse / Localisation *</label>
                        <input type="text" name="adresse" class="form-control form-control-sm" required placeholder="Ex: Dakar, Mermoz">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Loyer (FCFA) *</label>
                            <input type="number" name="prix_mensuel" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Superficie (m²) *</label>
                            <input type="number" name="superficie" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Chambres</label>
                            <input type="number" name="nb_chambres" class="form-control form-control-sm" value="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Pièces</label>
                            <input type="number" name="nb_pieces" class="form-control form-control-sm" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Description</label>
                        <textarea name="description" class="form-control form-control-sm" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100 py-2 rounded-pill fw-bold">Publier l'offre</button>
                </form>
            </div>
        </div>

        <!-- Colonne Droite : Liste des Biens Existants -->
        <div class="col-lg-8">
            <h3 class="fw-bold text-dark mb-4">Catalogue des Offres</h3>
            <div class="table-responsive-custom">
                <?php if (count($biens) > 0): ?>
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Titre / Localisation</th>
                                <th>Superficie</th>
                                <th>Loyer Mensuel</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($biens as $b): ?>
                                <tr>
                                    <td>
                                        <span class="badge <?php echo $b['type'] === 'Villa' ? 'bg-success' : 'bg-primary'; ?>">
                                            <?php echo htmlspecialchars($b['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($b['titre']); ?></div>
                                        <span class="text-muted text-xs"><?php echo htmlspecialchars($b['adresse']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($b['superficie']); ?> m²</td>
                                    <td class="fw-bold text-dark"><?php echo number_format($b['prix_mensuel'], 0, ',', ' '); ?> FCFA</td>
                                    <td class="text-end">
                                        <a href="../detail_bien.php?id=<?php echo $b['id_bien']; ?>" class="btn btn-xs btn-outline-dark rounded-pill px-2" target="_blank">Voir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center text-muted my-4">Aucun bien enregistré pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>