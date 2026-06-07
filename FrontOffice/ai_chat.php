<?php
declare(strict_types=1);

require_once __DIR__ . '/../Controllers/AiAssistantContext.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['answer' => 'Methode non autorisee.']);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '{}', true);
if (!is_array($payload)) {
    $payload = [];
}

$questionRaw = trim((string) ($payload['question'] ?? ''));
$page = strtolower(trim((string) ($payload['page'] ?? '')));

$questionNorm = ai_assistant_normalize_question($questionRaw);
$detectedLang = ai_assistant_detect_language($questionRaw);

$containsOneOf = static function (string $normalizedText, array $needles): bool {
    foreach ($needles as $needle) {
        if ($needle !== '' && str_contains($normalizedText, $needle)) {
            return true;
        }
    }
    return false;
};

$containsAll = static function (string $normalizedText, array $needles): bool {
    foreach ($needles as $needle) {
        if ($needle === '') {
            continue;
        }
        if (!str_contains($normalizedText, $needle)) {
            return false;
        }
    }
    return true;
};

$preferredLang = strtolower(trim((string) ($payload['preferred_lang'] ?? '')));
if (!in_array($preferredLang, ['en', 'fr'], true)) {
    $preferredLang = '';
}

$lang = $preferredLang !== '' ? $preferredLang : $detectedLang;

$appendLangHint = static function (string $answer) use ($detectedLang, $preferredLang, $lang, $questionRaw): string {
    if ($preferredLang === '' || $detectedLang === $preferredLang) {
        return $answer;
    }
    if (!ai_assistant_lang_detection_confident($questionRaw)) {
        return $answer;
    }
    $note = ai_assistant_lang_mismatch_note($detectedLang, $preferredLang, $lang);

    return $note !== '' ? $answer . "\n\n" . $note : $answer;
};

