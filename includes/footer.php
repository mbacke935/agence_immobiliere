<!-- includes/footer.php -->
    <?php
        $footer_current_dir = basename(dirname($_SERVER['SCRIPT_NAME']));
        $footer_prefix = ($footer_current_dir === 'auth' || $footer_current_dir === 'client' || $footer_current_dir === 'commercial') ? '../' : '';
    ?>
    <footer class="footer-premium pt-5 pb-4 mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-5">
                    <h6 class="fw-bold mb-3"><i class="bi bi-building me-2"></i>ImmoAgence</h6>
                    <p class="small mb-0">Une sélection exigeante de villas et d'appartements, accompagnée par une équipe commerciale dédiée à chaque étape de votre location.</p>
                </div>
                <div class="col-md-3">
                    <h6 class="fw-bold mb-3">Navigation</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="<?php echo $footer_prefix; ?>index.php">Nos biens</a></li>
                        <li class="mb-2"><a href="<?php echo $footer_prefix; ?>auth/connexion.php">Connexion</a></li>
                        <li class="mb-2"><a href="<?php echo $footer_prefix; ?>auth/inscription.php">Créer un compte</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold mb-3">Contact</h6>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Dakar, Sénégal</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i>+221 77 000 00 00</li>
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i>contact@immoagence.sn</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom text-center small mt-4 pt-3">
                &copy; <?php echo date('Y'); ?> ImmoAgence. Tous droits réservés.
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle avec Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Validation et scripts personnalisés -->
    <script src="/agence_immobiliere/js/script.js"></script>
</body>
</html>
