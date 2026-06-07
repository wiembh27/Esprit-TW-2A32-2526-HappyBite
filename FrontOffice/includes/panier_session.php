<?php

declare(strict_types=1);

/**
 * Panier en session.
 *
 * Ancien format supporté :
 * $_SESSION['panier'][id_produit] = [
 *   'quantite' => int,
 *   'prix_unitaire' => float
 * ]
 *
 * Nouveau format supporté :
 * $_SESSION['panier'][line_key] = [
 *   'id_produit' => int,
 *   'quantite' => int,
 *   'prix_unitaire' => float,
 *   'cadeau' => bool,
 *   'source_cadeau' => string
 * ]
 */

function panier_ensure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['panier']) || !is_array($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }
}

/**
 * @return array<string, array{
 *   id_produit:int,
 *   quantite:int,
 *   prix_unitaire:float,
 *   cadeau:bool,
 *   source_cadeau:string
 * }>
 */
function panier_get_items(): array
{
    panier_ensure_session();

    $out = [];

    foreach ($_SESSION['panier'] as $key => $row) {
        if (!is_array($row)) {
            continue;
        }

        if (isset($row['id_produit'])) {
            $idProduit = (int) $row['id_produit'];
        } else {
            $idProduit = (int) $key;
        }

        if ($idProduit < 1) {
            continue;
        }

        $quantite = (int) ($row['quantite'] ?? 0);

        if ($quantite < 1) {
            continue;
        }

        $prixUnitaire = (float) ($row['prix_unitaire'] ?? 0);
        $cadeau = !empty($row['cadeau']);
        $sourceCadeau = (string) ($row['source_cadeau'] ?? '');

        $out[(string) $key] = [
            'id_produit' => $idProduit,
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'cadeau' => $cadeau,
            'source_cadeau' => $sourceCadeau,
        ];
    }

    return $out;
}

function panier_add_product(int $idProduit, float $prixUnitaire): void
{
    panier_ensure_session();

    if ($idProduit < 1) {
        return;
    }

    $key = (string) $idProduit;

    if (!isset($_SESSION['panier'][$key]) || !is_array($_SESSION['panier'][$key])) {
        $_SESSION['panier'][$key] = [
            'id_produit' => $idProduit,
            'quantite' => 0,
            'prix_unitaire' => $prixUnitaire,
            'cadeau' => false,
            'source_cadeau' => '',
        ];
    }

    $_SESSION['panier'][$key]['id_produit'] = $idProduit;
    $_SESSION['panier'][$key]['quantite'] = (int) ($_SESSION['panier'][$key]['quantite'] ?? 0) + 1;
    $_SESSION['panier'][$key]['prix_unitaire'] = $prixUnitaire;
    $_SESSION['panier'][$key]['cadeau'] = false;
    $_SESSION['panier'][$key]['source_cadeau'] = '';

    fortune_wheel_apply_pending();
}

function panier_add_free_product(int $idProduit, string $sourceCadeau = 'Roue de la fortune'): void
{
    panier_ensure_session();

    if ($idProduit < 1) {
        return;
    }

    $sourceCadeau = trim($sourceCadeau);
    if ($sourceCadeau === '') {
        $sourceCadeau = 'Roue de la fortune';
    }

    $key = 'gift_' . $idProduit . '_' . md5($sourceCadeau);

    if (!isset($_SESSION['panier'][$key]) || !is_array($_SESSION['panier'][$key])) {
        $_SESSION['panier'][$key] = [
            'id_produit' => $idProduit,
            'quantite' => 0,
            'prix_unitaire' => 0.0,
            'cadeau' => true,
            'source_cadeau' => $sourceCadeau,
        ];
    }

    $_SESSION['panier'][$key]['quantite'] = (int) ($_SESSION['panier'][$key]['quantite'] ?? 0) + 1;
    $_SESSION['panier'][$key]['prix_unitaire'] = 0.0;
    $_SESSION['panier'][$key]['cadeau'] = true;
    $_SESSION['panier'][$key]['source_cadeau'] = $sourceCadeau;
}

function panier_decrement_product(int $idProduit): void
{
    panier_ensure_session();

    $key = (string) $idProduit;

    if (!isset($_SESSION['panier'][$key])) {
        return;
    }

    panier_decrement_item($key);
}

function panier_decrement_item(string $lineKey): void
{
    panier_ensure_session();

    if (!isset($_SESSION['panier'][$lineKey]) || !is_array($_SESSION['panier'][$lineKey])) {
        return;
    }

    $quantite = (int) ($_SESSION['panier'][$lineKey]['quantite'] ?? 0) - 1;

    if ($quantite < 1) {
        unset($_SESSION['panier'][$lineKey]);
        return;
    }

    $_SESSION['panier'][$lineKey]['quantite'] = $quantite;
}

