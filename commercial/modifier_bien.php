<?php
// commercial/modifier_bien.php
require_once '../config/bootstrap.php';

require_role($pdo, 'Commercial', '../auth/connexion.php');

$id_bien = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_bien <= 0) {
    header('Location: gestion_biens.php');
    exit();
}

include_once '../includes/header.php';

$erreur = '';
$succes = '';

// --- 1. ACTION COMPLÉMENTAIRE : SUPPRESSION D'UNE IMAGE INDIVIDUELLE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'supprimer_image' && verify_csrf()) {
    $id_img = (int)($_POST['id_image'] ?? 0);
    try {
        // Récupérer le nom du fichier pour le supprimer physiquement du serveur
        $stmtFind = $pdo->prepare("SELECT chemin_fichier, is_principal FROM IMAGES WHERE id_image = :id_img AND id_bien = :id_bien");
        $stmtFind->execute(['id_img' => $id_img, 'id_bien' => $id_bien]);
        $imgToDelete = $stmtFind->fetch();

        if ($imgToDelete) {
            $fichier_physique = "../uploads/" . $imgToDelete['chemin_fichier'];
            if (file_exists($fichier_physique)) {
                unlink($fichier_physique);
            }

            // Supprimer de la base de données
            $stmtDel = $pdo->prepare("DELETE FROM IMAGES WHERE id_image = :id_img");
            $stmtDel->execute(['id_img' => $id_img]);

            // Si on a supprimé la photo principale, on désigne une autre photo au hasard comme principale
            if ($imgToDelete['is_principal'] == 1) {
                $stmtNext = $pdo->prepare("UPDATE IMAGES SET is_principal = 1 WHERE id_bien = :id_bien LIMIT 1");
                $stmtNext->execute(['id_bien' => $id_bien]);
            }
            $succes = "L'image a été supprimée avec succès.";
        }
    } catch (Exception $e) {
        $erreur = "Erreur lors de la suppression de l'image : " . $e->getMessage();
    }
}

// --- 2. TRAITEMENT DU FORMULAIRE PRINCIPAL (MODIFICATION & AJOUTS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_bien'])) {
    if (!verify_csrf()) {
        $erreur = 'Votre session a expiré. Veuillez réessayer.';
    } else {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $adresse = trim($_POST['adresse']);
    $prix_mensuel = (float)$_POST['prix_mensuel'];
    $superficie = (float)$_POST['superficie'];
    $nb_chambres = (int)$_POST['nb_chambres'];
    $nb_pieces = (int)$_POST['nb_pieces'];
    $type = $_POST['type'];

    if (empty($titre) || empty($adresse) || $prix_mensuel <= 0 || $superficie <= 0 || $nb_chambres < 0 || $nb_pieces < 0 || !in_array($type, ['Appartement', 'Villa'], true)) {
        $erreur = "Veuillez remplir correctement tous les champs obligatoires.";
    } else {
        try {
            $pdo->beginTransaction();

            // 2.1. Mise à jour des informations textuelles
            $sql = "UPDATE BIENS SET 
                        titre = :titre, description = :description, adresse = :adresse, 
                        prix_mensuel = :prix_mensuel, superficie = :superficie, 
                        nb_chambres = :nb_chambres, nb_pieces = :nb_pieces, type = :type 
                    WHERE id_bien = :id_bien";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'titre' => $titre, 'description' => $description, 'adresse' => $adresse,
                'prix_mensuel' => $prix_mensuel, 'superficie' => $superficie,
                'nb_chambres' => $nb_chambres, 'nb_pieces' => $nb_pieces, 'type' => $type,
                'id_bien' => $id_bien
            ]);

            // 2.2. Modification/Remplacement ciblé d'images existantes
            if (isset($_FILES['remplacer_image'])) {
                foreach ($_FILES['remplacer_image']['name'] as $id_image_existant => $nom_original) {
                    if ($_FILES['remplacer_image']['error'][$id_image_existant] === 0
                        && $_FILES['remplacer_image']['size'][$id_image_existant] <= 5 * 1024 * 1024
                        && in_array((new finfo(FILEINFO_MIME_TYPE))->file($_FILES['remplacer_image']['tmp_name'][$id_image_existant]), ['image/jpeg', 'image/png', 'image/webp'], true)) {
                        $extension = strtolower(pathinfo($nom_original, PATHINFO_EXTENSION));
                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                            
                            // Trouver l'ancien fichier pour le supprimer
                            $stmtOld = $pdo->prepare("SELECT chemin_fichier FROM IMAGES WHERE id_image = :id_img AND id_bien = :id_bien");
                            $stmtOld->execute(['id_img' => $id_image_existant, 'id_bien' => $id_bien]);
                            $oldFile = $stmtOld->fetchColumn();

                            if ($oldFile && file_exists("../uploads/" . $oldFile)) {
                                unlink("../uploads/" . $oldFile);
                            }

                            // Téléverser le nouveau fichier
                            $nom_image = "bien_" . $id_bien . "_update_" . time() . "_" . $id_image_existant . "." . $extension;
                            if (move_uploaded_file($_FILES['remplacer_image']['tmp_name'][$id_image_existant], "../uploads/" . $nom_image)) {
                                $stmtUpImage = $pdo->prepare("UPDATE IMAGES SET chemin_fichier = :chemin WHERE id_image = :id_img");
                                $stmtUpImage->execute(['chemin' => $nom_image, 'id_img' => $id_image_existant]);
                            }
                        }
                    }
                }
            }

            // 2.3. Ajout en masse de nouvelles images
            if (isset($_FILES['images_bien']) && !empty($_FILES['images_bien']['name'][0])) {
                $dossier_cible = "../uploads/";
                $extensions_autorisees = ['jpg', 'jpeg', 'png', 'webp'];
                
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM IMAGES WHERE id_bien = :id_bien AND is_principal = 1");
                $stmtCheck->execute(['id_bien' => $id_bien]);
                $has_principal = $stmtCheck->fetchColumn() > 0;

                $sqlImg = "INSERT INTO IMAGES (chemin_fichier, is_principal, id_bien) VALUES (:chemin, :is_principal, :id_bien)";
                $stmtImg = $pdo->prepare($sqlImg);

                foreach ($_FILES['images_bien']['name'] as $index => $nom_original) {
                    if ($_FILES['images_bien']['error'][$index] === 0
                        && $_FILES['images_bien']['size'][$index] <= 5 * 1024 * 1024
                        && in_array((new finfo(FILEINFO_MIME_TYPE))->file($_FILES['images_bien']['tmp_name'][$index]), ['image/jpeg', 'image/png', 'image/webp'], true)) {
                        $extension = strtolower(pathinfo($nom_original, PATHINFO_EXTENSION));

                        if (in_array($extension, $extensions_autorisees)) {
                            $nom_image = "bien_" . $id_bien . "_" . time() . "_" . $index . "." . $extension;
                            
                            if (move_uploaded_file($_FILES['images_bien']['tmp_name'][$index], $dossier_cible . $nom_image)) {
                                $is_principal = (!$has_principal && $index === 0) ? 1 : 0;
                                $stmtImg->execute([
                                    'chemin' => $nom_image,
                                    'is_principal' => $is_principal,
                                    'id_bien' => $id_bien
                                ]);
                                $has_principal = true;
                            }
                        }
                    }
                }
            }

            $pdo->commit();
            $succes = "Le bien immobilier et sa galerie ont été mis à jour avec succès !";
        } catch (Exception $e) {
            $pdo->rollBack();
            $erreur = "Erreur lors de la modification : " . $e->getMessage();
        }
    }
    }
}

