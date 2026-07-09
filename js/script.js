/* js/script.js */

document.addEventListener('DOMContentLoaded', function () {
    
    // --- 1. VALIDATION DES FORMULAIRES BOOTSTRAP ---
    // Récupère tous les formulaires auxquels on veut appliquer des validations
    const forms = document.querySelectorAll('#form-inscription, #form-connexion');
    
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // --- 2. VALIDATION CHRONOLOGIQUE DES DATES DE RÉSERVATION ---
    const dateDebutInput = document.getElementById('date_debut');
    const dateFinInput = document.getElementById('date_fin');

    if (dateDebutInput && dateFinInput) {
        // Bloquer la sélection de dates passées dans le calendrier HTML5
        const aujourdhui = new Date().toISOString().split('T')[0];
        dateDebutInput.setAttribute('min', aujourdhui);

        // Mettre à jour dynamiquement la date minimale de fin selon la date de début choisie
        dateDebutInput.addEventListener('change', function () {
            if (dateDebutInput.value) {
                // La date de fin doit être au moins le lendemain du début
                let dateMinFin = new Date(dateDebutInput.value);
                dateMinFin.setDate(dateMinFin.getDate() + 1);
                
                const dateMinFinStr = dateMinFin.toISOString().split('T')[0];
                dateFinInput.setAttribute('min', dateMinFinStr);
                
                if (dateFinInput.value && dateFinInput.value <= dateDebutInput.value) {
                    dateFinInput.value = dateMinFinStr;
                }
            }
        });
    }
});