<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../config/openai_key.php';

/**
 * @return array{en: int, fr: int}
 */
function ai_assistant_language_scores(string $questionRaw): array
{
    $q = mb_strtolower(trim($questionRaw), 'UTF-8');
    $scores = ['en' => 0, 'fr' => 0];

    if (preg_match('/[àâäéèêëïîôùûüœæç]/u', $questionRaw)) {
        $scores['fr'] += 2;
    }

    $enSignals = [
        'which', 'what', 'where', 'when', 'how', 'why', 'best', 'product', 'products', 'the', 'is', 'are',
        'my', 'your', 'cheap', 'cheapest', 'expensive', 'recommend', 'recipe', 'recipes', 'order', 'track',
        'payment', 'help', 'thanks', 'thank', 'hello', 'hi', 'hey', 'bye', 'category', 'categories',
        'calories', 'allergen', 'promotion', 'discount', 'fridge', 'buy', 'can', 'you', 'please',
        'secure', 'card', 'cash', 'cancel', 'choose',
    ];
    $frSignals = [
        'quel', 'quelle', 'quels', 'quelles', 'meilleur', 'meilleure', 'produit', 'produits', 'comment',
        'combien', 'pourquoi', 'ou', 'mon', 'ma', 'mes', 'ton', 'ta', 'le', 'la', 'les', 'des', 'est',
        'sont', 'recette', 'recettes', 'commande', 'suivi', 'livraison', 'paiement', 'merci', 'bonjour',
        'salut', 'categorie', 'categories', 'calories', 'allergene', 'promotion', 'frigo', 'acheter',
        'moins', 'cher', 'guide', 'peux', 'puis', 'je', 'vous', 'au', 'revoir', 'securise', 'securisee',
        'chiffre', 'chiffrees', 'possible', 'annuler', 'choisir', 'carte',
    ];

    foreach ($enSignals as $word) {
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $q)) {
            $scores['en']++;
        }
    }
    foreach ($frSignals as $word) {
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $q)) {
            $scores['fr']++;
        }
    }

    return $scores;
}

/** True when detection is strong enough to show a language-mismatch hint. */
function ai_assistant_lang_detection_confident(string $questionRaw): bool
{
    $scores = ai_assistant_language_scores($questionRaw);
    $values = array_values($scores);
    rsort($values);

    return $values[0] >= 2 && ($values[0] - $values[1]) >= 2;
}

/** Detect reply language from the original question (en or fr). */
function ai_assistant_detect_language(string $questionRaw): string
{
    $scores = ai_assistant_language_scores($questionRaw);
    arsort($scores);
    $topLang = (string) array_key_first($scores);
    $topScore = $scores[$topLang];

    if ($topScore === 0) {
        return 'fr';
    }

    $q = mb_strtolower(trim($questionRaw), 'UTF-8');
    if ($scores['en'] === $scores['fr'] && $topScore > 0) {
        if (preg_match('/\b(which|what|where|how|is|the|my|best|product|recipe|secure)\b/u', $q)) {
            return 'en';
        }
        if (preg_match('/\b(quel|comment|est|mes|mon|ma|recette|commande)\b/u', $q)) {
            return 'fr';
        }
    }

    return $topLang;
}

/**
 * Message when the user spoke in one language but another is selected in the UI.
 */
function ai_assistant_lang_mismatch_note(string $detectedLang, string $selectedLang, string $replyLang = ''): string
{
    if (!in_array($detectedLang, ['en', 'fr'], true)
        || !in_array($selectedLang, ['en', 'fr'], true)
        || $detectedLang === $selectedLang) {
        return '';
    }

    $replyLang = in_array($replyLang, ['en', 'fr'], true) ? $replyLang : $selectedLang;
    $detectedChip = strtoupper($detectedLang);
    $names = [
        'fr' => ['fr' => 'français', 'en' => 'anglais'],
        'en' => ['fr' => 'French', 'en' => 'English'],
    ];
    $detectedName = $names[$replyLang][$detectedLang] ?? $detectedChip;

    return match ($replyLang) {
        'en' => "Your question looks like {$detectedName}. Select {$detectedChip} above for replies in that language.",
        default => "Votre question semble être en {$detectedName}. Sélectionnez {$detectedChip} ci-dessus pour des réponses dans cette langue.",
    };
}

function ai_assistant_language_instruction(string $lang, string $questionRaw): string
{
    $names = [
        'en' => 'English',
        'fr' => 'French',
    ];
    $name = $names[$lang] ?? 'the same language as the user';

    return 'CRITICAL: The customer question is written in ' . $name . '. '
        . 'You MUST reply ONLY in ' . $name . ' — the exact same language as the question below. '
        . 'Never answer in French if the question is in English, and never answer in English if the question is in French. '
        . 'Question for language reference: «' . $questionRaw . '»';
}

/**
 * @param array<int, string|int|float> $vars
 */
