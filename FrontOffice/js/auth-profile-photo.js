(function () {
    'use strict';

    var CAMERA_ERROR = 'Impossible d\'accéder à la caméra. Vérifiez les autorisations ou choisissez une photo depuis votre appareil.';

    var fileInput = document.getElementById('photo-preview-file');
    var previewImg = document.getElementById('photo-preview');
    var previewWrap = document.getElementById('photo-preview-container');
    var chooseBtn = document.getElementById('photo-choose-btn');
    var cameraBtn = document.getElementById('photo-camera-btn');

    var modal = document.getElementById('auth-photo-modal');
    var video = document.getElementById('auth-photo-video');
    var reviewImg = document.getElementById('auth-photo-review');
    var canvas = document.getElementById('auth-photo-canvas');
    var errorEl = document.getElementById('auth-photo-modal-error');
    var liveActions = document.getElementById('auth-photo-live-actions');
    var reviewActions = document.getElementById('auth-photo-review-actions');
    var captureBtn = document.getElementById('auth-photo-capture');
    var cancelBtn = document.getElementById('auth-photo-cancel');
    var retakeBtn = document.getElementById('auth-photo-retake');
    var confirmBtn = document.getElementById('auth-photo-confirm');

    if (!fileInput || !previewImg || !previewWrap || !chooseBtn || !cameraBtn || !modal) {
        return;
    }

    var stream = null;
    var pendingDataUrl = '';

    function showPreviewFromFile(file) {
        if (!file || file.type.indexOf('image/') !== 0) {
            return;
        }
        var reader = new FileReader();
        reader.onload = function (evt) {
            previewImg.src = evt.target.result;
            previewWrap.hidden = false;
        };
        reader.readAsDataURL(file);
    }

    function assignFileToInput(file) {
        var dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        showPreviewFromFile(file);
    }

    function dataUrlToFile(dataUrl, filename) {
        var parts = dataUrl.split(',');
        var mimeMatch = parts[0].match(/:(.*?);/);
        var mime = mimeMatch ? mimeMatch[1] : 'image/jpeg';
        var binary = atob(parts[1] || '');
        var len = binary.length;
        var bytes = new Uint8Array(len);
        for (var i = 0; i < len; i += 1) {
            bytes[i] = binary.charCodeAt(i);
        }
        return new File([bytes], filename, { type: mime });
    }

    function stopStream() {
        if (!stream) {
            return;
        }
        stream.getTracks().forEach(function (track) {
            track.stop();
        });
        stream = null;
    }

    function hideError() {
        if (!errorEl) {
            return;
        }
        errorEl.hidden = true;
        errorEl.textContent = '';
    }

    function showError(message) {
        if (!errorEl) {
            return;
        }
        errorEl.textContent = message || CAMERA_ERROR;
        errorEl.hidden = false;
    }

    function showCameraView() {
        if (liveActions) {
            liveActions.hidden = false;
        }
        if (reviewActions) {
            reviewActions.hidden = true;
        }
        if (video) {
            video.hidden = false;
        }
        if (reviewImg) {
            reviewImg.hidden = true;
            reviewImg.removeAttribute('src');
        }
    }

    function showCapturedView(dataUrl) {
        pendingDataUrl = dataUrl;
        if (reviewImg) {
            reviewImg.src = dataUrl;
            reviewImg.hidden = false;
        }
        if (video) {
            video.hidden = true;
        }
        if (liveActions) {
            liveActions.hidden = true;
        }
        if (reviewActions) {
            reviewActions.hidden = false;
        }
    }

    function closeModal() {
        stopStream();
        pendingDataUrl = '';
        hideError();
        showCameraView();
        if (video) {
            video.srcObject = null;
        }
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function startCamera() {
        hideError();
        showCameraView();
        pendingDataUrl = '';

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError(CAMERA_ERROR);
            if (captureBtn) {
                captureBtn.disabled = true;
            }
            return;
        }

        if (captureBtn) {
            captureBtn.disabled = false;
        }

        navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
            audio: false
        }).then(function (mediaStream) {
            stream = mediaStream;
            video.srcObject = mediaStream;
            video.hidden = false;
            return video.play();
        }).catch(function () {
            showError(CAMERA_ERROR);
            if (captureBtn) {
                captureBtn.disabled = true;
            }
        });
    }

    function openModal() {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        startCamera();
    }

    chooseBtn.addEventListener('click', function () {
        fileInput.click();
    });

    cameraBtn.addEventListener('click', function () {
        openModal();
    });

    fileInput.addEventListener('change', function (e) {
        var file = e.target.files && e.target.files[0];
        if (file) {
            showPreviewFromFile(file);
        }
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeModal);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    if (captureBtn) {
        captureBtn.addEventListener('click', function () {
            if (!video || !canvas || !reviewImg || video.hidden || !stream) {
                return;
            }
            var w = video.videoWidth || 640;
            var h = video.videoHeight || 640;
            canvas.width = w;
            canvas.height = h;
            var ctx = canvas.getContext('2d');
            if (!ctx) {
                return;
            }
            ctx.drawImage(video, 0, 0, w, h);
            showCapturedView(canvas.toDataURL('image/jpeg', 0.92));
        });
    }

    if (retakeBtn) {
        retakeBtn.addEventListener('click', function () {
            pendingDataUrl = '';
            if (stream && video) {
                showCameraView();
                video.play().catch(function () {});
                return;
            }
            startCamera();
        });
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (!pendingDataUrl) {
                return;
            }
            var file = dataUrlToFile(pendingDataUrl, 'profile-camera.jpg');
            assignFileToInput(file);
            closeModal();
        });
    }
})();