// --- 3. RÉCUPÉRATION DES DONNÉES À JOUR ---
try {
    $stmt = $pdo->prepare("SELECT * FROM BIENS WHERE id_bien = :id_bien");
    $stmt->execute(['id_bien' => $id_bien]);
    $bien = $stmt->fetch();

    if (!$bien) {
        die("<div class='container my-5 alert alert-danger'>Ce bien n'existe pas.</div>");
    }

    $stmtImg = $pdo->prepare("SELECT * FROM IMAGES WHERE id_bien = :id_bien ORDER BY is_principal DESC");
    $stmtImg->execute(['id_bien' => $id_bien]);
    $images = $stmtImg->fetchAll();
} catch (PDOException $e) {
    die("Erreur de récupération : " . $e->getMessage());
}

?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card p-4 border-0 shadow-sm bg-white rounded-3">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold text-dark mb-0"><i class="bi bi-pencil-square me-2"></i>Modifier la fiche du bien #<?php echo $bien['id_bien']; ?></h4>
                    <a href="gestion_biens.php" class="btn btn-sm btn-outline-secondary rounded-pill">Retour</a>
                </div>
                
                <?php if (!empty($erreur)) echo "<div class='alert alert-danger small'>$erreur</div>"; ?>
                <?php if (!empty($succes)) echo "<div class='alert alert-success small'>$succes</div>"; ?>

                <form method="POST" action="modifier_bien.php?id=<?php echo $id_bien; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="modifier_bien" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-secondary">Titre *</label>
                            <input type="text" name="titre" class="form-control" value="<?php echo htmlspecialchars($bien['titre']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">Type *</label>
                            <select name="type" class="form-select" required>
                                <option value="Appartement" <?php echo $bien['type'] === 'Appartement' ? 'selected' : ''; ?>>Appartement</option>
                                <option value="Villa" <?php echo $bien['type'] === 'Villa' ? 'selected' : ''; ?>>Villa</option>
                            </select>
                        </div>
                    </div>

                    <!-- GESTION COMPLÈTE DE LA GALERIE D'IMAGES -->
                    <div class="mb-4 p-4 bg-light rounded-3">
                        <label class="form-label fw-bold text-dark d-block mb-3">Galerie des photos</label>
                        
                        <?php if (count($images) > 0): ?>
                            <div class="row g-3 mb-4">
                                <?php foreach ($images as $img): ?>
                                    <div class="col-6 col-sm-4 col-md-3 text-center">
                                        <div class="position-relative bg-white p-2 rounded border shadow-sm group-image">
                                            
                                            <!-- Image Actuelle -->
                                            <img src="../uploads/<?php echo htmlspecialchars($img['chemin_fichier']); ?>" class="img-fluid rounded mb-2" style="height: 100px; width:100%; object-fit: cover;" alt="Aperçu">
                                            
                                            <?php if ($img['is_principal'] == 1): ?>
                                                <span class="badge bg-success position-absolute top-0 start-0 m-2 text-xs">Principale</span>
                                            <?php endif; ?>

                                            <div class="d-flex justify-content-between gap-1 mt-1">
                                                <!-- Modifier/Remplacer cette image spécifique -->
                                                <label class="btn btn-xs btn-outline-primary w-50 m-0 py-1 px-0 rounded-2" style="font-size: 11px;" title="Remplacer cette photo">
                                                    Changer
                                                    <input type="file" name="remplacer_image[<?php echo $img['id_image']; ?>]" accept="image/*" class="d-none" onchange="previewUpdate(this)">
                                                </label>
                                                
                                                <!-- Supprimer l'image -->
                                                <form method="POST" action="modifier_bien.php?id=<?php echo $id_bien; ?>" class="w-50">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                                    <input type="hidden" name="action" value="supprimer_image">
                                                    <input type="hidden" name="id_image" value="<?php echo $img['id_image']; ?>">
                                                    <button type="submit" class="btn btn-xs btn-outline-danger w-100 py-1 px-0 rounded-2" style="font-size: 11px;" onclick="return confirm('Supprimer définitivement cette photo ?');">Supprimer</button>
                                                </form>
                                            </div>

                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small">Aucune image enregistrée.</p>
                        <?php endif; ?>

                        <hr class="text-muted my-3">

                        <!-- Ajouter de nouvelles photos avec zone d'aperçu dynamique -->
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark">Ajouter de nouvelles photos</label>
                            <input type="file" id="images_bien" name="images_bien[]" class="form-control" accept="image/*" multiple onchange="previewNewImages(this)">
                        </div>
                        
                        <!-- Zone d'aperçu pour les nouvelles images sélectionnées -->
                        <div id="preview_zone_new" class="row g-2 mt-2"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Adresse *</label>
                        <input type="text" name="adresse" class="form-control" value="<?php echo htmlspecialchars($bien['adresse']); ?>" required>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Loyer (FCFA) *</label>
                            <input type="number" name="prix_mensuel" class="form-control" value="<?php echo htmlspecialchars($bien['prix_mensuel']); ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Superficie (m²) *</label>
                            <input type="number" name="superficie" class="form-control" value="<?php echo htmlspecialchars($bien['superficie']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Chambres</label>
                            <input type="number" name="nb_chambres" class="form-control" value="<?php echo htmlspecialchars($bien['nb_chambres']); ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-secondary">Pièces</label>
                            <input type="number" name="nb_pieces" class="form-control" value="<?php echo htmlspecialchars($bien['nb_pieces']); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($bien['description']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">Enregistrer les modifications</button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
