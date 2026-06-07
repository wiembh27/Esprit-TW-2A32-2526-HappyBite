<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/openai_key.php';

/**
 * @return array{ok: bool, body?: string, http?: int, error?: string}
 */
function communaute_openai_images_post(string $apiKey, array $payload): array
{
    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 25,
    ]);

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $curlErr !== '') {
        return ['ok' => false, 'error' => 'Connexion à OpenAI impossible. Réessayez dans un instant.'];
    }

    return ['ok' => true, 'body' => (string) $response, 'http' => $httpCode];
}

/**
 * @return array{ok: bool, binary?: string, message?: string}
 */
function communaute_extract_image_binary(string $responseBody, int $httpCode): array
{
    $decoded = json_decode($responseBody, true);
    if ($httpCode >= 400 || !is_array($decoded)) {
        $apiMsg = is_array($decoded) && isset($decoded['error']['message'])
            ? (string) $decoded['error']['message']
            : 'Erreur API OpenAI (HTTP ' . $httpCode . ').';

        return ['ok' => false, 'message' => $apiMsg];
    }

    $row = $decoded['data'][0] ?? null;
    if (!is_array($row)) {
        return ['ok' => false, 'message' => 'Réponse image vide de l\'API.'];
    }

    $b64 = (string) ($row['b64_json'] ?? '');
    if ($b64 !== '') {
        $binary = base64_decode($b64, true);
        if ($binary !== false && strlen($binary) >= 128) {
            return ['ok' => true, 'binary' => $binary];
        }
    }

    $url = (string) ($row['url'] ?? '');
    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        $binary = curl_exec($ch);
        curl_close($ch);
        if (is_string($binary) && strlen($binary) >= 128) {
            return ['ok' => true, 'binary' => $binary];
        }
    }

    return ['ok' => false, 'message' => 'Format de réponse image non reconnu.'];
}

/**
 * Generate a food image for community posts (OpenAI Images API).
 *
 * @return array{ok: bool, image?: string, message?: string}
 */
function communaute_generate_food_image(string $dishPrompt): array
{
    $dishPrompt = trim($dishPrompt);
    if ($dishPrompt === '') {
        return ['ok' => false, 'message' => 'Indiquez un plat à générer.'];
    }
    if (mb_strlen($dishPrompt, 'UTF-8') > 400) {
        $dishPrompt = mb_substr($dishPrompt, 0, 400, 'UTF-8');
    }

    $apiKey = hb_openai_api_key();
    if ($apiKey === '') {
        return [
            'ok' => false,
            'message' => 'Clé OpenAI manquante. Ajoutez-la dans config/openai.key ou config/secrets.php.',
        ];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'message' => 'Extension cURL requise pour la génération d\'image.'];
    }

    $prompt = 'Delicious realistic food photography of '
        . $dishPrompt
        . ', appetizing restaurant-style dish, soft natural lighting, highly detailed, no text, no watermark, no people';

    /* GPT Image models reject response_format / n; DALL-E 2 accepts them. */
    $attempts = [
        [
            'model' => 'gpt-image-1',
            'prompt' => $prompt,
            'size' => '1024x1024',
        ],
        [
            'model' => 'dall-e-2',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '512x512',
            'response_format' => 'b64_json',
        ],
        [
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'response_format' => 'url',
        ],
    ];

    $lastMessage = 'Génération d\'image impossible.';

    foreach ($attempts as $payload) {
        $http = communaute_openai_images_post($apiKey, $payload);
        if (!$http['ok']) {
            $lastMessage = (string) ($http['error'] ?? $lastMessage);
            continue;
        }

        $extract = communaute_extract_image_binary((string) $http['body'], (int) $http['http']);
        if (!empty($extract['ok'])) {
            require_once __DIR__ . '/UploadStorage.php';
            $saved = UploadStorage::saveBinaryImage((string) $extract['binary'], 'posts', 'ai');
            if (empty($saved['success']) || empty($saved['path'])) {
                return ['ok' => false, 'message' => $saved['message'] ?? 'Impossible d\'enregistrer l\'image sur le serveur.'];
            }

            return ['ok' => true, 'image' => (string) $saved['path']];
        }

        $lastMessage = (string) ($extract['message'] ?? $lastMessage);
        if (stripos($lastMessage, 'unknown parameter') === false
            && stripos($lastMessage, 'invalid') === false
            && stripos($lastMessage, 'does not exist') === false
            && stripos($lastMessage, 'not supported') === false
        ) {
            break;
        }
    }

    return ['ok' => false, 'message' => $lastMessage];
}
