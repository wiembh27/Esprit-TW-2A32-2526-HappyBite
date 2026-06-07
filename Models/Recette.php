<?php

class Recette
{
    private ?int $id_recette = null;
    private string $nom;
    private string $description;
    private int $calories;
    private ?string $image;
    private int $mise_en_avant = 0;

    public function __construct(string $nom, string $description, int $calories, ?string $image = null)
    {
        $this->nom = $nom;
        $this->description = $description;
        $this->calories = $calories;
        $this->image = $image;
    }

    public function getIdRecette(): ?int
    {
        return $this->id_recette;
    }

    public function setIdRecette(int $id_recette): void
    {
        $this->id_recette = $id_recette;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getCalories(): int
    {
        return $this->calories;
    }

    public function setCalories(int $calories): void
    {
        $this->calories = $calories;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getMiseEnAvant(): int
    {
        return $this->mise_en_avant;
    }

    public function setMiseEnAvant(int $mise_en_avant): void
    {
        $this->mise_en_avant = $mise_en_avant;
    }
}
?>