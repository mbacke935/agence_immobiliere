<?php
// commercial/gestion_biens.php
require_once '../config/db.php';
include_once '../includes/header.php';

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
    $type = $_POST['type'];

    if (empty($titre) || empty($adresse) || $prix_mensuel <= 0 || $superficie <= 0) {
        $erreur = "Veuillez remplir correctement tous les champs obligatoires.";
    } else {
        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO BIENS (titre, description, adresse, prix_mensuel, superficie, nb_chambres, nb_pieces, type) 
                    VALUES (:titre, :description, :adresse, :prix_mensuel, :superficie, :nb_chambres, :nb_pieces, :type)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'titre' => $titre, 'description' => $description, 'adresse' => $adresse,
                'prix_mensuel' => $prix_mensuel, 'superficie' => $superficie,
                'nb_chambres' => $nb_chambres, 'nb_pieces' => $nb_pieces, 'type' => $type
            ]);

            $id_bien = $pdo->lastInsertId();
            $nom_image = ($type === 'Villa') ? 'villa_default.jpg' : 'appartement_default.jpg';

            // Gestion de l'upload d'image
            if (isset($_FILES['image_bien']) && $_FILES['image_bien']['error'] === 0) {
                $dossier_cible = "../uploads/";
                if (!is_dir($dossier_cible)) {
                    mkdir($dossier_cible, 0777, true);
                }
                
                $extension = strtolower(pathinfo($_FILES['image_bien']['name'], PATHINFO_EXTENSION));
                $extensions_autorisees = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($extension, $extensions_autorisees)) {
                    $nom_image = "bien_" . $id_bien . "_" . time() . "." . $extension;
                    move_uploaded_file($_FILES['image_bien']['tmp_name'], $dossier_cible . $nom_image);
                }
            }

            $sqlImg = "INSERT INTO IMAGES (chemin_fichier, is_principal, id_bien) VALUES (:chemin, 1, :id_bien)";
            $pdo->prepare($sqlImg)->execute(['chemin' => $nom_image, 'id_bien' => $id_bien]);

            $pdo->commit();
            $succes = "Le bien immobilier a été ajouté avec succès !";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erreur = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}

try {
    $biens = $pdo->query("SELECT * FROM BIENS ORDER BY id_bien DESC")->fetchAll();
} catch (PDOException $e) {
    die("Erreur de récupération des données : " . $e->getMessage());
}
?>

<main class="container my-5">
    <div class="row g-4">
        <!-- Formulaire d'Ajout -->
        <div class="col-lg-4">
            <div class="card p-4 border-0 shadow-sm bg-white rounded-3">
                <h4 class="fw-bold text-dark mb-3">Ajouter un bien</h4>
                
                <?php if (!empty($erreur)) echo "<div class='alert alert-danger small'>$erreur</div>"; ?>
                <?php if (!empty($succes)) echo "<div class='alert alert-success small'>$succes</div>"; ?>

                <form method="POST" action="gestion_biens.php" enctype="multipart/form-data">
                    <input type="hidden" name="ajouter_bien" value="1">
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-secondary">Titre *</label>
                        <input type="text" name="titre" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-secondary">Type *</label>
                        <select name="type" class="form-select form-select-sm" required>
                            <option value="Appartement">Appartement</option>
                            <option value="Villa">Villa</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-secondary">Image du bien</label>
                        <input type="file" name="image_bien" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-secondary">Adresse *</label>
                        <input type="text" name="adresse" class="form-control form-control-sm" required>
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

        <!-- Catalogue / Liste -->
        <div class="col-lg-8">
            <h3 class="fw-bold text-dark mb-4">Catalogue des Offres</h3>
            <div class="table-responsive-custom">
                <?php if (count($biens) > 0): ?>
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Titre / Localisation</th>
                                <th>Loyer</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($biens as $b): ?>
                                <tr>
                                    <td><span class="badge <?php echo $b['type'] === 'Villa' ? 'bg-success' : 'bg-primary'; ?>"><?php echo $b['type']; ?></span></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($b['titre']); ?></div>
                                        <span class="text-muted text-xs"><?php echo htmlspecialchars($b['adresse']); ?></span>
                                    </td>
                                    <td class="fw-bold"><?php echo number_format($b['prix_mensuel'], 0, ',', ' '); ?> FCFA</td>
                                    <td class="text-end">
                                        <a href="modifier_bien.php?id=<?php echo $b['id_bien']; ?>" class="btn btn-xs btn-warning rounded-pill px-2 me-1">Modifier</a>
                                        <a href="../detail_bien.php?id=<?php echo $b['id_bien']; ?>" class="btn btn-xs btn-outline-dark rounded-pill px-2" target="_blank">Voir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center text-muted my-4">Aucun bien enregistré.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>