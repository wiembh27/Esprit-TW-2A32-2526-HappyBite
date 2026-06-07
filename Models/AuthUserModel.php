<?php

declare(strict_types=1);

/**
 * Modèle minimal pour l’auth (login / register / WebAuthn), adapté depuis le projet PR.
 */
class AuthUserModel
{
    private string $userPkColumn;

    public function __construct(private PDO $db)
    {
        $this->userPkColumn = $this->detectUserPkColumn();
    }

    private function detectUserPkColumn(): string
    {
        if ($this->hasColumn('utilisateur', 'id')) {
            return 'id';
        }
        if ($this->hasColumn('utilisateur', 'id_utilisateur')) {
            return 'id_utilisateur';
        }
        return 'id';
    }

    public function idFromUserRow(array $row): int
    {
        return (int) ($row[$this->userPkColumn] ?? $row['id'] ?? $row['id_utilisateur'] ?? 0);
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM utilisateur WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function addWebAuthnCredential(
        int $userId,
        string $credentialId,
        ?string $publicKey,
        ?string $attestationRaw,
        ?string $clientDataJson,
        int $signCount = 0,
        ?string $transports = null
    ): bool {
        $stmt = $this->db->prepare(
            'INSERT INTO webauthn_credentials (user_id, credential_id, public_key, attestation_raw, client_data_json, sign_count, transports)
             VALUES (:user_id, :credential_id, :public_key, :attestation_raw, :client_data_json, :sign_count, :transports)'
        );
        return $stmt->execute([
            'user_id' => $userId,
            'credential_id' => $credentialId,
            'public_key' => $publicKey,
            'attestation_raw' => $attestationRaw,
            'client_data_json' => $clientDataJson,
            'sign_count' => $signCount,
            'transports' => $transports,
        ]);
    }

    public function findCredentialById(string $credentialId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM webauthn_credentials WHERE credential_id = :cid LIMIT 1');
        $stmt->execute(['cid' => $credentialId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function getUserCredentials(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM webauthn_credentials WHERE user_id = :uid ORDER BY id DESC');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
        );
        $stmt->execute(['t' => $table, 'c' => $column]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function ensureReferralCode(int $userId): ?string
    {
        if (!$this->hasColumn('utilisateur', 'referral_code')) {
            return null;
        }
        $pk = $this->userPkColumn;
        $stmt = $this->db->prepare("SELECT referral_code FROM utilisateur WHERE `{$pk}` = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $code = $stmt->fetchColumn();
        if (!empty($code)) {
            return (string) $code;
        }
        $code = 'HB' . strtoupper(substr(hash('sha256', (string) $userId . uniqid('', true)), 0, 8));
        $up = $this->db->prepare("UPDATE utilisateur SET referral_code = :code WHERE `{$pk}` = :id");
        $up->execute(['code' => $code, 'id' => $userId]);
        return $code;
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t'
        );
        $stmt->execute(['t' => $table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function findByReferralCode(string $code): ?array
    {
        if (!$this->hasColumn('utilisateur', 'referral_code')) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM utilisateur WHERE referral_code = :code LIMIT 1');
        $stmt->execute(['code' => trim($code)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function attachReferralByCode(int $refereeId, string $code): bool
    {
        if (!$this->tableExists('loyalty_referrals') || !$this->hasColumn('utilisateur', 'referred_by')) {
            return false;
        }
        $referrer = $this->findByReferralCode($code);
        $referrerId = $referrer ? $this->idFromUserRow($referrer) : 0;
        if (!$referrer || $referrerId === $refereeId) {
            return false;
        }
        $existing = $this->db->prepare('SELECT id FROM loyalty_referrals WHERE referee_id = :referee LIMIT 1');
        $existing->execute(['referee' => $refereeId]);
        if ($existing->fetchColumn()) {
            return false;
        }
        $pk = $this->userPkColumn;
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO loyalty_referrals (referrer_id, referee_id, referral_code) VALUES (:referrer, :referee, :code)'
            );
            $stmt->execute(['referrer' => $referrerId, 'referee' => $refereeId, 'code' => $code]);
            $up = $this->db->prepare(
                "UPDATE utilisateur SET referred_by = :referrer, referral_count = COALESCE(referral_count, 0) WHERE `{$pk}` = :referee"
            );
            $up->execute(['referrer' => $referrerId, 'referee' => $refereeId]);
            $up2 = $this->db->prepare("UPDATE utilisateur SET referral_count = COALESCE(referral_count, 0) + 1 WHERE `{$pk}` = :id");
            $up2->execute(['id' => $referrerId]);
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }
}