function ai_assistant_msg(string $lang, string $key, array $vars = []): string
{
    if (!in_array($lang, ['en', 'fr'], true)) {
        $lang = 'fr';
    }

    static $catalog = [
        'catalog_unavailable' => [
            'en' => 'I cannot access the product catalog right now. Please try again in a moment.',
            'fr' => 'Je ne peux pas acceder au catalogue pour le moment. Reessayez dans un instant.',
        ],
        'benefits' => [
            'en' => 'Benefits',
            'fr' => 'Benefices',
        ],
        'frigo_list' => [
            'en' => 'In your Frigo right now: %s.',
            'fr' => 'Dans votre Frigo actuellement : %s.',
        ],
        'product_count' => [
            'en' => 'There are %d products in the catalog.',
            'fr' => 'Il y a %d produits dans le catalogue.',
        ],
        'recipe_count' => [
            'en' => 'There are %d recipes available.',
            'fr' => 'Il y a %d recettes disponibles.',
        ],
        'no_categories' => [
            'en' => 'No categories found.',
            'fr' => 'Aucune categorie trouvee.',
        ],
        'categories_list' => [
            'en' => 'Categories in the shop: %s.',
            'fr' => 'Categories dans la boutique : %s.',
        ],
        'no_promo' => [
            'en' => 'No products are on promotion in the database right now.',
            'fr' => 'Aucun produit en promotion dans la base pour le moment.',
        ],
        'promo_list' => [
            'en' => 'Products on promotion: %s',
            'fr' => 'Produits en promotion : %s',
        ],
        'lightest_recipe' => [
            'en' => 'Lightest recipe in the database: %s (%d kcal). %s',
            'fr' => 'Recette la plus legere en base : %s (%d kcal). %s',
        ],
        'recipe_found' => [
            'en' => 'Recipe found: %s — %d kcal. %s',
            'fr' => 'Recette trouvee : %s — %d kcal. %s',
        ],
        'cheapest' => [
            'en' => 'The cheapest product in the catalog is: %s.',
            'fr' => 'Le produit le moins cher du catalogue est : %s.',
        ],
        'expensive' => [
            'en' => 'The most expensive product is: %s.',
            'fr' => 'Le produit le plus cher est : %s.',
        ],
        'low_cal' => [
            'en' => 'The lowest-calorie product is: %s.',
            'fr' => 'Le produit le moins calorique est : %s.',
        ],
        'best_pick' => [
            'en' => 'Based on our catalog data (benefits, promo, calories), a strong pick is: %s. I can also suggest cheaper or lighter options if you prefer.',
            'fr' => 'D\'apres les donnees du catalogue (benefices, promo, calories), un bon choix est : %s. Je peux aussi proposer plus economique ou plus leger si vous voulez.',
        ],
        'allergen_safe' => [
            'en' => 'Products that may suit (check labels): %s',
            'fr' => 'Produits qui peuvent convenir (verifiez les etiquettes) : %s',
        ],
        'db_found' => [
            'en' => 'Here is what I found in the database: %s',
            'fr' => 'Voici ce que j\'ai trouve en base : %s',
        ],
        'top_picks' => [
            'en' => 'From our current catalog, popular picks are: %s',
            'fr' => 'Dans notre catalogue actuel, voici des choix interessants : %s',
        ],
        'promo_suffix' => [
            'en' => ' (promo -%s%%)',
            'fr' => ' (promo -%s%%)',
        ],
        'payment_secure' => [
            'en' => 'Yes. PayPal and card payments on HappyBite use encrypted connections; we do not store your full card details on our servers.',
            'fr' => 'Oui. PayPal et le paiement par carte sur HappyBite passent par des connexions chiffrees ; nous ne stockons pas vos coordonnees bancaires completes sur nos serveurs.',
        ],
        'cancel_order' => [
            'en' => 'Yes, you can cancel an order as long as it has not been shipped.',
            'fr' => 'Oui, vous pouvez annuler une commande tant qu elle n est pas expediee.',
        ],
        'payment_methods' => [
            'en' => 'You can choose Card, Cash, or PayPal depending on your preference.',
            'fr' => 'Vous pouvez choisir Carte, Cash ou PayPal selon votre preference.',
        ],
        'thanks' => [
            'en' => 'You are welcome. I can also help you with products, recipes, or tracking your order.',
            'fr' => 'Avec plaisir. Je peux aussi vous aider pour les produits, les recettes ou le suivi de commande.',
        ],
        'bye' => [
            'en' => 'Goodbye! Have a great day. I am here if you need help later.',
            'fr' => 'Au revoir ! Bonne journee. Je suis la si vous avez besoin d aide plus tard.',
        ],
        'hint_recipes' => [
            'en' => 'You can explore the recipes section, open details, and filter to find meals that match your needs.',
            'fr' => 'Vous pouvez explorer la section recettes, ouvrir les details et filtrer pour trouver des plats adaptes.',
        ],
        'hint_products' => [
            'en' => 'You can browse products by category, check details, and compare prices or promotions.',
            'fr' => 'Vous pouvez parcourir les produits par categorie, voir les details et comparer les prix ou promotions.',
        ],
        'hint_frigo' => [
            'en' => 'You can add items to your Frigo and review them from the Frigo page.',
            'fr' => 'Vous pouvez ajouter des elements au Frigo puis les consulter depuis la page Frigo.',
        ],
        'hint_health' => [
            'en' => 'Check product/recipe details for allergens, calories, and health indicators before choosing.',
            'fr' => 'Consultez les details des produits/recettes pour les allergenes, calories et indicateurs sante.',
        ],
        'hint_promo' => [
            'en' => 'You can search promoted products and compare prices from the product list.',
            'fr' => 'Vous pouvez rechercher les produits en promotion et comparer les prix dans la liste produits.',
        ],
        'hello' => [
            'en' => 'Hi! I can help with products, recipes, Frigo, payments, delivery, and order tracking.',
            'fr' => 'Bonjour ! Je peux vous aider pour les produits, recettes, frigo, paiement, livraison et suivi.',
        ],
        'hint_guide' => [
            'en' => 'Quick guide: choose products/recipes, add to cart, checkout, then track delivery.',
            'fr' => 'Guide rapide : produits/recettes, panier, commande, puis suivi via track.',
        ],
        'unknown' => [
            'en' => 'I can answer questions about our catalog (products, recipes), Frigo, orders, delivery, and payments.',
            'fr' => 'Je peux repondre sur le catalogue (produits, recettes), frigo, commande, livraison et paiement.',
        ],
        'not_programmed' => [
            'en' => "I'm not programmed to answer that question.",
            'fr' => 'Je ne suis pas programme pour repondre a cette question.',
        ],
    ];

    $templates = $catalog[$key] ?? null;
    if ($templates === null) {
        return '';
    }

    $template = $templates[$lang] ?? $templates['en'] ?? $templates['fr'] ?? '';

    return $vars === [] ? $template : vsprintf($template, $vars);
}

