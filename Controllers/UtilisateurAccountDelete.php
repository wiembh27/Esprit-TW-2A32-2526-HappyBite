<?php

declare(strict_types=1);

/**
 * Suppression complète d’un compte utilisateur et données liées.
 */
function utilisateur_db_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t'
    );
    $stmt->execute(['t' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function utilisateur_db_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute(['t' => $table, 'c' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

function utilisateur_destroy_session_and_go_home(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/');
    }
    session_destroy();
    header('Location: Home.php');
    exit;
}

function utilisateur_delete_upload_files(int $uid, ?string $relPhoto): void
{
    $root = dirname(__DIR__);
    foreach ([
        $root . '/uploads/face_auth/' . $uid . '.jpg',
        $root . '/uploads/face_auth/' . $uid . '.json',
    ] as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
    if ($relPhoto !== null && $relPhoto !== '') {
        $abs = $root . '/' . ltrim(str_replace(['../', '..\\'], '', $relPhoto), '/');
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}

/** @throws Throwable */
function utilisateur_delete_account_cascade(PDO $pdo, int $uid, string $pk): void
{
    if ($uid <= 0) {
        throw new InvalidArgumentException('Invalid user id');
    }

    $pdo->beginTransaction();

    try {
        if (utilisateur_db_table_exists($pdo, 'webauthn_credentials') && utilisateur_db_column_exists($pdo, 'webauthn_credentials', 'user_id')) {
            $pdo->prepare('DELETE FROM webauthn_credentials WHERE user_id = :id')->execute(['id' => $uid]);
        }

        if (utilisateur_db_table_exists($pdo, 'commande') && utilisateur_db_column_exists($pdo, 'commande', 'id_utilisateur')) {
            $cmdIds = $pdo->prepare('SELECT id_commande FROM commande WHERE id_utilisateur = :id');
            $cmdIds->execute(['id' => $uid]);
            $ids = $cmdIds->fetchAll(PDO::FETCH_COLUMN);
            if ($ids !== [] && utilisateur_db_table_exists($pdo, 'commande_produit')) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $pdo->prepare("DELETE FROM commande_produit WHERE id_commande IN ({$placeholders})")->execute($ids);
            }
            $pdo->prepare('DELETE FROM commande WHERE id_utilisateur = :id')->execute(['id' => $uid]);
        }

        if (utilisateur_db_table_exists($pdo, 'frigo') && utilisateur_db_column_exists($pdo, 'frigo', 'id_utilisateur')) {
            $pdo->prepare('DELETE FROM frigo WHERE id_utilisateur = :id')->execute(['id' => $uid]);
        }

        if (utilisateur_db_table_exists($pdo, 'profil_sante') && utilisateur_db_column_exists($pdo, 'profil_sante', 'id_utilisateur')) {
            $pdo->prepare('DELETE FROM profil_sante WHERE id_utilisateur = :id')->execute(['id' => $uid]);
        }

        if (utilisateur_db_table_exists($pdo, 'StoryCommentaire') && utilisateur_db_column_exists($pdo, 'StoryCommentaire', 'id_utilisateur')) {
            $pdo->prepare('DELETE FROM StoryCommentaire WHERE id_utilisateur = :id')->execute(['id' => $uid]);
        }

        if (utilisateur_db_table_exists($pdo, 'Story') && utilisateur_db_column_exists($pdo, 'Story', 'id_utilisateur')) {
            $storyIds = $pdo->prepare('SELECT id FROM Story WHERE id_utilisateur = :id');
            $storyIds->execute(['id' => $uid]);
            $sids = $storyIds->fetchAll(PDO::FETCH_COLUMN);
            if ($sids !== []) {
                $ph = implode(',', array_fill(0, count($sids), '?'));
                if (utilisateur_db_table_exists($pdo, 'StoryLike')) {
                    $pdo->prepare("DELETE FROM StoryLike WHERE story_id IN ({$ph})")->execute($sids);
                }
                if (utilisateur_db_table_exists($pdo, 'StoryCommentaire')) {
                    $pdo->prepare("DELETE FROM StoryCommentaire WHERE story_id IN ({$ph})")->execute($sids);
                }
            }
            $pdo->prepare('DELETE FROM Story WHERE id_utilisateur = :id')->execute(['id' => $uid]);
        }

        if (utilisateur_db_table_exists($pdo, 'Commentaire') && utilisateur_db_column_exists($pdo, 'Commentaire', 'id_utilisateur')) {
            $pdo->prepare('DELETE FROM Commentaire WHERE id_utilisateur = :id')->execute(['id' => $uid]);
        }

        if (utilisateur_db_table_exists($pdo, 'Post') && utilisateur_db_column_exists($pdo, 'Post', 'id_utilisateur')) {
            $postIds = $pdo->prepare('SELECT id FROM Post WHERE id_utilisateur = :id');
            $postIds->execute(['id' => $uid]);
            $pids = $postIds->fetchAll(PDO::FETCH_COLUMN);
            if ($pids !== [] && utilisateur_db_table_exists($pdo, 'Commentaire')) {
                $ph = implode(',', array_fill(0, count($pids), '?'));
                $pdo->prepare("DELETE FROM Commentaire WHERE post_id IN ({$ph})")->execute($pids);
            }
            $pdo->prepare('DELETE FROM Post WHERE id_utilisateur = :id')->execute(['id' => $uid]);
        }

        if (utilisateur_db_table_exists($pdo, 'user_notification')) {
            require_once __DIR__ . '/UserNotificationService.php';
            user_notification_delete_for_user($pdo, $uid);
        }

        if (utilisateur_db_table_exists($pdo, 'produit') && utilisateur_db_column_exists($pdo, 'produit', 'id_utilisateur')) {
            $prodIds = $pdo->prepare('SELECT id_produit FROM produit WHERE id_utilisateur = :id');
            $prodIds->execute(['id' => $uid]);
            $pr = $prodIds->fetchAll(PDO::FETCH_COLUMN);
            if ($pr !== [] && utilisateur_db_table_exists($pdo, 'recette_produit')) {
                $ph = implode(',', array_fill(0, count($pr), '?'));
                $pdo->prepare("DELETE FROM recette_produit WHERE id_produit IN ({$ph})")->execute($pr);
            }
            $pdo->prepare('DELETE FROM produit WHERE id_utilisateur = :id')->execute(['id' => $uid]);
        }

        $stmt = $pdo->prepare("DELETE FROM utilisateur WHERE `{$pk}` = :id");
        $stmt->execute(['id' => $uid]);
        if ($stmt->rowCount() < 1) {
            throw new RuntimeException('User row not deleted');
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
