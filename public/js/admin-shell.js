/**
 * Admin shell: keep the right sidebar permanently mounted.
 * Only .main content swaps on in-panel GET navigations so the menu
 * never reflows, reloads, or loses scroll position.
 */
(function () {
  'use strict';

  var SIDE_ID = 'admin-side';
  var MAIN_SEL = '.layout > .main, .main';
  var SCROLL_KEY = 'admin-side-scroll';
  var PERM_SRC = /admin-shell\.js|admin-jalali\.js/;

  var side = document.getElementById(SIDE_ID);
  var main = document.querySelector(MAIN_SEL);
  if (!side || !main) return;

  if ('scrollRestoration' in history) {
    try { history.scrollRestoration = 'manual'; } catch (e) {}
  }

  var abort = null;
  var navigating = false;

  function adminBasePath() {
    // e.g. /laravel-test/admin or /admin
    var m = location.pathname.match(/^(.*\/admin)(?:\/|$)/);
    return m ? m[1] : '/admin';
  }

  function isSoftNavUrl(url) {
    try {
      var u = new URL(url, location.href);
      if (u.origin !== location.origin) return false;
      var base = adminBasePath();
      if (u.pathname !== base && u.pathname.indexOf(base + '/') !== 0) return false;
      // login page is outside shell
      if (/\/admin\/login\/?$/.test(u.pathname)) return false;
      return true;
    } catch (e) {
      return false;
    }
  }

  function shouldSoftNav(a, e) {
    if (!a || e.defaultPrevented) return false;
    if (e.button !== 0) return false;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return false;
    if (a.target && a.target !== '' && a.target !== '_self') return false;
    if (a.hasAttribute('download')) return false;
    if (a.getAttribute('data-full-page') === '1') return false;
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return false;
    if (!isSoftNavUrl(a.href)) return false;
    // same URL (ignore hash)
    if (a.pathname === location.pathname && a.search === location.search) return false;
    return true;
  }

  function saveSideScroll() {
    try { sessionStorage.setItem(SCROLL_KEY, String(side.scrollTop)); } catch (e) {}
  }

  function restoreSideScroll() {
    try {
      var y = sessionStorage.getItem(SCROLL_KEY);
      if (y !== null) side.scrollTop = parseInt(y, 10) || 0;
    } catch (e) {}
  }

  function setBusy(on) {
    main.classList.toggle('is-nav-loading', !!on);
    main.setAttribute('aria-busy', on ? 'true' : 'false');
  }

  function syncSidebarFrom(doc) {
    var newSide = doc.getElementById(SIDE_ID);
    if (!newSide) return;

    // Update active classes by matching href (preserve DOM nodes)
    var classByHref = {};
    newSide.querySelectorAll('a[href]').forEach(function (a) {
      classByHref[a.href] = a.getAttribute('class') || '';
    });
    side.querySelectorAll('a[href]').forEach(function (a) {
      if (Object.prototype.hasOwnProperty.call(classByHref, a.href)) {
        a.setAttribute('class', classByHref[a.href]);
      } else {
        a.classList.remove('is-active', 'active');
      }
    });

    // Only client-fallback if server markup had no active item
    if (!side.querySelector('a.is-active, a.active')) {
      markActiveByUrl(location.href);
    }

    // CSRF on logout form
    var nt = newSide.querySelector('input[name="_token"]');
    var ot = side.querySelector('input[name="_token"]');
    if (nt && ot) ot.value = nt.value;

    // Any other hidden fields inside side forms
    newSide.querySelectorAll('form').forEach(function (nf, i) {
      var of = side.querySelectorAll('form')[i];
      if (!of) return;
      nf.querySelectorAll('input[type="hidden"]').forEach(function (inp) {
        var name = inp.getAttribute('name');
        if (!name) return;
        var target = of.querySelector('input[type="hidden"][name="' + name + '"]');
        if (target) target.value = inp.value;
      });
    });
  }

  function markActiveByUrl(urlStr) {
    var u;
    try { u = new URL(urlStr, location.href); } catch (e) { return; }
    var path = u.pathname;
    var search = u.searchParams;

    side.querySelectorAll('a[href]').forEach(function (a) {
      a.classList.remove('is-active', 'active');
    });

    // Prefer longest matching admin href
    var best = null;
    var bestLen = -1;
    side.querySelectorAll('a[href]').forEach(function (a) {
      if (a.target === '_blank') return;
      var au;
      try { au = new URL(a.href, location.href); } catch (e) { return; }
      if (au.origin !== u.origin) return;
      if (au.pathname === path) {
        // query: if link has type/tab, require match; else ok
        var ok = true;
        au.searchParams.forEach(function (val, key) {
          if (search.get(key) !== val) ok = false;
        });
        // special: settings without tab matches only default/general
        if (ok && /\/settings\/?$/.test(path) && au.searchParams.has('tab') === false) {
          var tab = search.get('tab');
          if (tab && tab !== 'general') ok = false;
        }
        if (ok) {
          var len = au.pathname.length + au.search.length;
          if (len > bestLen) {
            best = a;
            bestLen = len;
          }
        }
      }
    });

    // Posts edit/create: highlight list by type
    if (!best && /\/posts(\/|$)/.test(path)) {
      var type = search.get('type') || 'post';
      // create page
      if (/\/posts\/create/.test(path) && type !== 'page') {
        best = side.querySelector('a[href*="/posts/create"]');
      }
      if (!best) {
        var listHref = type === 'page' ? 'type=page' : 'type=post';
        side.querySelectorAll('a[href*="/posts"]').forEach(function (a) {
          if (a.href.indexOf('create') !== -1) return;
          if (a.href.indexOf(listHref) !== -1) best = a;
        });
      }
    }

    if (best) best.classList.add('is-active');
  }

  function collectPageScripts(doc) {
    var list = [];
    var seen = {};
    doc.querySelectorAll('script').forEach(function (s) {
      if (s.closest('#' + SIDE_ID)) return;
      var src = s.getAttribute('src') || '';
      if (src && PERM_SRC.test(src)) return;
      // Skip shell if inlined somehow
      if (!src && /admin-side-scroll|Admin shell|Keep sidebar/.test(s.textContent || '')) return;
      var key = src ? 'src:' + src : 'inline:' + (s.textContent || '').slice(0, 80);
      if (seen[key]) return;
      seen[key] = true;
      list.push({
        src: src,
        code: src ? '' : (s.textContent || ''),
        type: s.getAttribute('type') || ''
      });
    });
    return list;
  }

  function runScripts(scripts) {
    var chain = Promise.resolve();
    scripts.forEach(function (item) {
      chain = chain.then(function () {
        if (item.src) {
          // Already present → don't re-fetch; boot via event later
          var abs = new URL(item.src, location.href).href;
          var existing = Array.prototype.some.call(document.scripts, function (s) {
            return s.src === abs;
          });
          if (existing) return;
          return new Promise(function (resolve, reject) {
            var el = document.createElement('script');
            el.src = item.src;
            el.async = false;
            el.onload = function () { resolve(); };
            el.onerror = function () { resolve(); }; // don't block nav
            document.body.appendChild(el);
          });
        }
        if (item.code && item.type !== 'application/json') {
          try {
            var el = document.createElement('script');
            el.text = item.code;
            document.body.appendChild(el);
            el.parentNode && el.parentNode.removeChild(el);
          } catch (e) {
            try { (0, eval)(item.code); } catch (e2) {}
          }
        }
      });
    });
    return chain;
  }

  function cleanupBodyChrome() {
    // Remove orphaned media modals / pickers left outside .main from previous pages
    document.querySelectorAll('[data-media-modal]').forEach(function (m) {
      if (!main.contains(m)) m.remove();
    });
    document.querySelectorAll('.jdp-pop').forEach(function (p) { p.remove(); });
    document.body.style.overflow = '';
  }

  function navigate(url, push) {
    if (navigating) {
      if (abort) abort.abort();
    }
    navigating = true;
    setBusy(true);
    saveSideScroll();

    abort = typeof AbortController !== 'undefined' ? new AbortController() : null;
    var opts = {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'AdminShell',
        'Accept': 'text/html'
      }
    };
    if (abort) opts.signal = abort.signal;

    return fetch(url, opts)
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        // If redirected to login, full navigate
        if (/\/admin\/login/.test(res.url)) {
          location.href = res.url;
          return null;
        }
        return res.text().then(function (html) {
          return { html: html, finalUrl: res.url || url };
        });
      })
      .then(function (payload) {
        if (!payload) return;
        var doc = new DOMParser().parseFromString(payload.html, 'text/html');
        var newMain = doc.querySelector(MAIN_SEL);
        if (!newMain || !doc.getElementById(SIDE_ID)) {
          location.href = url;
          return;
        }

        cleanupBodyChrome();
        main.innerHTML = newMain.innerHTML;
        document.title = doc.title || document.title;

        if (push) {
          history.pushState({ adminShell: 1 }, '', payload.finalUrl);
        }

        syncSidebarFrom(doc);
        restoreSideScroll();
        window.scrollTo(0, 0);

        var scripts = collectPageScripts(doc);
        return runScripts(scripts).then(function () {
          document.dispatchEvent(new CustomEvent('admin:navigated', {
            detail: { url: payload.finalUrl }
          }));
        });
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
        // Fallback: hard navigation
        location.href = url;
      })
      .then(function () {
        navigating = false;
        setBusy(false);
        restoreSideScroll();
      });
  }

  // Intercept all in-panel GET link clicks (sidebar + main content)
  document.addEventListener('click', function (e) {
    var a = e.target.closest && e.target.closest('a');
    if (!a || !shouldSoftNav(a, e)) return;
    e.preventDefault();
    navigate(a.href, true);
  }, true);

  window.addEventListener('popstate', function () {
    if (!isSoftNavUrl(location.href)) {
      location.reload();
      return;
    }
    navigate(location.href, false);
  });

  // Persist sidebar scroll
  side.addEventListener('scroll', function () {
    saveSideScroll();
  }, { passive: true });
  side.querySelectorAll('a[href]').forEach(function (a) {
    a.addEventListener('click', saveSideScroll);
  });

  // Initial restore + active polish
  restoreSideScroll();
  markActiveByUrl(location.href);

  // Expose for debugging
  window.AdminShell = { navigate: navigate, restoreSideScroll: restoreSideScroll };
})();
