<?php
// auth/inscription.php
require_once '../config/db.php';
include_once '../includes/header.php';

// Si l'utilisateur est déjà connecté, on le redirige vers l'accueil
if (isset($_SESSION['id_user'])) {
    header('Location: ../index.php');
    exit();
}

$erreurs = [];
$succes = false;

// Traitement du formulaire après soumission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $mot_de_passe = $_POST['mot_de_passe'];
    $role = $_POST['role']; // 'Client' ou 'Commercial'

    // Validation des données côté serveur
    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($role)) {
        $erreurs[] = "Tous les champs obligatoires (*) doivent être remplis.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "Le format de l'adresse email n'est pas valide.";
    }
    if (!in_array($role, ['Client', 'Commercial'])) {
        $erreurs[] = "Le rôle sélectionné est invalide.";
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
            $erreurs[] = "Erreur de base de données : " . $e->getMessage();
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
            $erreurs[] = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}
?>

<main class="container my-5" style="max-width: 600px;">
    <div class="card shadow-sm border-0 p-4 bg-white rounded-3">
        <h3 class="fw-bold text-center mb-4 text-dark">Créer un compte</h3>

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
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label for="prenom" class="form-label small fw-bold text-secondary">Prénom *</label>
                        <input type="text" name="prenom" id="prenom" class="form-control" required value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>">
                    </div>
                    <div class="col-sm-6">
                        <label for="nom" class="form-label small fw-bold text-secondary">Nom *</label>
                        <input type="text" name="nom" id="nom" class="form-control" required value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                    </div>
                    <div class="col-12">
                        <label for="email" class="form-label small fw-bold text-secondary">Adresse Email *</label>
                        <input type="email" name="email" id="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="col-12">
                        <label for="telephone" class="form-label small fw-bold text-secondary">Téléphone</label>
                        <input type="tel" name="telephone" id="telephone" class="form-control" value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                    </div>
                    <div class="col-12">
                        <label for="mot_de_passe" class="form-label small fw-bold text-secondary">Mot de passe *</label>
                        <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label for="role" class="form-label small fw-bold text-secondary">Vous êtes ? *</label>
                        <select name="role" id="role" class="form-select" required>
                            <option value="Client" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Client') ? 'selected' : ''; ?>>Client (Pour louer un bien)</option>
                            <option value="Commercial" <?php echo (isset($_POST['role']) && $_POST['role'] === 'Commercial') ? 'selected' : ''; ?>>Commercial (Pour gérer l'agence)</option>
                        </select>
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
</main>

<?php include_once '../includes/footer.php'; ?>