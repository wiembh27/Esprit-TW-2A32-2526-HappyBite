<?php

class Produit
{
    private ?int $id_produit = null;
    private ?string $nom = null;
    private ?float $prix = null;
    private ?float $promo = null;
    private ?string $image = null;
    private ?string $allergene = null;
    private ?string $benefices = null;
    private ?int $calories = null;
    private ?string $date_ajout = null;
    private ?int $id_utilisateur = null;
    private ?int $id_categorie = null;

    public function __construct(
        ?string $nom = null,
        ?float $prix = null,
        ?string $image = null,
        ?string $allergene = null,
        ?string $benefices = null,
        ?int $calories = null,
        ?string $date_ajout = null,
        ?int $id_utilisateur = null,
        ?int $id_categorie = null
    ) {
        $this->nom = $nom;
        $this->prix = $prix;
        $this->image = $image;
        $this->allergene = $allergene;
        $this->benefices = $benefices;
        $this->calories = $calories;
        $this->date_ajout = $date_ajout;
        $this->id_utilisateur = $id_utilisateur;
        $this->id_categorie = $id_categorie;
    }

    public function getIdProduit(): ?int
    {
        return $this->id_produit;
    }

    public function setIdProduit(int $id_produit): void
    {
        $this->id_produit = $id_produit;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): void
    {
        $this->prix = $prix;
    }

    public function getPromo(): ?float
    {
        return $this->promo;
    }

    public function setPromo(?float $promo): void
    {
        $this->promo = $promo;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getAllergene(): ?string
    {
        return $this->allergene;
    }

    public function setAllergene(?string $allergene): void
    {
        $this->allergene = $allergene;
    }

    public function getBenefices(): ?string
    {
        return $this->benefices;
    }

    public function setBenefices(?string $benefices): void
    {
        $this->benefices = $benefices;
    }

    public function getCalories(): ?int
    {
        return $this->calories;
    }

    public function setCalories(?int $calories): void
    {
        $this->calories = $calories;
    }

    public function getDateAjout(): ?string
    {
        return $this->date_ajout;
    }

    public function setDateAjout(?string $date_ajout): void
    {
        $this->date_ajout = $date_ajout;
    }

    public function getIdUtilisateur(): ?int
    {
        return $this->id_utilisateur;
    }

    public function setIdUtilisateur(int $id_utilisateur): void
    {
        $this->id_utilisateur = $id_utilisateur;
    }

    public function getIdCategorie(): ?int
    {
        return $this->id_categorie;
    }

    public function setIdCategorie(int $id_categorie): void
    {
        $this->id_categorie = $id_categorie;
    }
}
?>