function panier_remove_product(int $idProduit): void
{
    panier_ensure_session();

    unset($_SESSION['panier'][(string) $idProduit]);
}

function panier_remove_item(string $lineKey): void
{
    panier_ensure_session();

    unset($_SESSION['panier'][$lineKey]);
}

function panier_clear(): void
{
    panier_ensure_session();

    $_SESSION['panier'] = [];
}

function panier_remove_line(string $lineKey): void
{
    panier_remove_item($lineKey);
}

function panier_decrement_line(string $lineKey): void
{
    panier_decrement_item($lineKey);
}

function panier_increment_line(string $lineKey, float $prixUnitaire): void
{
    panier_ensure_session();

    if (!isset($_SESSION['panier'][$lineKey]) || !is_array($_SESSION['panier'][$lineKey])) {
        return;
    }

    if (!empty($_SESSION['panier'][$lineKey]['cadeau'])) {
        return;
    }

    $_SESSION['panier'][$lineKey]['quantite'] = (int) ($_SESSION['panier'][$lineKey]['quantite'] ?? 0) + 1;
    $_SESSION['panier'][$lineKey]['prix_unitaire'] = $prixUnitaire;
    $_SESSION['panier'][$lineKey]['cadeau'] = false;
}

function panier_has_free_gift(int $idProduit, string $sourceCadeau): bool
{
    if ($idProduit < 1) {
        return false;
    }

    foreach (panier_get_items() as $entry) {
        if ((int) ($entry['id_produit'] ?? 0) === $idProduit
            && !empty($entry['cadeau'])
            && (string) ($entry['source_cadeau'] ?? '') === $sourceCadeau) {
            return true;
        }
    }

    return false;
}

function fortune_wheel_pending_ensure(): void
{
    panier_ensure_session();

    if (!isset($_SESSION['fortune_wheel_pending']) || !is_array($_SESSION['fortune_wheel_pending'])) {
        $_SESSION['fortune_wheel_pending'] = [];
    }
}

/**
 * @return list<array<string, mixed>>
 */
function fortune_wheel_pending_get(): array
{
    fortune_wheel_pending_ensure();

    return $_SESSION['fortune_wheel_pending'];
}

/**
 * @param array<string, mixed> $prize
 */
function fortune_wheel_pending_push(array $prize): void
{
    fortune_wheel_pending_ensure();
    $_SESSION['fortune_wheel_pending'][] = $prize;
}

/**
 * @param array<string, mixed> $gain
 * @return list<array<string, mixed>>
 */
