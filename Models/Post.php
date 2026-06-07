<?php

declare(strict_types=1);

class Post
{
    private int $id;
    private string $contenu;
    private string $datePublication;
    private ?string $image;
    private int $nombreLikes;

    public function __construct(
        int $id,
        string $contenu,
        string $datePublication,
        ?string $image = null,
        int $nombreLikes = 0
    ) {
        $this->id = $id;
        $this->contenu = $contenu;
        $this->datePublication = $datePublication;
        $this->image = $image;
        $this->nombreLikes = $nombreLikes;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function getDatePublication(): string
    {
        return $this->datePublication;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function getNombreLikes(): int
    {
        return $this->nombreLikes;
    }

    public function setContenu(string $contenu): void
    {
        $this->contenu = $contenu;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function setNombreLikes(int $nombreLikes): void
    {
        $this->nombreLikes = $nombreLikes;
    }

    public function incrementLikes(): void
    {
        $this->nombreLikes++;
    }

    public function decrementLikes(): void
    {
        if ($this->nombreLikes > 0) {
            $this->nombreLikes--;
        }
    }
}
