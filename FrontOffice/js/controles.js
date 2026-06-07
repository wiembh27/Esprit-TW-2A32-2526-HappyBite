/**
 * Contrôles formulaire commande (FrontOffice — commande.php uniquement).
 * Mode de paiement : affichage des blocs, formatage et validation (comme list-com-liv / BackOffice).
 */
(function () {
    'use strict';

    var MODE_KEYS = ['carte', 'cash', 'paypal'];

    var PANELS = {
        carte: 'carte-paiement-details',
        cash: 'cash-paiement-details',
        paypal: 'paypal-paiement-details'
    };

    function ids() {
        return {
            reduction: 'reduction',
            carteTitulaire: 'carte-titulaire',
            carteNumero: 'carte-numero',
            carteExpiration: 'carte-expiration',
            carteCvv: 'carte-cvv',
            cashMontant: 'cash-montant',
            cashContact: 'cash-contact',
            cashNote: 'cash-note',
            paypalVerified: 'paypal-verified'
        };
    }

    function initModePanels() {
        var sel = document.getElementById('mode-paiement');
        if (!sel) return;
        function sync() {
            var v = sel.value;
            MODE_KEYS.forEach(function (key) {
                var el = document.getElementById(PANELS[key]);
                if (!el) return;
                var show = v === key;
                el.hidden = !show;
                el.setAttribute('aria-hidden', show ? 'false' : 'true');
            });
        }
        sel.addEventListener('change', sync);
        sync();
    }

    function bindModePaiementChange(form) {
        var sel = document.getElementById('mode-paiement');
        if (!sel || !form) return;
        sel.addEventListener('change', function () {
            clearPanelErrors();
            hideFormMessage(form);
            if (sel.value !== 'paypal') {
                var verified = document.getElementById('paypal-verified');
                var status = document.getElementById('paypal-status');
                if (verified) verified.value = '0';
                if (status) status.hidden = true;
            }
        });
    }

    function onlyDigits(str) {
        return str.replace(/\D/g, '');
    }

    function formatCardNumber(el) {
        var raw = onlyDigits(el.value).slice(0, 19);
        var parts = raw.match(/.{1,4}/g);
        el.value = parts ? parts.join(' ') : '';
    }

    function formatExpiration(el) {
        var d = onlyDigits(el.value).slice(0, 4);
        el.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
    }

    function formatCvv(el) {
        el.value = onlyDigits(el.value).slice(0, 4);
    }

    function isValidCardNumber(value) {
        var n = onlyDigits(value);
        return n.length >= 13 && n.length <= 19;
    }

    function isValidExpiration(value) {
        var m = /^(\d{2})\/(\d{2})$/.exec(value.trim());
        if (!m) return false;
        var mo = parseInt(m[1], 10);
        return mo >= 1 && mo <= 12;
    }

    function isValidCvv(value) {
        return /^\d{3,4}$/.test(value.trim());
    }

    function isValidPhoneLoose(value) {
        var t = value.trim();
        if (t === '') return false;
        return /^[\d\s+().-]{8,}$/.test(t);
    }

    function isValidEmailLoose(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
    }

    function anyFilled(inputs) {
        for (var i = 0; i < inputs.length; i++) {
            if (inputs[i] && inputs[i].value.trim() !== '') return true;
        }
        return false;
    }

    function clearClassErr(el) {
        if (el) el.classList.remove('controle-erreur');
    }

    function setClassErr(el) {
        if (el) el.classList.add('controle-erreur');
    }

    function clearPanelErrors() {
        var I = ids();
        [
            I.carteTitulaire, I.carteNumero, I.carteExpiration, I.carteCvv,
            I.cashMontant, I.cashContact, I.cashNote
        ].forEach(function (id) {
            clearClassErr(document.getElementById(id));
        });
    }

    function showFormMessage(form, text) {
        hideFormMessage(form);
        if (typeof window.hbShowActionToast === 'function') {
            window.hbShowActionToast(text, 4000);
        }
    }

    function hideFormMessage(form) {
        var box = form.querySelector('.controle-js-message');
        if (box) {
            box.hidden = true;
            box.textContent = '';
        }
    }

    function validateCommandeForm(form) {
        hideFormMessage(form);
        clearPanelErrors();
        var I = ids();
        var sel = document.getElementById('mode-paiement');
        if (!sel || sel.value === '') {
            return 'Veuillez choisir un mode de paiement.';
        }

        var mode = sel.value;
        if (mode === 'carte') {
            var tit = document.getElementById(I.carteTitulaire);
            var num = document.getElementById(I.carteNumero);
            var exp = document.getElementById(I.carteExpiration);
            var cvv = document.getElementById(I.carteCvv);
            var ok = true;
            if (!tit || tit.value.trim().length < 2) {
                setClassErr(tit);
                ok = false;
            }
            if (!num || !isValidCardNumber(num.value)) {
                setClassErr(num);
                ok = false;
            }
            if (!exp || !isValidExpiration(exp.value)) {
                setClassErr(exp);
                ok = false;
            }
            if (!cvv || !isValidCvv(cvv.value)) {
                setClassErr(cvv);
                ok = false;
            }
            if (!ok) {
                return 'Complétez tous les champs carte correctement (titulaire, numéro 13–19 chiffres, expiration MM/AA, CVV 3–4 chiffres).';
            }
        } else if (mode === 'cash') {
            var m = document.getElementById(I.cashMontant);
            var tel = document.getElementById(I.cashContact);
            var note = document.getElementById(I.cashNote);
            var okC = true;
            if (!m || m.value.trim() === '') {
                setClassErr(m);
                okC = false;
            }
            if (!tel || !isValidPhoneLoose(tel.value)) {
                setClassErr(tel);
                okC = false;
            }
            if (!note || note.value.trim().length < 2) {
                setClassErr(note);
                okC = false;
            }
            if (!okC) {
                return 'Pour le paiement cash : remplissez tous les champs (montant, téléphone valide 8+ caractères et note min. 2 caractères).';
            }
        } else if (mode === 'paypal') {
            var ver = document.getElementById(I.paypalVerified);
            if (!ver || ver.value !== '1') {
                return 'Pour PayPal : connectez-vous via le bouton "Se connecter a PayPal" (ou Face ID), puis finalisez.';
            }
        }

        return null;
    }

    function bindFormCommandeDelegated(form) {
        if (!form || form.id !== 'form-commande') return;

        form.addEventListener('input', function (e) {
            var t = e.target;
            if (!t || t.tagName !== 'INPUT') return;
            var id = t.id;
            if (id === 'carte-numero') {
                formatCardNumber(t);
                clearClassErr(t);
                return;
            }
            if (id === 'carte-expiration') {
                formatExpiration(t);
                clearClassErr(t);
                return;
            }
            if (id === 'carte-cvv') {
                formatCvv(t);
                clearClassErr(t);
                return;
            }
            if (
                id === 'carte-titulaire' ||
                id === 'cash-montant' ||
                id === 'cash-contact' ||
                id === 'cash-note'
            ) {
                clearClassErr(t);
            }
        });

        form.addEventListener(
            'blur',
            function (e) {
                var t = e.target;
                if (t && t.id === 'carte-numero') {
                    formatCardNumber(t);
                }
            },
            true
        );
    }

    function bindCommandeSubmit(form) {
        if (!form) return;
        form.addEventListener('submit', function (e) {
            var err = validateCommandeForm(form);
            if (err) {
                e.preventDefault();
                showFormMessage(form, err);
            }
        });
    }

    function bindPaypalModal() {
        var modal = document.getElementById('paypal-modal');
        var openBtn = document.getElementById('paypal-auth-btn');
        var cancelBtn = document.getElementById('paypal-login-cancel');
        var loginBtn = document.getElementById('paypal-login-submit');
        var faceBtn = document.getElementById('paypal-faceid-submit');
        var loginEmail = document.getElementById('paypal-login-email');
        var loginPass = document.getElementById('paypal-login-password');
        var verified = document.getElementById('paypal-verified');
        var status = document.getElementById('paypal-status');
        var msg = document.getElementById('paypal-modal-msg');
        var selectMode = document.getElementById('mode-paiement');
        var faceScan = document.getElementById('paypal-face-scan');
        var cameraPreview = document.getElementById('paypal-camera-preview');
        var faceSnapshot = document.getElementById('paypal-face-snapshot');
        var cameraStream = null;

        if (!modal || !openBtn || !cancelBtn || !loginBtn || !faceBtn || !verified || !status || !msg || !selectMode) return;

        function stopCamera() {
            if (cameraStream && cameraStream.getTracks) {
                cameraStream.getTracks().forEach(function (track) {
                    track.stop();
                });
            }
            cameraStream = null;
            if (cameraPreview) cameraPreview.srcObject = null;
        }

        function startCamera() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                return Promise.reject(new Error('CAMERA_UNSUPPORTED'));
            }
            stopCamera();
            return navigator.mediaDevices.getUserMedia({ video: true, audio: false }).then(function (stream) {
                cameraStream = stream;
                if (cameraPreview) cameraPreview.srcObject = stream;
                return stream;
            });
        }

        function openModal() {
            if (selectMode.value !== 'paypal') return;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            stopCamera();
            if (faceScan) {
                faceScan.hidden = true;
                faceScan.setAttribute('aria-hidden', 'true');
            }
            msg.hidden = true;
            msg.textContent = '';
            if (loginEmail) loginEmail.focus();
        }

        function closeModal() {
            stopCamera();
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            if (faceScan) {
                faceScan.hidden = true;
                faceScan.setAttribute('aria-hidden', 'true');
            }
        }

        function setSuccess(emailValue, nameValue) {
            verified.value = '1';
            status.hidden = false;
            closeModal();
        }

        function captureSnapshotDataUrl() {
            if (!cameraPreview || !cameraPreview.videoWidth || !cameraPreview.videoHeight) {
                return null;
            }
            var canvas = document.createElement('canvas');
            canvas.width = cameraPreview.videoWidth;
            canvas.height = cameraPreview.videoHeight;
            var ctx = canvas.getContext('2d');
            if (!ctx) return null;
            ctx.drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);
            return canvas.toDataURL('image/jpeg', 0.85);
        }

        function detectFaceFromPreview() {
            if (!window.FaceDetector) {
                return detectFaceWithMediaPipeFallback();
            }
            if (!cameraPreview || !cameraPreview.videoWidth || !cameraPreview.videoHeight) {
                return Promise.resolve(false);
            }
            var canvas = document.createElement('canvas');
            canvas.width = cameraPreview.videoWidth;
            canvas.height = cameraPreview.videoHeight;
            var ctx = canvas.getContext('2d');
            if (!ctx) return Promise.resolve(false);
            ctx.drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);
            var detector = new window.FaceDetector({ fastMode: true, maxDetectedFaces: 1 });
            return createImageBitmap(canvas)
                .then(function (bitmap) {
                    return detector.detect(bitmap).then(function (faces) {
                        return Array.isArray(faces) && faces.length > 0;
                    });
                })
                .catch(function () {
                    return false;
                });
        }

        var mediaPipeReadyPromise = null;
        function loadScriptOnce(src) {
            return new Promise(function (resolve, reject) {
                var exists = document.querySelector('script[data-face-lib="' + src + '"]');
                if (exists) {
                    if (exists.getAttribute('data-loaded') === '1') {
                        resolve();
                    } else {
                        exists.addEventListener('load', function () { resolve(); }, { once: true });
                        exists.addEventListener('error', function () { reject(new Error('SCRIPT_LOAD_FAIL')); }, { once: true });
                    }
                    return;
                }
                var script = document.createElement('script');
                script.src = src;
                script.async = true;
                script.setAttribute('data-face-lib', src);
                script.onload = function () {
                    script.setAttribute('data-loaded', '1');
                    resolve();
                };
                script.onerror = function () {
                    reject(new Error('SCRIPT_LOAD_FAIL'));
                };
                document.head.appendChild(script);
            });
        }

        function ensureMediaPipeLoaded() {
            if (mediaPipeReadyPromise) return mediaPipeReadyPromise;
            mediaPipeReadyPromise = Promise.all([
                loadScriptOnce('https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/face_detection.js'),
                loadScriptOnce('https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js')
            ]);
            return mediaPipeReadyPromise;
        }

        function detectFaceWithMediaPipeFallback() {
            return ensureMediaPipeLoaded()
                .then(function () {
                    if (!window.FaceDetection || !cameraPreview || !cameraPreview.videoWidth || !cameraPreview.videoHeight) {
                        throw new Error('FACE_DETECT_UNSUPPORTED');
                    }

                    var canvas = document.createElement('canvas');
                    canvas.width = cameraPreview.videoWidth;
                    canvas.height = cameraPreview.videoHeight;
                    var ctx = canvas.getContext('2d');
                    if (!ctx) return false;
                    ctx.drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);

                    return new Promise(function (resolve) {
                        var resolved = false;
                        var detector = new window.FaceDetection({
                            locateFile: function (file) {
                                return 'https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/' + file;
                            }
                        });

                        detector.setOptions({
                            model: 'short',
                            minDetectionConfidence: 0.5
                        });

                        detector.onResults(function (results) {
                            if (resolved) return;
                            resolved = true;
                            resolve(!!(results && results.detections && results.detections.length > 0));
                            if (typeof detector.close === 'function') {
                                detector.close();
                            }
                        });

                        detector.send({ image: canvas }).catch(function () {
                            if (resolved) return;
                            resolved = true;
                            resolve(false);
                            if (typeof detector.close === 'function') {
                                detector.close();
                            }
                        });

                        setTimeout(function () {
                            if (resolved) return;
                            resolved = true;
                            resolve(false);
                            if (typeof detector.close === 'function') {
                                detector.close();
                            }
                        }, 2000);
                    });
                })
                .catch(function () {
                    throw new Error('FACE_DETECT_UNSUPPORTED');
                });
        }

        openBtn.addEventListener('click', openModal);
        cancelBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeModal();
        });

        loginBtn.addEventListener('click', function () {
            var em = loginEmail ? loginEmail.value.trim() : '';
            var pw = loginPass ? loginPass.value.trim() : '';
            if (!isValidEmailLoose(em) || pw.length < 4) {
                msg.hidden = false;
                msg.textContent = 'Entrez un email valide et un mot de passe.';
                return;
            }
            stopCamera();
            if (faceScan) {
                faceScan.hidden = true;
                faceScan.setAttribute('aria-hidden', 'true');
            }
            msg.hidden = false;
            msg.textContent = 'Connexion PayPal... paiement en cours...';
            setTimeout(function () {
                setSuccess(em, em.split('@')[0] || 'Compte PayPal');
            }, 900);
        });

        faceBtn.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                faceBtn.click();
            }
        });

        faceBtn.addEventListener('click', function () {
            verified.value = '0';
            if (faceSnapshot) faceSnapshot.value = '';
            msg.hidden = false;
            msg.textContent = 'Demande d acces a la camera...';
            startCamera()
                .then(function () {
                    if (faceScan) {
                        faceScan.hidden = false;
                        faceScan.setAttribute('aria-hidden', 'false');
                    }
                    msg.textContent = 'Authentification Face ID... regardez la camera.';
                    setTimeout(function () {
                        detectFaceFromPreview()
                            .then(function (hasFace) {
                                if (!hasFace) {
                                    verified.value = '0';
                                    if (faceSnapshot) faceSnapshot.value = '';
                                    stopCamera();
                                    if (faceScan) {
                                        faceScan.hidden = true;
                                        faceScan.setAttribute('aria-hidden', 'true');
                                    }
                                    msg.hidden = false;
                                    msg.textContent = 'Aucun visage detecte. Veuillez vous placer devant la camera et reessayer.';
                                    return;
                                }
                                var snapshot = captureSnapshotDataUrl();
                                if (faceSnapshot && snapshot) faceSnapshot.value = snapshot;
                                if (faceScan) {
                                    faceScan.hidden = true;
                                    faceScan.setAttribute('aria-hidden', 'true');
                                }
                                msg.hidden = false;
                                msg.textContent = 'Visage valide detecte. Paiement PayPal confirme.';
                                setSuccess('client@paypal.com', 'Utilisateur Face ID');
                            })
                            .catch(function (err) {
                                verified.value = '0';
                                if (faceSnapshot) faceSnapshot.value = '';
                                stopCamera();
                                if (faceScan) {
                                    faceScan.hidden = true;
                                    faceScan.setAttribute('aria-hidden', 'true');
                                }
                                msg.hidden = false;
                                if (err && err.message === 'FACE_DETECT_UNSUPPORTED') {
                                    msg.textContent = 'Detection faciale indisponible sur ce navigateur. Essayez Chrome/Edge a jour, sinon utilisez email/mot de passe PayPal.';
                                } else {
                                    msg.textContent = 'Echec de detection du visage. Reessayez Face ID.';
                                }
                            });
                    }, 1600);
                })
                .catch(function () {
                    stopCamera();
                    msg.hidden = false;
                    msg.textContent = 'Camera refusee ou indisponible. Autorisez la camera pour utiliser Face ID.';
                });
        });
    }

    function run() {
        var form = document.getElementById('form-commande');
        if (!form) return;

        initModePanels();
        bindModePaiementChange(form);
        bindFormCommandeDelegated(form);
        bindCommandeSubmit(form);
        bindPaypalModal();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
