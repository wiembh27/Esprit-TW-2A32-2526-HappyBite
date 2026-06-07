<?php

require_once __DIR__ . '/../config/openai_key.php';

class AiRecetteController
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = hb_openai_api_key();
    }

    public function genererMenuSemaine($produitsFrigo, $profilSante = null)
    {
        $profilSante = is_array($profilSante) ? $profilSante : [];

        if (empty($produitsFrigo)) {
            return "[]";
        }

        usort($produitsFrigo, function ($a, $b) {
            return strtotime($a['date_ajout'] ?? 'now') - strtotime($b['date_ajout'] ?? 'now');
        });

        $ingredientsAvecDates = [];

        foreach ($produitsFrigo as $produit) {
            if (!empty($produit['nom'])) {
                $ingredientsAvecDates[] = $produit['nom'] . " depuis le " . ($produit['date_ajout'] ?? 'date inconnue');
            }
        }

        $ingredientsAvecDates = array_slice(array_unique($ingredientsAvecDates), 0, 12);

        $objectif = $profilSante['objectif'] ?? 'non précisé';
        $allergenes = $profilSante['allergenes'] ?? 'aucune allergie';
        $maladies = $profilSante['maladies'] ?? 'aucune maladie';
        $carences = $profilSante['carences'] ?? 'aucune carence';

        $prompt = "Tu es ChefBot, un assistant alimentaire intelligent.

Crée exactement 7 recettes différentes pour une semaine.

Profil santé :
Objectif : $objectif
Maladies : $maladies
Allergènes : $allergenes
Carences : $carences

Produits du frigo du plus ancien au plus récent :
" . implode("\n", $ingredientsAvecDates) . "

Règles :
- Une recette pour chaque jour : Lundi, Mardi, Mercredi, Jeudi, Vendredi, Samedi, Dimanche.
- Chaque recette utilise seulement 2 à 4 produits du frigo.
- Tu peux ajouter seulement : sel, poivre, huile, eau.
- Priorise les aliments les plus anciens pour limiter le gaspillage.
- Respecte l'état de santé du client.
- Si un produit ne convient pas à son état de santé, évite-le.
- Réponds uniquement en JSON valide, sans texte avant ni après.

Format exact :
[
  {
    \"jour\": \"Lundi\",
    \"objectif\": \"perte de poids\",
    \"sante\": \"Diabète, allergènes : Gluten,Lactose, carence : Fer\",
    \"produits_prioritaires\": \"pomme depuis le 2026-04-23, carotte depuis le 2026-04-23\",
    \"titre\": \"...\",
    \"ingredients\": [\"...\", \"...\"],
    \"etapes\": [\"...\", \"...\"],
    \"pourquoi\": \"...\"
  }
]";

        if (empty($this->apiKey)) {
            return $this->genererMenuSemaineLocal($produitsFrigo, $profilSante);
        }

        $reponse = $this->appelOpenAI($prompt);
        $menuDecode = json_decode((string) $reponse, true);

        if (!is_array($menuDecode) || empty($menuDecode)) {
            return $this->genererMenuSemaineLocal($produitsFrigo, $profilSante);
        }

        return $reponse;
    }

    private function genererMenuSemaineLocal(array $produitsFrigo, ?array $profilSante = null): string
    {
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];

        usort($produitsFrigo, function ($a, $b) {
            return strtotime($a['date_ajout'] ?? 'now') - strtotime($b['date_ajout'] ?? 'now');
        });

        $nomsProduits = [];
        foreach ($produitsFrigo as $produit) {
            $nom = trim((string) ($produit['nom'] ?? ''));
            if ($nom !== '') {
                $nomsProduits[] = $nom;
            }
        }

        $nomsProduits = array_values(array_unique($nomsProduits));
        if (empty($nomsProduits)) {
            return "[]";
        }

        $objectif = trim((string) ($profilSante['objectif'] ?? 'équilibre alimentaire'));
        $santeResume = trim((string) ($profilSante['maladies'] ?? 'aucune maladie'));
        $allergenes = trim((string) ($profilSante['allergenes'] ?? 'aucun'));
        $carences = trim((string) ($profilSante['carences'] ?? 'aucune'));

        $menu = [];
        $nbProduits = count($nomsProduits);

        foreach ($jours as $index => $jour) {
            $choisis = [];
            $taille = min(3, $nbProduits);

            for ($i = 0; $i < $taille; $i++) {
                $choisis[] = $nomsProduits[($index + $i) % $nbProduits];
            }

            $produitsTexte = implode(', ', $choisis);
            $titre = 'Assiette healthy de ' . $choisis[0] . (isset($choisis[1]) ? ' et ' . $choisis[1] : '');

            $menu[] = [
                'jour' => $jour,
                'objectif' => $objectif !== '' ? $objectif : 'équilibre alimentaire',
                'sante' => 'Maladies: ' . $santeResume . ', allergènes: ' . $allergenes . ', carences: ' . $carences,
                'produits_prioritaires' => $produitsTexte,
                'titre' => $titre,
                'ingredients' => $choisis,
                'etapes' => [
                    'Laver et préparer les ingrédients: ' . $produitsTexte . '.',
                    'Cuire doucement avec un peu d\'huile, sel et poivre.',
                    'Assembler dans une assiette équilibrée et servir chaud.',
                ],
                'pourquoi' => 'Recette basée sur les produits déjà présents dans le frigo pour limiter le gaspillage.',
            ];
        }

        return json_encode($menu, JSON_UNESCAPED_UNICODE);
    }

    private function appelOpenAI($prompt)
    {
        if (empty($this->apiKey)) {
            return "Erreur : clé API manquante.";
        }

        $data = [
            "model" => "gpt-4.1-mini",
            "messages" => [
                ["role" => "user", "content" => $prompt]
            ],
            "temperature" => 0.7
        ];

        $ch = curl_init("https://api.openai.com/v1/chat/completions");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return "Erreur cURL : " . $error;
        }

        curl_close($ch);

        $result = json_decode($response, true);

        if (isset($result['error']['message'])) {
            return "Erreur OpenAI : " . $result['error']['message'];
        }

        return $result['choices'][0]['message']['content'] ?? "Erreur : réponse vide.";
    }

    public function analyserPlatPhoto($imagePath, $profilSante = null)
{
    $profilSante = is_array($profilSante) ? $profilSante : [];
    $objectif = $profilSante['objectif'] ?? 'non précisé';
    $allergenes = $profilSante['allergenes'] ?? 'aucune allergie';
    $maladies = $profilSante['maladies'] ?? 'aucune maladie';
    $carences = $profilSante['carences'] ?? 'aucune carence';

    $imageData = base64_encode(file_get_contents($imagePath));
    $mimeType = mime_content_type($imagePath);

    $prompt = "Tu es NutriVision, un assistant nutritionnel intelligent.

Analyse le plat sur la photo.

Profil santé :
Objectif : $objectif
Maladies : $maladies
Allergènes : $allergenes
Carences : $carences

Règles :
- Détecte les ingrédients visibles.
- Estime les calories.
- Estime protéines, glucides et lipides.
- Donne un score santé sur 10.
- Explique si le plat est équilibré ou non.
- Propose comment le rééquilibrer selon le profil santé.
- Propose une activité physique adaptée à l’objectif.
- Si objectif = prise de poids, ne propose pas de cardio intense pour brûler les calories.
- Si objectif = perte de poids, propose une activité modérée.
- Si maladie ou allergie détectée, adapte les conseils.
- Réponds uniquement en JSON valide.

Format exact :
{
  \"ingredients_detectes\": [\"...\"],
  \"calories_estimees\": 0,
  \"proteines\": \"0g\",
  \"glucides\": \"0g\",
  \"lipides\": \"0g\",
  \"score_sante\": 0,
  \"niveau\": \"vert/orange/rouge\",
  \"analyse\": \"...\",
  \"reequilibrage\": [\"...\", \"...\"],
  \"sport_conseille\": \"...\",
  \"avertissement_sante\": \"...\"
}";

    if (empty($this->apiKey)) {
        return $this->analyserPlatPhotoLocal($profilSante);
    }

    return $this->appelOpenAIVision($prompt, $imageData, $mimeType);
}

    /**
     * Estimation locale (sans OpenAI) quand aucune clé API n'est configurée.
     */
    private function analyserPlatPhotoLocal(array $profilSante): string
    {
        $objectif = $this->formatterChampProfilPourMessage($profilSante['objectif'] ?? '');
        if ($objectif === 'non précisé') {
            $objectif = 'équilibre alimentaire';
        }
        $allergenes = $this->formatterChampProfilPourMessage($profilSante['allergenes'] ?? '');
        $maladies = $this->formatterChampProfilPourMessage($profilSante['maladies'] ?? '');
        $carences = $this->formatterChampProfilPourMessage($profilSante['carences'] ?? '');

        $ligneProfil = $this->resumeProfilPourAnalysePhoto($objectif, $allergenes, $maladies, $carences);

        $analyse = 'Photo non analysée automatiquement : calories, nutrition et score sont indicatifs, pas mesurés sur votre plat. '
            . 'Conseils selon votre profil : ' . $ligneProfil . '.';

        $reeq = [
            'Compléter avec une portion de légumes verts ou une salade.',
            'Privilégier une cuisson simple (vapeur, four) et limiter les fritures.',
        ];
        if (stripos($objectif, 'perte') !== false) {
            $reeq[] = 'Pour la perte de poids : favoriser satiété (protéines maigres, fibres) et éviter les boissons sucrées.';
        }
        $avert = $this->resumeContraintesBudgetSante($allergenes, $maladies);
        if ($avert === '') {
            $avert = 'Rappel : cet outil ne remplace pas un avis médical ni une analyse nutritionnelle personnalisée.';
        } else {
            $avert .= ' Ceci ne remplace pas un avis médical.';
        }

        $data = [
            'ingredients_detectes' => ['Repas photographié (estimation locale, sans vision IA)'],
            'calories_estimees' => 480,
            'proteines' => '22 g',
            'glucides' => '48 g',
            'lipides' => '16 g',
            'score_sante' => 6,
            'niveau' => 'orange',
            'analyse' => $analyse,
            'reequilibrage' => $reeq,
            'sport_conseille' => stripos($objectif, 'perte') !== false
                ? 'Marche rapide ou vélo doux 30–40 min, 4 fois par semaine.'
                : 'Marche quotidienne 20–30 min + renforcement léger 2 fois par semaine.',
            'avertissement_sante' => $avert,
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

private function appelOpenAIVision($prompt, $imageData, $mimeType)
{
    $data = [
        "model" => "gpt-4.1-mini",
        "messages" => [
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "text",
                        "text" => $prompt
                    ],
                    [
                        "type" => "image_url",
                        "image_url" => [
                            "url" => "data:$mimeType;base64,$imageData"
                        ]
                    ]
                ]
            ]
        ],
        "temperature" => 0.4
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $this->apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return "Erreur cURL : " . $error;
    }

    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['error']['message'])) {
        return "Erreur OpenAI : " . $result['error']['message'];
    }

    return $result['choices'][0]['message']['content'] ?? "Erreur : réponse vide.";
}
public function proposerAlternativeBudgetSante($produitCher, $budget, $profilSante = null)
{
    $profilSante = is_array($profilSante) ? $profilSante : [];
    $objectif = $profilSante['objectif'] ?? 'non précisé';
    $allergenes = $profilSante['allergenes'] ?? 'aucune allergie';
    $maladies = $profilSante['maladies'] ?? 'aucune maladie';
    $carences = $profilSante['carences'] ?? 'aucune carence';

    $prompt = "Tu es BudgetBot, un assistant alimentaire intelligent.

Produit jugé cher : $produitCher
Budget disponible : $budget DT

Profil santé :
Objectif : $objectif
Maladies : $maladies
Allergènes : $allergenes
Carences : $carences

Donne une alternative alimentaire moins chère et adaptée au profil santé.
Exemples :
- saumon -> thon ou sardine
- lait -> lait d'amande si lactose
- pain normal -> pain sans gluten si gluten

Réponds en 4 lignes maximum :
Produit remplacé :
Alternative proposée :
Pourquoi :
Attention santé :";

    if (empty($this->apiKey)) {
        return $this->proposerAlternativeBudgetSanteLocal($produitCher, $budget, $profilSante);
    }

    return $this->appelOpenAI($prompt);
}

    private function proposerAlternativeBudgetSanteLocal(string $produitCher, string $budget, array $profilSante): string
    {
        $budgText = trim($budget) !== '' ? trim($budget) . ' DT' : 'non renseigné';
        $objectif = $this->formatterChampProfilPourMessage($profilSante['objectif'] ?? '');
        $allergenes = $this->formatterChampProfilPourMessage($profilSante['allergenes'] ?? '');
        $maladies = $this->formatterChampProfilPourMessage($profilSante['maladies'] ?? '');
        $carences = $this->formatterChampProfilPourMessage($profilSante['carences'] ?? '');

        $haystack = $this->normaliserTexteProduit($produitCher);
        $idees = $this->ideesAlternativesLocalesParProduit($haystack, $allergenes);

        $baseAlt = $idees !== ''
            ? $idees . ' — pensez aussi marque distributeur, format familial ou surgelé pour respecter votre budget (' . $budgText . ').'
            : 'privilégiez une option du même rayon moins chère (marque distributeur, format familial ou surgelé) pour rester sous votre budget (' . $budgText . ').';

        $contraintes = $this->resumeContraintesBudgetSante($allergenes, $maladies);
        $pourquoi = $idees !== ''
            ? 'Équivalences alimentaires courantes (même rôle en cuisine) souvent moins onéreuses ; suggestion automatique hors ligne sans assistant connecté.'
            : 'Suggestion automatique hors ligne, basée sur le budget ; sans assistant connecté, le détail par produit reste générique.';

        return "Produit remplacé : " . $produitCher . "\n"
            . "Alternative proposée : " . $baseAlt . "\n"
            . "Pourquoi : " . $pourquoi . "\n"
            . "Attention santé : objectif « " . $objectif . " », maladies : " . $maladies . ", allergies : " . $allergenes . ", carences : " . $carences . "."
            . ($contraintes !== '' ? ' ' . $contraintes : '')
            . ' Vérifiez toujours les étiquettes.';
    }

    /**
     * Affiche objectifs / allergies stockés en texte ou en JSON (tableau PHP).
     */
    private function formatterChampProfilPourMessage($value): string
    {
        if (is_array($value)) {
            $parts = array_filter(array_map(static function ($x) {
                return trim((string) $x);
            }, $value));

            return $parts === [] ? 'non précisé' : implode(', ', $parts);
        }

        $s = trim((string) $value);
        if ($s === '') {
            return 'non précisé';
        }

        if ($s[0] === '[' || $s[0] === '{') {
            $decoded = json_decode($s, true);
            if (is_array($decoded)) {
                return $this->formatterChampProfilPourMessage($decoded);
            }
        }

        return $s;
    }

    /** Ligne courte pour le message d’analyse photo (mode hors assistant). */
    private function resumeProfilPourAnalysePhoto(
        string $objectif,
        string $allergenes,
        string $maladies,
        string $carences
    ): string {
        $parts = [$objectif];
        if ($allergenes !== 'non précisé') {
            $parts[] = 'allergies : ' . $allergenes;
        }
        if ($maladies !== 'non précisé') {
            $parts[] = 'pathologies : ' . $maladies;
        }
        if ($carences !== 'non précisé') {
            $parts[] = 'carences : ' . $carences;
        }

        return implode(' ; ', $parts);
    }

    private function normaliserTexteProduit(string $nom): string
    {
        $nom = mb_strtolower($nom, 'UTF-8');
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nom);
        if ($ascii !== false) {
            $nom = strtolower($ascii);
        }

        return preg_replace('/\s+/u', ' ', $nom);
    }

    /**
     * @return string idées séparées par des virgules, ou chaîne vide si aucun motif connu
     */
    private function ideesAlternativesLocalesParProduit(string $haystackNorm, string $allergenesLisibles): string
    {
        $a = mb_strtolower($allergenesLisibles, 'UTF-8');
        $sansLait = (str_contains($a, 'lact') || str_contains($a, 'lait'));
        $sansGluten = str_contains($a, 'gluten');

        $paires = [
            [['saumon', 'salmon', 'samon', 'somon', 'truite'], 'thon en conserve, sardines, maquereau ou saumon surgelé / MDD'],
            [['crevette', 'crevettes', 'gambas', 'calamar', 'poulpe', 'moule'], 'poisson blanc surgelé, thon en boîte, ou fruits de mer MDD / surgelés'],
            [['boeuf', 'bœuf', 'steak', 'entrecote', 'entrecôte', 'viande hachee', 'viande hachée', 'agneau', 'veau'], 'légumineuses (lentilles, pois chiches), œufs, tofu, ou viande / poisson MDD et surgelés'],
            [['poulet', 'dinde', 'filet de'], 'cuisse ou ailes MDD, tofu, œufs, ou poisson en conserve'],
            [['lait', 'creme', 'crème', 'yaourt', 'yogourt', 'beurre'], $sansLait
                ? 'laits végétaux sans lactose, margarine végétale adaptée, yaourts végétaux (vérifiez « sans lactose »)'
                : 'lait demi-écrémé MDD, yaourt en multipack, ou laits UHT famille'],
            [['fromage'], $sansLait ? 'options sans lactose du rayon ou fromages à base adaptée (selon tolérance)' : 'fromage bloc MDD ou en promotion, ou fromage râpé grande surface'],
            [['pain', 'baguette', 'farine'], $sansGluten ? 'pain sans gluten du rayon ou farines adaptées' : 'pain MDD, pain de mie famille, ou congelé'],
            [['huile', 'olive'], 'huile de tournesol ou colza MDD (même usage en cuisson)'],
            [['avocat'], 'légumes de saison en salade, ou olives / houmous selon usage'],
            [['amande', 'noix', 'noisette', 'cacahuete', 'cacahuète'], 'mélange fruits secs MDD ou graines (tournesol, courge) en plus petite quantité'],
            [['chocolat'], 'cacao en poudre MDD ou tablette promotion / format partage'],
            [['riz', 'pates', 'pâtes', 'couscous'], 'riz ou pâtes MDD en sachet famille, ou légumineuses + féculent basique'],
        ];

        foreach ($paires as [$mots, $idee]) {
            foreach ($mots as $mot) {
                if ($mot !== '' && str_contains($haystackNorm, mb_strtolower($mot, 'UTF-8'))) {
                    return $idee;
                }
            }
        }

        return '';
    }

    private function resumeContraintesBudgetSante(string $allergenes, string $maladies): string
    {
        $a = mb_strtolower($allergenes, 'UTF-8');
        $m = mb_strtolower($maladies, 'UTF-8');
        $parts = [];

        if (str_contains($a, 'lact') || str_contains($a, 'lait')) {
            $parts[] = 'Évitez les produits contenant du lactose si vous y êtes sensible.';
        }
        if (str_contains($a, 'gluten')) {
            $parts[] = 'Pour l’allergie au gluten, vérifiez la mention « sans gluten ».';
        }
        if (str_contains($m, 'hypertension') || str_contains($m, 'tension')) {
            $parts[] = 'En cas d’hypertension, privilégiez les références basses en sel.';
        }
        if (str_contains($m, 'diab') || str_contains($m, 'diabète')) {
            $parts[] = 'En cas de diabète, surveillez sucres et index glycémique sur l’emballage.';
        }

        return $parts === [] ? '' : implode(' ', $parts);
    }
}