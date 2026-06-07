<?php

class Categorie
{
    private ?int $id_categorie = null;
    private ?string $nom = null;
    private ?string $description = null;

    public function __construct(?string $nom = null, ?string $description = null)
    {
        $this->nom = $nom;
        $this->description = $description;
    }

    public function getIdCategorie(): ?int
    {
        return $this->id_categorie;
    }

    public function setIdCategorie(int $id_categorie): void
    {
        $this->id_categorie = $id_categorie;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
?>