function ai_assistant_column_exists(PDO $pdo, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c'
    );
    $stmt->execute(['t' => $table, 'c' => $column]);
    $cache[$key] = (int) $stmt->fetchColumn() > 0;

    return $cache[$key];
}

/** @return array<string, mixed> */
function ai_assistant_build_context(PDO $pdo, int $userId = 0): array
{
    $ctx = [
        'categories' => [],
        'products' => [],
        'recipes' => [],
        'frigo' => [],
        'stats' => [],
        'error' => null,
    ];

    try {
        $ctx['categories'] = $pdo->query(
            'SELECT id_categorie, nom, description FROM categorie ORDER BY nom ASC'
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $promoCol = ai_assistant_column_exists($pdo, 'produit', 'promo') ? 'p.promo' : 'NULL AS promo';
        $productsSql = "SELECT p.id_produit, p.nom, p.prix, {$promoCol}, p.calories, p.allergene, p.benefices,
                               c.nom AS nom_categorie
                        FROM produit p
                        LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
                        ORDER BY p.id_produit DESC
                        LIMIT 60";
        $ctx['products'] = $pdo->query($productsSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $recipeCols = 'id_recette, nom, description, calories';
        if (ai_assistant_column_exists($pdo, 'recette', 'mise_en_avant')) {
            $recipeCols .= ', mise_en_avant';
        }
        $ctx['recipes'] = $pdo->query(
            "SELECT {$recipeCols} FROM recette ORDER BY id_recette DESC LIMIT 40"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if ($userId > 0) {
            $frigoStmt = $pdo->prepare(
                'SELECT p.nom, p.prix, p.calories, f.quantite, c.nom AS nom_categorie
                 FROM frigo f
                 INNER JOIN produit p ON f.id_produit = p.id_produit
                 LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
                 WHERE f.id_utilisateur = :uid
                 ORDER BY f.date_ajout DESC
                 LIMIT 30'
            );
            $frigoStmt->execute(['uid' => $userId]);
            $ctx['frigo'] = $frigoStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $ctx['stats'] = ai_assistant_compute_stats($ctx['products'], $ctx['recipes'], $ctx['categories']);
    } catch (Throwable $e) {
        $ctx['error'] = $e->getMessage();
    }

    return $ctx;
}

/**
 * @param array<int, array<string, mixed>> $products
 * @param array<int, array<string, mixed>> $recipes
 * @param array<int, array<string, mixed>> $categories
 * @return array<string, mixed>
 */
function ai_assistant_compute_stats(array $products, array $recipes, array $categories): array
{
    $stats = [
        'product_count' => count($products),
        'recipe_count' => count($recipes),
        'category_count' => count($categories),
    ];
    if ($products === []) {
        return $stats;
    }

    $cheapest = null;
    $priciest = null;
    $bestValue = null;
    $lowestCal = null;
    $onPromo = [];

    foreach ($products as $p) {
        $prix = (float) ($p['prix'] ?? 0);
        $promo = isset($p['promo']) && $p['promo'] !== null && $p['promo'] !== '' ? (float) $p['promo'] : 0.0;
        $effective = $promo > 0 ? $prix * (1 - $promo / 100) : $prix;
        $cal = isset($p['calories']) && $p['calories'] !== null && $p['calories'] !== '' ? (int) $p['calories'] : null;
        $benefLen = strlen(trim((string) ($p['benefices'] ?? '')));

        $row = [
            'id' => (int) ($p['id_produit'] ?? 0),
            'nom' => (string) ($p['nom'] ?? ''),
            'prix' => $prix,
            'effective' => round($effective, 2),
            'promo' => $promo,
            'calories' => $cal,
            'score' => $benefLen + ($promo > 0 ? 15 : 0) + ($cal !== null && $cal > 0 && $cal < 400 ? 8 : 0),
        ];

        if ($cheapest === null || $row['effective'] < $cheapest['effective']) {
            $cheapest = $row;
        }
        if ($priciest === null || $prix > $priciest['prix']) {
            $priciest = $row;
        }
        if ($bestValue === null || $row['score'] > $bestValue['score']) {
            $bestValue = $row;
        }
        if ($cal !== null && ($lowestCal === null || $cal < $lowestCal['calories'])) {
            $lowestCal = $row;
        }
        if ($promo > 0) {
            $onPromo[] = $row;
        }
    }

    $stats['cheapest'] = $cheapest;
    $stats['priciest'] = $priciest;
    $stats['best_overall'] = $bestValue;
    $stats['lowest_calories'] = $lowestCal;
    $stats['promo_count'] = count($onPromo);

    return $stats;
}

function ai_assistant_effective_price(array $product): float
{
    $prix = (float) ($product['prix'] ?? 0);
    $promo = isset($product['promo']) && $product['promo'] !== null && $product['promo'] !== ''
        ? (float) $product['promo'] : 0.0;

    return $promo > 0 ? round($prix * (1 - $promo / 100), 2) : $prix;
}

/**
 * @param array<string, mixed> $ctx
 */
function ai_assistant_context_to_text(array $ctx, string $lang): string
{
    $lines = [];
    $isEn = $lang === 'en';

    $lines[] = $isEn
        ? '=== HappyBite database snapshot (use only this data) ==='
        : '=== Instantane base HappyBite (utiliser uniquement ces donnees) ===';

    $stats = $ctx['stats'] ?? [];
    if ($stats !== []) {
        $lines[] = $isEn ? 'Statistics:' : 'Statistiques:';
        $lines[] = '- ' . ($isEn ? 'Products' : 'Produits') . ': ' . (int) ($stats['product_count'] ?? 0);
        $lines[] = '- ' . ($isEn ? 'Recipes' : 'Recettes') . ': ' . (int) ($stats['recipe_count'] ?? 0);
        $lines[] = '- ' . ($isEn ? 'Categories' : 'Categories') . ': ' . (int) ($stats['category_count'] ?? 0);
        if (!empty($stats['best_overall'])) {
            $b = $stats['best_overall'];
            $lines[] = '- ' . ($isEn ? 'Best overall (benefits/promo/calories)' : 'Meilleur global (benefices/promo/calories)')
                . ': ' . $b['nom'] . ' — ' . $b['effective'] . ' DT';
        }
        if (!empty($stats['cheapest'])) {
            $c = $stats['cheapest'];
            $lines[] = '- ' . ($isEn ? 'Cheapest' : 'Moins cher') . ': ' . $c['nom'] . ' — ' . $c['effective'] . ' DT';
        }
        if (!empty($stats['lowest_calories'])) {
            $l = $stats['lowest_calories'];
            $lines[] = '- ' . ($isEn ? 'Lowest calories' : 'Moins calorique') . ': ' . $l['nom']
                . ($l['calories'] !== null ? ' (' . $l['calories'] . ' kcal)' : '');
        }
        $lines[] = '- ' . ($isEn ? 'On promotion' : 'En promotion') . ': ' . (int) ($stats['promo_count'] ?? 0);
    }

    $lines[] = $isEn ? 'Categories:' : 'Categories:';
    foreach ($ctx['categories'] ?? [] as $cat) {
        $lines[] = '  • ' . ($cat['nom'] ?? '') . ' (id ' . ($cat['id_categorie'] ?? '') . ')';
    }

    $lines[] = $isEn ? 'Products (sample):' : 'Produits (extrait):';
    foreach (array_slice($ctx['products'] ?? [], 0, 45) as $p) {
        $eff = ai_assistant_effective_price($p);
        $promo = isset($p['promo']) && (float) $p['promo'] > 0 ? ' promo -' . $p['promo'] . '%' : '';
        $cal = isset($p['calories']) && $p['calories'] !== null && $p['calories'] !== ''
            ? ' | ' . $p['calories'] . ' kcal' : '';
        $all = trim((string) ($p['allergene'] ?? ''));
        $ben = trim((string) ($p['benefices'] ?? ''));
        $allStr = $all !== '' ? ' | allergens: ' . mb_substr($all, 0, 80) : '';
        $benStr = $ben !== '' ? ' | benefits: ' . mb_substr($ben, 0, 100) : '';
        $lines[] = '  • [' . ($p['nom_categorie'] ?? '?') . '] '
            . ($p['nom'] ?? '') . ' — ' . $eff . ' DT' . $promo . $cal . $allStr . $benStr;
    }

    $lines[] = $isEn ? 'Recipes:' : 'Recettes:';
    foreach (array_slice($ctx['recipes'] ?? [], 0, 25) as $r) {
        $lines[] = '  • ' . ($r['nom'] ?? '') . ' — ' . (int) ($r['calories'] ?? 0) . ' kcal — '
            . mb_substr(trim((string) ($r['description'] ?? '')), 0, 120);
    }

    if (!empty($ctx['frigo'])) {
        $lines[] = $isEn ? 'User fridge:' : 'Frigo utilisateur:';
        foreach ($ctx['frigo'] as $f) {
            $lines[] = '  • ' . ($f['nom'] ?? '') . ' x' . (int) ($f['quantite'] ?? 1)
                . ' (' . ($f['nom_categorie'] ?? '') . ')';
        }
    }

    if (!empty($ctx['error'])) {
        $lines[] = 'DB error: ' . $ctx['error'];
    }

    return implode("\n", $lines);
}

function ai_assistant_call_openai(string $question, string $contextText, string $lang): ?string
{
    $apiKey = hb_openai_api_key();
    if ($apiKey === '') {
        return null;
    }

    $langRule = ai_assistant_language_instruction($lang, $question);

    $system = 'You are the HappyBite shop assistant. You MUST answer using ONLY the database snapshot provided. '
        . 'If the answer is not in the data, say you do not have that information in the catalog. '
        . 'Mention real product/recipe names and prices from the data. Do not invent items. '
        . 'Keep answers to 2-5 short sentences, friendly. ' . $langRule;

    $user = $contextText . "\n\nCustomer question (reply in the SAME language as this question):\n" . $question;

    $payload = json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ],
        'temperature' => 0.35,
        'max_tokens' => 380,
    ], JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        return null;
    }

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 28,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($body) || $code < 200 || $code >= 300) {
        return null;
    }

    $decoded = json_decode($body, true);
    $text = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
    if ($text === '' || str_starts_with($text, 'Erreur')) {
        return null;
    }

    return $text;
}

