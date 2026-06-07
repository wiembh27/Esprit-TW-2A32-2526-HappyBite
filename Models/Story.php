<?php
declare(strict_types=1);

class Story
{
    private ?int $id;
    private string $image;
    private string $dateCreation;

    public function __construct(string $image, string $dateCreation = '', ?int $id = null)
    {
        $this->image = $image;
        $this->dateCreation = $dateCreation;
        $this->id = $id;
    }

    public function getId(): ?int { return $this->id; }
    public function getImage(): string { return $this->image; }
    public function getDateCreation(): string { return $this->dateCreation; }

    public function setId(int $id): void { $this->id = $id; }
    public function setImage(string $image): void { $this->image = $image; }
    public function setDateCreation(string $dateCreation): void { $this->dateCreation = $dateCreation; }
}
