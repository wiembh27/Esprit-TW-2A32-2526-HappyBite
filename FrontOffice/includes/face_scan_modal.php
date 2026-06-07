<?php
/** Modal caméra + scan visage (connexion / inscription Face ID). */
?>
<div id="auth-face-modal" class="auth-face-modal" hidden aria-hidden="true">
    <div class="auth-face-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="auth-face-modal-title">
        <h3 id="auth-face-modal-title" class="auth-face-modal__title">Face ID</h3>
        <p id="auth-face-msg" class="auth-face-modal__msg" hidden></p>
        <div id="auth-face-scan" class="auth-face-scan" hidden aria-hidden="true">
            <div class="auth-face-scan__frame">
                <video id="auth-face-video" class="auth-face-scan__video" autoplay playsinline muted></video>
                <div id="auth-face-guide" class="auth-face-scan__guide" aria-hidden="true"></div>
                <div id="auth-face-box" class="auth-face-scan__box" hidden aria-hidden="true"></div>
                <span class="auth-face-scan__line" aria-hidden="true"></span>
            </div>
            <p class="auth-face-scan__text">Placez votre visage dans le cadre, puis restez immobile.</p>
        </div>
        <button type="button" class="auth-face-modal__close" id="auth-face-close">Fermer</button>
    </div>
</div>
