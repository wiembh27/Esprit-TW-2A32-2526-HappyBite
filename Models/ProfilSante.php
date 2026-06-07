<?php
class ProfilSante
{
    private $id;
    private $id_utilisateur;
    private $taille;
    private $poids_actuel;
    private $objectif;
    private $allergenes;
    private $carences;
    private $maladies;
    private $date_mise_a_jour;

    public function __construct(
        $id_utilisateur = null,
        $taille = null,
        $poids_actuel = null,
        $objectif = null,
        $allergenes = [],
        $carences = [],
        $maladies = [],
        $date_mise_a_jour = null
    ) {
        $this->id_utilisateur = $id_utilisateur;
        $this->taille = $taille;
        $this->poids_actuel = $poids_actuel;
        $this->objectif = $objectif;
        $this->allergenes = $allergenes;
        $this->carences = $carences;
        $this->maladies = $maladies;
        $this->date_mise_a_jour = $date_mise_a_jour;
    }

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getIdUtilisateur() { return $this->id_utilisateur; }
    public function setIdUtilisateur($id_utilisateur) { $this->id_utilisateur = $id_utilisateur; }

    public function getTaille() { return $this->taille; }
    public function setTaille($taille) { $this->taille = $taille; }

    public function getPoidsActuel() { return $this->poids_actuel; }
    public function setPoidsActuel($poids_actuel) { $this->poids_actuel = $poids_actuel; }

    public function getObjectif() { return $this->objectif; }
    public function setObjectif($objectif) { $this->objectif = $objectif; }

    public function getAllergenes() { return $this->allergenes; }
    public function setAllergenes($allergenes) { $this->allergenes = $allergenes; }

    public function getCarences() { return $this->carences; }
    public function setCarences($carences) { $this->carences = $carences; }

    public function getMaladies() { return $this->maladies; }
    public function setMaladies($maladies) { $this->maladies = $maladies; }

    public function getDateMiseAJour() { return $this->date_mise_a_jour; }
    public function setDateMiseAJour($date_mise_a_jour) { $this->date_mise_a_jour = $date_mise_a_jour; }
}