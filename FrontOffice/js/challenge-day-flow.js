(function () {
    'use strict';

    window.hbInitChallengeDayFlow = function (config) {
        if (!config || !config.draw || !config.draw.enabled) {
            return;
        }

        function hideDrawOverlay() {
            var overlay = document.getElementById('hbChallengeDrawOverlay');
            if (overlay) {
                overlay.classList.add('is-hidden');
            }
        }

        function startDraw(force) {
            var drawCfg = Object.assign({}, config.draw, { forceShow: !!force });
            if (typeof window.hbInitChallengeDrawOverlay === 'function') {
                window.hbInitChallengeDrawOverlay(drawCfg);
            }
        }

        function periodEnded() {
            if (countdownStillRunning()) {
                return false;
            }
            if (config.challengePeriodEnded === true) {
                return true;
            }
            if (!config.countdownEndsAt) {
                return false;
            }
            var endMs = new Date(config.countdownEndsAt).getTime();
            return !Number.isNaN(endMs) && Date.now() >= endMs;
        }

        function countdownStillRunning() {
            var root = document.getElementById('challengeDayCountdown');
            if (!root) {
                return false;
            }
            if (root.classList.contains('is-ended')) {
                return false;
            }
            var endsAt = root.getAttribute('data-ends-at');
            if (!endsAt) {
                return false;
            }
            var endMs = new Date(endsAt).getTime();
            return !Number.isNaN(endMs) && Date.now() < endMs;
        }

        function pickWinnersPayload(preferCountdown) {
            if (preferCountdown && config.countdownWinners && config.countdownWinners.challengeId) {
                return config.countdownWinners;
            }
            if (config.winners && config.winners.challengeId) {
                return config.winners;
            }
            return null;
        }

        function runWinnersThenDraw(forceWinners, preferCountdownWinners) {
            var winners = pickWinnersPayload(!!preferCountdownWinners);
            var hasWinners = winners && winners.challengeId;

            if (hasWinners && typeof window.hbInitChallengeWinnersOverlay === 'function') {
                var shown = window.hbInitChallengeWinnersOverlay(winners, function () {
                    var fallLayer = document.getElementById('hbChallengeWinnersFall');
                    if (fallLayer) {
                        fallLayer.classList.add('is-hidden');
                    }
                    startDraw(true);
                }, !!forceWinners);

                if (shown) {
                    return true;
                }
            }

            if (periodEnded()) {
                startDraw(!!config.draw.forceDraw);
            } else {
                hideDrawOverlay();
            }
            return false;
        }

        window.hbChallengeOnCountdownEnded = function () {
            if (!periodEnded()) {
                return;
            }
            hideDrawOverlay();
            runWinnersThenDraw(true, true);
        };

        var winners = config.winners;
        var showCeremonyOnLoad = winners && winners.challengeId && winners.showOnLoad === true;

        if (showCeremonyOnLoad && periodEnded()) {
            runWinnersThenDraw(false, false);
            return;
        }

        hideDrawOverlay();
    };
})();
