<?php
/** @var int $ROUE_COST */
?>
<div class="wheel-modal" id="roueModal" aria-hidden="true">
    <div class="wheel-overlay-panel" role="dialog" aria-modal="true" aria-labelledby="fortuneWheelTitle">
        <h2 id="fortuneWheelTitle"><?php echo fo_e('cart.fortune_title'); ?></h2>
        <p class="wheel-overlay-subtitle">
            <?php echo htmlspecialchars(sprintf(fo_t('cart.fortune_btn_cost'), (string) (int) $ROUE_COST), ENT_QUOTES, 'UTF-8'); ?>
        </p>

        <div class="fortune-wheel-machine" id="fortuneWheelMachine">
            <div class="fortune-wheel-stage">
                <div class="fortune-wheel-pointer" aria-hidden="true"></div>
                <div class="fortune-wheel-frame">
                    <svg class="fortune-wheel-svg" id="fortuneWheelSvg" viewBox="0 0 200 200" preserveAspectRatio="xMidYMid meet" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Roue de la fortune"></svg>
                </div>
                <div class="fortune-wheel-hub" aria-hidden="true"></div>
            </div>
            <div class="fortune-wheel-stand" aria-hidden="true"></div>
            <div class="fortune-wheel-platform" aria-hidden="true"></div>
        </div>

        <div class="wheel-result" id="wheelResult"></div>
        <button type="button" class="btn-draw" id="btnDrawWheel">DRAW</button>
    </div>
</div>
