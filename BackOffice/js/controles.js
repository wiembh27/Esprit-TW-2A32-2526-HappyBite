/**
 * Contrôles BackOffice : formulaire édition commande, livraison, confirmation suppression (modal).
 */
(function () {
    'use strict';

    var MODE_KEYS = ['carte', 'cash', 'paypal'];

    var PANELS = {
        carte: 'carte-paiement-details-edit',
        cash: 'cash-paiement-details-edit',
        paypal: 'paypal-paiement-details-edit'
    };

    function ids() {
        return {
            reduction: 'reduction-edit-com',
            carteTitulaire: 'carte-titulaire-edit',
            carteNumero: 'carte-numero-edit',
            carteExpiration: 'carte-expiration-edit',
            carteCvv: 'carte-cvv-edit',
            cashMontant: 'cash-montant-edit',
            cashContact: 'cash-contact-edit',
            cashNote: 'cash-note-edit',
            paypalEmail: 'paypal-email-edit',
            paypalNom: 'paypal-nom-edit'
        };
    }

    function initModePanels() {
        var sel = document.getElementById('mode-paiement-edit');
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

    function bindModePaiementEditChange(form) {
        var sel = document.getElementById('mode-paiement-edit');
        if (!sel || !form) return;
        sel.addEventListener('change', function () {
            clearPanelErrors();
            hideFormMessage(form);
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
            I.cashMontant, I.cashContact, I.cashNote, I.paypalEmail, I.paypalNom
        ].forEach(function (id) {
            clearClassErr(document.getElementById(id));
        });
    }

    function showFormMessage(form, text) {
        var box = form.querySelector('.controle-js-message');
        if (!box) {
            box = document.createElement('p');
            box.className = 'controle-js-message commande-flash-erreur';
            box.setAttribute('role', 'alert');
            form.insertBefore(box, form.firstChild);
        }
        box.textContent = text;
        box.hidden = false;
        box.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
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
        var sel = document.getElementById('mode-paiement-edit');
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
            var em = document.getElementById(I.paypalEmail);
            var nom = document.getElementById(I.paypalNom);
            var okP = true;
            if (!em || !isValidEmailLoose(em.value)) {
                setClassErr(em);
                okP = false;
            }
            if (!nom || nom.value.trim().length < 2) {
                setClassErr(nom);
                okP = false;
            }
            if (!okP) {
                return 'Pour PayPal : remplissez tous les champs avec un e-mail valide et un nom d’au moins 2 caractères.';
            }
        }

        return null;
    }

    /**
     * Délégation sur le formulaire d’édition commande : formatage + retrait erreur (comme FrontOffice/commande.php).
     */
    function bindCommandeEditDelegated(form) {
        if (!form || !form.querySelector('#mode-paiement-edit')) return;

        form.addEventListener('input', function (e) {
            var t = e.target;
            if (!t || t.tagName !== 'INPUT') return;
            var id = t.id;
            if (id === 'carte-numero-edit') {
                formatCardNumber(t);
                clearClassErr(t);
                return;
            }
            if (id === 'carte-expiration-edit') {
                formatExpiration(t);
                clearClassErr(t);
                return;
            }
            if (id === 'carte-cvv-edit') {
                formatCvv(t);
                clearClassErr(t);
                return;
            }
            if (
                id === 'carte-titulaire-edit' ||
                id === 'cash-montant-edit' ||
                id === 'cash-contact-edit' ||
                id === 'cash-note-edit' ||
                id === 'paypal-email-edit' ||
                id === 'paypal-nom-edit'
            ) {
                clearClassErr(t);
            }
        });

        form.addEventListener(
            'blur',
            function (e) {
                var t = e.target;
                if (t && t.id === 'carte-numero-edit') {
                    formatCardNumber(t);
                }
            },
            true
        );
    }

    function bindCommandeEditSubmit(form) {
        if (!form) return;
        form.addEventListener('submit', function (e) {
            var err = validateCommandeForm(form);
            if (err) {
                e.preventDefault();
                showFormMessage(form, err);
            }
        });
    }

    function bindLivraisonForm() {
        var dateIn = document.getElementById('livraison_date');
        var statut = document.getElementById('statut');
        if (!dateIn || !statut) return;
        var form = dateIn.closest('form');
        if (!form || !form.querySelector('[name="maj_livraison"]')) return;

        function clear() {
            dateIn.classList.remove('controle-erreur');
            statut.classList.remove('controle-erreur');
            hideFormMessage(form);
        }

        dateIn.addEventListener('input', clear);
        statut.addEventListener('input', clear);

        form.addEventListener('submit', function (e) {
            clear();
            var msg = null;
            if (!dateIn.value) {
                dateIn.classList.add('controle-erreur');
                msg = 'Choisissez une date de livraison.';
            }
            var st = statut.value.trim();
            if (st.length < 2) {
                statut.classList.add('controle-erreur');
                msg = msg || 'Le statut doit contenir au moins 2 caractères.';
            }
            if (msg) {
                e.preventDefault();
                showFormMessage(form, msg);
            }
        });
    }

    function initDeleteModal() {
        var modal = document.getElementById('modal-suppression-liste');
        if (!modal) return;

        var pendingUrl = null;
        var backdrop = modal.querySelector('.liste-com-liv-modal__backdrop');
        var btnCancel = document.getElementById('modal-suppression-annuler');
        var btnOk = document.getElementById('modal-suppression-confirmer');

        function close() {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            pendingUrl = null;
            document.body.classList.remove('liste-com-liv-modal-open');
        }

        function open(url) {
            pendingUrl = url;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('liste-com-liv-modal-open');
            if (btnOk) btnOk.focus();
        }

        document.addEventListener('click', function (e) {
            var a = e.target.closest('a.table-com-liv-delete-link');
            if (!a || !a.getAttribute('href')) return;
            e.preventDefault();
            open(a.href);
        });

        if (btnCancel) btnCancel.addEventListener('click', close);
        if (backdrop) backdrop.addEventListener('click', close);
        if (btnOk) {
            btnOk.addEventListener('click', function () {
                if (pendingUrl) {
                    window.location.href = pendingUrl;
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) {
                close();
            }
        });
    }

    function run() {
        initModePanels();
        var selEdit = document.getElementById('mode-paiement-edit');
        var formEdit = selEdit ? selEdit.closest('form') : null;
        if (formEdit) {
            bindModePaiementEditChange(formEdit);
            bindCommandeEditDelegated(formEdit);
            bindCommandeEditSubmit(formEdit);
        }
        bindLivraisonForm();
        initDeleteModal();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
