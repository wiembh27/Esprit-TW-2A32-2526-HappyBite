document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("profilForm");

    if (!form) {
        console.log("Formulaire introuvable");
        return;
    }

    const tailleInput = document.querySelector('input[name="taille"]');
    const poidsInput  = document.querySelector('input[name="poids_actuel"]');

    const errTaille = document.getElementById("err-taille");
    const errPoids  = document.getElementById("err-poids");

    function resetErrors() {
        if (errTaille) {
            errTaille.textContent = "";
            errTaille.style.display = "none";
        }
        if (errPoids) {
            errPoids.textContent = "";
            errPoids.style.display = "none";
        }
    }

    function validate() {
        let valid = true;

        resetErrors();

        const taille = parseFloat(tailleInput.value);
        const poids  = parseFloat(poidsInput.value);

        // Validation taille
        if (isNaN(taille) || taille < 55 || taille > 251) {
            if (errTaille) {
                errTaille.textContent = "⚠️ Taille invalide(55 - 251 cm)";
                errTaille.style.display = "block";
            }
            valid = false;
        }

        // Validation poids
        if (isNaN(poids) || poids < 12 || poids > 700) {
            if (errPoids) {
                errPoids.textContent = "⚠️ Poids invalide (12 - 700 kg)";
                errPoids.style.display = "block";
            }
            valid = false;
        }

        return valid;
    }

    // Validation en temps réel
    if (tailleInput) {
        tailleInput.addEventListener("input", validate);
    }

    if (poidsInput) {
        poidsInput.addEventListener("input", validate);
    }

    // Validation à la soumission
    form.addEventListener("submit", function (e) {
        if (!validate()) {
            e.preventDefault();
        }
    });

});