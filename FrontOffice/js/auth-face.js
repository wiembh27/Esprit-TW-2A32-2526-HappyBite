/**
 * Face ID auth — détection visage, recadrage carré sur le visage, envoi à AuthProcess.
 */
(function () {
    'use strict';

    function hbFaceAlert(msg) {
        if (typeof window.hbAlert === 'function') {
            window.hbAlert(msg);
        } else if (typeof window.hbShowActionToast === 'function') {
            window.hbShowActionToast(msg, 3500);
        } else {
            window.alert(msg);
        }
    }

    var SCAN_MS = 3000;
    var DETECT_INTERVAL_MS = 350;
    var DESCRIPTOR_GRID = 64;

    function controllerUrl() {
        return new URL('../../Controllers/AuthProcess.php', window.location.href).href;
    }

    function getEl(id) {
        return document.getElementById(id);
    }

    function stopCamera(stream, videoEl) {
        if (stream && stream.getTracks) {
            stream.getTracks().forEach(function (t) {
                t.stop();
            });
        }
        if (videoEl) {
            videoEl.srcObject = null;
        }
    }

    function startCamera(videoEl) {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return Promise.reject(new Error('CAMERA_UNSUPPORTED'));
        }
        return navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
            audio: false
        }).then(function (stream) {
            if (videoEl) {
                videoEl.srcObject = stream;
            }
            return stream;
        });
    }

    function waitForVideoReady(videoEl) {
        return new Promise(function (resolve, reject) {
            if (!videoEl) {
                reject(new Error('NO_VIDEO'));
                return;
            }
            function ready() {
                if (videoEl.videoWidth > 0 && videoEl.videoHeight > 0) {
                    resolve();
                }
            }
            if (videoEl.readyState >= 2) {
                ready();
                if (videoEl.videoWidth > 0) {
                    return;
                }
            }
            videoEl.addEventListener('loadeddata', ready, { once: true });
            videoEl.addEventListener('playing', ready, { once: true });
            setTimeout(function () {
                if (videoEl.videoWidth > 0) {
                    resolve();
                } else {
                    reject(new Error('VIDEO_TIMEOUT'));
                }
            }, 8000);
        });
    }

    /** Carré centré-haut si la détection échoue ou est aberrante. */
    function centerFaceFallback(vw, vh) {
        var side = Math.round(Math.min(vw, vh) * 0.72);
        var x = Math.max(0, Math.floor((vw - side) / 2));
        var y = Math.max(0, Math.floor((vh - side) * 0.08));
        return { x: x, y: y, w: side, h: side };
    }

    function squareFaceBounds(bounds, vw, vh) {
        var cx = bounds.x + bounds.w / 2;
        var cy = bounds.y + bounds.h / 2;
        var side = Math.max(bounds.w, bounds.h) * 1.25;
        side = Math.min(side, vw * 0.92, vh * 0.92);
        var x = Math.max(0, Math.floor(cx - side / 2));
        var y = Math.max(0, Math.floor(cy - side / 2));
        if (x + side > vw) {
            x = vw - side;
        }
        if (y + side > vh) {
            y = vh - side;
        }
        x = Math.max(0, x);
        y = Math.max(0, y);
        return { x: x, y: y, w: Math.floor(side), h: Math.floor(side) };
    }

    function normalizeBounds(bounds, vw, vh) {
        if (!bounds || bounds.w < 8 || bounds.h < 8) {
            return centerFaceFallback(vw, vh);
        }
        var area = bounds.w * bounds.h;
        var frame = vw * vh;
        if (area < frame * 0.02 || area > frame * 0.9) {
            return centerFaceFallback(vw, vh);
        }
        return squareFaceBounds(bounds, vw, vh);
    }

    function expandFaceBounds(box, vw, vh) {
        var padRatio = 0.35;
        var padW = box.width * padRatio;
        var padH = box.height * padRatio;
        var x = Math.max(0, Math.floor(box.x - padW));
        var y = Math.max(0, Math.floor(box.y - padH));
        var w = Math.min(vw - x, Math.ceil(box.width + padW * 2));
        var h = Math.min(vh - y, Math.ceil(box.height + padH * 2));
        if (w < 8 || h < 8) {
            return null;
        }
        return { x: x, y: y, w: w, h: h };
    }

    function boundsFromRelativeBox(rel, vw, vh) {
        if (!rel) {
            return null;
        }
        var xmin = rel.xmin != null ? rel.xmin : rel.left;
        var ymin = rel.ymin != null ? rel.ymin : rel.top;
        var rw = rel.width;
        var rh = rel.height;
        if (xmin == null || ymin == null || rw == null || rh == null) {
            return null;
        }
        if (rw <= 1 && rh <= 1 && xmin <= 1 && ymin <= 1) {
            return expandFaceBounds({
                x: xmin * vw,
                y: ymin * vh,
                width: rw * vw,
                height: rh * vh
            }, vw, vh);
        }
        return expandFaceBounds({ x: xmin, y: ymin, width: rw, height: rh }, vw, vh);
    }

    function drawVideoToCanvas(videoEl) {
        var vw = videoEl.videoWidth;
        var vh = videoEl.videoHeight;
        var canvas = document.createElement('canvas');
        canvas.width = vw;
        canvas.height = vh;
        var ctx = canvas.getContext('2d');
        if (!ctx) {
            return null;
        }
        ctx.drawImage(videoEl, 0, 0, vw, vh);
        return canvas;
    }

    /** Capture recadrée — sans retournement (même repère que la détection). */
    function captureSnapshotDataUrl(videoEl, maxWidth, crop) {
        if (!videoEl || !videoEl.videoWidth || !videoEl.videoHeight) {
            return null;
        }
        var vw = videoEl.videoWidth;
        var vh = videoEl.videoHeight;
        var bounds = normalizeBounds(crop, vw, vh);
        var sx = bounds.x;
        var sy = bounds.y;
        var sw = bounds.w;
        var sh = bounds.h;
        var scale = 1;
        if (maxWidth && sw > maxWidth) {
            scale = maxWidth / sw;
        }
        var cw = Math.round(sw * scale);
        var ch = Math.round(sh * scale);
        var canvas = document.createElement('canvas');
        canvas.width = cw;
        canvas.height = ch;
        var ctx = canvas.getContext('2d');
        if (!ctx) {
            return null;
        }
        ctx.drawImage(videoEl, sx, sy, sw, sh, 0, 0, cw, ch);
        return canvas.toDataURL('image/jpeg', 0.9);
    }

    /** Empreinte 64×64 (z-score) — comparaison côté serveur sans extension GD. */
    function computeDescriptorFromDataUrl(dataUrl) {
        return new Promise(function (resolve, reject) {
            var img = new Image();
            img.onload = function () {
                var size = DESCRIPTOR_GRID;
                var canvas = document.createElement('canvas');
                canvas.width = size;
                canvas.height = size;
                var ctx = canvas.getContext('2d');
                if (!ctx) {
                    reject(new Error('NO_CANVAS'));
                    return;
                }
                ctx.drawImage(img, 0, 0, size, size);
                var px = ctx.getImageData(0, 0, size, size).data;
                var vec = [];
                var i;
                for (i = 0; i < px.length; i += 4) {
                    vec.push((0.299 * px[i] + 0.587 * px[i + 1] + 0.114 * px[i + 2]) / 255);
                }
                var mean = 0;
                for (i = 0; i < vec.length; i++) {
                    mean += vec[i];
                }
                mean /= vec.length;
                var varSum = 0;
                for (i = 0; i < vec.length; i++) {
                    var d = vec[i] - mean;
                    varSum += d * d;
                }
                var std = Math.sqrt(varSum / vec.length + 1e-8);
                for (i = 0; i < vec.length; i++) {
                    vec[i] = (vec[i] - mean) / std;
                }
                resolve(vec);
            };
            img.onerror = function () {
                reject(new Error('IMG_LOAD_FAIL'));
            };
            img.src = dataUrl;
        });
    }

    function updateFaceOverlay(bounds, videoEl) {
        var boxEl = getEl('auth-face-box');
        var guideEl = getEl('auth-face-guide');
        if (!boxEl || !videoEl || !videoEl.videoWidth) {
            return;
        }
        var vw = videoEl.videoWidth;
        var vh = videoEl.videoHeight;
        var b = normalizeBounds(bounds, vw, vh);
        var leftPct = (b.x / vw) * 100;
        var topPct = (b.y / vh) * 100;
        var wPct = (b.w / vw) * 100;
        var hPct = (b.h / vh) * 100;
        boxEl.style.left = (100 - leftPct - wPct) + '%';
        boxEl.style.top = topPct + '%';
        boxEl.style.width = wPct + '%';
        boxEl.style.height = hPct + '%';
        boxEl.hidden = false;
        boxEl.setAttribute('aria-hidden', 'false');
        if (guideEl) {
            guideEl.setAttribute('aria-hidden', 'true');
        }
    }

    function hideFaceOverlay() {
        var boxEl = getEl('auth-face-box');
        var guideEl = getEl('auth-face-guide');
        if (boxEl) {
            boxEl.hidden = true;
            boxEl.setAttribute('aria-hidden', 'true');
        }
        if (guideEl) {
            guideEl.setAttribute('aria-hidden', 'false');
        }
    }

    function getFaceBoundsChrome(videoEl) {
        if (!window.FaceDetector || !videoEl || !videoEl.videoWidth) {
            return Promise.resolve(null);
        }
        var vw = videoEl.videoWidth;
        var vh = videoEl.videoHeight;
        var canvas = drawVideoToCanvas(videoEl);
        if (!canvas) {
            return Promise.resolve(null);
        }
        var detector = new window.FaceDetector({ fastMode: false, maxDetectedFaces: 1 });
        return createImageBitmap(canvas)
            .then(function (bitmap) {
                return detector.detect(bitmap).then(function (faces) {
                    if (!Array.isArray(faces) || faces.length === 0 || !faces[0].boundingBox) {
                        return null;
                    }
                    var bb = faces[0].boundingBox;
                    return expandFaceBounds({
                        x: bb.x,
                        y: bb.y,
                        width: bb.width,
                        height: bb.height
                    }, vw, vh);
                });
            })
            .catch(function () {
                return null;
            });
    }

    var mediaPipeReadyPromise = null;
    function loadScriptOnce(src) {
        return new Promise(function (resolve, reject) {
            var exists = document.querySelector('script[data-auth-face-lib="' + src + '"]');
            if (exists) {
                if (exists.getAttribute('data-loaded') === '1') {
                    resolve();
                } else {
                    exists.addEventListener('load', function () {
                        resolve();
                    }, { once: true });
                    exists.addEventListener('error', function () {
                        reject(new Error('SCRIPT_LOAD_FAIL'));
                    }, { once: true });
                }
                return;
            }
            var script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.setAttribute('data-auth-face-lib', src);
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
        if (mediaPipeReadyPromise) {
            return mediaPipeReadyPromise;
        }
        mediaPipeReadyPromise = Promise.all([
            loadScriptOnce('https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/face_detection.js'),
            loadScriptOnce('https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js')
        ]);
        return mediaPipeReadyPromise;
    }

    function getFaceBoundsMediaPipe(videoEl) {
        return ensureMediaPipeLoaded()
            .then(function () {
                if (!window.FaceDetection || !videoEl || !videoEl.videoWidth) {
                    return null;
                }
                var vw = videoEl.videoWidth;
                var vh = videoEl.videoHeight;
                var canvas = drawVideoToCanvas(videoEl);
                if (!canvas) {
                    return null;
                }
                return new Promise(function (resolve) {
                    var resolved = false;
                    var detector = new window.FaceDetection({
                        locateFile: function (file) {
                            return 'https://cdn.jsdelivr.net/npm/@mediapipe/face_detection/' + file;
                        }
                    });
                    detector.setOptions({ model: 'short', minDetectionConfidence: 0.45 });
                    detector.onResults(function (results) {
                        if (resolved) {
                            return;
                        }
                        resolved = true;
                        var det = results && results.detections && results.detections[0];
                        var rel = det && det.locationData && det.locationData.relativeBoundingBox;
                        var bb = det && det.boundingBox;
                        var out = boundsFromRelativeBox(rel, vw, vh);
                        if (!out && bb) {
                            var nx = bb.xmin != null ? bb.xmin : (bb.xCenter - bb.width / 2);
                            var ny = bb.ymin != null ? bb.ymin : (bb.yCenter - bb.height / 2);
                            out = boundsFromRelativeBox({
                                xmin: nx,
                                ymin: ny,
                                width: bb.width,
                                height: bb.height
                            }, vw, vh);
                        }
                        resolve(out);
                        if (typeof detector.close === 'function') {
                            detector.close();
                        }
                    });
                    detector.send({ image: canvas }).catch(function () {
                        if (!resolved) {
                            resolved = true;
                            resolve(null);
                        }
                        if (typeof detector.close === 'function') {
                            detector.close();
                        }
                    });
                    setTimeout(function () {
                        if (resolved) {
                            return;
                        }
                        resolved = true;
                        resolve(null);
                        if (typeof detector.close === 'function') {
                            detector.close();
                        }
                    }, 2500);
                });
            })
            .catch(function () {
                return null;
            });
    }

    function getFaceBoundsFromVideo(videoEl) {
        return getFaceBoundsMediaPipe(videoEl).then(function (b) {
            if (b) {
                return b;
            }
            return getFaceBoundsChrome(videoEl);
        });
    }

    function scanFaceLoop(videoEl, durationMs, onBounds) {
        var deadline = Date.now() + durationMs;
        var last = null;

        function tick() {
            return getFaceBoundsFromVideo(videoEl).then(function (b) {
                if (b) {
                    last = b;
                    if (onBounds) {
                        onBounds(b);
                    }
                }
                if (Date.now() < deadline) {
                    return new Promise(function (r) {
                        setTimeout(r, DETECT_INTERVAL_MS);
                    }).then(tick);
                }
                return last;
            });
        }

        return tick();
    }

    function setModalVisible(visible) {
        var modal = getEl('auth-face-modal');
        if (!modal) {
            return;
        }
        modal.hidden = !visible;
        modal.setAttribute('aria-hidden', visible ? 'false' : 'true');
    }

    function setScanVisible(visible) {
        var scan = getEl('auth-face-scan');
        if (!scan) {
            return;
        }
        scan.hidden = !visible;
        scan.setAttribute('aria-hidden', visible ? 'false' : 'true');
    }

    function setMsg(text) {
        var el = getEl('auth-face-msg');
        if (el) {
            el.textContent = text || '';
            el.hidden = !text;
        }
    }

    /**
     * @param {{ mode: 'login'|'enroll', getEmail: () => string, onDone?: (ok: boolean, data?: object) => void }} opts
     */
    function runFaceScan(opts) {
        var modal = getEl('auth-face-modal');
        var videoEl = getEl('auth-face-video');
        var stream = null;

        function cleanup() {
            stopCamera(stream, videoEl);
            stream = null;
            setScanVisible(false);
            hideFaceOverlay();
        }

        if (!modal || !videoEl) {
            return;
        }

        setModalVisible(true);
        setScanVisible(false);
        hideFaceOverlay();
        setMsg("Demande d'accès à la caméra...");

        startCamera(videoEl)
            .then(function (s) {
                stream = s;
                return videoEl.play().catch(function () {});
            })
            .then(function () {
                return waitForVideoReady(videoEl);
            })
            .then(function () {
                setScanVisible(true);
                setMsg('Placez votre visage dans le cadre vert…');
                return scanFaceLoop(videoEl, SCAN_MS, function (b) {
                    updateFaceOverlay(b, videoEl);
                });
            })
            .then(function (lastBounds) {
                var vw = videoEl.videoWidth;
                var vh = videoEl.videoHeight;
                var bounds = normalizeBounds(lastBounds, vw, vh);
                updateFaceOverlay(bounds, videoEl);
                var snap = captureSnapshotDataUrl(videoEl, 512, bounds);
                if (!snap) {
                    cleanup();
                    setModalVisible(true);
                    setMsg('Impossible de capturer le visage. Réessayez.');
                    if (opts.onDone) {
                        opts.onDone(false);
                    }
                    return;
                }
                return computeDescriptorFromDataUrl(snap).then(function (descriptor) {
                    cleanup();
                    setModalVisible(false);
                    var email = (opts.getEmail && opts.getEmail()) || '';
                    email = email.trim();
                    if (!email) {
                        if (opts.onDone) {
                            opts.onDone(false);
                        }
                        return;
                    }
                    var action = 'face_login';
                    if (opts.mode === 'enroll') {
                        action = 'face_enroll';
                    } else if (opts.mode === 'password_verify') {
                        action = 'password_face_verify';
                    }
                    var body = new URLSearchParams();
                    body.set('action', action);
                    body.set('email', email);
                    body.set('snapshot', snap);
                    body.set('descriptor', JSON.stringify(descriptor));
                    return fetch(controllerUrl(), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body
                }).then(function (r) {
                    if (!r.ok) {
                        return Promise.reject(new Error('HTTP_' + r.status));
                    }
                    return r.text().then(function (text) {
                        try {
                            return JSON.parse(text);
                        } catch (parseErr) {
                            if (typeof console !== 'undefined' && console.error) {
                                console.error('Face auth: réponse non-JSON', text.slice(0, 400));
                            }
                            return Promise.reject(new Error('BAD_JSON'));
                        }
                    });
                });
                });
            })
            .then(function (json) {
                if (!json) {
                    return;
                }
                if (json.ok) {
                    if (opts.mode === 'login' && json.redirect) {
                        window.location.href = json.redirect;
                    } else if (opts.onDone) {
                        opts.onDone(true, json);
                    } else if (opts.mode !== 'password_verify') {
                        /* noop */
                    }
                } else {
                    if (opts.onDone) {
                        opts.onDone(false, json);
                    } else {
                        hbFaceAlert(json.error || 'Erreur');
                    }
                }
            })
            .catch(function (err) {
                cleanup();
                setModalVisible(false);
                var m = err && err.message;
                if (m === 'CAMERA_UNSUPPORTED') {
                    hbFaceAlert('Caméra non disponible sur ce navigateur.');
                } else if (m === 'VIDEO_TIMEOUT') {
                    hbFaceAlert('La caméra met trop de temps à démarrer. Réessayez.');
                } else if (m === 'BAD_JSON') {
                    hbFaceAlert('Réponse serveur invalide.');
                } else {
                    hbFaceAlert('Caméra refusée ou erreur. Réessayez.');
                }
                if (opts.onDone) {
                    opts.onDone(false);
                }
            });
    }

    window.HappyBiteAuthFace = {
        runLogin: function (getEmail) {
            runFaceScan({ mode: 'login', getEmail: getEmail });
        },
        runEnroll: function (getEmail, onDone) {
            runFaceScan({
                mode: 'enroll',
                getEmail: getEmail,
                onDone: onDone
            });
        },
        runPasswordVerify: function (getEmail, onDone) {
            runFaceScan({
                mode: 'password_verify',
                getEmail: getEmail,
                onDone: onDone
            });
        },
        closeModal: function () {
            var videoEl = getEl('auth-face-video');
            if (videoEl && videoEl.srcObject) {
                stopCamera(videoEl.srcObject, videoEl);
            }
            setScanVisible(false);
            hideFaceOverlay();
            setModalVisible(false);
            setMsg('');
        }
    };

    document.addEventListener('click', function (e) {
        var modal = getEl('auth-face-modal');
        if (modal && e.target === modal) {
            window.HappyBiteAuthFace.closeModal();
        }
    });

    var closeBtn = getEl('auth-face-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            window.HappyBiteAuthFace.closeModal();
        });
    }
})();