// 1. Aperçu instantané lors du remplacement d'une photo existante
function previewUpdate(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        var cardImg = input.closest('.position-relative').querySelector('img');
        
        reader.onload = function (e) {
            cardImg.src = e.target.result;
            cardImg.style.border = "2px solid #0d6efd"; // Bordure bleue temporaire pour indiquer le changement en cours
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// 2. Aperçu instantané en galerie lors de l'ajout de nouvelles photos
function previewNewImages(input) {
    var zone = document.getElementById('preview_zone_new');
    zone.innerHTML = ""; // Vider l'aperçu précédent
    
    if (input.files) {
        Array.from(input.files).forEach(file => {
            var reader = new FileReader();
            reader.onload = function(e) {
                var col = document.createElement('div');
                col.className = "col-3 text-center";
                col.innerHTML = `
                    <div class="p-1 border bg-white rounded">
                        <img src="${e.target.result}" class="img-fluid rounded text-center" style="height: 60px; width:100%; object-fit: cover;">
                        <span class="text-xs text-success d-block" style="font-size: 9px; word-break: break-all;">À ajouter</span>
                    </div>
                `;
                zone.appendChild(col);
            }
            reader.readAsDataURL(file);
        });
    }
}
</script>

<?php include_once '../includes/footer.php'; ?>