$aiChatRespond = static function (array $payload, string $lang) use ($appendLangHint, $detectedLang): void {
    $payload['lang'] = $lang;
    $payload['detected_lang'] = $detectedLang;
    if (isset($payload['answer']) && is_string($payload['answer'])) {
        $payload['answer'] = $appendLangHint($payload['answer']);
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
};

if ($questionRaw === '') {
    $aiChatRespond(['answer' => 'Veuillez ecrire une question.'], 'fr');
}

// API badword (local moderation layer) — whole words only (évite "con" dans "commande").
$badWords = [
    'connard', 'idiot', 'imbecile', 'pute', 'merde', 'salope', 'encule',
    'fuck', 'shit', 'bitch', 'asshole',
];
$hasBadWord = static function (string $normalizedText, array $words): bool {
    foreach ($words as $word) {
        if ($word !== '' && preg_match('/\b' . preg_quote($word, '/') . '\b/u', $normalizedText)) {
            return true;
        }
    }
    return false;
};
if ($hasBadWord($questionNorm, $badWords) || preg_match('/\bcon\b/u', $questionNorm)) {
    $aiChatRespond([
        'answer' => match ($lang) {
            'en' => 'Please keep it respectful. Rephrase your question without offensive language.',
            default => 'Merci de rester respectueux. Reformulez votre question sans termes offensants.',
        },
    ], $lang);
}

// Business priority rule requested by teacher/user: tracking link.
if (ai_assistant_is_tracking_intent($questionNorm, $questionRaw)) {
    $aiChatRespond(ai_assistant_build_tracking_response($lang, $page), $lang);
}

if (ai_assistant_is_out_of_scope($questionNorm, $questionRaw)) {
    $aiChatRespond(['answer' => ai_assistant_msg($lang, 'not_programmed')], $lang);
}

// FAQ paiement / commande (avant catalogue et OpenAI).
$faqAnswer = ai_assistant_try_faq_answer($questionRaw, $questionNorm, $lang, $containsOneOf);
if (is_string($faqAnswer) && $faqAnswer !== '') {
    $aiChatRespond(['answer' => $faqAnswer], $lang);
}

// Catalogue / recettes / frigo : reponses basees sur la base de donnees (+ OpenAI si cle configuree).
$userId = !empty($_SESSION['logged_in']) ? (int) ($_SESSION['user_id'] ?? 0) : 0;
try {
    $pdo = Database::getConnection();
    $dbContext = ai_assistant_build_context($pdo, $userId);
    $dbAnswer = ai_assistant_try_database_answer(
        $questionRaw,
        $questionNorm,
        $lang,
        $dbContext,
        $containsOneOf,
        $containsAll
    );
    if (is_string($dbAnswer) && $dbAnswer !== '') {
        $aiChatRespond(['answer' => $dbAnswer], $lang);
    }
} catch (Throwable $e) {
    // Continue vers reponse locale catalogue / fallbacks deterministes.
}

// Second chance : reponse catalogue sans OpenAI (si connexion DB indisponible plus haut).
if (
    !ai_assistant_is_out_of_scope($questionNorm, $questionRaw)
    && ai_assistant_is_catalog_question($questionNorm, $containsOneOf)
) {
    try {
        $pdo = Database::getConnection();
        $dbContext = ai_assistant_build_context($pdo, $userId);
        $localOnly = ai_assistant_answer_locally(
            $questionRaw,
            $questionNorm,
            $lang,
            $dbContext,
            $containsOneOf,
            $containsAll
        );
        if (is_string($localOnly) && $localOnly !== '') {
            $aiChatRespond(['answer' => $localOnly], $lang);
        }
    } catch (Throwable $e) {
        // fallthrough
    }
}

// API externe / API chat / H.Face (Hugging Face Inference API).
$hfApiKey = getenv('HF_API_KEY');
$hfModel = getenv('HF_MODEL') ?: 'google/flan-t5-base';
$externalAnswer = null;

if (is_string($hfApiKey) && trim($hfApiKey) !== '') {
    $langInstruction = ai_assistant_language_instruction($lang, $questionRaw);
    $prompt = "You are the HappyBite assistant. " . $langInstruction . "\nUser question: " . $questionRaw;
    $url = 'https://api-inference.huggingface.co/models/' . rawurlencode($hfModel);
    $requestBody = json_encode([
        'inputs' => $prompt,
        'parameters' => [
            'max_new_tokens' => 120,
            'temperature' => 0.6,
            'return_full_text' => false,
        ],
    ], JSON_UNESCAPED_UNICODE);

    if ($requestBody !== false) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $hfApiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $requestBody,
            CURLOPT_TIMEOUT => 18,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);

        $responseBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (is_string($responseBody) && $statusCode >= 200 && $statusCode < 300) {
            $decoded = json_decode($responseBody, true);
            if (is_array($decoded)) {
                if (isset($decoded[0]['generated_text']) && is_string($decoded[0]['generated_text'])) {
                    $externalAnswer = trim($decoded[0]['generated_text']);
                } elseif (isset($decoded['generated_text']) && is_string($decoded['generated_text'])) {
                    $externalAnswer = trim($decoded['generated_text']);
                }
            }
        }
    }
}

if (
    is_string($externalAnswer) && $externalAnswer !== ''
    && !ai_assistant_is_out_of_scope($questionNorm, $questionRaw)
    && !ai_assistant_is_catalog_question($questionNorm, $containsOneOf)
) {
    $aiChatRespond(['answer' => $externalAnswer], ai_assistant_detect_language($externalAnswer) ?: $lang);
}

