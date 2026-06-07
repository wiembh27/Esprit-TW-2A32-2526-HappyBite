document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("profilForm");
    if (!form) return;

    const get = (name) => form.querySelector(`[name="${name}"]`);

    const poidsInput = get("poids");
    const caloriesInput = get("calories");
    const sommeilInput = get("sommeil_heures");
    const pasInput = get("nbr_pas");
    const sportInput = get("nbr_activites_sport");

    const errors = [
        "err-poids",
        "err-calories",
        "err-sommeil",
        "err-pas",
        "err-sport",
        "err-hydratation"
    ];

    function showError(id, msg) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = msg;
            el.style.display = "block";
        }
    }

    function clearErrors() {
        errors.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = "";
                el.style.display = "none";
            }
        });
    }

    function validate() {
        let hasError = false;
        clearErrors();

        const poids = Number(poidsInput?.value);
        if (!Number.isFinite(poids) || poids <= 12 || poids > 300) {
            showError("err-poids", "Poids invalide");
            hasError = true;
        }

        const calories = Number(caloriesInput?.value);
        if (!Number.isFinite(calories) || calories <= 0) {
            showError("err-calories", "Calories invalides");
            hasError = true;
        }

        const sommeil = Number(sommeilInput?.value);
        if (!Number.isFinite(sommeil) || sommeil <= 0 || sommeil > 24) {
            showError("err-sommeil", "Sommeil invalide");
            hasError = true;
        }

        const pas = Number(pasInput?.value);
        if (!Number.isFinite(pas) || pas <= 0) {
            showError("err-pas", "Pas invalides");
            hasError = true;
        }

        const sport = Number(sportInput?.value);
        if (!Number.isFinite(sport) || sport < 0) {
            showError("err-sport", "Sport invalide");
            hasError = true;
        }

        const hydratation = document.querySelector('input[name="hydratation_litre"]:checked');
        if (!hydratation) {
            showError("err-hydratation", "Choisir hydratation");
            hasError = true;
        }

        return !hasError;
    }

    form.addEventListener("submit", function (e) {
        if (!validate()) {
            e.preventDefault();
        }
    });
});