/**
 * @param array<string, mixed> $ctx
 */
function ai_assistant_answer_locally(
    string $questionRaw,
    string $questionNorm,
    string $lang,
    array $ctx,
    callable $containsOneOf,
    callable $containsAll
): ?string {
    $products = $ctx['products'] ?? [];
    $recipes = $ctx['recipes'] ?? [];
    $stats = $ctx['stats'] ?? [];

    if (!empty($ctx['error']) || $products === []) {
        return ai_assistant_msg($lang, 'catalog_unavailable');
    }

    $formatProduct = static function (array $p, string $langInner): string {
        $eff = ai_assistant_effective_price($p);
        $extra = '';
        if (isset($p['promo']) && (float) $p['promo'] > 0) {
            $extra .= sprintf(ai_assistant_msg($langInner, 'promo_suffix'), (string) $p['promo']);
        }
        if (isset($p['calories']) && $p['calories'] !== null && $p['calories'] !== '') {
            $extra .= ', ' . $p['calories'] . ' kcal';
        }
        $cat = $p['nom_categorie'] ?? '';
        $ben = trim((string) ($p['benefices'] ?? ''));
        if ($ben !== '') {
            $extra .= '. ' . ai_assistant_msg($langInner, 'benefits') . ': ' . mb_substr($ben, 0, 140);
        }

        return ($p['nom'] ?? '') . ($cat !== '' ? " [{$cat}]" : '') . " — {$eff} DT{$extra}";
    };

    $wantsBest = $containsOneOf($questionNorm, [
        'meilleur', 'meilleure', 'best', 'beste', 'besten', 'top', 'recommand', 'recommend', 'empfehl',
        'quel produit', 'which product', 'welches produkt', 'welche produkt',
    ]);
    $wantsCheap = $containsOneOf($questionNorm, [
        'moins cher', 'cheapest', 'cheap', 'low price', 'prix bas', 'gunstig', 'billig', 'gunstigste',
    ]);
    $wantsExpensive = $containsOneOf($questionNorm, ['plus cher', 'expensive', 'highest price', 'teuer', 'teuerste']);
    $wantsLowCal = $containsOneOf($questionNorm, [
        'moins calor', 'low calorie', 'low cal', 'light', 'leger', 'faible calorie',
    ]);
    $wantsPromo = $containsOneOf($questionNorm, ['promo', 'promotion', 'discount', 'reduction', 'solde']);
    $wantsRecipe = $containsOneOf($questionNorm, ['recette', 'recettes', 'recipe', 'recipes', 'plat', 'meal']);
    $wantsCount = $containsOneOf($questionNorm, ['combien', 'how many', 'number of', 'total']);
    $wantsCategory = $containsOneOf($questionNorm, ['categorie', 'categories', 'category']);
    $wantsFrigo = $containsOneOf($questionNorm, ['frigo', 'fridge', 'refrigerateur']);
    $wantsAllergen = $containsOneOf($questionNorm, ['allerg', 'allergene', 'sans gluten', 'gluten free', 'lactose']);

    if ($wantsFrigo && !empty($ctx['frigo'])) {
        $names = array_map(static fn ($f) => ($f['nom'] ?? '') . ' x' . (int) ($f['quantite'] ?? 1), $ctx['frigo']);
        return ai_assistant_msg($lang, 'frigo_list', [implode(', ', array_slice($names, 0, 12))]);
    }

    if ($wantsCount && $containsOneOf($questionNorm, ['produit', 'product', 'catalogue', 'catalog'])) {
        return ai_assistant_msg($lang, 'product_count', [(int) ($stats['product_count'] ?? count($products))]);
    }

    if ($wantsCount && $wantsRecipe) {
        return ai_assistant_msg($lang, 'recipe_count', [(int) ($stats['recipe_count'] ?? count($recipes))]);
    }

    if ($wantsCategory) {
        $names = array_map(static fn ($c) => $c['nom'] ?? '', $ctx['categories'] ?? []);
        if ($names === []) {
            return ai_assistant_msg($lang, 'no_categories');
        }
        return ai_assistant_msg($lang, 'categories_list', [implode(', ', $names)]);
    }

    if ($wantsPromo) {
        $promoProducts = array_values(array_filter($products, static function ($p) {
            return isset($p['promo']) && (float) $p['promo'] > 0;
        }));
        if ($promoProducts === []) {
            return ai_assistant_msg($lang, 'no_promo');
        }
        usort($promoProducts, static fn ($a, $b) => (float) ($b['promo'] ?? 0) <=> (float) ($a['promo'] ?? 0));
        $top = array_slice($promoProducts, 0, 5);
        $list = implode('; ', array_map(static fn ($p) => $formatProduct($p, $lang), $top));
        return ai_assistant_msg($lang, 'promo_list', [$list]);
    }

    if ($wantsRecipe && ($wantsBest || $wantsLowCal) && $recipes !== []) {
        usort($recipes, static fn ($a, $b) => (int) ($a['calories'] ?? 9999) <=> (int) ($b['calories'] ?? 9999));
        $pick = $recipes[0];
        return ai_assistant_msg($lang, 'lightest_recipe', [
            (string) ($pick['nom'] ?? ''),
            (int) ($pick['calories'] ?? 0),
            mb_substr(trim((string) ($pick['description'] ?? '')), 0, 160),
        ]);
    }

    if ($wantsRecipe && $recipes !== []) {
        $matched = ai_assistant_match_by_keywords($questionNorm, $recipes, 'nom', 'description');
        if ($matched !== []) {
            $r = $matched[0];
            return ai_assistant_msg($lang, 'recipe_found', [
                (string) ($r['nom'] ?? ''),
                (int) ($r['calories'] ?? 0),
                mb_substr(trim((string) ($r['description'] ?? '')), 0, 180),
            ]);
        }
    }

    if ($wantsCheap && !empty($stats['cheapest'])) {
        $id = (int) $stats['cheapest']['id'];
        $p = ai_assistant_find_product_by_id($products, $id);
        if ($p !== null) {
            return ai_assistant_msg($lang, 'cheapest', [$formatProduct($p, $lang)]);
        }
    }

    if ($wantsExpensive && !empty($stats['priciest'])) {
        $id = (int) $stats['priciest']['id'];
        $p = ai_assistant_find_product_by_id($products, $id);
        if ($p !== null) {
            return ai_assistant_msg($lang, 'expensive', [$formatProduct($p, $lang)]);
        }
    }

    if ($wantsLowCal && !empty($stats['lowest_calories'])) {
        $id = (int) $stats['lowest_calories']['id'];
        $p = ai_assistant_find_product_by_id($products, $id);
        if ($p !== null) {
            return ai_assistant_msg($lang, 'low_cal', [$formatProduct($p, $lang)]);
        }
    }

    if ($wantsBest || $containsOneOf($questionNorm, ['produit', 'product', 'acheter', 'buy'])) {
        if (!empty($stats['best_overall'])) {
            $id = (int) $stats['best_overall']['id'];
            $p = ai_assistant_find_product_by_id($products, $id);
            if ($p !== null) {
                return ai_assistant_msg($lang, 'best_pick', [$formatProduct($p, $lang)]);
            }
        }
    }

    if ($wantsAllergen) {
        $needle = '';
        foreach (['gluten', 'lactose', 'arachide', 'oeuf', 'egg', 'milk', 'lait', 'soja', 'nuts', 'noix'] as $term) {
            if (str_contains($questionNorm, $term)) {
                $needle = $term;
                break;
            }
        }
        $safe = array_values(array_filter($products, static function ($p) use ($needle, $questionNorm) {
            $all = mb_strtolower((string) ($p['allergene'] ?? ''), 'UTF-8');
            if ($needle === '') {
                return $all === '' || str_contains($questionNorm, 'sans') || str_contains($questionNorm, 'free');
            }
            return !str_contains($all, $needle);
        }));
        if ($safe !== []) {
            $list = implode('; ', array_map(static fn ($p) => $formatProduct($p, $lang), array_slice($safe, 0, 5)));
            return ai_assistant_msg($lang, 'allergen_safe', [$list]);
        }
    }

    $matchedProducts = ai_assistant_match_by_keywords($questionNorm, $products, 'nom', 'benefices', 'nom_categorie', 'allergene');
    if ($matchedProducts !== []) {
        $list = implode('; ', array_map(static fn ($p) => $formatProduct($p, $lang), array_slice($matchedProducts, 0, 4)));
        return ai_assistant_msg($lang, 'db_found', [$list]);
    }

    if ($wantsBest || $containsOneOf($questionNorm, ['quoi', 'what', 'quel', 'which', 'suggest', 'idee', 'idea'])) {
        $top = ai_assistant_top_products($products, 3);
        $list = implode('; ', array_map(static fn ($p) => $formatProduct($p, $lang), $top));
        return ai_assistant_msg($lang, 'top_picks', [$list]);
    }

    return null;
}

