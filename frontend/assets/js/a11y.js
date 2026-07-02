(function () {
    'use strict';

    var STORAGE_KEY = 'ildis-a11y-settings';
    var FONT_CLASSES = ['a11y-font-scale-1', 'a11y-font-scale-2', 'a11y-font-scale-3'];
    var TOGGLE_CLASSES = [
        'a11y-high-contrast',
        'a11y-grayscale',
        'a11y-highlight-links',
        'a11y-readable-font',
    ];

    function getRoot() {
        return document.documentElement;
    }

    function getMainContent() {
        return document.getElementById('main-content');
    }

    function loadSettings() {
        try {
            return JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '{}');
        } catch (e) {
            return {};
        }
    }

    function saveSettings(settings) {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
    }

    function setFontScale(level) {
        var root = getRoot();
        FONT_CLASSES.forEach(function (cls) {
            root.classList.remove(cls);
        });
        if (level > 0) {
            root.classList.add('a11y-font-scale-' + level);
        }
    }

    function toggleClass(className, enabled) {
        getRoot().classList.toggle(className, !!enabled);
    }

    function applySettings(settings) {
        setFontScale(settings.fontScale || 0);
        TOGGLE_CLASSES.forEach(function (cls) {
            toggleClass(cls, !!settings[cls]);
        });
        syncActiveButtons(settings);
    }

    function syncActiveButtons(settings) {
        document.querySelectorAll('[data-a11y-action]').forEach(function (btn) {
            var action = btn.getAttribute('data-a11y-action');
            var active = false;

            if (action === 'font-increase') {
                active = (settings.fontScale || 0) > 0;
            } else if (TOGGLE_CLASSES.indexOf(action) !== -1) {
                active = !!settings[action];
            }

            btn.classList.toggle('is-active', active);
        });
    }

    function initAccessibilityWidget() {
        var widget = document.getElementById('a11y-widget');
        var toggle = document.getElementById('a11y-widget-toggle');
        var panel = document.getElementById('a11y-widget-panel');
        var closeBtn = document.getElementById('a11y-widget-close');

        if (!widget || !toggle || !panel) {
            return;
        }

        var settings = loadSettings();
        applySettings(settings);

        var setPanelOpen = function (open) {
            panel.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        toggle.addEventListener('click', function () {
            setPanelOpen(panel.hidden);
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                setPanelOpen(false);
                toggle.focus();
            });
        }

        document.addEventListener('click', function (event) {
            if (!widget.contains(event.target)) {
                setPanelOpen(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !panel.hidden) {
                setPanelOpen(false);
                toggle.focus();
            }
        });

        widget.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-a11y-action]');
            if (!btn) {
                return;
            }

            var action = btn.getAttribute('data-a11y-action');
            settings = loadSettings();

            if (action === 'font-increase') {
                settings.fontScale = Math.min(3, (settings.fontScale || 0) + 1);
                setFontScale(settings.fontScale);
            } else if (action === 'font-decrease') {
                settings.fontScale = Math.max(0, (settings.fontScale || 0) - 1);
                setFontScale(settings.fontScale);
            } else if (action === 'read-aloud') {
                readAloud();
            } else if (action === 'stop-read') {
                stopReadAloud();
            } else if (action === 'reset') {
                settings = {};
                window.speechSynthesis.cancel();
                applySettings(settings);
                saveSettings(settings);
                return;
            } else if (TOGGLE_CLASSES.indexOf(action) !== -1) {
                settings[action] = !settings[action];
                toggleClass(action, settings[action]);
            }

            saveSettings(settings);
            syncActiveButtons(settings);
        });
    }

    function readAloud() {
        var main = getMainContent();
        if (!main) {
            return;
        }

        if (!('speechSynthesis' in window)) {
            window.alert('Browser Anda tidak mendukung fitur pembaca layar.');
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
    }

    function stopReadAloud() {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
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
        initAccessibilityWidget();
        initMobileNavA11y();
    });
})();
