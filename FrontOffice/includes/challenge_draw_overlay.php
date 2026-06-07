<?php

declare(strict_types=1);

/**
 * In-page challenge draw overlay (gumball machine + reveal card).
 * Expects fo_i18n loaded; shown only for clients via challenge_du_jour.php.
 */
?>
<link rel="stylesheet" href="css/challenge-draw-overlay.css">
<div id="hbChallengeDrawOverlay" class="hb-challenge-draw-overlay is-hidden" role="dialog" aria-modal="true" aria-labelledby="hbChallengeDrawTitle">
    <div class="hb-challenge-draw-overlay__panel">
        <div class="hb-challenge-draw-machine" id="hbChallengeMachine">
            <div class="globe-stack">
                <div class="globe-cap" aria-hidden="true"><span class="cap-knob"></span></div>
                <div class="globe">
                    <div class="balls-area" id="hbChallengeBallsArea"></div>
                </div>
                <div class="globe-neck" aria-hidden="true"></div>
            </div>
            <div class="base">
                <div class="base-body">
                    <div class="base-plate" aria-hidden="true">
                        <div class="knob" id="hbChallengeKnob" aria-hidden="true"></div>
                    </div>
                </div>
                <div class="dispenser" aria-label="<?php echo fo_e('challenge.draw_dispenser'); ?>">
                    <div class="winner-slot" id="hbChallengeWinnerSlot" aria-hidden="true"></div>
                </div>
            </div>
        </div>
        <button type="button" class="btn-green hb-challenge-draw-btn" id="hbChallengeDrawBtn">
            <?php echo fo_e('challenge.draw_btn'); ?>
        </button>
        <div class="hb-challenge-draw-reveal" id="hbChallengeReveal" aria-live="polite">
            <p class="hb-challenge-draw-reveal__kicker" id="hbChallengeDrawTitle"><?php echo fo_e('challenge.reveal_kicker'); ?></p>
            <p class="hb-challenge-draw-reveal__title" id="hbChallengeRevealTitle"></p>
            <p class="hb-challenge-draw-reveal__desc" id="hbChallengeRevealDesc"></p>
        </div>
    </div>
</div>
<script src="js/challenge-draw-overlay.js"></script>
