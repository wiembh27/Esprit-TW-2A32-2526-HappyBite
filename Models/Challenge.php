<?php

declare(strict_types=1);

class Challenge
{
    private int $id;
    private string $titre;
    private string $description;
    private ?string $image;
    private string $statut;
    private string $dateCreation;
    private ?string $dateSelection;
    private int $nutritionnisteId;

    public function __construct(
        int $id,
        string $titre,
        string $description,
        ?string $image,
        string $statut,
        string $dateCreation,
        ?string $dateSelection,
        int $nutritionnisteId
    ) {
        $this->id = $id;
        $this->titre = $titre;
        $this->description = $description;
        $this->image = $image;
        $this->statut = $statut;
        $this->dateCreation = $dateCreation;
        $this->dateSelection = $dateSelection;
        $this->nutritionnisteId = $nutritionnisteId;
    }

    public function getId(): int { return $this->id; }
    public function getTitre(): string { return $this->titre; }
    public function getDescription(): string { return $this->description; }
    public function getImage(): ?string { return $this->image; }
    public function getStatut(): string { return $this->statut; }
    public function getDateCreation(): string { return $this->dateCreation; }
    public function getDateSelection(): ?string { return $this->dateSelection; }
    public function getNutritionnnisteId(): int { return $this->nutritionnisteId; }
    public function isDisponible(): bool { return $this->statut === 'disponible'; }
    public function isTermine(): bool { return $this->statut === 'termine'; }
}
