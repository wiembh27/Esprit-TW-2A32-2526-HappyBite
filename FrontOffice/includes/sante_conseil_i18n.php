<?php

declare(strict_types=1);

require_once __DIR__ . '/fo_i18n.php';

/**
 * @return array<string, array<string, string>>
 */
function sante_conseil_messages(): array
{
    static $messages = null;
    if ($messages !== null) {
        return $messages;
    }

    $messages = [
        'fr' => [
            'titre_good' => 'Belle journée santé : +10 points.',
            'titre_improve' => 'Journée à améliorer : -10 points.',
            'motivation_good' => 'Bravo, votre journée montre une vraie progression. Continuez avec régularité, même les petites habitudes répétées font une grande différence.',
            'motivation_improve' => 'Une journée imparfaite ne veut pas dire un échec. Elle vous donne simplement des informations pour mieux ajuster demain.',
            'calories_imprecise' => 'Alimentation : les calories ne sont pas assez précises pour juger l’équilibre complet de la journée. Renseignez-les pour obtenir une analyse fiable.',
            'perte_high_low_move' => 'Alimentation et activité : pour un objectif de perte de poids, la journée est très chargée en calories avec peu de mouvement. Demain, allégez légèrement les portions très caloriques et ajoutez une petite séance douce, par exemple 15 à 20 minutes de marche.',
            'perte_high_sport' => 'Alimentation et activité : les calories sont élevées pour une perte de poids, mais votre séance sportive aide à équilibrer la journée. Gardez l’activité, mais essayez d’alléger un peu les repas les plus riches.',
            'perte_high_walk' => 'Alimentation et marche : les calories sont un peu élevées pour une perte de poids, mais votre nombre de pas compense partiellement. Continuez à marcher régulièrement et essayez de rendre les repas un peu plus légers demain.',
            'perte_ok' => 'Alimentation et activité : votre journée est plutôt cohérente avec un objectif de perte de poids. Les calories restent raisonnables et l’activité physique aide à maintenir un bon équilibre.',
            'perte_low_cal' => 'Alimentation : les calories semblent très basses. Même pour perdre du poids, évitez une restriction trop forte. Gardez des repas simples mais complets : légumes, protéines et féculents en quantité modérée.',
            'perte_ok2' => 'Alimentation : votre journée reste globalement acceptable pour un objectif de perte de poids. Pour progresser, gardez des portions régulières et ajoutez un peu de mouvement si possible.',
            'prise_low_high_act' => 'Alimentation et effort : pour une prise de poids ou de masse, votre apport semble trop faible par rapport à l’activité réalisée. Ajoutez une collation nourrissante : yaourt, fruits secs, œufs, fromage, sandwich équilibré ou smoothie.',
            'prise_ok' => 'Alimentation et objectif : votre apport est cohérent avec une prise de poids ou de masse. Continuez avec des repas réguliers, riches en protéines, féculents et bonnes graisses.',
            'prise_low' => 'Alimentation : pour votre objectif de prise de poids ou de masse, l’apport semble un peu bas. Augmentez progressivement les quantités sans sauter de repas.',
            'prise_ok2' => 'Alimentation : la journée est correcte pour soutenir votre objectif. Gardez une bonne régularité sur plusieurs jours.',
            'gen_high_low' => 'Alimentation et activité : les calories sont élevées et la journée est peu active. Pour rééquilibrer, mangez plus léger demain et ajoutez une marche ou une séance douce.',
            'gen_high_comp' => 'Alimentation et activité : les calories sont assez élevées, mais votre activité aide à compenser. Gardez le mouvement tout en choisissant des repas un peu plus équilibrés.',
            'gen_balanced' => 'Alimentation et activité : la journée semble assez équilibrée. Les calories restent raisonnables et votre activité physique soutient bien votre santé.',
            'gen_ok' => 'Alimentation : votre journée semble correcte. Visez une assiette variée avec légumes, protéines, féculents et une quantité raisonnable de matières grasses.',
            'hyd_unknown' => 'Hydratation : la valeur enregistrée n’est pas reconnue. Vérifiez le choix d’eau dans le formulaire.',
            'hyd_low_sport' => 'Hydratation : vous avez bu moins de 1L alors que vous avez fait une activité physique. C’est insuffisant pour bien récupérer. Buvez avant, pendant si besoin, puis après l’effort.',
            'hyd_low' => 'Hydratation : vous avez bu moins de 1L. Gardez une bouteille près de vous demain et buvez petit à petit pendant la journée.',
            'hyd_mid_intense' => 'Hydratation : entre 1L et 1,5L peut être juste après une séance intense. Augmentez légèrement l’eau les jours d’effort.',
            'hyd_mid' => 'Hydratation : votre niveau est correct, mais encore améliorable. Un ou deux verres d’eau en plus peuvent rendre la journée plus équilibrée.',
            'hyd_good' => 'Hydratation : très bon équilibre. Votre consommation d’eau est adaptée pour une journée normale.',
            'hyd_high' => 'Hydratation : bonne hydratation aujourd’hui. C’est particulièrement positif si vous avez marché ou fait du sport.',
            'recover_bad' => 'Récupération : vous avez fait un effort intense avec peu de sommeil. Ce n’est pas idéal. Privilégiez une séance plus légère ou améliorez le sommeil avant un effort important.',
            'recover_good' => 'Sport et récupération : votre séance est bien soutenue par un bon sommeil. C’est une combinaison positive pour progresser sans trop fatiguer le corps.',
            'sport_sleep_low' => 'Sport et sommeil : l’activité est positive, mais le sommeil reste un peu faible. Pour mieux récupérer, dormez plus tôt ou réduisez l’intensité les jours de fatigue.',
            'walk_good' => 'Activité : même sans séance sportive, votre marche est un bon point. Beaucoup de pas peuvent déjà soutenir votre santé et votre objectif.',
            'move_medium' => 'Activité : vous avez bougé un minimum, mais vous pouvez améliorer la journée avec une petite marche supplémentaire ou quelques exercices doux.',
            'move_low' => 'Activité : la journée est peu active. Une courte marche après un repas peut déjà améliorer la digestion, l’énergie et l’équilibre global.',
            'sport_good' => 'Sport : bonne séance. Une activité modérée et régulière est souvent plus durable qu’un effort trop intense de temps en temps.',
            'sport_short' => 'Sport : même une courte séance est positive. Augmentez progressivement la durée si vous vous sentez bien.',
            'sleep_missing' => 'Sommeil : renseignez vos heures de sommeil pour mieux comprendre votre récupération.',
            'sleep_low' => 'Sommeil : votre sommeil est faible. Cela peut augmenter la faim, la fatigue et diminuer la motivation. Essayez de stabiliser votre heure de coucher.',
            'sleep_good' => 'Sommeil : très bon point, votre sommeil favorise une meilleure récupération et un meilleur contrôle de l’appétit.',
            'sleep_high' => 'Sommeil : vous avez beaucoup dormi. Si cela arrive souvent avec de la fatigue, surveillez votre rythme général.',
            'allergens' => 'Précaution allergènes : évitez les aliments déclarés dans votre profil : %s.',
            'deficiencies' => 'Carences : comme vous avez indiqué %s, variez davantage les repas avec des aliments naturellement riches en nutriments.',
            'diseases' => 'Santé : comme vous avez indiqué %s, évitez les changements alimentaires extrêmes et gardez une progression prudente.',
        ],
        'en' => [
            'titre_good' => 'Great health day: +10 points.',
            'titre_improve' => 'Day to improve: -10 points.',
            'motivation_good' => 'Well done — your day shows real progress. Keep it up; even small repeated habits make a big difference.',
            'motivation_improve' => 'An imperfect day is not a failure. It simply gives you information to adjust tomorrow.',
            'calories_imprecise' => 'Nutrition: calories are not precise enough to judge your full daily balance. Enter them for a reliable analysis.',
            'perte_high_low_move' => 'Nutrition & activity: for weight loss, today was very high in calories with little movement. Tomorrow, lighten very caloric portions and add a gentle session, e.g. 15–20 minutes of walking.',
            'perte_high_sport' => 'Nutrition & activity: calories are high for weight loss, but your workout helps balance the day. Stay active and try to lighten the richest meals a bit.',
            'perte_high_walk' => 'Nutrition & walking: calories are a bit high for weight loss, but your step count partly compensates. Keep walking regularly and try lighter meals tomorrow.',
            'perte_ok' => 'Nutrition & activity: your day fits a weight-loss goal fairly well. Calories are reasonable and physical activity supports balance.',
            'perte_low_cal' => 'Nutrition: calories look very low. Even for weight loss, avoid excessive restriction. Keep simple, complete meals: vegetables, protein, and moderate starch.',
            'perte_ok2' => 'Nutrition: your day is generally acceptable for weight loss. Keep regular portions and add movement when possible.',
            'prise_low_high_act' => 'Nutrition & effort: for weight/mass gain, intake seems too low for your activity. Add a nourishing snack: yogurt, dried fruit, eggs, cheese, balanced sandwich, or smoothie.',
            'prise_ok' => 'Nutrition & goal: intake matches weight/mass gain. Continue regular meals rich in protein, starch, and healthy fats.',
            'prise_low' => 'Nutrition: for weight/mass gain, intake seems a bit low. Gradually increase portions without skipping meals.',
            'prise_ok2' => 'Nutrition: the day supports your goal. Keep steady habits over several days.',
            'gen_high_low' => 'Nutrition & activity: calories are high and the day was inactive. Rebalance with lighter meals tomorrow and a walk or gentle session.',
            'gen_high_comp' => 'Nutrition & activity: calories are fairly high, but activity helps compensate. Keep moving and choose more balanced meals.',
            'gen_balanced' => 'Nutrition & activity: the day looks fairly balanced. Calories are reasonable and activity supports your health.',
            'gen_ok' => 'Nutrition: your day looks fine. Aim for varied plates with vegetables, protein, starch, and reasonable fat.',
            'hyd_unknown' => 'Hydration: the recorded value was not recognized. Check your water choice in the form.',
            'hyd_low_sport' => 'Hydration: you drank less than 1L despite physical activity. That is insufficient to recover well. Drink before, during if needed, then after effort.',
            'hyd_low' => 'Hydration: you drank less than 1L. Keep a bottle nearby tomorrow and sip through the day.',
            'hyd_mid_intense' => 'Hydration: 1L to 1.5L may be tight after an intense session. Drink a bit more on effort days.',
            'hyd_mid' => 'Hydration: your level is acceptable but can improve. One or two extra glasses can balance the day.',
            'hyd_good' => 'Hydration: very good balance. Your water intake fits a normal day.',
            'hyd_high' => 'Hydration: good hydration today. Especially positive if you walked or exercised.',
            'recover_bad' => 'Recovery: intense effort with little sleep is not ideal. Prefer a lighter session or better sleep before hard effort.',
            'recover_good' => 'Sport & recovery: your session is well supported by good sleep — a positive combination.',
            'sport_sleep_low' => 'Sport & sleep: activity is positive, but sleep is a bit low. Sleep earlier or reduce intensity on tired days.',
            'walk_good' => 'Activity: even without a workout, your walking is a plus. Many steps already support health and your goal.',
            'move_medium' => 'Activity: you moved a little; a short extra walk or gentle exercises can improve the day.',
            'move_low' => 'Activity: the day was inactive. A short walk after a meal can help digestion, energy, and balance.',
            'sport_good' => 'Sport: good session. Moderate, regular activity is often more sustainable than occasional intense effort.',
            'sport_short' => 'Sport: even a short session is positive. Gradually increase duration if you feel well.',
            'sleep_missing' => 'Sleep: enter your sleep hours to better understand recovery.',
            'sleep_low' => 'Sleep: sleep is low. This can increase hunger and fatigue. Try a steadier bedtime.',
            'sleep_good' => 'Sleep: great job — sleep supports recovery and appetite control.',
            'sleep_high' => 'Sleep: you slept a lot. If this often comes with fatigue, watch your overall rhythm.',
            'allergens' => 'Allergen caution: avoid foods listed on your profile: %s.',
            'deficiencies' => 'Deficiencies: as you noted %s, vary meals with nutrient-rich foods.',
            'diseases' => 'Health: as you noted %s, avoid extreme diet changes and progress carefully.',
        ],
    ];

    return $messages;
}

function sante_conseil_t(string $key, ?string $lang = null): string
{
    $lang = $lang ?? (function_exists('fo_lang') ? fo_lang() : 'fr');
    if ($lang !== 'en') {
        $lang = 'fr';
    }
    $messages = sante_conseil_messages();

    return $messages[$lang][$key] ?? $messages['fr'][$key] ?? $key;
}
