<?php
// client/mes_reservations.php
require_once '../config/db.php';
include_once '../includes/header.php';

// Sécurisation de l'accès : uniquement pour les clients connectés
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Client') {
    header('Location: ../auth/connexion.php');
    exit();
}

$id_client = $_SESSION['id_user'];

try {
    // Récupération de l'historique des réservations du client
    $sql = "SELECT r.*, b.titre, b.type, b.adresse, b.prix_mensuel 
            FROM RESERVATIONS r 
            JOIN BIENS b ON r.id_bien = b.id_bien 
            WHERE r.id_client = :id_client 
            ORDER BY r.id_reservation DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_client' => $id_client]);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération de vos réservations : " . $e->getMessage());
}
?>

<main class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="bi bi-calendar-check me-2"></i>Mon Suivi de Réservations</h3>
        <a href="../index.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">Nouvelle recherche</a>
    </div>

    <div class="table-responsive-custom">
        <?php if (count($reservations) > 0): ?>
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Bien</th>
                        <th>Date Début</th>
                        <th>Date Fin</th>
                        <th>Loyer Mensuel</th>
                        <th>Statut</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $res): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($res['titre']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($res['type']); ?> — <?php echo htmlspecialchars($res['adresse']); ?></small>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($res['date_debut'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($res['date_fin'])); ?></td>
                            <td class="fw-bold price-tag"><?php echo number_format($res['prix_mensuel'], 0, ',', ' '); ?> FCFA</td>
                            <td>
                                <?php 
                                    // Style de badge selon le statut
                                    $badge_class = 'bg-warning text-dark'; // En attente
                                    if ($res['statut'] === 'Validé') {
                                        $badge_class = 'bg-success text-white';
                                    } elseif ($res['statut'] === 'Annulé') {
                                        $badge_class = 'bg-danger text-white';
                                    }
                                ?>
                                <span class="badge <?php echo $badge_class; ?> rounded-pill px-3 py-2">
                                    <?php echo htmlspecialchars($res['statut']); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="../detail_bien.php?id=<?php echo $res['id_bien']; ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3">Fiche du bien</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="fs-5 text-muted mb-0">Vous n'avez pas encore effectué de demande de réservation.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>