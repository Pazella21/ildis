/**
 * Lazy background loader for legacy data-background attributes.
 * Also supports [data-bg] for explicitly deferred backgrounds.
 */
(function () {
  'use strict';

  function resolveUrl(url) {
    if (!url || url.indexOf('http') === 0 || url.indexOf('//') === 0) {
      return url;
    }
    if (url.charAt(0) === '/') {
      return url;
    }
    return (document.baseURI || window.location.href).replace(/[^/]*$/, '') + url;
  }

  function applyBackground(el) {
    var url = el.getAttribute('data-background') || el.getAttribute('data-bg');
    if (!url || el.classList.contains('is-bg-loaded')) {
      return;
    }
    el.style.backgroundImage = 'url("' + resolveUrl(url) + '")';
    el.classList.add('is-bg-loaded');
  }

  function observeBackgrounds(elements) {
    if (!elements.length) {
      return;
    }

    if (!('IntersectionObserver' in window)) {
      elements.forEach(applyBackground);
      return;
    }

    var observer = new IntersectionObserver(
      function (entries, obs) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            applyBackground(entry.target);
            obs.unobserve(entry.target);
          }
        });
      },
      { rootMargin: '250px 0px' }
    );

    elements.forEach(function (el) {
      var rect = el.getBoundingClientRect();
      if (rect.top < window.innerHeight + 100) {
        applyBackground(el);
      } else {
        observer.observe(el);
      }
    });
  }

  function init() {
    observeBackgrounds(
      Array.prototype.slice.call(document.querySelectorAll('[data-background], [data-bg]'))
    );
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
