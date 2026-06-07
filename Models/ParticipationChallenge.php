<?php

declare(strict_types=1);

class ParticipationChallenge
{
    private int $id;
    private int $clientId;
    private int $challengeId;
    private ?string $photo;
    private ?string $description;
    private string $statutValidationIA;
    private int $nombreLikes;
    private string $dateParticipation;

    public function __construct(
        int $id,
        int $clientId,
        int $challengeId,
        ?string $photo,
        ?string $description,
        string $statutValidationIA,
        int $nombreLikes,
        string $dateParticipation
    ) {
        $this->id = $id;
        $this->clientId = $clientId;
        $this->challengeId = $challengeId;
        $this->photo = $photo;
        $this->description = $description;
        $this->statutValidationIA = $statutValidationIA;
        $this->nombreLikes = $nombreLikes;
        $this->dateParticipation = $dateParticipation;
    }

    public function getId(): int { return $this->id; }
    public function getClientId(): int { return $this->clientId; }
    public function getChallengeId(): int { return $this->challengeId; }
    public function getPhoto(): ?string { return $this->photo; }
    public function getDescription(): ?string { return $this->description; }
    public function getStatutValidationIA(): string { return $this->statutValidationIA; }
    public function getNombreLikes(): int { return $this->nombreLikes; }
    public function getDateParticipation(): string { return $this->dateParticipation; }
    public function isValide(): bool { return $this->statutValidationIA === 'valide'; }
}
