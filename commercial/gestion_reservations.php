<?php
// commercial/gestion_reservations.php
require_once '../config/db.php';
include_once '../includes/header.php';

// Sécurisation : uniquement pour les commerciaux connectés
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Commercial') {
    header('Location: ../auth/connexion.php');
    exit();
}

$message = '';

// --- 1. TRAITEMENT DU CHANGEMENT DE STATUT (VALIDATION / ANNULATION) ---
if (isset($_GET['action']) && isset($_GET['id_res'])) {
    $id_res = (int)$_GET['id_res'];
    $action = $_GET['action'];

    if ($id_res > 0 && in_array($action, ['valider', 'annuler'])) {
        $nouveau_statut = ($action === 'valider') ? 'Validé' : 'Annulé';
        
        try {
            $sql = "UPDATE RESERVATIONS SET statut = :statut WHERE id_reservation = :id_res";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'statut' => $nouveau_statut,
                'id_res' => $id_res
            ]);
            $message = "<div class='alert alert-success text-center small'>La réservation #$id_res a été mise à jour avec le statut : <strong>$nouveau_statut</strong>.</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger text-center small'>Erreur lors du changement de statut : " . $e->getMessage() . "</div>";
        }
    }
}

// --- 2. RÉCUPÉRATION DES RÉSERVATIONS TOUTES CONFIGURATIONS CONFONDUES ---
try {
    $sql = "SELECT r.*, b.titre as bien_titre, b.type as bien_type, b.prix_mensuel, 
                   u.nom as client_nom, u.prenom as client_prenom, u.telephone as client_tel, u.email as client_email
            FROM RESERVATIONS r
            JOIN BIENS b ON r.id_bien = b.id_bien
            JOIN UTILISATEURS u ON r.id_client = u.id_user
            ORDER BY FIELD(r.statut, 'En attente', 'Validé', 'Annulé'), r.id_reservation DESC";
            
    $stmt = $pdo->query($sql);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur de récupération des demandes de réservation : " . $e->getMessage());
}
?>

<main class="container my-5">
    <h3 class="fw-bold text-dark mb-2"><i class="bi bi-clipboard-check me-2"></i>Suivi Global des Réservations</h3>
    <p class="text-muted small mb-4">Gérez les demandes de location et traitez-les par ordre de priorité.</p>

    <?php if (!empty($message)) echo $message; ?>

    <div class="table-responsive-custom">
        <?php if (count($reservations) > 0): ?>
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Bien Immobilier</th>
                        <th>Période Demandée</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $res): ?>
                        <tr>
                            <td class="text-muted">#<?php echo $res['id_reservation']; ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($res['client_prenom'] . ' ' . $res['client_nom']); ?></div>
                                <div class="text-muted text-xs" style="font-size: 0.8rem;">
                                    📞 <?php echo htmlspecialchars($res['client_tel'] ?: 'Non renseigné'); ?><br>
                                    ✉️ <?php echo htmlspecialchars($res['client_email']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-secondary"><?php echo htmlspecialchars($res['bien_titre']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($res['bien_type']); ?> — <?php echo number_format($res['prix_mensuel'], 0, ',', ' '); ?> FCFA/mois</small>
                            </td>
                            <td>
                                du <span class="fw-bold"><?php echo date('d/m/Y', strtotime($res['date_debut'])); ?></span><br>
                                au <span class="fw-bold"><?php echo date('d/m/Y', strtotime($res['date_fin'])); ?></span>
                            </td>
                            <td>
                                <?php 
                                    $badge_class = 'bg-warning text-dark';
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
                                <?php if ($res['statut'] === 'En attente'): ?>
                                    <a href="gestion_reservations.php?action=valider&id_res=<?php echo $res['id_reservation']; ?>" class="btn btn-xs btn-success rounded-pill px-3 me-1 fw-bold" onclick="return confirm('Confirmer et valider cette location ?');">Valider</a>
                                    <a href="gestion_reservations.php?action=annuler&id_res=<?php echo $res['id_reservation']; ?>" class="btn btn-xs btn-outline-danger rounded-pill px-3" onclick="return confirm('Refuser et annuler cette demande ?');">Annuler</a>
                                <?php else: ?>
                                    <span class="text-muted small">Traité</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center text-muted my-4">Aucune demande de réservation n'a été soumise pour le moment.</p>
        <?php endif; ?>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>