/**
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, array<string, mixed>>
 */
function ai_assistant_match_by_keywords(string $questionNorm, array $rows, string ...$fields): array
{
    $stopwords = array_flip(ai_assistant_keyword_stopwords());
    $words = array_filter(
        explode(' ', $questionNorm),
        static fn ($w) => strlen($w) >= 3 && !isset($stopwords[$w])
    );
    if ($words === []) {
        return [];
    }
    $scored = [];
    foreach ($rows as $row) {
        $hay = '';
        foreach ($fields as $f) {
            $hay .= ' ' . mb_strtolower((string) ($row[$f] ?? ''), 'UTF-8');
        }
        $score = 0;
        foreach ($words as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '\b/u', $hay) === 1) {
                $score++;
            }
        }
        if ($score > 0) {
            $scored[] = ['row' => $row, 'score' => $score];
        }
    }
    usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);

    return array_map(static fn ($x) => $x['row'], $scored);
}

/**
 * @param array<int, array<string, mixed>> $products
 * @return array<int, array<string, mixed>>
 */
function ai_assistant_top_products(array $products, int $limit): array
{
    $scored = [];
    foreach ($products as $p) {
        $benefLen = strlen(trim((string) ($p['benefices'] ?? '')));
        $promo = isset($p['promo']) && (float) $p['promo'] > 0 ? 12 : 0;
        $scored[] = ['row' => $p, 'score' => $benefLen + $promo];
    }
    usort($scored, static fn ($a, $b) => $b['score'] <=> $a['score']);

    return array_map(static fn ($x) => $x['row'], array_slice($scored, 0, $limit));
}

