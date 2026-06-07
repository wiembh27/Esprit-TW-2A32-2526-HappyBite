<style>
    .fo-ai-widget {
        position: fixed;
        right: 22px;
        bottom: 22px;
        z-index: 1200;
        width: 190px;
        transition: width 0.2s ease, height 0.2s ease;
    }
    .fo-ai-widget__trigger {
        width: 100%;
        min-height: 52px;
        border: 2px solid #43a047;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 12px 34px rgba(19, 30, 23, 0.25);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 12px;
        cursor: pointer;
        font-weight: 700;
        font-size: 0.95rem;
        font-family: inherit;
    }
    .fo-ai-widget__label {
        background: linear-gradient(90deg, #e53935 0%, #fb8c00 52%, #43a047 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    .fo-ai-widget__icon {
        width: 20px;
        height: 20px;
        object-fit: contain;
        display: block;
    }
    .fo-ai-widget__panel {
        display: none;
        width: 100%;
        height: 100%;
        background: #f8fffb;
        border: 2px solid #43a047;
        border-radius: 18px;
        padding: 14px;
        box-sizing: border-box;
        position: relative;
        box-shadow: 0 12px 34px rgba(19, 30, 23, 0.25);
    }
    .fo-ai-widget.is-open {
        width: min(515px, calc(100vw - 24px));
        height: min(752px, calc(100vh - 24px));
        max-width: 515px;
        max-height: 752px;
    }
    .fo-ai-widget.is-open .fo-ai-widget__trigger {
        display: none;
    }
    .fo-ai-widget.is-open .fo-ai-widget__panel {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .fo-ai-widget__title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 4px;
    }
    .fo-ai-widget__title-text {
        font-size: 1rem;
        font-weight: 700;
        background: linear-gradient(90deg, #e53935 0%, #fb8c00 52%, #43a047 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    .fo-ai-widget__close-wrap {
        display: flex;
        justify-content: flex-end;
    }
    .fo-ai-widget__close {
        border: 1px solid #9ccc9f;
        background: #fff;
        color: #1d4a2f;
        border-radius: 8px;
        padding: 5px 9px;
        font-weight: 600;
        cursor: pointer;
    }
    .fo-ai-widget__welcome-row {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .fo-ai-widget__messages {
        display: flex;
        flex-direction: column;
        gap: 10px;
        flex: 1 1 auto;
        overflow-y: auto;
        min-height: 0;
        padding-right: 2px;
    }
    .fo-ai-widget__welcome-icon {
        width: 24px;
        height: 24px;
        object-fit: contain;
        flex: 0 0 auto;
        margin-top: 4px;
    }
    .fo-ai-widget__bubble {
        flex: 1 1 auto;
        border: 2px solid #43a047;
        border-radius: 14px;
        background: #fff;
        color: #173023;
        padding: 12px 14px;
        font-weight: 500;
        line-height: 1.4;
    }
    .fo-ai-widget__spacer {
        flex: 1 1 auto;
    }
    .fo-ai-widget__askbar {
        border: 2px solid #43a047;
        border-radius: 12px;
        background: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
    }
    .fo-ai-widget__voice-lang {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
    .fo-ai-widget__voice-lang-label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #1d4a2f;
        line-height: 1.2;
    }
    .fo-ai-widget__voice-lang-hint {
        font-size: 0.68rem;
        font-weight: 500;
        color: #4a6b55;
        line-height: 1.25;
        max-width: 100%;
    }
    .fo-ai-widget__voice-lang-btns {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    .fo-ai-widget__voice-lang-btn {
        border: 1px solid #9ccc9f;
        background: #fff;
        color: #1d4a2f;
        border-radius: 999px;
        padding: 3px 8px;
        font-size: 0.68rem;
        font-weight: 700;
        cursor: pointer;
        font-family: inherit;
        line-height: 1.2;
    }
    .fo-ai-widget__voice-lang-btn.is-active {
        background: #2e7d32;
        border-color: #2e7d32;
        color: #fff;
    }
    .fo-ai-widget__suggestions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .fo-ai-widget__suggestion {
        border: 1px solid #9ccc9f;
        background: #fff;
        color: #1d4a2f;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 0.8rem;
        line-height: 1.2;
        cursor: pointer;
    }
    .fo-ai-widget__suggestion:hover {
        background: #e8f5e9;
    }
    .fo-ai-widget__send,
    .fo-ai-widget__mic-icon {
        width: 18px;
        height: 18px;
        object-fit: contain;
        flex: 0 0 auto;
        display: block;
    }
    .fo-ai-widget__mic-icon {
        color: #2e7d32;
    }
    .fo-ai-widget__micbtn {
        user-select: none;
        -webkit-user-select: none;
        touch-action: none;
    }
    .fo-ai-widget__micbtn.is-listening {
        background: rgba(229, 57, 53, 0.12);
        border-radius: 50%;
        animation: fo-ai-mic-pulse 1.1s ease-in-out infinite;
    }
    @keyframes fo-ai-mic-pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.08); }
    }
    .fo-ai-widget__micbtn.is-listening .fo-ai-widget__mic-icon {
        color: #c62828;
    }
    .fo-ai-widget__input {
        width: 100%;
        border: 0;
        outline: 0;
        background: transparent;
        color: #173023;
        font-family: inherit;
        font-size: 0.95rem;
    }
    .fo-ai-widget__askbtn {
        border: 0;
        background: transparent;
        padding: 0;
        margin: 0;
        line-height: 0;
        cursor: pointer;
    }
    .fo-ai-widget__msg-user {
        align-self: flex-end;
        max-width: 86%;
        border-radius: 14px;
        background: #2e7d32;
        color: #fff;
        padding: 10px 12px;
        line-height: 1.35;
        font-weight: 500;
    }
    .fo-ai-widget__msg-ai {
        align-self: flex-start;
        max-width: 100%;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }
    .fo-ai-widget__msg-ai-icon {
        width: 22px;
        height: 22px;
        object-fit: contain;
        flex: 0 0 auto;
        margin-top: 4px;
    }
    .fo-ai-widget__msg-ai-bubble {
        max-width: 86%;
        border: 2px solid #43a047;
        border-radius: 14px;
        background: #fff;
        color: #173023;
        padding: 10px 12px;
        line-height: 1.35;
        font-weight: 500;
    }
    .fo-ai-widget__track-link {
        display: inline-block;
        margin-top: 8px;
        padding: 8px 14px;
        border: none;
        border-radius: 10px;
        background: #1a73e8;
        color: #fff;
        font-weight: 700;
        font-size: 0.88rem;
        font-family: inherit;
        cursor: pointer;
    }
    .fo-ai-widget__track-link:hover {
        filter: brightness(1.06);
    }
</style>

<?php $aiCurrentPage = strtolower(basename((string) ($_SERVER['PHP_SELF'] ?? ''))); ?>
<div id="fo-ai-widget" class="fo-ai-widget" aria-expanded="false" data-page="<?php echo htmlspecialchars($aiCurrentPage, ENT_QUOTES, 'UTF-8'); ?>">
    <button type="button" class="fo-ai-widget__trigger" aria-label="Ouvrir l'assistant">
        <img src="images/ai-technology.png" alt="" class="fo-ai-widget__icon">
        <span class="fo-ai-widget__label"><?php echo fo_e('ai.ask_me'); ?></span>
    </button>
    <div class="fo-ai-widget__panel">
        <div class="fo-ai-widget__title">
            <span class="fo-ai-widget__title-text">Assistant Ai</span>
            <button type="button" class="fo-ai-widget__close" aria-label="Fermer l'assistant">Fermer</button>
        </div>
        <div class="fo-ai-widget__messages" id="fo-ai-messages">
            <div class="fo-ai-widget__welcome-row">
                <img src="images/ai-technology.png" alt="" class="fo-ai-widget__welcome-icon">
                <div class="fo-ai-widget__bubble" id="fo-ai-welcome-bubble">Bonjour ! Comment puis-je vous aider aujourd'hui ?</div>
            </div>
        </div>
        <div class="fo-ai-widget__suggestions" id="fo-ai-suggestions"></div>
        <div class="fo-ai-widget__voice-lang" id="fo-ai-voice-lang" aria-label="Langue de l'assistant">
            <span class="fo-ai-widget__voice-lang-label">Langue de l'assistant</span>
            <span class="fo-ai-widget__voice-lang-hint" id="fo-ai-voice-lang-hint">Langue du micro et des réponses vocales. Si vous parlez une autre langue, l'assistant répond quand même dans votre langue.</span>
            <div class="fo-ai-widget__voice-lang-btns" id="fo-ai-voice-lang-btns"></div>
        </div>
        <div class="fo-ai-widget__askbar">
            <input type="text" class="fo-ai-widget__input" placeholder="Posez votre question..." autocomplete="off">
            <button type="button" class="fo-ai-widget__askbtn fo-ai-widget__micbtn" id="fo-ai-mic-btn" aria-label="Maintenez pour parler" title="Maintenez pour parler">
                <svg class="fo-ai-widget__mic-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 2a3 3 0 0 1 3 3v6a3 3 0 0 1-6 0V5a3 3 0 0 1 3-3z"/>
                    <path d="M19 10v1a7 7 0 0 1-14 0v-1"/>
                    <line x1="12" y1="18" x2="12" y2="22"/>
                    <line x1="8" y1="22" x2="16" y2="22"/>
                </svg>
            </button>
            <button type="button" class="fo-ai-widget__askbtn" id="fo-ai-send-btn" aria-label="Envoyer">
                <img src="images/sent.png" alt="" class="fo-ai-widget__send">
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        var widget = document.getElementById('fo-ai-widget');
        if (!widget) return;
        var trigger = widget.querySelector('.fo-ai-widget__trigger');
        var close = widget.querySelector('.fo-ai-widget__close');
        var messages = widget.querySelector('#fo-ai-messages');
        var input = widget.querySelector('.fo-ai-widget__input');
        var sendBtn = widget.querySelector('#fo-ai-send-btn');
        var micBtn = widget.querySelector('#fo-ai-mic-btn');
        var suggestionsWrap = widget.querySelector('#fo-ai-suggestions');
        var currentPage = (widget.getAttribute('data-page') || '').toLowerCase();
        var aiChatUrl = (function () {
            var path = window.location.pathname || '';
            var base = path.substring(0, path.lastIndexOf('/') + 1);
            return base + 'ai_chat.php';
        })();
        var speechVoices = [];
        var speechRecognition = null;
        var isListening = false;
        var voiceSendOnStop = false;
        var voiceFinalParts = [];
        var voiceLastInterim = '';
        var voiceSendTimer = null;
        var lastQuestionLang = 'fr';
        var assistantLang = sessionStorage.getItem('hb_ai_assistant_lang') || sessionStorage.getItem('hb_ai_voice_lang') || 'fr';
        var voiceLangWrap = widget.querySelector('#fo-ai-voice-lang');
        var voiceLangBtns = widget.querySelector('#fo-ai-voice-lang-btns');

        var SpeechRecognitionCtor = window.SpeechRecognition || window.webkitSpeechRecognition;

        function langToBcp47(lang) {
            if (lang === 'en') return 'en-US';
            return 'fr-FR';
        }

        function langToRecognitionLocale(lang) {
            if (lang === 'en') return 'en-US';
            return 'fr-FR';
        }

        function langToSpeechLocale(lang) {
            if (lang === 'en') return ['en-US', 'en-GB', 'en'];
            return ['fr-FR', 'fr-CA', 'fr'];
        }

        function loadSpeechVoices() {
            if (!window.speechSynthesis) return;
            speechVoices = window.speechSynthesis.getVoices() || [];
        }

        if (window.speechSynthesis) {
            loadSpeechVoices();
            window.speechSynthesis.onvoiceschanged = loadSpeechVoices;
        }

        function scoreVoiceForLang(voice, lang) {
            var name = (voice.name || '').toLowerCase();
            var voiceLang = (voice.lang || '').toLowerCase();
            var locales = langToSpeechLocale(lang);
            var localeMatch = false;
            var li;
            var primaryLocale = locales[0].toLowerCase();
            for (li = 0; li < locales.length; li++) {
                if (voiceLang.indexOf(locales[li].toLowerCase()) === 0) {
                    localeMatch = true;
                    break;
                }
            }
            if (!localeMatch) {
                return -1000;
            }
            var score = 0;
            if (voiceLang === primaryLocale) {
                score += 40;
            }
            if (/en-in|en-pk|en-bd|hi-in|ta-in|te-in|mr-in|bn-in|gu-in|kn-in|ml-in|india|hindi|bengali|tamil|telugu|marathi|gujarati|kannada|malayalam|punjabi/i.test(voiceLang + ' ' + name)) {
                return -2000;
            }
            var preferredByLang = {
                fr: [/microsoft (henri|paul)\b/i, /google.*fran[cç]ais/i, /hortense|denise|julie/i],
                en: [/microsoft (david|mark)\b/i, /google.*english.*(united states|us)/i, /google us english/i]
            };
            var pref = preferredByLang[lang] || [];
            for (li = 0; li < pref.length; li++) {
                if (pref[li].test(name)) {
                    score += 80;
                }
            }
            var femaleRe = /female|femme|zira|samantha|hortense|julie|anna|marie|denise|caroline|virginie|hedda|marlene/i;
            var maleByLang = {
                fr: /male|homme|thomas|paul|nicolas|daniel|henri|guillaume/i,
                en: /male|david|mark|james|guy/i
            };
            if (femaleRe.test(name)) {
                score -= 50;
            }
            if ((maleByLang[lang] || maleByLang.fr).test(name)) {
                score += 35;
            }
            if (/male/i.test(name)) {
                score += 15;
            }
            if (lang === 'fr' && /henri|paul/.test(name)) {
                score += 25;
            }
            if (lang === 'en' && /david|mark/.test(name)) {
                score += 25;
            }
            return score;
        }

        function pickAnyVoiceForLang(lang) {
            var locales = langToSpeechLocale(lang);
            var li;
            var i;
            for (li = 0; li < locales.length; li++) {
                var loc = locales[li].toLowerCase();
                for (i = 0; i < speechVoices.length; i++) {
                    var vLang = (speechVoices[i].lang || '').toLowerCase();
                    if (vLang.indexOf(loc) === 0) {
                        return speechVoices[i];
                    }
                }
            }
            for (i = 0; i < speechVoices.length; i++) {
                var vLang2 = (speechVoices[i].lang || '').toLowerCase();
                if (vLang2.indexOf(lang) === 0) {
                    return speechVoices[i];
                }
            }
            return null;
        }

        function pickVoiceForLang(lang) {
            loadSpeechVoices();
            if (!speechVoices.length) {
                loadSpeechVoices();
            }
            var bestVoice = null;
            var bestScore = -10000;
            var i;
            for (i = 0; i < speechVoices.length; i++) {
                var s = scoreVoiceForLang(speechVoices[i], lang);
                if (s > bestScore) {
                    bestScore = s;
                    bestVoice = speechVoices[i];
                }
            }
            var noMaleVoice = bestScore < 25;
            if (!bestVoice || bestScore < 0) {
                bestVoice = pickAnyVoiceForLang(lang);
                noMaleVoice = true;
            }
            return {
                voice: bestVoice,
                lowPitch: noMaleVoice,
                noVoice: !bestVoice
            };
        }

        function normalizeVoiceTranscript(text, lang) {
            var t = String(text || '').replace(/\s+/g, ' ').trim();
            if (!t) {
                return '';
            }
            if (lang === 'fr') {
                t = t.replace(/\bpay[\s-]?pal\b/gi, 'PayPal');
                t = t.replace(/\bou est\b/gi, 'où est');
                t = t.replace(/\bou en est\b/gi, 'où en est');
                t = t.replace(/\bsecurise\b/gi, 'sécurisé');
                t = t.replace(/\bsecuriser\b/gi, 'sécurisé');
                t = t.replace(/\bannuler ma commende\b/gi, 'annuler ma commande');
                t = t.replace(/\bcommende\b/gi, 'commande');
                t = t.replace(/\blivraison\b/gi, 'livraison');
            }
            if (lang === 'en') {
                t = t.replace(/\bpay[\s-]?pal\b/gi, 'PayPal');
            }
            return t;
        }

        function pickBestRecognitionAlternative(result) {
            if (!result || !result.length) {
                return '';
            }
            var best = result[0].transcript || '';
            var bestConf = result[0].confidence != null ? result[0].confidence : 0.5;
            var j;
            for (j = 1; j < result.length; j++) {
                var conf = result[j].confidence != null ? result[j].confidence : 0;
                if (conf > bestConf) {
                    bestConf = conf;
                    best = result[j].transcript || '';
                }
            }
            return best;
        }

        function stripForSpeech(text) {
            var parts = String(text || '').split(/\n\n+/);
            var kept = [];
            var i;
            for (i = 0; i < parts.length; i++) {
                var p = parts[i].trim();
                if (!p) continue;
                if (/sélectionnez (FR|EN|DE|AR)|select (FR|EN|DE|AR)|wählen Sie.*(FR|EN|DE|AR)|اختر (FR|EN|DE|AR)/i.test(p)) {
                    continue;
                }
                if (/semble être en|looks like|scheint auf|يبدو أن سؤالك/i.test(p)) {
                    continue;
                }
                kept.push(p);
            }
            return kept.join(' ').replace(/\s+/g, ' ').trim();
        }

        function stopSpeaking() {
            if (window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
        }

        function spokenLanguage(question) {
            return detectLanguage(question || '');
        }

        function langMismatchHint(detectedLang, selectedLang, replyLang) {
            if (detectedLang === selectedLang) {
                return '';
            }
            replyLang = replyLang || selectedLang;
            var chip = detectedLang.toUpperCase();
            var names = {
                fr: { fr: 'français', en: 'anglais' },
                en: { fr: 'French', en: 'English' }
            };
            var detectedName = (names[replyLang] && names[replyLang][detectedLang]) || chip;
            var templates = {
                fr: 'Votre question semble être en ' + detectedName + '. Sélectionnez ' + chip + ' ci-dessus pour des réponses dans cette langue.',
                en: 'Your question looks like ' + detectedName + '. Select ' + chip + ' above for replies in that language.'
            };
            return templates[replyLang] || templates.fr;
        }

        function isTrackingQuestion(question) {
            return (
                /(ou est ma commande|est ma commande|ou en est ma commande|suivre ma commande|where is my order|where s my order|track my order|wo ist meine bestellung|bestellung verfolgen)/.test(question) ||
                (/\b(commande|order|bestellung|colis|livraison|delivery)\b/.test(question) && /\b(ou|where|wo|suivre|track|status|arrive|arrivera|en est)\b/.test(question))
            );
        }

        var offTopicTerms = [
            'voiture', 'voitures', 'car', 'cars', 'automobile', 'auto',
            'chambre', 'chambres', 'room', 'rooms', 'bedroom',
            'maison', 'maisons', 'house', 'houses', 'home', 'apartment', 'appartement',
            'telephone', 'phone', 'smartphone', 'mobile',
            'velo', 'bike', 'bicycle',
            'chien', 'dog', 'chat', 'cat',
            'cle', 'cles', 'key', 'keys',
            'sac', 'bag', 'wallet', 'portefeuille',
            'ami', 'friend', 'friends',
            'avion', 'plane', 'flight', 'vol',
            'hotel', 'vacances', 'vacation', 'holiday',
            'film', 'movie', 'football', 'basketball', 'politique', 'politics'
        ];

        function isOutOfScopeQuestion(question, raw) {
            if (isTrackingQuestion(question)) return false;
            var combined = question + ' ' + (raw || '');
            var term;
            for (var i = 0; i < offTopicTerms.length; i++) {
                term = offTopicTerms[i];
                if (new RegExp('\\b' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\b', 'i').test(combined)) {
                    return true;
                }
            }
            if (/\bwhere\s+(is|are|'s|s)\s+(my|the)\s+\w+/i.test(raw || question) && !/\b(order|commande|delivery|colis|package|track|livraison)\b/i.test(combined)) {
                return true;
            }
            if (/\b(ou est|où est)\s+(ma|mon|mes|la|le)\s+(?!commande\b)/i.test(raw || question)) {
                return true;
            }
            return false;
        }

        function getTrackingFallback(lang) {
            var labels = {
                fr: { answer: 'Voici le lien de suivi de votre commande :', btn: 'Suivre ma commande' },
                en: { answer: 'Here is your order tracking link:', btn: 'Track my order' }
            };
            var row = labels[lang] || labels.fr;
            return {
                answer: row.answer,
                action: 'open_track_map',
                link: { label: row.btn, action: 'open_track_map' }
            };
        }

        function speakAiAnswer(text, lang) {
            if (!window.speechSynthesis) return;
            var clean = stripForSpeech(text);
            if (!clean) return;
            var speakLang = lang || lastQuestionLang;
            stopSpeaking();
            loadSpeechVoices();
            var picked = pickVoiceForLang(speakLang);
            var utter = new SpeechSynthesisUtterance(clean);
            utter.lang = langToBcp47(speakLang);
            if (picked.voice) {
                utter.voice = picked.voice;
                utter.lang = picked.voice.lang || utter.lang;
            }
            utter.pitch = picked.lowPitch ? 0.72 : 0.88;
            utter.rate = 0.93;
            utter.volume = 1;
            window.speechSynthesis.speak(utter);
        }

        function setMicListening(active) {
            isListening = !!active;
            if (micBtn) {
                micBtn.classList.toggle('is-listening', isListening);
                micBtn.setAttribute('aria-label', isListening ? 'Relâchez pour envoyer' : 'Maintenez pour parler');
            }
        }

        function stopListening(sendMessage) {
            if (!speechRecognition) {
                setMicListening(false);
                return;
            }
            if (sendMessage) {
                voiceSendOnStop = true;
            } else {
                voiceSendOnStop = false;
            }
            if (isListening) {
                try {
                    speechRecognition.stop();
                } catch (e) {
                    setMicListening(false);
                }
            } else if (sendMessage && input && input.value.trim() !== '') {
                sendCurrentMessage(input.value.trim());
            }
        }

        function flushVoiceSend() {
            var text = voiceFinalParts.join(' ').trim();
            if (!text && voiceLastInterim) {
                text = voiceLastInterim;
            }
            text = normalizeVoiceTranscript(text, assistantLang);
            voiceFinalParts = [];
            voiceLastInterim = '';
            if (text !== '') {
                sendCurrentMessage(text);
            }
        }

        function initSpeechRecognition() {
            if (!SpeechRecognitionCtor) return null;
            var rec = new SpeechRecognitionCtor();
            rec.continuous = true;
            rec.interimResults = true;
            rec.maxAlternatives = 3;
            rec.onstart = function () {
                setMicListening(true);
            };
            rec.onend = function () {
                setMicListening(false);
                if (voiceSendOnStop) {
                    voiceSendOnStop = false;
                    if (voiceSendTimer) {
                        clearTimeout(voiceSendTimer);
                    }
                    voiceSendTimer = setTimeout(flushVoiceSend, 450);
                } else {
                    voiceFinalParts = [];
                    voiceLastInterim = '';
                }
            };
            rec.onerror = function (ev) {
                voiceSendOnStop = false;
                if (voiceSendTimer) {
                    clearTimeout(voiceSendTimer);
                    voiceSendTimer = null;
                }
                setMicListening(false);
                if (ev && ev.error === 'not-allowed') {
                    appendAiMessage({
                        answer: 'Autorisez l\'accès au micro dans votre navigateur pour poser une question à la voix.'
                    });
                }
            };
            rec.onresult = function (ev) {
                var finals = [];
                var interim = '';
                var i;
                for (i = 0; i < ev.results.length; i++) {
                    var piece = pickBestRecognitionAlternative(ev.results[i]);
                    if (ev.results[i].isFinal) {
                        if (piece.trim() !== '') {
                            finals.push(piece.trim());
                        }
                    } else {
                        interim += piece;
                    }
                }
                voiceFinalParts = finals;
                voiceLastInterim = interim.replace(/\s+/g, ' ').trim();
                var combined = (finals.join(' ') + (voiceLastInterim ? ' ' + voiceLastInterim : '')).replace(/\s+/g, ' ').trim();
                if (input) {
                    input.value = combined;
                }
            };
            return rec;
        }

        speechRecognition = initSpeechRecognition();

        function beginVoiceHold(ev) {
            if (ev && ev.preventDefault) {
                ev.preventDefault();
            }
            if (!SpeechRecognitionCtor) {
                appendAiMessage({
                    answer: 'La reconnaissance vocale n\'est pas disponible sur ce navigateur. Utilisez Chrome ou Edge, ou saisissez votre question.'
                });
                return;
            }
            if (sendBtn && sendBtn.disabled) {
                return;
            }
            if (!speechRecognition) {
                speechRecognition = initSpeechRecognition();
            }
            if (!speechRecognition || isListening) {
                return;
            }
            stopSpeaking();
            voiceSendOnStop = false;
            voiceFinalParts = [];
            if (input) {
                input.value = '';
            }
            speechRecognition.lang = langToRecognitionLocale(assistantLang);
            try {
                speechRecognition.start();
            } catch (e) {
                setMicListening(false);
            }
        }

        var assistantLangUi = {
            fr: {
                short: 'FR',
                label: 'Français',
                placeholder: 'Posez votre question en français...',
                hint: 'Micro + voix FR. Réponses en français. Voix Windows : Paramètres → Heure et langue → Voix → ajouter Français (France) — Paul ou Henri.',
                welcome: 'Bonjour ! Comment puis-je vous aider aujourd\'hui ?',
                suggestions: {
                    paypal: 'PayPal est-il sécurisé ?',
                    cancel: 'Est-il possible d\'annuler ma commande ?',
                    payment: 'Quel mode de paiement dois-je choisir ?',
                    track: 'Où est ma commande ?',
                    delivery: 'Quand ma commande arrivera-t-elle ?'
                }
            },
            en: {
                short: 'EN',
                label: 'English',
                placeholder: 'Ask your question in English...',
                hint: 'Mic + voice EN. Replies in English. Windows: Settings → Time & language → Speech → add English (United States) — David or Mark (not India).',
                welcome: 'Hello! How can I help you today?',
                suggestions: {
                    paypal: 'Is PayPal secure?',
                    cancel: 'Can I cancel my order?',
                    payment: 'Which payment method should I choose?',
                    track: 'Where is my order?',
                    delivery: 'When will my order arrive?'
                }
            }
        };

        function setAssistantLang(code) {
            if (code !== 'fr' && code !== 'en') return;
            assistantLang = code;
            sessionStorage.setItem('hb_ai_assistant_lang', code);
            if (voiceLangBtns) {
                voiceLangBtns.querySelectorAll('.fo-ai-widget__voice-lang-btn').forEach(function (btn) {
                    btn.classList.toggle('is-active', btn.getAttribute('data-lang') === code);
                });
            }
            if (input && assistantLangUi[code]) {
                input.placeholder = assistantLangUi[code].placeholder;
                input.setAttribute('dir', 'ltr');
            }
            var hintEl = document.getElementById('fo-ai-voice-lang-hint');
            if (hintEl && assistantLangUi[code]) {
                hintEl.textContent = assistantLangUi[code].hint;
                hintEl.setAttribute('dir', 'ltr');
            }
            loadSpeechVoices();
            updateAssistantUiText();
        }

        function updateAssistantUiText() {
            var meta = assistantLangUi[assistantLang] || assistantLangUi.fr;
            var welcomeEl = document.getElementById('fo-ai-welcome-bubble');
            var welcomeRow = widget.querySelector('.fo-ai-widget__welcome-row');
            if (welcomeEl && meta.welcome) {
                welcomeEl.textContent = meta.welcome;
            }
            if (welcomeRow) {
                welcomeRow.setAttribute('dir', 'ltr');
            }
            if (suggestionsWrap) {
                suggestionsWrap.setAttribute('dir', 'ltr');
            }
            renderSuggestions();
        }

        function renderVoiceLangButtons() {
            if (!voiceLangBtns) return;
            ['fr', 'en'].forEach(function (code) {
                var meta = assistantLangUi[code];
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'fo-ai-widget__voice-lang-btn';
                btn.setAttribute('data-lang', code);
                btn.title = meta.label;
                btn.textContent = meta.short;
                btn.setAttribute('aria-label', meta.label);
                btn.addEventListener('click', function () {
                    setAssistantLang(code);
                });
                voiceLangBtns.appendChild(btn);
            });
            setAssistantLang(assistantLang);
        }

        function endVoiceHold(ev) {
            if (ev && ev.preventDefault) {
                ev.preventDefault();
            }
            if (!isListening && !voiceSendOnStop) {
                return;
            }
            stopListening(true);
        }

        function setOpen(opened) {
            widget.classList.toggle('is-open', opened);
            widget.setAttribute('aria-expanded', opened ? 'true' : 'false');
        }

        function normalizeQuestion(text) {
            return text
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[?!.,]/g, '')
                .replace(/\s+/g, ' ')
                .trim();
        }

        function languageScores(questionRaw) {
            var q = normalizeQuestion(questionRaw);
            var scores = { en: 0, fr: 0 };
            if (/[àâéèêëïîôùûüœæç]/i.test(questionRaw)) scores.fr += 2;
            var enSignals = ['which', 'what', 'where', 'how', 'best', 'product', 'the', 'is', 'my', 'order', 'track', 'payment', 'thanks', 'thank', 'bye', 'hello', 'hi', 'recipe', 'cheap', 'recommend', 'secure', 'card', 'cancel'];
            var frSignals = ['quel', 'quelle', 'meilleur', 'produit', 'comment', 'combien', 'commande', 'suivi', 'livraison', 'paiement', 'merci', 'bonjour', 'salut', 'recette', 'categorie', 'moins', 'cher', 'est', 'securise', 'chiffre', 'annuler', 'carte'];
            function bump(list, key) {
                list.forEach(function (w) {
                    if (new RegExp('\\b' + w + '\\b').test(q)) scores[key]++;
                });
            }
            bump(enSignals, 'en');
            bump(frSignals, 'fr');
            return scores;
        }

        function langDetectionConfident(questionRaw) {
            var scores = languageScores(questionRaw);
            var vals = [scores.en, scores.fr].sort(function (a, b) { return b - a; });
            return vals[0] >= 2 && (vals[0] - vals[1]) >= 2;
        }

        function detectLanguage(questionRaw) {
            var q = normalizeQuestion(questionRaw);
            var scores = languageScores(questionRaw);
            var topLang = 'fr';
            var topScore = -1;
            ['en', 'fr'].forEach(function (code) {
                if (scores[code] > topScore) {
                    topScore = scores[code];
                    topLang = code;
                }
            });
            if (topScore === 0) return 'fr';
            if (scores.en === scores.fr && topScore > 0) {
                if (/\b(which|what|where|how|is|the|my|best|product|recipe|secure)\b/.test(q)) return 'en';
                if (/\b(quel|comment|est|mes|mon|ma|recette|commande)\b/.test(q)) return 'fr';
            }
            return topLang;
        }

        function t(lang, key) {
            if (lang !== 'en' && lang !== 'fr') lang = 'fr';
            var L = {
                thanks: { en: 'You are welcome. I can also help with products, recipes, or order tracking.', fr: 'Avec plaisir. Je peux aussi vous aider pour les produits, les recettes ou le suivi de commande.' },
                bye: { en: 'Goodbye! Have a great day.', fr: 'Au revoir ! Bonne journee.' },
                unknown: { en: 'I can answer questions about our catalog, Frigo, orders, delivery, and payments.', fr: 'Je peux repondre sur le catalogue, frigo, commande, livraison et paiement.' },
                catalog_unavailable: { en: 'I cannot access the product catalog right now. Please try again in a moment.', fr: 'Je ne peux pas acceder au catalogue pour le moment. Reessayez dans un instant.' },
                not_programmed: { en: "I'm not programmed to answer that question.", fr: 'Je ne suis pas programme pour repondre a cette question.' },
                payment_secure: { en: 'Yes. PayPal and card payments on HappyBite use encrypted connections; we do not store your full card details on our servers.', fr: 'Oui. PayPal et le paiement par carte sur HappyBite passent par des connexions chiffrees ; nous ne stockons pas vos coordonnees bancaires completes sur nos serveurs.' },
                cancel_order: { en: 'Yes, you can cancel an order as long as it has not been shipped.', fr: 'Oui, vous pouvez annuler une commande tant qu elle n est pas expediee.' },
                payment_methods: { en: 'You can choose Card, Cash, or PayPal depending on your preference.', fr: 'Vous pouvez choisir Carte, Cash ou PayPal selon votre preference.' },
                hint_recipes: { en: 'You can explore the recipes section, open details, and filter to find meals that match your needs.', fr: 'Vous pouvez explorer la section recettes, ouvrir les details et filtrer pour trouver des plats adaptes.' },
                hint_products: { en: 'You can browse products by category, check details, and compare prices or promotions.', fr: 'Vous pouvez parcourir les produits par categorie, voir les details et comparer les prix ou promotions.' },
                hint_frigo: { en: 'You can add items to your Frigo and review them from the Frigo page.', fr: 'Vous pouvez ajouter des elements au Frigo puis les consulter depuis la page Frigo.' },
                hint_health: { en: 'Check product/recipe details for allergens, calories, and health indicators before choosing.', fr: 'Consultez les details des produits/recettes pour les allergenes, calories et indicateurs sante.' },
                hint_promo: { en: 'You can search promoted products and compare prices from the product list.', fr: 'Vous pouvez rechercher les produits en promotion et comparer les prix dans la liste produits.' },
                hello: { en: 'Hi! I can help with products, recipes, Frigo, payments, delivery, and order tracking.', fr: 'Bonjour ! Je peux vous aider pour les produits, recettes, frigo, paiement, livraison et suivi.' }
            };
            var row = L[key] || {};
            return row[lang] || row.en || row.fr || '';
        }

        function isCatalogQuestion(question) {
            return /(\bproduct\b|\bproducts\b|produit|produits|recette|recettes|\brecipe\b|\brecipes\b|catalogue|catalog|\bfrigo\b|fridge|refrigerateur|categorie|categories|\bcategory\b|\bbest\b|meilleur|meilleure|cheapest|moins cher|promo|promotion|allerg|calorie|calories)/.test(question);
        }

        function getLocalFallbackReply(questionRaw) {
            var question = normalizeQuestion(questionRaw);
            var lang = assistantLang || detectLanguage(questionRaw);
            if (isTrackingQuestion(question) || /quand ma commande arrivera/.test(question) || /when will my order arrive/.test(question)) {
                return getTrackingFallback(lang);
            }
            if (isOutOfScopeQuestion(question, questionRaw)) {
                return { answer: t(lang, 'not_programmed') };
            }
            if (isCatalogQuestion(question)) {
                return { answer: t(lang, 'catalog_unavailable') || t(lang, 'unknown') };
            }

            if (/(\bthanks\b|thank you|\bthx\b|merci|danke)/.test(question)) {
                return { answer: t(lang, 'thanks') };
            }
            if (/(\bbye\b|goodbye|see you|au revoir|a bientot|tschuss)/.test(question)) {
                return { answer: t(lang, 'bye') };
            }

            if (/(\brecipe\b|\brecipes\b|recette|recettes|plat|meal|rezept)/.test(question)) {
                return { answer: t(lang, 'hint_recipes') };
            }
            if (/(\bproduct\b|\bproducts\b|produit|produits|categorie|category|categories)/.test(question)) {
                return { answer: t(lang, 'hint_products') };
            }
            if (/(\bfrigo\b|fridge|refrigerator|kuhlschrank)/.test(question)) {
                return { answer: t(lang, 'hint_frigo') };
            }
            if (/(\ballerg|allergene|allergen|calorie|calories|sante|health)/.test(question)) {
                return { answer: t(lang, 'hint_health') };
            }
            if (/(\bpromo|promotion|discount|budget|moins cher|cheap|gunstig)/.test(question)) {
                return { answer: t(lang, 'hint_promo') };
            }
            if (/(\bhello\b|\bhi\b|hey|bonjour|salut|hallo)/.test(question)) {
                return { answer: t(lang, 'hello') };
            }
            if (
                (/paypal/.test(question) && /(secur|safe|fiable|sicher)/.test(question)) ||
                (/(paiement|payment|carte|card|zahlung|bezahlung|karte)/.test(question) && /(secur|safe|sicher)/.test(question))
            ) {
                return { answer: t(lang, 'payment_secure') };
            }
            if (/annuler|cancel|stornieren/.test(question) && /commande|order|bestellung/.test(question)) {
                return { answer: t(lang, 'cancel_order') };
            }
            if (/mode|method|zahlungsmethode|welche/.test(question) && /(paiement|payment|zahlung)/.test(question)) {
                return { answer: t(lang, 'payment_methods') };
            }
            return { answer: t(lang, 'unknown') };
        }

        function appendUserMessage(text) {
            if (!messages) return;
            var msg = document.createElement('div');
            msg.className = 'fo-ai-widget__msg-user';
            msg.textContent = text;
            messages.appendChild(msg);
            messages.scrollTop = messages.scrollHeight;
        }

        function appendAiMessage(payload) {
            if (!messages) return;
            var row = document.createElement('div');
            row.className = 'fo-ai-widget__msg-ai';

            var icon = document.createElement('img');
            icon.className = 'fo-ai-widget__msg-ai-icon';
            icon.src = 'images/ai-technology.png';
            icon.alt = '';

            var bubble = document.createElement('div');
            bubble.className = 'fo-ai-widget__msg-ai-bubble';
            var answerText = (payload && typeof payload.answer === 'string') ? payload.answer : '';
            bubble.textContent = answerText;
            if (payload && payload.action === 'open_track_map') {
                var trackBtn = document.createElement('button');
                trackBtn.type = 'button';
                trackBtn.className = 'fo-ai-widget__track-link';
                trackBtn.textContent = (payload.link && payload.link.label) ? String(payload.link.label) : 'Suivre ma commande';
                trackBtn.addEventListener('click', function (ev) {
                    ev.preventDefault();
                    if (typeof window.hbOpenTrackMap === 'function') {
                        window.hbOpenTrackMap();
                    }
                });
                bubble.appendChild(document.createElement('br'));
                bubble.appendChild(trackBtn);
            } else if (payload && payload.link && payload.link.url && payload.link.label) {
                var link = document.createElement('a');
                link.href = String(payload.link.url);
                link.textContent = String(payload.link.label);
                link.style.marginLeft = '6px';
                link.style.fontWeight = '700';
                bubble.appendChild(document.createTextNode(' '));
                bubble.appendChild(link);
            }

            row.appendChild(icon);
            row.appendChild(bubble);
            messages.appendChild(row);
            messages.scrollTop = messages.scrollHeight;
        }

        function getSuggestionQuestions() {
            var meta = assistantLangUi[assistantLang] || assistantLangUi.fr;
            var s = meta.suggestions || assistantLangUi.fr.suggestions;
            var suggestions = [s.paypal, s.cancel, s.payment, s.track];
            if (currentPage === 'livraison.php') {
                suggestions.push(s.delivery);
            }
            return suggestions;
        }

        function renderSuggestions() {
            if (!suggestionsWrap) return;
            suggestionsWrap.innerHTML = '';
            getSuggestionQuestions().forEach(function (question) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'fo-ai-widget__suggestion';
                btn.textContent = question;
                btn.addEventListener('click', function () {
                    if (input) {
                        input.value = question;
                    }
                    sendCurrentMessage();
                });
                suggestionsWrap.appendChild(btn);
            });
        }

        function requestAiReply(questionRaw) {
            return fetch(aiChatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    question: questionRaw,
                    page: currentPage,
                    preferred_lang: assistantLang
                })
            }).then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            });
        }

        function sendCurrentMessage(textOverride) {
            if (!input) return;
            var value = (typeof textOverride === 'string' ? textOverride : input.value).trim();
            if (value === '') return;
            stopSpeaking();
            stopListening(false);
            var spokenLang = spokenLanguage(value);
            lastQuestionLang = spokenLang;
            appendUserMessage(value);
            if (sendBtn) sendBtn.disabled = true;
            if (micBtn) micBtn.disabled = true;
            input.value = '';
            requestAiReply(value)
                .then(function (data) {
                    var payload = (data && typeof data.answer === 'string' && data.answer !== '')
                        ? data
                        : getLocalFallbackReply(value);
                    var answerText = payload.answer || '';
                    var replyLang = (data && data.lang) ? data.lang : assistantLang;
                    var detected = (data && data.detected_lang) ? data.detected_lang : spokenLang;
                    if ((!data || !data.answer) && langDetectionConfident(value) && detected !== assistantLang) {
                        var hint = langMismatchHint(detected, assistantLang, replyLang);
                        if (hint) {
                            answerText = answerText + '\n\n' + hint;
                        }
                    }
                    payload.answer = answerText;
                    appendAiMessage(payload);
                    speakAiAnswer(answerText, replyLang);
                })
                .catch(function () {
                    var payload = getLocalFallbackReply(value);
                    var answerText = payload.answer || '';
                    var replyLang = assistantLang;
                    if (langDetectionConfident(value) && spokenLang !== assistantLang) {
                        var hint = langMismatchHint(spokenLang, assistantLang, replyLang);
                        if (hint) {
                            answerText = answerText + '\n\n' + hint;
                        }
                    }
                    payload.answer = answerText;
                    appendAiMessage(payload);
                    speakAiAnswer(answerText, replyLang);
                })
                .finally(function () {
                    if (sendBtn) sendBtn.disabled = false;
                    if (micBtn) micBtn.disabled = false;
                    input.focus();
                });
        }

        renderSuggestions();
        renderVoiceLangButtons();

        function primeSpeechVoices() {
            loadSpeechVoices();
            if (!window.speechSynthesis) {
                return;
            }
            try {
                var u = new SpeechSynthesisUtterance(' ');
                u.volume = 0;
                u.rate = 10;
                window.speechSynthesis.speak(u);
                window.speechSynthesis.cancel();
            } catch (e) {
            }
            setTimeout(loadSpeechVoices, 300);
        }

        if (trigger) {
            trigger.addEventListener('click', function () {
                setOpen(true);
                primeSpeechVoices();
            });
        }

        if (close) {
            close.addEventListener('click', function () {
                stopSpeaking();
                stopListening(false);
                setOpen(false);
            });
        }
        if (micBtn) {
            micBtn.addEventListener('mousedown', beginVoiceHold);
            micBtn.addEventListener('touchstart', beginVoiceHold, { passive: false });
            micBtn.addEventListener('click', function (ev) {
                ev.preventDefault();
            });
            document.addEventListener('mouseup', endVoiceHold);
            document.addEventListener('touchend', endVoiceHold);
            document.addEventListener('touchcancel', endVoiceHold);
        }
        if (sendBtn) {
            sendBtn.addEventListener('click', function () {
                sendCurrentMessage();
            });
        }
        if (input) {
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    sendCurrentMessage();
                }
            });
        }
    })();
</script>
