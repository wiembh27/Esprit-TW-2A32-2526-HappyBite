<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/fo_i18n.php';
fo_init_i18n_for_request();

if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    return;
}

?>
<style>
.hb-guest-gate--hidden {
    display: none !important;
}
.hb-guest-gate {
    position: fixed;
    inset: 0;
    z-index: 99990;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    background: rgba(15, 42, 28, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    box-sizing: border-box;
}
.hb-guest-gate__panel {
    max-width: 420px;
    width: 100%;
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem 1.35rem 1.35rem;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.22);
    border: 1px solid #d5ebe0;
    text-align: center;
    font-family: "Poppins", system-ui, sans-serif;
}
.hb-guest-gate__title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #2C7E34;
    margin: 0 0 0.65rem;
    line-height: 1.35;
}
.hb-guest-gate__text {
    font-size: 0.92rem;
    font-weight: 400;
    color: #3d5248;
    line-height: 1.55;
    margin: 0 0 1.15rem;
}
.hb-guest-gate__actions {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
}
@media (min-width: 480px) {
    .hb-guest-gate__actions {
        flex-direction: row;
        justify-content: center;
    }
}
.hb-guest-gate__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.65rem 1.1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    border: 2px solid transparent;
    flex: 1;
    min-width: 0;
}
.hb-guest-gate__btn--signup {
    background: #2C7E34;
    color: #fff;
    border-color: #256b2d;
}
.hb-guest-gate__btn--signup:hover {
    filter: brightness(1.06);
}
.hb-guest-gate__btn--login {
    background: #fff;
    color: #2C7E34;
    border-color: #2C7E34;
}
.hb-guest-gate__btn--login:hover {
    background: #f0faf4;
}
</style>
<?php
$foGuestGateHidden = !empty($foGuestGateHidden);
?>
<div class="hb-guest-gate<?php echo $foGuestGateHidden ? ' hb-guest-gate--hidden' : ''; ?>" role="dialog" aria-modal="true" aria-labelledby="hb-guest-gate-title">
    <div class="hb-guest-gate__panel">
        <h2 id="hb-guest-gate-title" class="hb-guest-gate__title"><?php echo fo_e('guest.title'); ?></h2>
        <p class="hb-guest-gate__text">
            <?php echo fo_e('guest.text'); ?>
        </p>
        <div class="hb-guest-gate__actions">
            <a class="hb-guest-gate__btn hb-guest-gate__btn--login" href="auth/login.php"><?php echo fo_e('nav.login'); ?></a>
            <a class="hb-guest-gate__btn hb-guest-gate__btn--signup" href="auth/register.php"><?php echo fo_e('nav.signup'); ?></a>
        </div>
    </div>
</div>
