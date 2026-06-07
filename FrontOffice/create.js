document.getElementById("formProfil").addEventListener("submit", function (e) {

    let valid = true;

    // reset erreurs
    document.querySelectorAll(".error").forEach(el => {
        el.style.display = "none";
        el.innerText = "";
    });

    let taille = parseFloat(document.getElementById("taille").value);
    let poids = parseFloat(document.getElementById("poids").value);
    let objectif = document.getElementById("objectif").value;

    // Taille (55 - 251 cm)
    if (isNaN(taille) || taille < 55 || taille > 251) {
        let err = document.getElementById("error_taille");
        err.innerText = "Taille doit être entre 55 et 251 cm";
        err.style.display = "block";
        valid = false;
    }

    // Poids (12 - 700 kg)
    if (isNaN(poids) || poids < 12 || poids > 700) {
        let err = document.getElementById("error_poids");
        err.innerText = "Poids doit être entre 12 et 700 kg";
        err.style.display = "block";
        valid = false;
    }

    // Objectif
    if (objectif === "") {
        let err = document.getElementById("error_objectif");
        err.innerText = "Veuillez choisir un objectif";
        err.style.display = "block";
        valid = false;
    }

 // ALLERGENES
let allergenes = document.querySelectorAll('input[name="allergenes[]"]:checked');
let infoAll = document.getElementById("info_allergenes");

if (allergenes.length === 0) {
    infoAll.innerText = "Vous pouvez sélectionner des allergènes (optionnel)";
    infoAll.style.display = "block";
} else {
    infoAll.style.display = "none";
}

// CARENCES
let carences = document.querySelectorAll('input[name="carences[]"]:checked');
let infoCar = document.getElementById("info_carences");

if (carences.length === 0) {
    infoCar.innerText = "Vous pouvez sélectionner des carences (optionnel)";
    infoCar.style.display = "block";
} else {
    infoCar.style.display = "none";
}

// MALADIES
let maladies = document.querySelectorAll('input[name="maladies[]"]:checked');
let infoMal = document.getElementById("info_maladies");

if (maladies.length === 0) {
    infoMal.innerText = "Vous pouvez sélectionner des maladies (optionnel)";
    infoMal.style.display = "block";
} else {
    infoMal.style.display = "none";
}

    if (!valid) {
        e.preventDefault();
    }
});