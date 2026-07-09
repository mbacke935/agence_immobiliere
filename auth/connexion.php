<?php
// auth/connexion.php
require_once '../config/db.php';
include_once '../includes/header.php';

// Si l'utilisateur est déjà connecté, on le redirige vers l'accueil
if (isset($_SESSION['id_user'])) {
    header('Location: ../index.php');
    exit();
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

                    // Redirection selon le rôle ou vers la page d'accueil
                    header('Location: ../index.php');
                    exit();
                }
            } else {
                $erreur = "Identifiants incorrects.";
            }
        } catch (PDOException $e) {
            $erreur = "Erreur de base de données : " . $e->getMessage();
        }
    }
}
?>

<main class="container my-5" style="max-width: 450px;">
    <div class="card shadow-sm border-0 p-4 bg-white rounded-3">
        <h3 class="fw-bold text-center mb-4 text-dark">Connexion</h3>

        <?php if (!empty($erreur)): ?>
            <div class="alert alert-danger text-center small" role="alert">
                <?php echo htmlspecialchars($erreur); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="connexion.php" id="form-connexion" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label small fw-bold text-secondary">Adresse Email</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="mb-4">
                <label for="mot_de_passe" class="form-label small fw-bold text-secondary">Mot de passe</label>
                <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill fw-bold">Se connecter</button>
        </form>
        
        <p class="text-center small text-muted mt-3 mb-0">
            Pas encore de compte ? <a href="inscription.php" class="text-decoration-none">Inscrivez-vous</a>
        </p>
    </div>
</main>

<?php include_once '../includes/footer.php'; ?>