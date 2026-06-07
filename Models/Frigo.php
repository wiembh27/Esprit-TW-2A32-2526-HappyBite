<?php

class Frigo
{
    private ?int $id_utilisateur = null;
    private ?int $id_produit = null;
    private ?int $quantite = null;
    private ?string $date_ajout = null;

    public function __construct(
        ?int $id_utilisateur = null,
        ?int $id_produit = null,
        ?int $quantite = 1,
        ?string $date_ajout = null
    ) {
        $this->id_utilisateur = $id_utilisateur;
        $this->id_produit = $id_produit;
        $this->quantite = $quantite;
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

    public function getIdProduit(): ?int
    {
        return $this->id_produit;
    }

    public function setIdProduit(int $id_produit): void
    {
        $this->id_produit = $id_produit;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): void
    {
        $this->quantite = $quantite;
    }

    public function getDateAjout(): ?string
    {
        return $this->date_ajout;
    }

    public function setDateAjout(?string $date_ajout): void
    {
        $this->date_ajout = $date_ajout;
    }
}
?>