/**
 * @param array<int, array<string, mixed>> $products
 * @return array<string, mixed>|null
 */
function ai_assistant_find_product_by_id(array $products, int $id): ?array
{
    foreach ($products as $p) {
        if ((int) ($p['id_produit'] ?? 0) === $id) {
            return $p;
        }
    }

    return null;
}

/**
 * Normalise une question utilisateur (accents latins sans iconv — fiable sous Windows).
 */
function ai_assistant_normalize_question(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    if (!preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u', $text)) {
        static $accentMap = [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'å' => 'a',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o', 'õ' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'ÿ' => 'y', 'ñ' => 'n', 'ç' => 'c',
            'œ' => 'oe', 'æ' => 'ae', 'ß' => 'ss',
        ];
        $text = strtr($text, $accentMap);
    }
    $text = preg_replace('/[^\pL\pN\s]/u', ' ', $text) ?? $text;
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;

    return trim($text);
}

/**
 * Suivi de commande / livraison (prioritaire sur le catalogue).
 */
function ai_assistant_is_tracking_intent(string $questionNorm, string $questionRaw): bool
{
    if (preg_match('/أين\s+طلبي|تتبع\s+الطلب|متى\s+يصل\s+الطلب/u', $questionRaw) === 1) {
        return true;
    }

    $phrases = [
        'ou est ma commande', 'est ma commande', 'ou en est ma commande',
        'quand ma commande arrivera', 'suivre ma commande', 'statut de ma commande',
        'where is my order', 'where s my order', 'when will my order arrive',
        'track my order', 'order status', 'where is my delivery',
        'wo ist meine bestellung', 'bestellung verfolgen', 'bestellstatus',
    ];
    foreach ($phrases as $phrase) {
        if ($phrase !== '' && str_contains($questionNorm, $phrase)) {
            return true;
        }
    }

    if (
        preg_match('/\b(commande|order|bestellung|colis|package|livraison|delivery|shipment)\b/u', $questionNorm) === 1
        && preg_match('/\b(ou|where|wo|suivre|track|status|arrive|arrivera|en est|locate|find|localiser)\b/u', $questionNorm) === 1
    ) {
        return true;
    }

    if (
        preg_match('/\bwhere\b/i', $questionRaw) === 1
        && preg_match('/\b(order|delivery|package|shipment|commande|livraison)\b/i', $questionRaw) === 1
    ) {
        return true;
    }

    return false;
}

