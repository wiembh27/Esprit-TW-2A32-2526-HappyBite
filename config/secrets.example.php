<?php

declare(strict_types=1);

/**
 * 1. Copiez ce fichier :  secrets.example.php  →  secrets.php  (dans le même dossier config/).
 * 2. Remplacez la valeur par votre vraie clé API OpenAI (commence par sk-...).
 * 3. Ne commitez pas secrets.php et ne la partagez pas.
 */
if (!defined('OPENAI_API_KEY')) {
    define('OPENAI_API_KEY', 'collez-votre-cle-openai-ici');
}
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', 'collez-votre-cle-gemini-ici');
}
