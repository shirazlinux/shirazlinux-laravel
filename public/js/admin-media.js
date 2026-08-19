/**
 * Post media upload + library for featured image and block editor.
 * Depends on window.SHIRAZ_MEDIA = { uploadUrl, listUrl, csrf, assetBase }
 */
(function () {
  'use strict';

  function cfg() {
    return window.SHIRAZ_MEDIA || {};
  }

  function assetUrl(path) {
    if (!path) return '';
    if (/^https?:\/\//i.test(path)) return path;
    var base = (cfg().assetBase || '/').replace(/\/?$/, '/');
    return base + String(path).replace(/^\//, '');
  }

  function setStatus(msg, isErr) {
    var el = document.querySelector('[data-media-status]');
    if (!el) return;
    el.textContent = msg || '';
    el.style.color = isErr ? '#b91c1c' : '';
  }

  function uploadFile(file) {
    var c = cfg();
    if (!c.uploadUrl) return Promise.reject(new Error('uploadUrl missing'));
    var fd = new FormData();
    fd.append('file', file);
    fd.append('_token', c.csrf || '');
    return fetch(c.uploadUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': c.csrf || '',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: fd
    }).then(function (r) {
      return r.json().then(function (j) {
        if (!r.ok || !j.ok) {
          var msg = (j.message || (j.errors && j.errors.file && j.errors.file[0]) || 'آپلود ناموفق');
          throw new Error(msg);
        }
        return j;
      });
    });
  }

  function setFeatured(path, url) {
    var input = document.querySelector('[data-cover-input]');
    var prev = document.querySelector('[data-cover-prev]');
    if (input) {
      input.value = path || url || '';
      input.dispatchEvent(new Event('input', { bubbles: true }));
    }
    if (prev) {
      var src = url || assetUrl(path);
      if (src) {
        prev.hidden = false;
        prev.innerHTML = '<img src="' + src.replace(/"/g, '&quot;') + '" alt="">';
      }
    }
  }

  function paintCoverFromInput() {
    var input = document.querySelector('[data-cover-input]');
    var prev = document.querySelector('[data-cover-prev]');
    if (!input || !prev) return;
    var v = (input.value || '').trim();
    if (!v) {
      prev.hidden = true;
      prev.innerHTML = '';
      return;
    }
    prev.hidden = false;
    prev.innerHTML = '<img src="' + assetUrl(v).replace(/"/g, '&quot;') + '" alt="">';
  }

  /* ---------- Dropzone for featured ---------- */
  function bootDropzone(zone) {
    var fileInput = zone.querySelector('[data-media-file]');
    var target = zone.getAttribute('data-media-target') || 'featured';

    function onFiles(files) {
      if (!files || !files.length) return;
      var file = files[0];
      setStatus('در حال آپلود…');
      zone.classList.add('is-uploading');
      uploadFile(file).then(function (res) {
        if (target === 'featured') setFeatured(res.path, res.url);
        setStatus('آپلود شد');
        zone.classList.remove('is-uploading');
        refreshLibrary();
      }).catch(function (err) {
        setStatus(err.message || 'خطا', true);
        zone.classList.remove('is-uploading');
      });
    }

    zone.addEventListener('click', function (e) {
      if (e.target.closest('input,button,a')) return;
      if (fileInput) fileInput.click();
    });
    if (fileInput) {
      fileInput.addEventListener('change', function () {
        onFiles(fileInput.files);
        fileInput.value = '';
      });
    }
    ['dragenter', 'dragover'].forEach(function (ev) {
      zone.addEventListener(ev, function (e) {
        e.preventDefault();
        zone.classList.add('is-drag');
      });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      zone.addEventListener(ev, function (e) {
        e.preventDefault();
        zone.classList.remove('is-drag');
      });
    });
    zone.addEventListener('drop', function (e) {
      onFiles(e.dataTransfer && e.dataTransfer.files);
    });
  }

  /* ---------- Library modal ---------- */
  var modalMode = 'featured'; // featured | insert
  var insertCallback = null;

  function openModal(mode, cb) {
    modalMode = mode || 'featured';
    insertCallback = cb || null;
    var modal = document.querySelector('[data-media-modal]');
    if (!modal) return;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    refreshLibrary();
  }

  function closeModal() {
    var modal = document.querySelector('[data-media-modal]');
    if (!modal) return;
    modal.hidden = true;
    document.body.style.overflow = '';
    insertCallback = null;
  }

  function refreshLibrary() {
    var grid = document.querySelector('[data-media-grid]');
    var c = cfg();
    if (!grid || !c.listUrl) return;
    grid.innerHTML = '<p class="jdp-hint">بارگذاری…</p>';
    fetch(c.listUrl, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        var items = (j && j.items) || [];
        if (!items.length) {
          grid.innerHTML = '<p class="jdp-hint">هنوز تصویری آپلود نشده. از دکمه آپلود استفاده کنید.</p>';
          return;
        }
        grid.innerHTML = '';
        items.forEach(function (item) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'media-grid-item';
          btn.title = item.path;
          btn.innerHTML = '<img src="' + String(item.url).replace(/"/g, '&quot;') + '" alt="">';
          btn.addEventListener('click', function () {
            if (modalMode === 'insert' && typeof insertCallback === 'function') {
              insertCallback(item);
            } else {
              setFeatured(item.path, item.url);
            }
            closeModal();
          });
          grid.appendChild(btn);
        });
      })
      .catch(function () {
        grid.innerHTML = '<p class="jdp-hint" style="color:#b91c1c">خطا در بارگذاری کتابخانه</p>';
      });
  }

  function bootModal() {
    var modal = document.querySelector('[data-media-modal]');
    if (!modal) return;
    modal.querySelectorAll('[data-media-close]').forEach(function (el) {
      el.addEventListener('click', closeModal);
    });
    var openers = document.querySelectorAll('[data-media-open]');
    openers.forEach(function (btn) {
      btn.addEventListener('click', function () {
        openModal('featured');
      });
    });
    var modalFile = modal.querySelector('[data-media-modal-file]');
    if (modalFile) {
      modalFile.addEventListener('change', function () {
        if (!modalFile.files || !modalFile.files.length) return;
        setStatus('در حال آپلود…');
        uploadFile(modalFile.files[0]).then(function (res) {
          setStatus('آپلود شد — برای استفاده کلیک کنید');
          refreshLibrary();
          if (modalMode === 'featured') setFeatured(res.path, res.url);
        }).catch(function (err) {
          setStatus(err.message || 'خطا', true);
        });
        modalFile.value = '';
      });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && modal && !modal.hidden) closeModal();
    });
  }

  // API for block editor image upload
  window.ShirazMedia = {
    upload: uploadFile,
    openLibrary: openModal,
    assetUrl: assetUrl,
    setFeatured: setFeatured
  };

  function bootMedia() {
    document.querySelectorAll('[data-media-drop]').forEach(function (zone) {
      if (zone.dataset.mediaReady === '1') return;
      zone.dataset.mediaReady = '1';
      bootDropzone(zone);
    });
    var coverInput = document.querySelector('[data-cover-input]');
    if (coverInput && coverInput.dataset.mediaReady !== '1') {
      coverInput.dataset.mediaReady = '1';
      coverInput.addEventListener('input', paintCoverFromInput);
      paintCoverFromInput();
    }
    var modal = document.querySelector('[data-media-modal]');
    if (modal && modal.dataset.mediaReady !== '1') {
      modal.dataset.mediaReady = '1';
      bootModal();
    }
  }
  document.addEventListener('DOMContentLoaded', bootMedia);
  document.addEventListener('admin:navigated', bootMedia);
  window.ShirazMediaBoot = bootMedia;
})();
