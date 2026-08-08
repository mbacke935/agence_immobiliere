<?php
// commercial/gestion_clients.php
require_once '../config/bootstrap.php';

// Sécurisation : uniquement pour les commerciaux connectés
require_role($pdo, 'Commercial', '../auth/connexion.php');

include_once '../includes/header.php';

$message = '';

// --- 1. TRAITEMENT DE LA BASCULE DE STATUT (ACTIVER / SUSPENDRE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $id_user_target = (int)($_POST['id_user'] ?? 0);
    $action = $_POST['action'] ?? '';

    // Empêcher un commercial de s'auto-désactiver ou de s'auto-supprimer par mégarde
    if ($id_user_target === (int)$_SESSION['id_user']) {
        $message = "<div class='alert alert-danger text-center small'>Action impossible : Vous ne pouvez pas modifier votre propre compte depuis cette page.</div>";
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
    } elseif ($id_user_target > 0 && $action === 'supprimer') {
        // La suppression n'est autorisée que pour les comptes Client (jamais un autre Commercial)
        try {
            $stmtRole = $pdo->prepare("SELECT role FROM UTILISATEURS WHERE id_user = :id_user");
            $stmtRole->execute(['id_user' => $id_user_target]);
            $roleCible = $stmtRole->fetchColumn();

            if ($roleCible !== 'Client') {
                $message = "<div class='alert alert-danger text-center small'>Seuls les comptes Client peuvent être supprimés depuis cette page.</div>";
            } else {
                $stmtDel = $pdo->prepare("DELETE FROM UTILISATEURS WHERE id_user = :id_user AND role = 'Client'");
                $stmtDel->execute(['id_user' => $id_user_target]);
                $message = "<div class='alert alert-success text-center small'>Le compte client #$id_user_target a été supprimé définitivement, avec son historique de favoris et de réservations.</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger text-center small'>Erreur lors de la suppression : " . $e->getMessage() . "</div>";
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
                                    <?php if ($user['role'] === 'Client'): ?>
                                        <a href="historique.php?id_client=<?php echo $user['id_user']; ?>" class="btn btn-xs btn-outline-secondary rounded-pill px-3 me-1">Historique</a>
                                    <?php endif; ?>
                                    <?php if ($user['statut'] === 'Actif'): ?>
                                        <form method="POST" action="gestion_clients.php" class="d-inline"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>"><input type="hidden" name="id_user" value="<?php echo $user['id_user']; ?>"><input type="hidden" name="action" value="suspendre"><button type="submit" class="btn btn-xs btn-outline-danger rounded-pill px-3 me-1" onclick="return confirm('Suspendre temporairement ce compte ? L\'utilisateur ne pourra plus se connecter.');">Suspendre</button></form>
                                    <?php else: ?>
                                        <form method="POST" action="gestion_clients.php" class="d-inline"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>"><input type="hidden" name="id_user" value="<?php echo $user['id_user']; ?>"><input type="hidden" name="action" value="activer"><button type="submit" class="btn btn-xs btn-success rounded-pill px-3 me-1" onclick="return confirm('Réactiver l\'accès pour ce compte ?');">Activer</button></form>
                                    <?php endif; ?>
                                    <?php if ($user['role'] === 'Client'): ?>
                                        <form method="POST" action="gestion_clients.php" class="d-inline"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>"><input type="hidden" name="id_user" value="<?php echo $user['id_user']; ?>"><input type="hidden" name="action" value="supprimer"><button type="submit" class="btn btn-xs btn-danger rounded-pill px-3" onclick="return confirm('Supprimer définitivement ce compte client ? Ses favoris et réservations seront aussi supprimés. Cette action est irréversible.');">Supprimer</button></form>
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