/** @return list<string> */
function ai_assistant_off_topic_terms(): array
{
    return [
        'voiture', 'voitures', 'car', 'cars', 'automobile', 'auto',
        'chambre', 'chambres', 'room', 'rooms', 'bedroom',
        'maison', 'maisons', 'house', 'houses', 'home', 'apartment', 'appartement',
        'telephone', 'phone', 'smartphone', 'mobile',
        'velo', 'bike', 'bicycle',
        'chien', 'dog', 'chat', 'cat',
        'cle', 'cles', 'key', 'keys',
        'sac', 'bag', 'wallet', 'portefeuille',
        'ami', 'friend', 'friends',
        'avion', 'plane', 'flight', 'vol',
        'hotel', 'vacances', 'vacation', 'holiday',
        'film', 'movie', 'football', 'basketball', 'politique', 'politics',
    ];
}

/**
 * Questions hors périmètre HappyBite (voiture, chambre, etc.).
 */
function ai_assistant_is_out_of_scope(string $questionNorm, string $questionRaw): bool
{
    if (ai_assistant_is_tracking_intent($questionNorm, $questionRaw)) {
        return false;
    }

    $combined = $questionNorm . ' ' . $questionRaw;

    foreach (ai_assistant_off_topic_terms() as $term) {
        if (preg_match('/\b' . preg_quote($term, '/') . '\b/ui', $combined) === 1) {
            return true;
        }
    }

    if (
        preg_match('/\bwhere\s+(is|are|\'s|s)\s+(my|the)\s+\w+/i', $questionRaw) === 1
        && preg_match('/\b(order|commande|delivery|package|colis|shipment|track|livraison)\b/i', $combined) !== 1
    ) {
        return true;
    }

    if (
        preg_match('/\b(ou est|où est)\s+(ma|mon|mes|la|le)\s+(?!commande\b)/ui', $questionRaw) === 1
    ) {
        return true;
    }

    return false;
}

/**
 * @return array{answer: string, action: string, link: array{label: string, action: string}}
 */
function ai_assistant_build_tracking_response(string $lang, string $page): array
{
    $answer = match ($lang) {
        'en' => $page !== 'commande.php'
            ? 'Here is your order tracking link:'
            : 'Please complete your order first, then use this tracking link:',
        'de' => $page !== 'commande.php'
            ? 'Hier ist Ihr Bestellungs-Tracking-Link:'
            : 'Schliessen Sie zuerst Ihre Bestellung ab, dann nutzen Sie diesen Link:',
        'ar' => $page !== 'commande.php'
            ? 'إليك رابط تتبع طلبك:'
            : 'أكمل طلبك أولا، ثم استخدم هذا الرابط:',
        default => $page !== 'commande.php'
            ? 'Voici le lien de suivi de votre commande :'
            : 'Finalisez d abord votre commande, puis utilisez ce lien :',
    };
    $linkLabel = match ($lang) {
        'en' => 'Track my order',
        default => 'Suivre ma commande',
    };

    return [
        'answer' => $answer,
        'action' => 'open_track_map',
        'link' => [
            'label' => $linkLabel,
            'action' => 'open_track_map',
        ],
    ];
}

/** @return list<string> */
function ai_assistant_keyword_stopwords(): array
{
    return [
        'the', 'and', 'for', 'with', 'that', 'this', 'from', 'your', 'have', 'are', 'was', 'were',
        'where', 'what', 'when', 'which', 'how', 'why', 'can', 'you', 'please', 'about',
        'est', 'sont', 'qui', 'que', 'dans', 'pour', 'avec', 'sans', 'mon', 'mes', 'ton', 'tes',
        'notre', 'vous', 'nous', 'les', 'des', 'une', 'aux', 'sur', 'pas', 'peux', 'puis',
        'my', 'our', 'ihr', 'eine', 'ein', 'der', 'die', 'das', 'ist', 'sind', 'wo', 'was', 'wie',
        'und', 'ich', 'not', 'programmed', 'answer', 'question',
    ];
}

