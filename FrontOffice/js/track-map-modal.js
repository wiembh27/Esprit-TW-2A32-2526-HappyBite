(function () {
    'use strict';

    var modal = document.getElementById('hb-track-modal');
    var body = document.getElementById('hb-track-modal-body');
    if (!modal || !body) {
        return;
    }

    var mapInstance = null;
    var carTimer = null;
    var uiTimer = null;

    function t(key) {
        if (typeof window.hbI18nT === 'function') {
            return window.hbI18nT(key);
        }
        return key;
    }

    function escapeHtml(text) {
        var d = document.createElement('div');
        d.textContent = text == null ? '' : String(text);
        return d.innerHTML;
    }

    function refreshMapSize() {
        if (!mapInstance) {
            return;
        }
        try {
            mapInstance.invalidateSize({ animate: false, pan: false });
        } catch (e) {
        }
    }

    function scheduleMapResize() {
        refreshMapSize();
        requestAnimationFrame(function () {
            refreshMapSize();
            setTimeout(refreshMapSize, 120);
        });
    }

    function destroyMap() {
        if (carTimer) {
            clearInterval(carTimer);
            carTimer = null;
        }
        if (uiTimer) {
            clearInterval(uiTimer);
            uiTimer = null;
        }
        if (mapInstance) {
            try {
                mapInstance.remove();
            } catch (e) {
            }
            mapInstance = null;
        }
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        destroyMap();
        body.innerHTML = '';
    }

    function openModalShell() {
        modal.hidden = false;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function showMessage(msg) {
        body.innerHTML = '<p class="hb-track-modal__msg">' + escapeHtml(msg) + '</p>';
    }

    function showLoading() {
        body.innerHTML = '<p class="hb-track-modal__loading">' + escapeHtml(t('track.loading')) + '</p>';
    }

    function buildPanelHtml(data) {
        var selectHtml = '';
        if (data.select_options && data.select_options.length > 0) {
            selectHtml = '<div class="track-commande-toolbar"><select id="track-commande-select" class="track-commande-select" aria-label="' + escapeHtml(t('track.select_order')) + '" title="' + escapeHtml(t('track.select_order')) + '">';
            data.select_options.forEach(function (opt) {
                selectHtml += '<option value="' + escapeHtml(String(opt.id_commande)) + '"'
                    + (opt.selected ? ' selected' : '') + '>' + escapeHtml(opt.label) + '</option>';
            });
            selectHtml += '</select></div>';
        }

        var notifHtml = data.status_key === 'livree'
            ? '<div class="track-notif"><img src="images/success.svg" alt=""><span>' + escapeHtml(t('track.order_arrived')) + '</span></div>'
            : '';

        var etaHtml = data.eta_line
            ? '<p class="track-card-line track-card-line--eta" id="track-eta-line">' + escapeHtml(data.eta_line) + '</p>'
            : '';
        var subHtml = data.sub_line
            ? '<p class="track-card-line track-card-line--sub" id="track-sub-line">' + escapeHtml(data.sub_line) + '</p>'
            : '';

        return (
            '<div class="track-shell" aria-label="' + escapeHtml(t('track.map_aria')) + '">' +
            '<div id="track-map"></div>' +
            selectHtml +
            '<aside class="track-legend">' +
            '<div class="track-legend-row"><img src="images/store.png" alt="" class="track-legend-icon"><span>' + escapeHtml(t('track.store_departure')) + '</span></div>' +
            '<div class="track-legend-row"><img src="images/order.png" alt="" class="track-legend-icon"><span>' + escapeHtml(t('track.delivery_status').replace('%s', data.status_raw || '—')) + '</span></div>' +
            '<div class="track-legend-row"><img src="images/house.png" alt="" class="track-legend-icon"><span>' + escapeHtml(t('track.your_position')) + '</span></div>' +
            '<p id="track-geo-status" class="track-geo-status" hidden></p>' +
            '</aside>' +
            notifHtml +
            '<article class="track-card">' +
            '<h2>' + escapeHtml(data.status_raw) + '</h2>' +
            etaHtml + subHtml +
            '<div class="track-progress"><div class="track-progress-bar" id="track-progress-bar" style="width:' + (data.progress || 10) + '%"></div></div>' +
            '<div class="track-progress-label" id="track-progress-label">' + (data.progress || 10) + '%</div>' +
            '</article></div>'
        );
    }

    function initMapTracking(data) {
        if (typeof L === 'undefined') {
            showMessage(t('track.map_unavailable'));
            return;
        }

        var status = data.status_key || 'preparation';
        var timeline = data.timeline || {};
        var idCommande = data.id_commande || 0;
        var storeLatLng = [data.shop_lat || 36.8996184, data.shop_lng || 10.1929178];
        var trackPostUrl = 'api/track_map.php?id_commande=' + encodeURIComponent(String(idCommande));

        var geoStatusEl = document.getElementById('track-geo-status');
        var etaLineEl = document.getElementById('track-eta-line');
        var subLineEl = document.getElementById('track-sub-line');
        var progressBarEl = document.getElementById('track-progress-bar');
        var progressLabelEl = document.getElementById('track-progress-label');

        var sel = document.getElementById('track-commande-select');
        if (sel) {
            sel.addEventListener('change', function () {
                var v = parseInt(sel.value, 10);
                if (v > 0) {
                    window.hbOpenTrackMap(v);
                }
            });
        }

        function setGeoStatus(msg, isErr) {
            if (!geoStatusEl) return;
            geoStatusEl.hidden = false;
            geoStatusEl.textContent = msg;
            geoStatusEl.classList.toggle('track-geo-status--err', !!isErr);
        }

        destroyMap();

        mapInstance = L.map('track-map', { zoomControl: false, attributionControl: true }).setView(storeLatLng, 14);
        L.control.zoom({ position: 'topright' }).addTo(mapInstance);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(mapInstance);

        var storeIcon = L.icon({ iconUrl: 'images/store.png', iconSize: [40, 40], iconAnchor: [20, 36] });
        var houseIcon = L.icon({ iconUrl: 'images/house.png', iconSize: [40, 40], iconAnchor: [20, 36] });
        var orderIcon = L.icon({ iconUrl: 'images/order.png', iconSize: [42, 42], iconAnchor: [21, 37] });

        L.marker(storeLatLng, { icon: storeIcon }).addTo(mapInstance).bindPopup(t('track.store_departure'));

        function haversineMeters(lat1, lon1, lat2, lon2) {
            var R = 6371000;
            var toRad = Math.PI / 180;
            var dLat = (lat2 - lat1) * toRad;
            var dLon = (lon2 - lon1) * toRad;
            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * toRad) * Math.cos(lat2 * toRad) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function pointAlongRoute(coords, t) {
            if (!coords || coords.length === 0) return storeLatLng;
            if (coords.length === 1 || t <= 0) return coords[0];
            if (t >= 1) return coords[coords.length - 1];
            var segs = [];
            var total = 0;
            for (var i = 0; i < coords.length - 1; i++) {
                var d = haversineMeters(coords[i][0], coords[i][1], coords[i + 1][0], coords[i + 1][1]);
                segs.push({ i0: i, len: d, start: total });
                total += d;
            }
            if (total <= 0) return coords[Math.floor(t * (coords.length - 1))];
            var dist = t * total;
            for (var j = 0; j < segs.length; j++) {
                var s = segs[j];
                var end = s.start + s.len;
                if (dist <= end || j === segs.length - 1) {
                    var along = s.len > 0 ? (dist - s.start) / s.len : 0;
                    if (!isFinite(along)) along = 0;
                    var a = coords[s.i0];
                    var b = coords[s.i0 + 1];
                    return [a[0] + (b[0] - a[0]) * along, a[1] + (b[1] - a[1]) * along];
                }
            }
            return coords[coords.length - 1];
        }

        function fetchOsrmRoute(fromLL, toLL) {
            var url = 'https://router.project-osrm.org/route/v1/driving/' +
                fromLL[1] + ',' + fromLL[0] + ';' + toLL[1] + ',' + toLL[0] +
                '?overview=full&geometries=geojson';
            return fetch(url).then(function (r) { return r.json(); }).then(function (res) {
                if (!res.routes || !res.routes[0] || !res.routes[0].geometry) return null;
                var route = res.routes[0];
                var g = route.geometry;
                if (!g.coordinates || !g.coordinates.length) return null;
                return {
                    coords: g.coordinates.map(function (c) { return [c[1], c[0]]; }),
                    durationSeconds: Math.max(60, Math.round(route.duration || 0))
                };
            }).catch(function () { return null; });
        }

        function estimateTransitSeconds(fromLL, toLL) {
            return Math.max(300, Math.round(haversineMeters(fromLL[0], fromLL[1], toLL[0], toLL[1]) / (28 / 3.6)));
        }

        function applyTimeline(tl) {
            if (!tl) return;
            timeline = tl;
            status = tl.phase || status;
            if (etaLineEl && tl.eta_line) etaLineEl.textContent = tl.eta_line;
            if (subLineEl && tl.sub_line) subLineEl.textContent = tl.sub_line;
            refreshTimelineUi();
        }

        function saveTransitDuration(seconds) {
            if (!idCommande || seconds < 1) return Promise.resolve(null);
            if (timeline && timeline.arrival_ms && !timeline.needs_route_calc) {
                return Promise.resolve({ ok: true, timeline: timeline });
            }
            var fd = new FormData();
            fd.append('action', 'set_transit');
            fd.append('id_commande', String(idCommande));
            fd.append('transit_seconds', String(Math.round(seconds)));
            return fetch(trackPostUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res && res.ok && res.timeline) applyTimeline(res.timeline);
                    return res;
                })
                .catch(function () { return null; });
        }

        function formatRemainingMs(ms) {
            if (ms <= 0) {
                return t('track.time_less_minute');
            }
            var seconds = Math.ceil(ms / 1000);
            if (seconds < 3600) {
                return t('track.time_min').replace('%d', String(Math.ceil(seconds / 60)));
            }
            if (seconds < 86400) {
                var h = Math.floor(seconds / 3600);
                var m = Math.floor((seconds % 3600) / 60);
                return m > 0
                    ? t('track.time_h_min').replace('%d', String(h)).replace('%d', String(m))
                    : t('track.time_h').replace('%d', String(h));
            }
            var d = Math.floor(seconds / 86400);
            var h2 = Math.floor((seconds % 86400) / 3600);
            return h2 > 0
                ? t('track.time_d_h').replace('%d', String(d)).replace('%d', String(h2))
                : t('track.time_d').replace('%d', String(d));
        }

        function routeProgressNow() {
            if (!timeline) return 0;
            if (status !== 'encours') {
                return typeof timeline.route_progress === 'number' ? timeline.route_progress : 0;
            }
            var start = timeline.en_cours_ms || 0;
            var end = timeline.arrival_ms || 0;
            if (end <= start) return 0;
            return Math.max(0, Math.min(1, (Date.now() - start) / (end - start)));
        }

        function refreshTimelineUi() {
            if (!timeline) return;
            var now = Date.now();
            if (status === 'encours' && timeline.arrival_ms) {
                if (subLineEl) subLineEl.textContent = t('track.arrival_in').replace('%s', formatRemainingMs(timeline.arrival_ms - now));
                var pct = Math.round(40 + routeProgressNow() * 60);
                if (progressBarEl) progressBarEl.style.width = pct + '%';
                if (progressLabelEl) progressLabelEl.textContent = pct + '%';
            } else if (status === 'preparation' && timeline.en_cours_ms) {
                var prepRemain = timeline.en_cours_ms - now;
                if (subLineEl && prepRemain > 0) {
                    subLineEl.textContent = t('track.shipment_in').replace('%s', formatRemainingMs(prepRemain));
                }
                if (timeline.created_ms && progressBarEl && progressLabelEl) {
                    var totalPrep = Math.max(1, timeline.en_cours_ms - timeline.created_ms);
                    var elapsed = Math.max(0, now - timeline.created_ms);
                    var prepPct = Math.max(5, Math.round(Math.min(39, (elapsed / totalPrep) * 39)));
                    progressBarEl.style.width = prepPct + '%';
                    progressLabelEl.textContent = prepPct + '%';
                }
            }
        }

        function buildMap(houseLatLng, routeCoords) {
            var houseMarker = L.marker(houseLatLng, { icon: houseIcon })
                .addTo(mapInstance)
                .bindPopup(t('track.your_position'));

            L.polyline(routeCoords, {
                color: '#7eb8ff', weight: 11, opacity: 0.55, lineCap: 'round', lineJoin: 'round'
            }).addTo(mapInstance);
            var routeLine = L.polyline(routeCoords, {
                color: '#1a73e8', weight: 6, opacity: 1, lineCap: 'round', lineJoin: 'round'
            }).addTo(mapInstance);
            routeLine.bringToFront();

            try {
                mapInstance.fitBounds(routeLine.getBounds().pad(0.18));
            } catch (e) {
                mapInstance.setView(storeLatLng, 13);
            }

            setTimeout(function () {
                refreshMapSize();
                try {
                    mapInstance.fitBounds(routeLine.getBounds().pad(0.18));
                } catch (e2) {
                }
            }, 160);

            var startT = status === 'livree' ? 1 : (status === 'encours' && timeline.arrival_ms ? routeProgressNow() : 0);
            var orderLatLng = status === 'livree' ? houseLatLng : pointAlongRoute(routeCoords, startT);
            var orderMarker = L.marker(orderLatLng, { icon: orderIcon }).addTo(mapInstance);
            var deliveredReload = false;

            function onCarArrived() {
                if (deliveredReload || status === 'livree') return;
                deliveredReload = true;
                orderMarker.setLatLng(houseLatLng);
                if (carTimer) clearInterval(carTimer);
                window.hbOpenTrackMap(idCommande);
            }

            function syncCarPosition() {
                if (status !== 'encours' || !timeline || !timeline.show_car_motion) return;
                var t = routeProgressNow();
                if (t >= 1) {
                    onCarArrived();
                    return;
                }
                orderMarker.setLatLng(pointAlongRoute(routeCoords, t));
                refreshTimelineUi();
            }

            if (status === 'encours' && timeline && timeline.show_car_motion) {
                syncCarPosition();
                carTimer = setInterval(syncCarPosition, 1000);
            } else if (status === 'livree') {
                orderMarker.setLatLng(houseLatLng);
            }

            refreshTimelineUi();
            if (status === 'encours' || status === 'preparation') {
                uiTimer = setInterval(refreshTimelineUi, 1000);
            }
        }

        function startTrackingWithRoute(houseLatLng, routeResult) {
            var routeCoords = routeResult && routeResult.coords ? routeResult.coords : null;
            var durationSec = routeResult && routeResult.durationSeconds
                ? routeResult.durationSeconds
                : estimateTransitSeconds(storeLatLng, houseLatLng);

            if (!routeCoords || routeCoords.length < 2) {
                setGeoStatus(t('track.route_fallback'), false);
                routeCoords = [storeLatLng, houseLatLng];
            } else {
                var first = routeCoords[0];
                var last = routeCoords[routeCoords.length - 1];
                if (haversineMeters(storeLatLng[0], storeLatLng[1], first[0], first[1]) > 35) {
                    routeCoords = [storeLatLng].concat(routeCoords);
                }
                if (haversineMeters(last[0], last[1], houseLatLng[0], houseLatLng[1]) > 35) {
                    routeCoords = routeCoords.concat([houseLatLng]);
                }
            }

            var needsCalc = !timeline || !timeline.arrival_ms || timeline.needs_route_calc;
            (needsCalc ? saveTransitDuration(durationSec) : Promise.resolve(null)).then(function () {
                buildMap(houseLatLng, routeCoords);
                scheduleMapResize();
            });
        }

        scheduleMapResize();
        setGeoStatus(t('track.geo_searching'), false);

        if (!navigator.geolocation) {
            setGeoStatus(t('track.geo_unsupported'), true);
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function (pos) {
                var houseLatLng = [pos.coords.latitude, pos.coords.longitude];
                setGeoStatus(t('track.geo_precision').replace('%d', String(Math.round(pos.coords.accuracy || 0))), false);
                fetchOsrmRoute(storeLatLng, houseLatLng).then(function (rr) {
                    startTrackingWithRoute(houseLatLng, rr);
                });
            },
            function (err) {
                var msg = err && err.code === 1
                    ? t('track.geo_denied')
                    : t('track.geo_unavailable');
                setGeoStatus(msg, true);
                var fallback = [storeLatLng[0] - 0.004, storeLatLng[1] + 0.006];
                fetchOsrmRoute(storeLatLng, fallback).then(function (rr) {
                    startTrackingWithRoute(fallback, rr);
                });
            },
            { enableHighAccuracy: true, maximumAge: 0, timeout: 20000 }
        );
    }

    function loadAndShow(idCommande) {
        openModalShell();
        showLoading();
        destroyMap();

        var url = 'api/track_map.php';
        if (idCommande && idCommande > 0) {
            url += '?id_commande=' + encodeURIComponent(String(idCommande));
        }

        fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) {
                    showMessage((data && data.message) ? data.message : t('track.unavailable'));
                    return;
                }
                body.innerHTML = buildPanelHtml(data);
                initMapTracking(data);
            })
            .catch(function () {
                showMessage(t('track.load_error'));
            });
    }

    window.hbOpenTrackMap = function (idCommande) {
        var id = idCommande ? parseInt(idCommande, 10) : 0;
        loadAndShow(id > 0 ? id : 0);
    };

    modal.querySelectorAll('[data-hb-track-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-hb-open-track-map]');
        if (!btn) return;
        e.preventDefault();
        var cmdId = btn.getAttribute('data-commande-id');
        window.hbOpenTrackMap(cmdId ? parseInt(cmdId, 10) : 0);
    });

    var params = new URLSearchParams(window.location.search);
    var cmdFromUrl = params.get('id_commande');
    var shouldOpen = params.get('open_track') === '1' || params.get('track') === '1';
    if (shouldOpen) {
        params.delete('open_track');
        params.delete('track');
        var qs = params.toString();
        var clean = window.location.pathname + (qs ? '?' + qs : '') + window.location.hash;
        window.history.replaceState({}, '', clean);
        window.hbOpenTrackMap(cmdFromUrl ? parseInt(cmdFromUrl, 10) : 0);
    }
})();
