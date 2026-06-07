<?php

class SuiviJournalier
{
    private $id;
    private $id_profil_sante;
    private $date_jour;
    private $poids;
    private $calories;
    private $sommeil_heures;
    private $nbr_pas;
    private $nbr_activites_sport;
    private $hydratation_litre;

    public function __construct(
        $id_profil_sante = null,
        $date_jour = null,
        $poids = null,
        $calories = null,
        $sommeil_heures = null,
        $nbr_pas = null,
        $nbr_activites_sport = null,
        $hydratation_litre = null
    ) {
        $this->id_profil_sante = $id_profil_sante;
        $this->date_jour = $date_jour;
        $this->poids = $poids;
        $this->calories = $calories;
        $this->sommeil_heures = $sommeil_heures;
        $this->nbr_pas = $nbr_pas;
        $this->nbr_activites_sport = $nbr_activites_sport;
        $this->hydratation_litre = $hydratation_litre;
    }

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getIdProfilSante() { return $this->id_profil_sante; }
    public function setIdProfilSante($id_profil_sante) { $this->id_profil_sante = $id_profil_sante; }

    public function getDateJour() { return $this->date_jour; }
    public function setDateJour($date_jour) { $this->date_jour = $date_jour; }

    public function getPoids() { return $this->poids; }
    public function setPoids($poids) { $this->poids = $poids; }

    public function getCalories() { return $this->calories; }
    public function setCalories($calories) { $this->calories = $calories; }

    public function getSommeilHeures() { return $this->sommeil_heures; }
    public function setSommeilHeures($sommeil_heures) { $this->sommeil_heures = $sommeil_heures; }

    public function getNbrPas() { return $this->nbr_pas; }
    public function setNbrPas($nbr_pas) { $this->nbr_pas = $nbr_pas; }

    public function getNbrActivitesSport() { return $this->nbr_activites_sport; }
    public function setNbrActivitesSport($nbr_activites_sport) { $this->nbr_activites_sport = $nbr_activites_sport; }

    public function getHydratationLitre() { return $this->hydratation_litre; }
    public function setHydratationLitre($hydratation_litre) { $this->hydratation_litre = $hydratation_litre; }
}