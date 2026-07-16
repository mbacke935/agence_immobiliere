<?php
// commercial/gestion_clients.php
require_once '../config/db.php';
include_once '../includes/header.php';

// Sécurisation : uniquement pour les commerciaux connectés
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'Commercial') {
    header('Location: ../auth/connexion.php');
    exit();
}

$message = '';

// --- 1. TRAITEMENT DE LA BASCULE DE STATUT (ACTIVER / SUSPENDRE) ---
if (isset($_GET['action']) && isset($_GET['id_user'])) {
    $id_user_target = (int)$_GET['id_user'];
    $action = $_GET['action'];

    // Empêcher un commercial de s'auto-désactiver par mégarde
    if ($id_user_target === (int)$_SESSION['id_user']) {
        $message = "<div class='alert alert-danger text-center small'>Action impossible : Vous ne pouvez pas désactiver votre propre compte.</div>";
    } elseif ($id_user_target > 0 && in_array($action, ['activer', 'suspendre'])) {
        $nouveau_statut = ($action === 'activer') ? 'Actif' : 'Inactif';
        
        try {
            $sql = "UPDATE UTILISATEURS SET statut = :statut WHERE id_user = :id_user";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'statut' => $nouveau_statut,
                'id_user' => $id_user_target
            ]);
            $message = "<div class='alert alert-success text-center small'>Le statut de l'utilisateur #$id_user_target a été configuré sur : <strong>$nouveau_statut</strong>.</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger text-center small'>Erreur lors du changement de statut : " . $e->getMessage() . "</div>";
        }
    }
}

// --- 2. RÉCUPÉRATION DE TOUS LES UTILISATEURS ---
try {
    $stmt = $pdo->query("SELECT id_user, nom, prenom, email, telephone, role, statut FROM UTILISATEURS ORDER BY role ASC, nom ASC");
    $utilisateurs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erreur lors de la récupération des comptes utilisateurs : " . $e->getMessage());
}
?>

<main class="container my-5">
    <h3 class="fw-bold text-dark mb-2"><i class="bi bi-people me-2"></i>Gestion des Comptes</h3>
    <p class="text-muted small mb-4">Consultez la liste des utilisateurs et suspendez ou activez des accès à la plateforme.</p>

    <?php if (!empty($message)) echo $message; ?>

    <div class="table-responsive-custom">
        <?php if (count($utilisateurs) > 0): ?>
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nom & Prénom</th>
                        <th>Adresse Email</th>
                        <th>Téléphone</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $user): ?>
                        <tr>
                            <td class="text-muted">#<?php echo $user['id_user']; ?></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['telephone'] ?: 'Non renseigné'); ?></td>
                            <td>
                                <span class="badge <?php echo $user['role'] === 'Commercial' ? 'bg-dark' : 'bg-secondary'; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $user['statut'] === 'Actif' ? 'bg-success' : 'bg-danger'; ?> rounded-pill px-2">
                                    <?php echo htmlspecialchars($user['statut']); ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($user['id_user'] !== (int)$_SESSION['id_user']): ?>
                                    <?php if ($user['statut'] === 'Actif'): ?>
                                        <a href="gestion_clients.php?action=suspendre&id_user=<?php echo $user['id_user']; ?>" class="btn btn-xs btn-outline-danger rounded-pill px-3" onclick="return confirm('Suspendre temporairement ce compte ? L\'utilisateur ne pourra plus se connecter.');">Suspendre</a>
                                    <?php else: ?>
                                        <a href="gestion_clients.php?action=activer&id_user=<?php echo $user['id_user']; ?>" class="btn btn-xs btn-success rounded-pill px-3" onclick="return confirm('Réactiver l\'accès pour ce compte ?');">Activer</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small italic">Votre compte</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center text-muted my-4">Aucun compte utilisateur trouvé.</p>
        <?php endif; ?>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>