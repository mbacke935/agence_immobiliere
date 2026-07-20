<?php
// auth/inscription.php
require_once '../config/bootstrap.php';

// Si l'utilisateur est déjà connecté, on le redirige vers l'accueil
if (isset($_SESSION['id_user'])) {
    header('Location: ../index.php');
    exit();
}

include_once '../includes/header.php';

$erreurs = [];
$succes = false;

// Traitement du formulaire après soumission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $erreurs[] = 'Votre session a expiré. Veuillez réessayer.';
    } else {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $mot_de_passe = $_POST['mot_de_passe'];
    $role = 'Client';

    // Validation des données côté serveur
    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($role)) {
        $erreurs[] = "Tous les champs obligatoires (*) doivent être remplis.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "Le format de l'adresse email n'est pas valide.";
    }

    // Vérification de l'unicité de l'email
    if (empty($erreurs)) {
        try {
            $stmt = $pdo->prepare("SELECT id_user FROM UTILISATEURS WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $erreurs[] = "Cette adresse email est déjà enregistrée.";
            }
        } catch (PDOException $e) {
            error_log('Erreur de vérification d’inscription : ' . $e->getMessage());
            $erreurs[] = "Impossible de vérifier cette adresse email pour le moment.";
        }
    }
    }

    // Insertion si aucune erreur
    if (empty($erreurs)) {
        try {
            // Hachage sécurisé du mot de passe
            $password_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO UTILISATEURS (nom, prenom, email, telephone, mot_de_passe, role, statut) 
                    VALUES (:nom, :prenom, :email, :telephone, :mot_de_passe, :role, 'Actif')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'mot_de_passe' => $password_hash,
                'role' => $role
            ]);
            
            $succes = true;
        } catch (PDOException $e) {
            error_log('Erreur d’inscription : ' . $e->getMessage());
            $erreurs[] = "Impossible de créer le compte pour le moment.";
        }
    }
}
?>

<main class="container my-5" style="max-width: 950px;">
    <div class="card auth-split">
        <div class="row g-0">
            <div class="col-md-5 d-none d-md-flex">
                <div class="auth-visual">
                    <div class="eyebrow mb-2">Bienvenue</div>
                    <h2 class="mb-3">Rejoignez ImmoAgence</h2>
                    <p class="mb-0">Créez votre compte pour réserver un bien, suivre vos favoris ou, en tant que commercial, gérer le catalogue de l'agence.</p>
                </div>
            </div>
            <div class="col-md-7">
                <div class="auth-form-panel">
                    <h3 class="fw-bold mb-4 text-dark">Créer un compte</h3>

                    <?php if ($succes): ?>
                        <div class="alert alert-success text-center" role="alert">
                            Inscription réussie ! Vous pouvez maintenant vous <a href="connexion.php" class="alert-link">connecter ici</a>.
                        </div>
                    <?php else: ?>

                        <?php if (!empty($erreurs)): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($erreurs as $erreur): ?>
                                        <li><?php echo htmlspecialchars($erreur); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="inscription.php" id="form-inscription" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label for="prenom" class="form-label small fw-bold">Prénom *</label>
                                    <input type="text" name="prenom" id="prenom" class="form-control" required value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>">
                                </div>
                                <div class="col-sm-6">
                                    <label for="nom" class="form-label small fw-bold">Nom *</label>
                                    <input type="text" name="nom" id="nom" class="form-control" required value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                                </div>
                                <div class="col-12">
                                    <label for="email" class="form-label small fw-bold">Adresse Email *</label>
                                    <input type="email" name="email" id="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                <div class="col-12">
                                    <label for="telephone" class="form-label small fw-bold">Téléphone</label>
                                    <input type="tel" name="telephone" id="telephone" class="form-control" value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                                </div>
                                <div class="col-12">
                                    <label for="mot_de_passe" class="form-label small fw-bold">Mot de passe *</label>
                                    <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required>
                                </div>
                                <div class="col-12">
                                    <p class="small text-muted mb-0">Les inscriptions publiques créent un compte Client. Les comptes commerciaux sont créés par l'administration de l'agence.</p>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">S'inscrire</button>
                                </div>
                            </div>
                        </form>

                        <p class="text-center small text-muted mt-3 mb-0">
                            Déjà inscrit ? <a href="connexion.php" class="text-decoration-none">Connectez-vous</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>
