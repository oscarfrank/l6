/*
 * Version 1 JavaScript. No libraries (Section 10).
 *
 * Two jobs:
 *  1) hamburger menu on small screens (FR4)
 *  2) check required fields and email format before submit
 *
 * PHP still validates every POST. If JS is off, the CSS still shows the
 * nav on tablet/desktop and forms still submit.
 * Search / filter JS lives in v2/assets/js/main.js (this version has no search).
 */
(function () {
    'use strict';

    // ----- Mobile nav (FR4) -----
    // The button is only visible below 768px (see styles.css).
    // .is-open reveals the stacked link list; aria-expanded is for screen readers.
    var toggle = document.getElementById('nav-toggle');
    var nav = document.getElementById('site-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // ----- Client-side validation -----
    // Courtesy only. contact.php and the admin forms still validate in PHP.
    var forms = document.querySelectorAll('form[data-validate]');

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            clearJsErrors(form);
            var valid = true;

            form.querySelectorAll('[required]').forEach(function (field) {
                if (!String(field.value || '').trim()) {
                    showJsError(field, 'This field is required.');
                    valid = false;
                }
            });

            form.querySelectorAll('input[type="email"]').forEach(function (field) {
                var value = String(field.value || '').trim();
                if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    showJsError(field, 'Please enter a valid email address.');
                    valid = false;
                }
            });

            if (!valid) {
                event.preventDefault();
                var first = form.querySelector('.js-error');
                if (first && first.previousElementSibling) {
                    first.previousElementSibling.focus();
                }
            }
        });
    });

    /**
     * showJsError(field, message)
     * Insert a .field-error paragraph after the field.
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
     * Remove errors from a previous attempt before we re-check.
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
