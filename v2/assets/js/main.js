/*
 * Version 2 JavaScript. No libraries (Section 10).
 *
 * Three jobs:
 *  1) hamburger menu on small screens (FR4)
 *  2) check required / email / dates before submit (PHP still validates)
 *  3) auto-submit the FR12 filter bar when a select changes
 *
 * Wrapped in an IIFE so these variables do not leak into the global scope.
 * If JS is disabled the CSS still shows the nav on tablet/desktop and
 * forms still POST to PHP.
 */
(function () {
    'use strict';

    // ----- Mobile nav (FR4) -----
    // CSS hides #nav-toggle above 768px. Adding .is-open stacks the links.
    // aria-expanded is kept in sync for screen readers.
    var toggle = document.getElementById('nav-toggle');
    var nav = document.getElementById('site-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // ----- Hotel check-out min date (FR11) -----
    // The check-out picker must not offer a day on or before check-in.
    // Updating min greys those dates out in the native calendar.
    var checkInInput = document.getElementById('check_in');
    var checkOutInput = document.getElementById('check_out');

    /** Pad a number to two digits (5 -> "05") for YYYY-MM-DD. */
    function pad2(n) {
        return n < 10 ? '0' + n : String(n);
    }

    /** Format a Date as YYYY-MM-DD in local time (not UTC). */
    function toIso(dateObj) {
        return dateObj.getFullYear() + '-' + pad2(dateObj.getMonth() + 1) + '-' + pad2(dateObj.getDate());
    }

    /** Return the calendar day after an ISO date string. */
    function dayAfter(iso) {
        var d = new Date(iso + 'T00:00:00');
        d.setDate(d.getDate() + 1);
        return toIso(d);
    }

    /** Keep check-out.min one day after check-in (or today if check-in is empty). */
    function syncCheckoutMin() {
        if (!checkInInput || !checkOutInput) {
            return;
        }
        var floor = checkOutInput.getAttribute('data-min-today') || checkInInput.getAttribute('min') || '';
        var minOut = checkInInput.value ? dayAfter(checkInInput.value) : floor;
        checkOutInput.min = minOut;
        // Clear a check-out that is now invalid rather than leaving a bad value.
        if (checkOutInput.value && checkOutInput.value < minOut) {
            checkOutInput.value = '';
        }
    }

    if (checkInInput && checkOutInput) {
        checkInInput.addEventListener('change', syncCheckoutMin);
        checkInInput.addEventListener('input', syncCheckoutMin);
        syncCheckoutMin();
    }

    // ----- Client-side validation -----
    // Any form with data-validate is checked here. This is only a courtesy
    // so the customer sees errors before the round-trip. PHP still validates
    // every POST (Section 8) in case JS is off or someone crafts a request.
    var forms = document.querySelectorAll('form[data-validate]');

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            clearJsErrors(form);
            var valid = true;

            // Required fields (HTML required plus our check for whitespace-only).
            form.querySelectorAll('[required]').forEach(function (field) {
                if (!String(field.value || '').trim()) {
                    showJsError(field, 'This field is required.');
                    valid = false;
                }
            });

            // Simple email shape check. PHP uses filter_var as well.
            form.querySelectorAll('input[type="email"]').forEach(function (field) {
                var value = String(field.value || '').trim();
                if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    showJsError(field, 'Please enter a valid email address.');
                    valid = false;
                }
            });

            // Search forms: block dates before today.
            var today = new Date();
            today.setHours(0, 0, 0, 0);

            form.querySelectorAll('input[type="date"]').forEach(function (field) {
                var value = String(field.value || '').trim();
                if (!value) {
                    return;
                }
                var chosen = new Date(value + 'T00:00:00');
                if (chosen < today) {
                    showJsError(field, 'Please choose today or a future date.');
                    valid = false;
                }
            });

            // Hotel form: check-out must be after check-in.
            var checkIn = form.querySelector('#check_in');
            var checkOut = form.querySelector('#check_out');
            if (checkIn && checkOut && checkIn.value && checkOut.value && checkOut.value <= checkIn.value) {
                showJsError(checkOut, 'Check-out must be after check-in.');
                valid = false;
            }

            if (!valid) {
                event.preventDefault();
                var first = form.querySelector('.js-error');
                if (first && first.previousElementSibling) {
                    first.previousElementSibling.focus();
                }
            }
        });
    });

    // ----- FR12 result filters -----
    // The toolbar is a GET form. PHP applies price / stops / duration / stars
    // with bound placeholders. Submitting on change keeps the demo snappy;
    // the Apply button is hidden when JS works, but stays there if JS is off.
    var filterBar = document.querySelector('[data-result-filters]');

    if (filterBar && filterBar.tagName === 'FORM') {
        var applyBtn = filterBar.querySelector('[data-filter-submit]');
        if (applyBtn) {
            applyBtn.hidden = true;
        }
        filterBar.addEventListener('change', function () {
            filterBar.submit();
        });
    }

    /**
     * showJsError(field, message)
     * Insert a .field-error paragraph after the field and mark it invalid.
     */
    function showJsError(field, message) {
        var p = document.createElement('p');
        p.className = 'field-error js-error';
        p.textContent = message;
        field.insertAdjacentElement('afterend', p);
        field.setAttribute('aria-invalid', 'true');
    }

    /**
     * clearJsErrors(form)
     * Remove errors from a previous submit attempt before we re-check.
     */
    function clearJsErrors(form) {
        form.querySelectorAll('.js-error').forEach(function (el) {
            el.parentNode.removeChild(el);
        });
        form.querySelectorAll('[aria-invalid]').forEach(function (el) {
            el.removeAttribute('aria-invalid');
        });
    }
}());
