<?php
// client/favoris.php
require_once '../config/db.php';
include_once '../includes/header.php';

// Sécurisation de l'accès : uniquement pour les clients connectés
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Client') {
    header('Location: ../auth/connexion.php');
    exit();
}

$id_client = $_SESSION['id_user'];

// --- 1. GESTION DE L'ACTION TOGGLE (AJOUT/SUPPRESSION) ---
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id_bien'])) {
    $id_bien = (int)$_GET['id_bien'];

    if ($id_bien > 0) {
        try {
            // Vérifier si le favori existe déjà
            $stmtCheck = $pdo->prepare("SELECT 1 FROM FAVORIS WHERE id_client = :id_client AND id_bien = :id_bien");
            $stmtCheck->execute(['id_client' => $id_client, 'id_bien' => $id_bien]);
            
            if ($stmtCheck->fetch()) {
                // Il existe -> On le supprime
                $stmtDelete = $pdo->prepare("DELETE FROM FAVORIS WHERE id_client = :id_client AND id_bien = :id_bien");
                $stmtDelete->execute(['id_client' => $id_client, 'id_bien' => $id_bien]);
            } else {
                // Il n'existe pas -> On l'ajoute
                $stmtInsert = $pdo->prepare("INSERT INTO FAVORIS (id_client, id_bien) VALUES (:id_client, :id_bien)");
                $stmtInsert->execute(['id_client' => $id_client, 'id_bien' => $id_bien]);
            }
        } catch (PDOException $e) {
            die("Erreur lors de la mise à jour des favoris : " . $e->getMessage());
        }
    }
    
    // Redirection vers la page de détails du bien pour conserver le contexte fluide
    header("Location: ../detail_bien.php?id=" . $id_bien);
    exit();
}

// --- 2. GESTION DE LA SUPPRESSION DIRECTE DEPUIS LA LISTE ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id_bien'])) {
    $id_bien = (int)$_GET['id_bien'];
    try {
        $stmtDelete = $pdo->prepare("DELETE FROM FAVORIS WHERE id_client = :id_client AND id_bien = :id_bien");
        $stmtDelete->execute(['id_client' => $id_client, 'id_bien' => $id_bien]);
    } catch (PDOException $e) {
        die("Erreur lors de la suppression : " . $e->getMessage());
    }
    header("Location: favoris.php");
    exit();
}

// --- 3. RÉCUPÉRATION DES FAVORIS POUR L'AFFICHAGE ---
try {
    $sql = "SELECT b.* FROM FAVORIS f 
            JOIN BIENS b ON f.id_bien = b.id_bien 
            WHERE f.id_client = :id_client 
            ORDER BY b.id_bien DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_client' => $id_client]);
    $favoris = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération de vos favoris : " . $e->getMessage());
}
?>

<main class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark">Mes Biens Favoris</h3>
        <a href="../index.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">Parcourir les offres</a>
    </div>

    <div class="table-responsive-custom">
        <?php if (count($favoris) > 0): ?>
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Type</th>
                        <th>Titre du bien</th>
                        <th>Adresse</th>
                        <th>Prix Mensuel</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($favoris as $item): ?>
                        <tr>
                            <td>
                                <span class="badge <?php echo $item['type'] === 'Villa' ? 'bg-success' : 'bg-primary'; ?>">
                                    <?php echo htmlspecialchars($item['type']); ?>
                                </span>
                            </td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($item['titre']); ?></td>
                            <td class="text-muted small"><?php echo htmlspecialchars($item['adresse']); ?></td>
                            <td class="fw-bold text-primary"><?php echo number_format($item['prix_mensuel'], 0, ',', ' '); ?> FCFA</td>
                            <td class="text-end">
                                <a href="../detail_bien.php?id=<?php echo $item['id_bien']; ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3 me-1">Consulter</a>
                                <a href="favoris.php?action=delete&id_bien=<?php echo $item['id_bien']; ?>" class="btn btn-sm btn-danger rounded-pill px-3" onclick="return confirm('Retirer ce bien de vos favoris ?');">Retirer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="fs-5 text-muted mb-0">Vous n'avez pas encore ajouté de biens à vos favoris.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>