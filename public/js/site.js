(function () {
  'use strict';

  var THEME_KEY = 'shiraz-theme';

  function currentTheme() {
    return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
  }

  function applyTheme(theme) {
    var t = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', t);
    try {
      localStorage.setItem(THEME_KEY, t);
    } catch (e) {}
    var meta = document.getElementById('meta-theme-color');
    if (meta) {
      meta.setAttribute('content', t === 'dark' ? '#121110' : '#F1592D');
    }
    var btn = document.getElementById('theme-toggle');
    if (btn) {
      btn.setAttribute('aria-label', t === 'dark' ? 'رفتن به حالت روشن' : 'رفتن به حالت تیره');
      btn.setAttribute('title', t === 'dark' ? 'حالت روشن' : 'حالت تیره');
    }
  }

  function initTheme() {
    applyTheme(currentTheme());

    var btn = document.getElementById('theme-toggle');
    if (btn) {
      btn.addEventListener('click', function () {
        applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
      });
    }

    try {
      if (!localStorage.getItem(THEME_KEY) && window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        var onChange = function (e) {
          if (!localStorage.getItem(THEME_KEY)) {
            applyTheme(e.matches ? 'dark' : 'light');
          }
        };
        if (mq.addEventListener) mq.addEventListener('change', onChange);
        else if (mq.addListener) mq.addListener(onChange);
      }
    } catch (e) {}
  }

  function initNav() {
    var header = document.getElementById('site-header');
    var toggle = document.getElementById('nav-toggle');
    var nav = document.getElementById('main-nav');
    if (!header || !toggle || !nav) return;

    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = header.classList.toggle('nav-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    nav.querySelectorAll('.sub-trigger').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        if (!window.matchMedia('(max-width: 1100px)').matches) return;
        e.preventDefault();
        e.stopPropagation();
        var item = btn.closest('.has-sub');
        if (!item) return;
        nav.querySelectorAll('.has-sub.open').forEach(function (other) {
          if (other !== item) {
            other.classList.remove('open');
            var t = other.querySelector('.sub-trigger');
            if (t) t.setAttribute('aria-expanded', 'false');
          }
        });
        var expanded = item.classList.toggle('open');
        btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      });
    });

    document.addEventListener('click', function (e) {
      if (!header.contains(e.target) && header.classList.contains('nav-open')) {
        header.classList.remove('nav-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function initSlider() {
    var root = document.getElementById('home-slider');
    if (!root) return;

    var slides = Array.prototype.slice.call(root.querySelectorAll(':scope > .slide'));
    if (!slides.length) {
      slides = Array.prototype.slice.call(root.querySelectorAll('.slide')).filter(function (el) {
        return el.parentElement === root;
      });
    }
    if (!slides.length) return;

    var dots = Array.prototype.slice.call(root.querySelectorAll('.slider-dots .dot'));
    var interval = parseInt(root.getAttribute('data-interval') || '4000', 10);
    if (isNaN(interval) || interval < 1500) interval = 4000;

    var i = 0;
    var timer = null;
    var reduceMotion = false;
    try {
      reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (e) {}

    function show(n) {
      i = ((n % slides.length) + slides.length) % slides.length;
      slides.forEach(function (s, idx) {
        if (idx === i) s.classList.add('is-active');
        else s.classList.remove('is-active');
      });
      dots.forEach(function (d, idx) {
        if (idx === i) d.classList.add('is-active');
        else d.classList.remove('is-active');
      });
    }

    function next(dir) {
      show(i + (typeof dir === 'number' ? dir : 1));
    }

    function stop() {
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    }

    function start() {
      stop();
      if (slides.length < 2 || reduceMotion) return;
      timer = setInterval(function () {
        next(1);
      }, interval);
    }

    dots.forEach(function (d) {
      d.addEventListener('click', function () {
        var go = parseInt(d.getAttribute('data-go'), 10);
        if (isNaN(go)) go = 0;
        show(go);
        start();
      });
    });

    root.querySelectorAll('.slider-nav').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var dir = parseInt(btn.getAttribute('data-dir'), 10);
        if (isNaN(dir)) dir = 1;
        next(dir);
        start();
      });
    });

    var startX = null;
    root.addEventListener(
      'touchstart',
      function (e) {
        if (!e.changedTouches || !e.changedTouches[0]) return;
        startX = e.changedTouches[0].clientX;
        stop();
      },
      { passive: true }
    );
    root.addEventListener(
      'touchend',
      function (e) {
        if (startX == null || !e.changedTouches || !e.changedTouches[0]) return;
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 40) next(dx > 0 ? -1 : 1);
        startX = null;
        start();
      },
      { passive: true }
    );

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);

    show(0);
    start();
  }

  function initToTop() {
    var btn = document.getElementById('to-top');
    if (!btn) return;
    var onScroll = function () {
      if (window.scrollY > 480) btn.classList.add('is-visible');
      else btn.classList.remove('is-visible');
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  function boot() {
    initTheme();
    initNav();
    initSlider();
    initToTop();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