// Deterministic fallback replies if external API unavailable.
if (
    $containsOneOf($questionNorm, [
        'paypal est il securise', 'paiement avec carte est il securise', 'payement avec carte est il securise',
        'is paypal secure', 'is card payment secure', 'is payment secure',
        'is paypal safe', 'paypal safe', 'is card safe',
    ]) ||
    ($containsOneOf($questionNorm, ['paypal', 'payment', 'paiement', 'card', 'carte']) &&
        $containsOneOf($questionNorm, ['secure', 'securise']))
) {
    $aiChatRespond(['answer' => ai_assistant_msg($lang, 'payment_secure')], $lang);
}
if (
    $containsOneOf($questionNorm, ['annuler ma commande', 'possible d annuler', 'cancel my order', 'can i cancel my order']) ||
    ($containsOneOf($questionNorm, ['annuler', 'cancel']) && $containsOneOf($questionNorm, ['commande', 'order']))
) {
    $aiChatRespond(['answer' => ai_assistant_msg($lang, 'cancel_order')], $lang);
}
if (
    $containsOneOf($questionNorm, [
        'mode de paiement', 'payment method', 'payment methods',
        'which payment method', 'what payment method', 'quel mode de paiement',
    ]) ||
    (
        $containsOneOf($questionNorm, ['carte', 'cash', 'paypal', 'card']) &&
        $containsOneOf($questionNorm, ['mode', 'method', 'paiement', 'payment', 'choose', 'choisir'])
    )
) {
    $aiChatRespond(['answer' => ai_assistant_msg($lang, 'payment_methods')], $lang);
}

$aiChatRespond([
    'answer' => (function () use ($questionNorm, $questionRaw, $lang, $userId, $containsOneOf, $containsAll): string {
        if (ai_assistant_is_out_of_scope($questionNorm, $questionRaw)) {
            return ai_assistant_msg($lang, 'not_programmed');
        }
        if ($containsOneOf($questionNorm, ['thanks', 'thank you', 'thx', 'merci', 'danke', 'شكر'])) {
            return ai_assistant_msg($lang, 'thanks');
        }
        if ($containsOneOf($questionNorm, ['bye', 'goodbye', 'see you', 'au revoir', 'a bientot', 'tschuss', 'wiedersehen'])) {
            return ai_assistant_msg($lang, 'bye');
        }
        if (ai_assistant_is_catalog_question($questionNorm, $containsOneOf)) {
            try {
                $pdo = Database::getConnection();
                $dbContext = ai_assistant_build_context($pdo, $userId);
                $localAnswer = ai_assistant_answer_locally(
                    $questionRaw,
                    $questionNorm,
                    $lang,
                    $dbContext,
                    $containsOneOf,
                    $containsAll
                );
                if (is_string($localAnswer) && $localAnswer !== '') {
                    return $localAnswer;
                }
            } catch (Throwable $e) {
                // fallthrough
            }
        }
        if ($containsOneOf($questionNorm, ['recette', 'recettes', 'recipe', 'recipes', 'plat', 'meal', 'rezept'])) {
            return ai_assistant_msg($lang, 'hint_recipes');
        }
        if ($containsOneOf($questionNorm, ['produit', 'produits', 'product', 'products', 'categorie', 'category', 'categories'])) {
            return ai_assistant_msg($lang, 'hint_products');
        }
        if ($containsOneOf($questionNorm, ['frigo', 'refrigerateur', 'refrigerator', 'fridge', 'kuhlschrank'])) {
            return ai_assistant_msg($lang, 'hint_frigo');
        }
        if ($containsOneOf($questionNorm, ['allerg', 'allergen', 'allergene', 'allergenes', 'calorie', 'calories', 'sante', 'health'])) {
            return ai_assistant_msg($lang, 'hint_health');
        }
        if ($containsOneOf($questionNorm, ['promo', 'promotion', 'discount', 'cheap', 'moins cher', 'budget', 'gunstig'])) {
            return ai_assistant_msg($lang, 'hint_promo');
        }
        if ($containsOneOf($questionNorm, ['bonjour', 'salut', 'hello', 'hi', 'hey', 'hallo'])) {
            return ai_assistant_msg($lang, 'hello');
        }
        if ($containsAll($questionNorm, ['comment', 'utiliser']) || $containsOneOf($questionNorm, ['how to use', 'how does it work', 'guide'])) {
            return ai_assistant_msg($lang, 'hint_guide');
        }
        return ai_assistant_msg($lang, 'unknown');
    })(),
], $lang);
