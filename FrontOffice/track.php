<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/panier_session.php';

panier_ensure_session();

$params = ['open_track' => '1'];
$id = (int) ($_GET['id_commande'] ?? 0);
if ($id > 0) {
    $params['id_commande'] = (string) $id;
}

header('Location: Home.php?' . http_build_query($params));
exit;
