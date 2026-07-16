<?php
// client/reservations.php
require_once '../config/db.php';
include_once '../includes/header.php';

// Sécurisation de l'accès : uniquement pour les clients connectés
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Client') {
    header('Location: ../auth/connexion.php');
    exit();
}

$id_bien = isset($_GET['id_bien']) ? (int)$_GET['id_bien'] : 0;
$id_client = $_SESSION['id_user'];
$erreur = '';
$succes = false;

// Vérification de l'existence du bien
try {
    $stmtBien = $pdo->prepare("SELECT * FROM BIENS WHERE id_bien = :id_bien");
    $stmtBien->execute(['id_bien' => $id_bien]);
    $bien = $stmtBien->fetch();

    if (!$bien) {
        die("<div class='container my-5 alert alert-danger'>Ce bien immobilier n'existe pas ou a été supprimé.</div>");
    }
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Traitement de la demande de réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $aujourdhui = date('Y-m-d');

    // Validation des dates
    if (empty($date_debut) || empty($date_fin)) {
        $erreur = "Veuillez renseigner toutes les dates.";
    } elseif ($date_debut < $aujourdhui) {
        $erreur = "La date de début ne peut pas être une date passée.";
    } elseif ($date_fin <= $date_debut) {
        $erreur = "La date de fin doit être strictement postérieure à la date de début.";
    } else {
        try {
            // Insertion de la réservation avec le statut 'En attente'
            $sql = "INSERT INTO RESERVATIONS (date_debut, date_fin, statut, id_client, id_bien) 
                    VALUES (:date_debut, :date_fin, 'En attente', :id_client, :id_bien)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'date_debut' => $date_debut,
                'date_fin' => $date_fin,
                'id_client' => $id_client,
                'id_bien' => $id_bien
            ]);
            $succes = true;
        } catch (PDOException $e) {
            $erreur = "Erreur lors de la réservation : " . $e->getMessage();
        }
    }
}
?>

<main class="container my-5" style="max-width: 650px;">
    <div class="card shadow-sm border-0 p-4 bg-white">
        <h3 class="fw-bold text-center mb-2 text-dark"><i class="bi bi-calendar-plus me-2"></i>Demande de Réservation</h3>
        <p class="text-muted text-center small mb-4">Vous initiez une demande de location pour le bien ci-dessous</p>

        <!-- Petit récapitulatif du bien sélectionné -->
        <div class="p-3 bg-light rounded-3 mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($bien['titre']); ?></h6>
                <small class="text-muted"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($bien['adresse']); ?></small>
            </div>
            <span class="fw-bold price-tag"><?php echo number_format($bien['prix_mensuel'], 0, ',', ' '); ?> FCFA</span>
        </div>

        <?php if ($succes): ?>
            <div class="alert alert-success text-center mb-0" role="alert">
                <h5 class="fw-bold">Demande enregistrée !</h5>
                <p class="small mb-3">Votre demande est actuellement en attente de validation par notre équipe commerciale.</p>
                <a href="mes_reservations.php" class="btn btn-success btn-sm rounded-pill px-4">Suivre mes réservations</a>
            </div>
        <?php else: ?>
            
            <?php if (!empty($erreur)): ?>
                <div class="alert alert-danger text-center small" role="alert">
                    <?php echo htmlspecialchars($erreur); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="reservations.php?id_bien=<?php echo $bien['id_bien']; ?>" novalidate>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="date_debut" class="form-label small fw-bold text-secondary">Date d'entrée souhaitée</label>
                        <input type="date" name="date_debut" id="date_debut" class="form-control" required value="<?php echo isset($_POST['date_debut']) ? htmlspecialchars($_POST['date_debut']) : ''; ?>">
                    </div>
                    <div class="col-sm-6">
                        <label for="date_fin" class="form-label small fw-bold text-secondary">Date de sortie prévue</label>
                        <input type="date" name="date_fin" id="date_fin" class="form-control" required value="<?php echo isset($_POST['date_fin']) ? htmlspecialchars($_POST['date_fin']) : ''; ?>">
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">Confirmer ma demande</button>
                    </div>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <a href="../detail_bien.php?id=<?php echo $bien['id_bien']; ?>" class="text-secondary small text-decoration-none">← Retour à la fiche du bien</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>