/**
 * Questions catalogue (produits, recettes, frigo…) — OpenAI uniquement pour celles-ci.
 */
function ai_assistant_is_catalog_question(string $questionNorm, callable $containsOneOf): bool
{
    return $containsOneOf($questionNorm, [
        'produit', 'produits', 'product', 'products', 'recette', 'recettes', 'recipe', 'recipes',
        'catalogue', 'catalog', 'frigo', 'fridge', 'refrigerateur', 'kuhlschrank',
        'categorie', 'categories', 'category', 'promo', 'promotion', 'discount', 'solde',
        'calorie', 'calories', 'allerg', 'allergene', 'gluten', 'lactose',
        'moins cher', 'cheapest', 'cheap', 'meilleur', 'meilleure', 'best', 'beste',
        'recommand', 'recommend', 'combien', 'how many', 'prix', 'price', 'acheter', 'buy',
        'plat', 'meal', 'rezept', 'nutrition', 'leger', 'light', 'economique',
    ]);
}

/**
 * FAQ service (paiement, annulation) — prioritaire sur le catalogue / OpenAI.
 */
function ai_assistant_try_faq_answer(
    string $questionRaw,
    string $questionNorm,
    string $lang,
    callable $containsOneOf
): ?string {
    $paymentSecure =
        $containsOneOf($questionNorm, [
            'paypal est il securise', 'paiement avec carte est il securise', 'payement avec carte est il securise',
            'is paypal secure', 'is card payment secure', 'is payment secure',
            'is paypal safe', 'paypal safe', 'is card safe',
            'ist paypal sicher', 'paypal sicher', 'ist die zahlung sicher', 'sind zahlungen sicher',
            'ist kartenzahlung sicher', 'ist bezahlung sicher',
        ]) ||
        (
            $containsOneOf($questionNorm, ['paypal', 'payment', 'paiement', 'payement', 'card', 'carte', 'zahlung', 'bezahlung', 'karte']) &&
            $containsOneOf($questionNorm, ['secure', 'securise', 'securisee', 'sur', 'safe', 'fiable', 'confiance', 'sicher', 'sicherheit'])
        ) ||
        (preg_match('/paypal|باي\s*بال|بايبال/ui', $questionRaw) === 1
            && preg_match('/آمن|امان|أمان|secure|sicher|securise/ui', $questionRaw) === 1);

    if ($paymentSecure) {
        return ai_assistant_msg($lang, 'payment_secure');
    }

    if (
        $containsOneOf($questionNorm, [
            'annuler ma commande', 'possible d annuler', 'cancel my order', 'can i cancel my order',
            'bestellung stornieren', 'kann ich bestellung stornieren', 'bestellung annulieren',
        ]) ||
        ($containsOneOf($questionNorm, ['annuler', 'cancel', 'stornieren']) && $containsOneOf($questionNorm, ['commande', 'order', 'bestellung'])) ||
        (preg_match('/إلغاء/u', $questionRaw) === 1 && preg_match('/طلب/u', $questionRaw) === 1)
    ) {
        return ai_assistant_msg($lang, 'cancel_order');
    }

    if (
        $containsOneOf($questionNorm, [
            'mode de paiement', 'payment method', 'payment methods',
            'which payment method', 'what payment method', 'quel mode de paiement',
            'zahlungsmethode', 'welche zahlungsmethode', 'welches zahlungsmittel',
        ]) ||
        (
            $containsOneOf($questionNorm, ['carte', 'cash', 'paypal', 'card', 'karte', 'bar']) &&
            $containsOneOf($questionNorm, ['mode', 'method', 'paiement', 'payment', 'choose', 'choisir', 'choisi', 'zahlung', 'wahlen', 'welche'])
        ) ||
        (preg_match('/طريقة\s+الدفع|وسيلة\s+الدفع/u', $questionRaw) === 1)
    ) {
        return ai_assistant_msg($lang, 'payment_methods');
    }

    return null;
}

/**
 * @param array<string, mixed> $ctx
 */
function ai_assistant_try_database_answer(
    string $questionRaw,
    string $questionNorm,
    string $lang,
    array $ctx,
    callable $containsOneOf,
    callable $containsAll
): ?string {
    $faq = ai_assistant_try_faq_answer($questionRaw, $questionNorm, $lang, $containsOneOf);
    if ($faq !== null && $faq !== '') {
        return $faq;
    }

    if (ai_assistant_is_out_of_scope($questionNorm, $questionRaw)) {
        return ai_assistant_msg($lang, 'not_programmed');
    }

    $dbAnswer = ai_assistant_answer_locally(
        $questionRaw,
        $questionNorm,
        $lang,
        $ctx,
        $containsOneOf,
        $containsAll
    );
    if ($dbAnswer !== null && $dbAnswer !== '') {
        return $dbAnswer;
    }

    if (hb_openai_api_key() !== '' && ai_assistant_is_catalog_question($questionNorm, $containsOneOf)) {
        $contextText = ai_assistant_context_to_text($ctx, $lang);
        $aiAnswer = ai_assistant_call_openai($questionRaw, $contextText, $lang);
        if ($aiAnswer !== null && $aiAnswer !== '') {
            return $aiAnswer;
        }
    }

    return null;
}
