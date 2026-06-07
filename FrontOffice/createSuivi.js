document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("profilForm");
    if (!form) return;

    const get = (name) => form.querySelector(`[name="${name}"]`);


    const poidsInput = get("poids");
    const caloriesInput = get("calories");
    const sommeilInput = get("sommeil_heures");
    const pasInput = get("nbr_pas");
    const sportInput = get("nbr_activites_sport");

    const err = {
       
        poids: document.getElementById("err_poids"),
        calories: document.getElementById("err_calories"),
        sommeil: document.getElementById("err_sommeil"),
        pas: document.getElementById("err_pas"),
        sport: document.getElementById("err_sport"),
        hydratation: document.getElementById("err_hydratation")
    };

    function showError(el, msg) {
        if (!el) return;
        el.textContent = msg;
        el.style.display = "block";
    }

    function clearErrors() {
        Object.values(err).forEach(el => {
            if (!el) return;
            el.textContent = "";
            el.style.display = "none";
        });
    }

    function toNumber(input) {
        if (!input) return null;
        return input.value.trim() === "" ? null : Number(input.value);
    }

    function getHydratation() {
        const selected = form.querySelector('input[name="hydratation_litre"]:checked');
        return selected ? selected.value : null;
    }

    function validate() {
        let valid = true;
        clearErrors();

       
        const poids = toNumber(poidsInput);
        const calories = toNumber(caloriesInput);
        const sommeil = toNumber(sommeilInput);
        const pas = toNumber(pasInput);
        const sport = toNumber(sportInput);
        const hydra = getHydratation();

       

        if (poids === null || poids <= 12 || poids > 300) {
            showError(err.poids, "Poids invalide");
            valid = false;
        }

        if (calories === null || calories <= 0 || calories > 10000) {
            showError(err.calories, "Calories invalides");
            valid = false;
        }

        if (sommeil === null || sommeil < 0 || sommeil > 24) {
            showError(err.sommeil, "Sommeil invalide");
            valid = false;
        }

        if (pas === null || pas <= 0) {
            showError(err.pas, "Pas invalides");
            valid = false;
        }

        if (sport !== null && sport < 0) {
            showError(err.sport, "Sport invalide");
            valid = false;
        }

        if (!hydra) {
            showError(err.hydratation, "Choisir une hydratation");
            valid = false;
        }

        return valid;
    }

    form.addEventListener("submit", function (e) {
        if (!validate()) {
            e.preventDefault();
        }
    });

});