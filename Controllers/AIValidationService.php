<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/openai_key.php';

/**
 * Validation IA des participations aux challenges.
 *
 * Objectif :
 * - analyser la photo réellement ;
 * - vérifier si elle correspond au challenge du jour ;
 * - refuser avant d'enregistrer si la photo ne respecte pas le défi.
 */
class AIValidationService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = hb_openai_api_key();
    }

    /**
     * Valide une participation à un challenge.
     *
     * @return array{
     *   success: bool,
     *   message: string,
     *   score: int,
     *   details?: array<string,mixed>
     * }
     */
    public function validateChallengeWithAI(
        ?string $imagePath,
        string $description,
        array $challenge
    ): array {
        if ($imagePath === null || trim($imagePath) === '' || !is_file($imagePath)) {
            return [
                'success' => false,
                'message' => 'Veuillez ajouter une photo du repas pour participer au challenge.',
                'score' => 0,
                'details' => [
                    'reason' => 'missing_image',
                ],
            ];
        }

        $mimeType = mime_content_type($imagePath) ?: '';

        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            return [
                'success' => false,
                'message' => 'Format image non reconnu. Utilisez une image JPEG, PNG, WebP ou GIF.',
                'score' => 0,
                'details' => [
                    'reason' => 'invalid_mime',
                    'mime' => $mimeType,
                ],
            ];
        }

        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'Validation IA impossible : clé API OpenAI manquante. Ajoutez votre nouvelle clé dans config/secrets.php ou config/openai.key.',
                'score' => 0,
                'details' => [
                    'reason' => 'missing_api_key',
                ],
            ];
        }

        $titre = trim((string) ($challenge['titre'] ?? ''));
        $challengeDescription = trim((string) ($challenge['description'] ?? ''));
        $regleIa = trim((string) ($challenge['regle_ia'] ?? ''));

        $descriptionUser = trim($description);

        $prompt = $this->buildPrompt(
            $titre,
            $challengeDescription,
            $regleIa,
            $descriptionUser
        );

        $raw = $this->callOpenAIVision($prompt, $imagePath, $mimeType);

        if (!$raw['success']) {
            return [
                'success' => false,
                'message' => $raw['message'],
                'score' => 0,
                'details' => [
                    'reason' => 'api_error',
                    'raw' => $raw,
                ],
            ];
        }

        $json = $this->extractJson((string) $raw['content']);

        if (!is_array($json)) {
            return [
                'success' => false,
                'message' => 'L’IA n’a pas renvoyé une validation lisible. Réessayez avec une photo plus claire.',
                'score' => 0,
                'details' => [
                    'reason' => 'invalid_json',
                    'raw_content' => $raw['content'] ?? '',
                ],
            ];
        }

        $correspondance = (bool) ($json['correspondance'] ?? false);
        $score = (int) ($json['score'] ?? 0);
        $score = max(0, min(100, $score));

        $messageCourt = trim((string) ($json['message_utilisateur'] ?? ''));
        $raison = trim((string) ($json['raison'] ?? ''));

        if ($messageCourt === '') {
            $messageCourt = $correspondance
                ? 'Photo validée : elle correspond au challenge.'
                : 'Photo refusée : elle ne correspond pas assez au challenge.';
        }

        $success = $correspondance && $score >= 70;

        if (!$success && $raison !== '') {
            $messageCourt .= ' Raison : ' . $raison;
        }

        return [
            'success' => $success,
            'message' => $messageCourt . ' Score IA : ' . $score . '/100',
            'score' => $score,
            'details' => $json,
        ];
    }

    private function buildPrompt(
        string $titre,
        string $challengeDescription,
        string $regleIa,
        string $descriptionUser
    ): string {
        if ($regleIa === '') {
            $regleIa = $this->inferRuleFromChallenge($titre . ' ' . $challengeDescription);
        }

        return <<<PROMPT
Tu es un validateur IA pour une application appelée HappyBite.

Tu dois vérifier si la photo envoyée par l'utilisateur correspond réellement au challenge alimentaire du jour.

Challenge :
Titre : {$titre}
Description : {$challengeDescription}
Règle IA à appliquer : {$regleIa}

Description écrite par l'utilisateur :
{$descriptionUser}

Exemples de logique attendue :
- Si le challenge demande des légumes verts, la photo doit contenir clairement des légumes verts visibles.
- Si le challenge demande des œufs, la photo doit contenir clairement des œufs visibles.
- Si le challenge demande de remplacer le soda, la photo ne doit PAS contenir de soda, canette de soda, bouteille de soda ou boisson gazeuse sucrée.
- Si le challenge demande un fruit, la photo doit contenir un fruit visible.
- Si le challenge demande une salade, la photo doit contenir une vraie salade ou un plat majoritairement composé de légumes.
- Si le challenge demande un repas fait maison, la photo doit ressembler à un plat préparé, pas seulement un emballage industriel.
- Si la photo est floue, hors sujet, ou ne montre pas de nourriture, refuse.
- Ne valide pas seulement grâce au texte écrit par l'utilisateur. La photo est prioritaire.

Réponds uniquement en JSON valide, sans texte avant ni après.

Format obligatoire :
{
  "correspondance": true,
  "score": 0,
  "elements_detectes": ["..."],
  "elements_interdits_detectes": ["..."],
  "raison": "...",
  "message_utilisateur": "..."
}

Critères :
- correspondance = true uniquement si la photo respecte clairement le challenge.
- score entre 0 et 100.
- score >= 70 si la photo est acceptable.
- score < 70 si la photo est douteuse ou refusée.
- message_utilisateur doit être court, clair et en français.
PROMPT;
    }

    private function inferRuleFromChallenge(string $text): string
    {
        $t = mb_strtolower($text, 'UTF-8');

        if ($this->containsAny($t, ['légumes verts', 'legumes verts', 'vert', 'verts', 'brocoli', 'salade', 'épinard', 'epinard'])) {
            return 'La photo doit contenir clairement des légumes verts visibles dans le repas.';
        }

        if ($this->containsAny($t, ['oeuf', 'œuf', 'oeufs', 'œufs', 'omelette'])) {
            return 'La photo doit contenir clairement des œufs visibles, par exemple œuf dur, omelette, œufs brouillés ou plat à base d’œufs.';
        }

        if ($this->containsAny($t, ['soda', 'boisson gazeuse', 'coca', 'cola'])) {
            return 'La photo ne doit pas contenir de soda, boisson gazeuse sucrée, canette ou bouteille de soda. Elle doit montrer une alternative plus saine comme eau, jus naturel ou boisson maison.';
        }

        if ($this->containsAny($t, ['fruit', 'fruits', 'pomme', 'banane', 'orange', 'fraise'])) {
            return 'La photo doit contenir clairement au moins un fruit visible.';
        }

        if ($this->containsAny($t, ['salade'])) {
            return 'La photo doit contenir une salade ou un plat majoritairement composé de légumes.';
        }

        if ($this->containsAny($t, ['maison', 'fait maison', 'home made', 'homemade'])) {
            return 'La photo doit montrer un repas préparé ou fait maison, pas seulement un produit emballé.';
        }

        if ($this->containsAny($t, ['eau', 'hydratation'])) {
            return 'La photo doit montrer de l’eau ou une boisson saine adaptée au challenge.';
        }

        return 'La photo doit correspondre clairement au titre et à la description du challenge alimentaire.';
    }

    private function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && mb_strpos($text, mb_strtolower($needle, 'UTF-8')) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{success: bool, message: string, content?: string}
     */
    private function callOpenAIVision(string $prompt, string $imagePath, string $mimeType): array
    {
        $imageData = base64_encode((string) file_get_contents($imagePath));

        $payload = [
            'model' => 'gpt-4.1-mini',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:' . $mimeType . ';base64,' . $imageData,
                            ],
                        ],
                    ],
                ],
            ],
            'temperature' => 0.1,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');

        if ($ch === false) {
            return [
                'success' => false,
                'message' => 'Impossible d’initialiser cURL pour la validation IA.',
            ];
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);

            return [
                'success' => false,
                'message' => 'Erreur cURL pendant la validation IA : ' . $error,
            ];
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode((string) $response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            $apiMessage = is_array($decoded) && isset($decoded['error']['message'])
                ? (string) $decoded['error']['message']
                : 'Réponse API invalide.';

            return [
                'success' => false,
                'message' => 'Erreur API OpenAI : ' . $apiMessage,
            ];
        }

        $content = is_array($decoded)
            ? (string) ($decoded['choices'][0]['message']['content'] ?? '')
            : '';

        if (trim($content) === '') {
            return [
                'success' => false,
                'message' => 'L’IA n’a pas renvoyé de contenu.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Validation IA reçue.',
            'content' => $content,
        ];
    }

    /**
     * Extrait un JSON même si le modèle ajoute accidentellement du texte.
     *
     * @return array<string,mixed>|null
     */
    private function extractJson(string $text): ?array
    {
        $text = trim($text);

        $direct = json_decode($text, true);
        if (is_array($direct)) {
            return $direct;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $jsonPart = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($jsonPart, true);

        return is_array($decoded) ? $decoded : null;
    }
}