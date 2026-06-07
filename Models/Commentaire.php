<?php

declare(strict_types=1);

class Commentaire
{
    private int $id;
    private string $contenu;
    private string $dateCommentaire;
    private int $post_id;

    public function __construct(
        int $id,
        string $contenu,
        string $dateCommentaire,
        int $post_id
    ) {
        $this->id = $id;
        $this->contenu = $contenu;
        $this->dateCommentaire = $dateCommentaire;
        $this->post_id = $post_id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function getDateCommentaire(): string
    {
        return $this->dateCommentaire;
    }

    public function getPostId(): int
    {
        return $this->post_id;
    }

    public function setContenu(string $contenu): void
    {
        $this->contenu = $contenu;
    }
}
