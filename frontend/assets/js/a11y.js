(function () {
    'use strict';

    function getMainContent() {
        return document.getElementById('main-content');
    }

    function initScreenReaderButton() {
        var btn = document.getElementById('a11y-read-aloud');
        if (!btn) {
            return;
        }

        btn.addEventListener('click', function () {
            var main = getMainContent();
            if (!main) {
                return;
            }

            if (!('speechSynthesis' in window)) {
                alert('Browser Anda tidak mendukung fitur pembaca layar.');
                return;
            }

            window.speechSynthesis.cancel();
            var text = main.innerText.replace(/\s+/g, ' ').trim();
            if (!text) {
                return;
            }

            var utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 1;
            window.speechSynthesis.speak(utterance);
        });
    }

    function initMobileNavA11y() {
        var toggle = document.querySelector('.mobile-nav-toggle');
        var mobileNav = document.getElementById('mobile-nav');
        if (!toggle || !mobileNav) {
            return;
        }

        var sync = function () {
            var open = mobileNav.classList.contains('mobile-nav--open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            mobileNav.setAttribute('aria-hidden', open ? 'false' : 'true');
        };

        toggle.addEventListener('click', function () {
            window.setTimeout(sync, 0);
        });

        var closeBtn = mobileNav.querySelector('.mobile-nav-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                window.setTimeout(sync, 0);
            });
        }

        sync();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initScreenReaderButton();
        initMobileNavA11y();
    });
})();
