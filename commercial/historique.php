<?php
// commercial/historique.php
require_once '../config/bootstrap.php';

// Sécurisation : uniquement pour les commerciaux connectés
require_role($pdo, 'Commercial', '../auth/connexion.php');

include_once '../includes/header.php';

$recherche = trim($_GET['q'] ?? '');
$id_client = (int)($_GET['id_client'] ?? 0);

// --- 1. LISTE DES CLIENTS (avec recherche optionnelle par nom/prénom/email) ---
try {
    if ($recherche !== '') {
        $stmt = $pdo->prepare("SELECT id_user, nom, prenom, email, statut FROM UTILISATEURS
                                WHERE role = 'Client'
                                AND (nom LIKE :q OR prenom LIKE :q OR email LIKE :q)
                                ORDER BY nom ASC");
        $stmt->execute(['q' => '%' . $recherche . '%']);
    } else {
        $stmt = $pdo->query("SELECT id_user, nom, prenom, email, statut FROM UTILISATEURS
                              WHERE role = 'Client' ORDER BY nom ASC");
    }
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Erreur lors de la récupération des clients : ' . $e->getMessage());
    $clients = [];
}

// --- 2. HISTORIQUE DU CLIENT SÉLECTIONNÉ ---
$client_selectionne = null;
$reservations = [];

if ($id_client > 0) {
    try {
        $stmtClient = $pdo->prepare("SELECT id_user, nom, prenom, email, telephone, statut, date_creation
                                      FROM UTILISATEURS WHERE id_user = :id_client AND role = 'Client'");
        $stmtClient->execute(['id_client' => $id_client]);
        $client_selectionne = $stmtClient->fetch();

        if ($client_selectionne) {
            $stmtRes = $pdo->prepare("SELECT r.*, b.titre AS bien_titre, b.type AS bien_type, b.prix_mensuel
                                       FROM RESERVATIONS r
                                       JOIN BIENS b ON r.id_bien = b.id_bien
                                       WHERE r.id_client = :id_client
                                       ORDER BY r.date_demande DESC");
            $stmtRes->execute(['id_client' => $id_client]);
            $reservations = $stmtRes->fetchAll();
        }
    } catch (PDOException $e) {
        error_log('Erreur lors de la récupération de l\'historique : ' . $e->getMessage());
    }
}

$nb_validees = count(array_filter($reservations, fn($r) => $r['statut'] === 'Validé'));
$nb_annulees = count(array_filter($reservations, fn($r) => $r['statut'] === 'Annulé'));
$nb_attente = count(array_filter($reservations, fn($r) => $r['statut'] === 'En attente'));
?>

<main class="container my-5">
    <h3 class="fw-bold text-dark mb-2"><i class="bi bi-clock-history me-2"></i>Historique des Locations</h3>
    <p class="text-muted small mb-4">Recherchez un client pour consulter l'ensemble de ses demandes et locations.</p>

    <div class="row g-4">
        <!-- Colonne Gauche : Liste / recherche des clients -->
        <div class="col-lg-4">
            <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                <form method="GET" action="historique.php" class="mb-3">
                    <div class="input-group input-group-sm">
                        <input type="text" name="q" class="form-control" placeholder="Nom, prénom ou email..." value="<?php echo htmlspecialchars($recherche); ?>">
                        <button type="submit" class="btn btn-dark"><i class="bi bi-search"></i></button>
                    </div>
                </form>

                <?php if (count($clients) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($clients as $c): ?>
                            <a href="historique.php?id_client=<?php echo $c['id_user']; ?><?php echo $recherche !== '' ? '&q=' . urlencode($recherche) : ''; ?>"
                               class="list-group-item list-group-item-action <?php echo $c['id_user'] === $id_client ? 'active' : ''; ?>">
                                <div class="fw-bold small"><?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?></div>
                                <div class="text-xs <?php echo $c['id_user'] === $id_client ? 'text-white-50' : 'text-muted'; ?>" style="font-size:0.8rem;">
                                    <?php echo htmlspecialchars($c['email']); ?>
                                    <?php if ($c['statut'] !== 'Actif'): ?> · <span class="fw-bold">Inactif</span><?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted small my-3">Aucun client trouvé.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Colonne Droite : Détail de l'historique -->
        <div class="col-lg-8">
            <?php if (!$client_selectionne): ?>
                <div class="card p-5 border-0 shadow-sm bg-white rounded-3 text-center text-muted">
                    <i class="bi bi-person-lines-fill fs-1 mb-3"></i>
                    <p class="mb-0">Sélectionnez un client dans la liste pour afficher son historique de locations.</p>
                </div>
            <?php else: ?>
                <div class="card p-4 border-0 shadow-sm bg-white rounded-3 mb-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($client_selectionne['prenom'] . ' ' . $client_selectionne['nom']); ?></h4>
                            <div class="text-muted small">
                                <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($client_selectionne['email']); ?><br>
                                <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($client_selectionne['telephone'] ?: 'Non renseigné'); ?><br>
                                <i class="bi bi-calendar3 me-1"></i>Client depuis le <?php echo date('d/m/Y', strtotime($client_selectionne['date_creation'])); ?>
                            </div>
                        </div>
                        <span class="badge <?php echo $client_selectionne['statut'] === 'Actif' ? 'bg-success' : 'bg-danger'; ?> rounded-pill px-3 py-2">
                            <?php echo htmlspecialchars($client_selectionne['statut']); ?>
                        </span>
                    </div>
                    <div class="row g-2 mt-3 text-center">
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3">
                                <div class="fw-bold fs-5 text-dark"><?php echo count($reservations); ?></div>
                                <div class="text-xs text-muted">Total demandes</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3">
                                <div class="fw-bold fs-5 text-success"><?php echo $nb_validees; ?></div>
                                <div class="text-xs text-muted">Locations validées</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded-3">
                                <div class="fw-bold fs-5 text-danger"><?php echo $nb_annulees; ?></div>
                                <div class="text-xs text-muted">Annulées</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive-custom">
                    <?php if (count($reservations) > 0): ?>
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Bien</th>
                                    <th>Période</th>
                                    <th>Demandée le</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reservations as $res): ?>
                                    <tr>
                                        <td class="text-muted">#<?php echo $res['id_reservation']; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($res['bien_titre']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($res['bien_type']); ?> — <?php echo number_format($res['prix_mensuel'], 0, ',', ' '); ?> FCFA/mois</small>
                                        </td>
                                        <td>
                                            du <?php echo date('d/m/Y', strtotime($res['date_debut'])); ?><br>
                                            au <?php echo date('d/m/Y', strtotime($res['date_fin'])); ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($res['date_demande'])); ?></td>
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-center text-muted my-4">Ce client n'a encore soumis aucune demande de réservation.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>
