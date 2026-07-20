<?php
// auth/connexion.php
require_once '../config/bootstrap.php';

// Si l'utilisateur est déjà connecté, on le redirige vers l'accueil
if (isset($_SESSION['id_user'])) {
    header('Location: ../index.php');
    exit();
}

include_once '../includes/header.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $erreur = 'Votre session a expiré. Veuillez réessayer.';
    } else {
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];

    if (empty($email) || empty($mot_de_passe)) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Le format de l'adresse email est incorrect.";
    } else {
        try {
            // Récupération de l'utilisateur
            $stmt = $pdo->prepare("SELECT * FROM UTILISATEURS WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Vérification du mot de passe et du statut du compte
            if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
                if ($user['statut'] === 'Inactif') {
                    $erreur = "Votre compte a été désactivé par un commercial.";
                } else {
                    // Authentification réussie : initialisation de la session
                    $_SESSION['id_user'] = $user['id_user'];
                    $_SESSION['nom'] = $user['nom'];
                    $_SESSION['prenom'] = $user['prenom'];
                    $_SESSION['role'] = $user['role'];
                    session_regenerate_id(true);

                    // Redirection selon le rôle ou vers la page d'accueil
                    header('Location: ../index.php');
                    exit();
                }
            } else {
                $erreur = "Identifiants incorrects.";
            }
        } catch (PDOException $e) {
            error_log('Erreur de connexion utilisateur : ' . $e->getMessage());
            $erreur = 'Impossible de vous connecter pour le moment. Veuillez réessayer.';
        }
    }
    }
}
?>

<main class="container my-5" style="max-width: 900px;">
    <div class="card auth-split">
        <div class="row g-0">
            <div class="col-md-5 d-none d-md-flex">
                <div class="auth-visual">
                    <div class="eyebrow mb-2">Bon retour</div>
                    <h2 class="mb-3">Retrouvez votre espace</h2>
                    <p class="mb-0">Accédez à vos favoris, suivez vos demandes de réservation ou gérez le catalogue de l'agence.</p>
                </div>
            </div>
            <div class="col-md-7">
                <div class="auth-form-panel">
                    <h3 class="fw-bold mb-4 text-dark">Connexion</h3>

                    <?php if (!empty($erreur)): ?>
                        <div class="alert alert-danger text-center small" role="alert">
                            <?php echo htmlspecialchars($erreur); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="connexion.php" id="form-connexion" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label small fw-bold">Adresse Email</label>
                            <input type="email" name="email" id="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="mb-4">
                            <label for="mot_de_passe" class="form-label small fw-bold">Mot de passe</label>
                            <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">Se connecter</button>
                    </form>

                    <p class="text-center small text-muted mt-3 mb-0">
                        Pas encore de compte ? <a href="inscription.php" class="text-decoration-none">Inscrivez-vous</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>
