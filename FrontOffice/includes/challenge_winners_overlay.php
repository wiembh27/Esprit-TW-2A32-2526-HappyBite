<?php

declare(strict_types=1);

/**
 * Winners leaderboard overlay (after ended challenge, before gumball draw).
 */
?>
<link rel="stylesheet" href="css/challenge-winners-overlay.css">
<div id="hbChallengeWinnersFall" class="hb-challenge-winners-overlay__fall is-hidden" aria-hidden="true"></div>
<div id="hbChallengeWinnersOverlay" class="hb-challenge-winners-overlay is-hidden" role="dialog" aria-modal="true" aria-labelledby="hbChallengeWinnersTitle">
    <div class="hb-challenge-winners-overlay__panel">
        <h2 id="hbChallengeWinnersTitle" class="hb-challenge-winners-overlay__title"><?php echo fo_e('challenge.winners_title'); ?></h2>
        <table class="hb-challenge-winners-table">
            <thead>
                <tr>
                    <th><?php echo fo_e('challenge.winners_rank'); ?></th>
                    <th><?php echo fo_e('challenge.winners_player'); ?></th>
                </tr>
            </thead>
            <tbody id="hbChallengeWinnersBody"></tbody>
        </table>
        <button type="button" class="hb-challenge-winners-tap" id="hbChallengeWinnersTap">
            <?php echo fo_e('challenge.winners_tap_continue'); ?>
        </button>
    </div>
</div>
<script src="js/challenge-winners-overlay.js"></script>