function fortune_wheel_gain_to_prizes(array $gain): array
{
    $type = (string) ($gain['type'] ?? '');

    if ($type === 'produit' && isset($gain['produit']) && is_array($gain['produit'])) {
        $produit = $gain['produit'];

        return [[
            'type' => 'produit',
            'id_produit' => (int) ($produit['id_produit'] ?? 0),
            'nom' => (string) ($produit['nom'] ?? 'Produit'),
            'image' => (string) ($produit['image'] ?? ''),
            'source_cadeau' => 'roue_fortune_' . (string) ($gain['code'] ?? ('produit_' . (int) ($produit['id_produit'] ?? 0))),
            'gain_label' => (string) ($gain['label'] ?? ''),
        ]];
    }

    if ($type === 'recette' && isset($gain['produits_recette'], $gain['recette'])
        && is_array($gain['produits_recette']) && is_array($gain['recette'])) {
        $produits = [];

        foreach ($gain['produits_recette'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $idProduit = (int) ($row['id_produit'] ?? 0);

            if ($idProduit < 1) {
                continue;
            }

            $produits[] = [
                'id_produit' => $idProduit,
                'nom' => (string) ($row['nom'] ?? 'Produit'),
                'image' => (string) ($row['image'] ?? ''),
            ];
        }

        if ($produits === []) {
            return [];
        }

        return [[
            'type' => 'recette',
            'id_recette' => (int) ($gain['recette']['id_recette'] ?? 0),
            'nom' => (string) ($gain['recette']['nom'] ?? 'Recette'),
            'produits' => $produits,
            'source_cadeau' => 'roue_recette_' . (int) ($gain['recette']['id_recette'] ?? 0) . '_' . (string) ($gain['code'] ?? ''),
            'gain_label' => (string) ($gain['label'] ?? ''),
        ]];
    }

    return [];
}

/**
 * @param array<string, mixed> $gain
 */
function fortune_wheel_store_gain(array $gain): void
{
    foreach (fortune_wheel_gain_to_prizes($gain) as $prize) {
        fortune_wheel_pending_push($prize);
    }
}

/**
 * @param array<string, mixed> $prize
 */
function fortune_wheel_apply_single_prize(array $prize): bool
{
    $type = (string) ($prize['type'] ?? '');

    if ($type === 'produit') {
        $idProduit = (int) ($prize['id_produit'] ?? 0);
        $source = (string) ($prize['source_cadeau'] ?? 'roue_fortune');

        if ($idProduit < 1) {
            return false;
        }

        if (!panier_has_free_gift($idProduit, $source)) {
            panier_add_free_product($idProduit, $source);
        }

        return true;
    }

    if ($type === 'recette') {
        $produits = $prize['produits'] ?? [];

        if (!is_array($produits) || $produits === []) {
            return false;
        }

        $source = (string) ($prize['source_cadeau'] ?? 'roue_recette');
        $applied = false;

        foreach ($produits as $row) {
            if (!is_array($row)) {
                continue;
            }

            $idProduit = (int) ($row['id_produit'] ?? 0);

            if ($idProduit < 1) {
                continue;
            }

            if (!panier_has_free_gift($idProduit, $source)) {
                panier_add_free_product($idProduit, $source);
            }

            $applied = true;
        }

        return $applied;
    }

    return false;
}

function fortune_wheel_apply_pending(): int
{
    fortune_wheel_pending_ensure();

    $applied = 0;
    $remaining = [];

    foreach ($_SESSION['fortune_wheel_pending'] as $prize) {
        if (!is_array($prize)) {
            continue;
        }

        if (fortune_wheel_apply_single_prize($prize)) {
            $applied++;
            continue;
        }

        $remaining[] = $prize;
    }

    $_SESSION['fortune_wheel_pending'] = $remaining;

    return $applied;
}

/**
 * Applique les gains produit/recette enregistrés en base (survit au rechargement).
 */
function fortune_wheel_apply_db_rewards(int $clientId): int
{
    if ($clientId < 1) {
        return 0;
    }

    panier_ensure_session();

    require_once dirname(__DIR__, 2) . '/config/Database.php';
    require_once dirname(__DIR__, 2) . '/Controllers/RecetteController.php';

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare(
        "SELECT id, typeGain, produitRoueId, nomGain
         FROM recompense
         WHERE clientId = :clientId
         AND statut = 'en_attente'
         AND typeGain IN ('produit', 'recette')
         ORDER BY id ASC"
    );
    $stmt->execute(['clientId' => $clientId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!is_array($rows) || $rows === []) {
        return 0;
    }

    $recetteController = new RecetteController();
    $markUsed = $pdo->prepare(
        "UPDATE recompense
         SET statut = 'utilisee'
         WHERE id = :id
         AND statut = 'en_attente'
         LIMIT 1"
    );

    $applied = 0;

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $recompenseId = (int) ($row['id'] ?? 0);
        $typeGain = (string) ($row['typeGain'] ?? '');
        $refId = (int) ($row['produitRoueId'] ?? 0);
        $source = 'roue_recompense_' . $recompenseId;

        if ($recompenseId < 1 || $refId < 1) {
            continue;
        }

        if ($typeGain === 'produit') {
            if (!panier_has_free_gift($refId, $source)) {
                panier_add_free_product($refId, $source);
            }

            if (panier_has_free_gift($refId, $source)) {
                $markUsed->execute(['id' => $recompenseId]);
                $applied++;
            }

            continue;
        }

        if ($typeGain === 'recette') {
            $produits = $recetteController->getProduitsByRecette($refId);
            $allInCart = is_array($produits) && $produits !== [];

            if ($allInCart) {
                foreach ($produits as $produit) {
                    if (!is_array($produit)) {
                        continue;
                    }

                    $idProduit = (int) ($produit['id_produit'] ?? 0);

                    if ($idProduit < 1) {
                        $allInCart = false;
                        continue;
                    }

                    if (!panier_has_free_gift($idProduit, $source)) {
                        panier_add_free_product($idProduit, $source);
                    }

                    if (!panier_has_free_gift($idProduit, $source)) {
                        $allInCart = false;
                    }
                }
            }

            if ($allInCart) {
                $markUsed->execute(['id' => $recompenseId]);
                $applied++;
            }
        }
    }

    return $applied;
}

/**
 * Ré-applique le dernier gain mémorisé en session (secours si la session AJAX n'a pas fusionné).
 */
function fortune_wheel_consume_last_gain(int $clientId): void
{
    if ($clientId < 1) {
        return;
    }

    panier_ensure_session();

    if (!isset($_SESSION['fortune_wheel_last_gain'])) {
        return;
    }

    unset($_SESSION['fortune_wheel_last_gain']);

    fortune_wheel_apply_db_rewards($clientId);
    fortune_wheel_apply_pending();
}
