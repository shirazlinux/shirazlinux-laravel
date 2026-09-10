/**
 * Block editor (Virgool / Publii-style) for admin post body.
 * - Each block is separate (paragraph, heading, list, quote, image, code, html, hr)
 * - Serializes to HTML into a hidden/source textarea[name=body]
 */
(function () {
  'use strict';

  var TYPES = [
    { id: 'paragraph', label: 'پاراگراف', icon: '¶' },
    { id: 'h2', label: 'عنوان ۱', icon: 'H1' },
    { id: 'h3', label: 'عنوان ۲', icon: 'H2' },
    { id: 'h4', label: 'عنوان ۳', icon: 'H3' },
    { id: 'list', label: 'فهرست', icon: '•' },
    { id: 'olist', label: 'فهرست عددی', icon: '1.' },
    { id: 'toc', label: 'فهرست مطالب', icon: '☰' },
    { id: 'quote', label: 'نقل‌قول', icon: '❝' },
    { id: 'image', label: 'تصویر', icon: '🖼' },
    { id: 'code', label: 'کد', icon: '</>' },
    { id: 'html', label: 'HTML خام', icon: '{}' },
    { id: 'hr', label: 'جداکننده', icon: '—' }
  ];

  function el(tag, cls, html) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (html != null) n.innerHTML = html;
    return n;
  }

  function uid() {
    return 'b' + Math.random().toString(36).slice(2, 9);
  }

  function stripHtml(s) {
    var d = document.createElement('div');
    d.innerHTML = s || '';
    return d.textContent || d.innerText || '';
  }

  function escapeHtml(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ---------- HTML → blocks ---------- */
  function htmlToBlocks(html) {
    html = (html || '').trim();
    if (!html) {
      return [{ id: uid(), type: 'paragraph', html: '' }];
    }

    var wrap = document.createElement('div');
    wrap.innerHTML = html;
    var blocks = [];
    var nodes = Array.prototype.slice.call(wrap.childNodes);

    // If no element children, treat whole as paragraph
    var hasEl = nodes.some(function (n) { return n.nodeType === 1; });
    if (!hasEl) {
      return [{ id: uid(), type: 'paragraph', html: html }];
    }

    nodes.forEach(function (node) {
      if (node.nodeType === 3) {
        var t = node.textContent.trim();
        if (t) blocks.push({ id: uid(), type: 'paragraph', html: escapeHtml(t) });
        return;
      }
      if (node.nodeType !== 1) return;
      var tag = node.tagName.toLowerCase();
      if (tag === 'h1' || tag === 'h2') {
        blocks.push({ id: uid(), type: 'h2', html: node.innerHTML });
      } else if (tag === 'h3') {
        blocks.push({ id: uid(), type: 'h3', html: node.innerHTML });
      } else if (tag === 'h4' || tag === 'h5' || tag === 'h6') {
        blocks.push({ id: uid(), type: 'h4', html: node.innerHTML });
      } else if (tag === 'nav' && /\bpost-toc\b/.test(node.className || '')) {
        blocks.push({ id: uid(), type: 'toc' });
      } else if (tag === 'ul' && /\bpost-toc\b/.test(node.className || '')) {
        blocks.push({ id: uid(), type: 'toc' });
      } else if (tag === 'ol' && /\bpost-toc\b/.test(node.className || '')) {
        blocks.push({ id: uid(), type: 'toc' });
      } else if (tag === 'ul') {
        blocks.push({ id: uid(), type: 'list', html: listToText(node) });
      } else if (tag === 'ol') {
        blocks.push({ id: uid(), type: 'olist', html: listToText(node) });
      } else if (tag === 'blockquote') {
        blocks.push({ id: uid(), type: 'quote', html: node.innerHTML });
      } else if (tag === 'pre') {
        blocks.push({ id: uid(), type: 'code', text: node.textContent || '' });
      } else if (tag === 'hr') {
        blocks.push({ id: uid(), type: 'hr' });
      } else if (tag === 'figure') {
        var imgF = node.querySelector('img');
        if (imgF) {
          var cap = node.querySelector('figcaption');
          blocks.push({
            id: uid(),
            type: 'image',
            src: imgF.getAttribute('src') || '',
            alt: imgF.getAttribute('alt') || '',
            caption: cap ? cap.textContent : ''
          });
        } else {
          blocks.push({ id: uid(), type: 'html', text: node.outerHTML });
        }
      } else if (tag === 'p') {
        var onlyImg = node.childNodes.length === 1 && node.firstChild.tagName && node.firstChild.tagName.toLowerCase() === 'img';
        if (onlyImg) {
          var im = node.firstChild;
          blocks.push({
            id: uid(),
            type: 'image',
            src: im.getAttribute('src') || '',
            alt: im.getAttribute('alt') || '',
            caption: ''
          });
        } else if (node.querySelector('img') && stripHtml(node.innerHTML).trim() === '') {
          var im2 = node.querySelector('img');
          blocks.push({
            id: uid(),
            type: 'image',
            src: im2.getAttribute('src') || '',
            alt: im2.getAttribute('alt') || '',
            caption: ''
          });
        } else {
          blocks.push({ id: uid(), type: 'paragraph', html: node.innerHTML });
        }
      } else if (tag === 'div' || tag === 'section') {
        // Recurse shallow: if mostly structured, convert children; else raw html
        if (node.children.length && !node.querySelector('script')) {
          var inner = htmlToBlocks(node.innerHTML);
          inner.forEach(function (b) { blocks.push(b); });
        } else {
          blocks.push({ id: uid(), type: 'html', text: node.outerHTML });
        }
      } else {
        blocks.push({ id: uid(), type: 'html', text: node.outerHTML });
      }
    });

    if (!blocks.length) {
      blocks.push({ id: uid(), type: 'paragraph', html: html });
    }
    return blocks;
  }

  function listToText(listEl) {
    return Array.prototype.map.call(listEl.querySelectorAll(':scope > li'), function (li) {
      return li.textContent.trim();
    }).filter(Boolean).join('\n');
  }

  function slugifyHeading(html, used) {
    var t = stripHtml(html).replace(/\s+/g, ' ').trim();
    var s = t
      .replace(/[^\u0600-\u06FFa-zA-Z0-9\s\-]/g, '')
      .trim()
      .replace(/\s+/g, '-')
      .toLowerCase();
    if (!s) s = 'bakhsh';
    var base = s;
    var n = 2;
    while (used[s]) {
      s = base + '-' + n;
      n += 1;
    }
    used[s] = true;
    return s;
  }

  function tocHtmlFromHeadings(headings) {
    if (!headings.length) {
      return '<nav class="post-toc" aria-label="فهرست مطالب"><p class="post-toc-title">فهرست مطالب</p><p class="muted">عنوان‌های مطلب را اضافه کنید تا اینجا لینک شوند.</p></nav>';
    }
    var items = headings.map(function (h) {
      return '<li class="post-toc-l' + h.level + '"><a href="#' + escapeHtml(h.id) + '">' + escapeHtml(h.text) + '</a></li>';
    }).join('');
    return '<nav class="post-toc" aria-label="فهرست مطالب"><p class="post-toc-title">فهرست مطالب</p><ol>' + items + '</ol></nav>';
  }

  /* ---------- blocks → HTML ---------- */
  function blocksToHtml(blocks) {
    var used = {};
    var headings = [];
    blocks.forEach(function (b) {
      if (b.type === 'h2' || b.type === 'h3' || b.type === 'h4') {
        var id = slugifyHeading(b.html || '', used);
        b._hid = id;
        headings.push({
          id: id,
          level: b.type === 'h2' ? 2 : (b.type === 'h3' ? 3 : 4),
          text: stripHtml(b.html || '')
        });
      }
    });
    return blocks.map(function (b) {
      switch (b.type) {
        case 'toc': return tocHtmlFromHeadings(headings);
        case 'h2': return '<h2 id="' + b._hid + '">' + (b.html || '') + '</h2>';
        case 'h3': return '<h3 id="' + b._hid + '">' + (b.html || '') + '</h3>';
        case 'h4': return '<h4 id="' + b._hid + '">' + (b.html || '') + '</h4>';
        case 'quote': return '<blockquote>' + (b.html || '') + '</blockquote>';
        case 'code': return '<pre><code>' + escapeHtml(b.text || '') + '</code></pre>';
        case 'hr': return '<hr>';
        case 'list':
          return '<ul>' + textToListItems(b.html || b.text || '') + '</ul>';
        case 'olist':
          return '<ol>' + textToListItems(b.html || b.text || '') + '</ol>';
        case 'image':
          if (!b.src) return '';
          var cap = (b.caption || '').trim();
          var img = '<img src="' + escapeHtml(b.src) + '" alt="' + escapeHtml(b.alt || '') + '" loading="lazy">';
          return cap
            ? '<figure class="post-image">' + img + '<figcaption>' + escapeHtml(cap) + '</figcaption></figure>'
            : '<p class="align-center">' + img + '</p>';
        case 'html':
          return b.text || '';
        case 'paragraph':
        default:
          var h = (b.html || '').trim();
          return h ? '<p>' + h + '</p>' : '';
      }
    }).filter(Boolean).join('\n');
  }

  function textToListItems(text) {
    return String(text).split(/\n+/).map(function (line) {
      line = line.replace(/^[\s•\-\*\d\.\)،]+/, '').trim();
      if (!line) return '';
      return '<li>' + escapeHtml(line) + '</li>';
    }).join('');
  }

  /* ---------- UI ---------- */
  function createBlockEl(block, api) {
    var root = el('div', 'be-block be-block--' + block.type);
    root.dataset.id = block.id;
    root.dataset.type = block.type;

    var side = el('div', 'be-side');
    var handle = el('button', 'be-handle');
    handle.type = 'button';
    handle.title = 'کشیدن برای جابه‌جایی';
    handle.setAttribute('aria-label', 'جابه‌جایی بلاک');
    handle.innerHTML =
      '<svg class="be-ico" viewBox="0 0 20 20" width="16" height="16" aria-hidden="true" focusable="false">' +
      '<circle cx="7" cy="5" r="1.6"/><circle cx="13" cy="5" r="1.6"/>' +
      '<circle cx="7" cy="10" r="1.6"/><circle cx="13" cy="10" r="1.6"/>' +
      '<circle cx="7" cy="15" r="1.6"/><circle cx="13" cy="15" r="1.6"/>' +
      '</svg>';

    var addBtn = el('button', 'be-add');
    addBtn.type = 'button';
    addBtn.title = 'افزودن بلاک زیر این';
    addBtn.setAttribute('aria-label', 'افزودن بلاک');
    addBtn.innerHTML =
      '<svg class="be-ico" viewBox="0 0 20 20" width="15" height="15" aria-hidden="true" focusable="false">' +
      '<path d="M10 4v12M4 10h12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>' +
      '</svg>';
    addBtn.addEventListener('click', function () {
      api.insertAfter(block.id, { id: uid(), type: 'paragraph', html: '' });
    });

    side.appendChild(addBtn);
    side.appendChild(handle);

    var main = el('div', 'be-main');
    var bar = el('div', 'be-bar');

    var typeSel = el('select', 'be-type');
    TYPES.forEach(function (t) {
      var o = document.createElement('option');
      o.value = t.id;
      o.textContent = t.label;
      if (t.id === block.type) o.selected = true;
      typeSel.appendChild(o);
    });
    typeSel.addEventListener('change', function () {
      var next = typeSel.value;
      var data = api.getBlock(block.id);
      data.type = next;
      if (next === 'code' || next === 'html') {
        data.text = data.text != null ? data.text : stripHtml(data.html || '');
      }
      if (next === 'list' || next === 'olist') {
        data.html = data.html || stripHtml(data.html || data.text || '');
      }
      if (next === 'image') {
        data.src = data.src || '';
        data.alt = data.alt || '';
        data.caption = data.caption || '';
      }
      api.updateBlock(block.id, data);
      api.rerender();
      api.focus(block.id);
    });

    var tools = el('div', 'be-tools');
    var btnUp = el('button', 'be-tool', '↑');
    btnUp.type = 'button'; btnUp.title = 'بالا';
    btnUp.addEventListener('click', function () { api.move(block.id, -1); });
    var btnDown = el('button', 'be-tool', '↓');
    btnDown.type = 'button'; btnDown.title = 'پایین';
    btnDown.addEventListener('click', function () { api.move(block.id, 1); });
    var btnDel = el('button', 'be-tool be-tool-danger', '×');
    btnDel.type = 'button'; btnDel.title = 'حذف';
    btnDel.addEventListener('click', function () {
      if (api.count() <= 1) {
        api.updateBlock(block.id, { id: block.id, type: 'paragraph', html: '' });
        api.rerender();
        return;
      }
      api.remove(block.id);
    });
    tools.appendChild(btnUp);
    tools.appendChild(btnDown);
    tools.appendChild(btnDel);

    bar.appendChild(typeSel);
    bar.appendChild(tools);

    var body = el('div', 'be-body');
    renderBody(body, block, api);

    main.appendChild(bar);
    main.appendChild(body);
    root.appendChild(side);
    root.appendChild(main);

    // drag and drop
    root.draggable = true;
    root.addEventListener('dragstart', function (e) {
      e.dataTransfer.setData('text/plain', block.id);
      root.classList.add('is-dragging');
    });
    root.addEventListener('dragend', function () {
      root.classList.remove('is-dragging');
    });
    root.addEventListener('dragover', function (e) {
      e.preventDefault();
      root.classList.add('is-drop');
    });
    root.addEventListener('dragleave', function () {
      root.classList.remove('is-drop');
    });
    root.addEventListener('drop', function (e) {
      e.preventDefault();
      root.classList.remove('is-drop');
      var from = e.dataTransfer.getData('text/plain');
      if (from && from !== block.id) api.moveTo(from, block.id);
    });

    return root;
  }

  function renderBody(container, block, api) {
    container.innerHTML = '';
    var type = block.type;

    if (type === 'hr') {
      container.appendChild(el('div', 'be-hr-preview', '<hr><span>جداکننده</span>'));
      return;
    }

    if (type === 'toc') {
      var tocBox = el('div', 'be-toc-preview');
      tocBox.innerHTML = '<strong>فهرست مطالب</strong><p>از عنوان‌های همین مطلب (عنوان ۱ و ۲ و ۳) لینک ساخته می‌شود و بالای متن می‌آید.</p>';
      container.appendChild(tocBox);
      return;
    }

    if (type === 'image') {
      var box = el('div', 'be-image');
      var actions = el('div', 'be-image-actions');
      var uploadBtn = el('label', 'btn light be-image-btn', 'آپلود از دستگاه');
      var fileIn = document.createElement('input');
      fileIn.type = 'file';
      fileIn.accept = 'image/*';
      fileIn.hidden = true;
      uploadBtn.appendChild(fileIn);
      var libBtn = el('button', 'btn light be-image-btn', 'از کتابخانه');
      libBtn.type = 'button';
      actions.appendChild(uploadBtn);
      actions.appendChild(libBtn);

      var url = el('input', 'be-input');
      url.type = 'text';
      url.dir = 'ltr';
      url.placeholder = 'media/uploads/… یا https://…';
      url.value = block.src || '';
      var alt = el('input', 'be-input');
      alt.placeholder = 'متن جایگزین (alt)';
      alt.value = block.alt || '';
      var cap = el('input', 'be-input');
      cap.placeholder = 'توضیح زیر تصویر (اختیاری)';
      cap.value = block.caption || '';
      var prev = el('div', 'be-image-prev');
      function resolveSrc(v) {
        v = (v || '').trim();
        if (!v) return '';
        if (/^https?:\/\//i.test(v)) return v;
        if (window.ShirazMedia && window.ShirazMedia.assetUrl) return window.ShirazMedia.assetUrl(v);
        return v;
      }
      function refreshPrev() {
        var src = resolveSrc(url.value);
        if (src) {
          prev.innerHTML = '<img src="' + escapeHtml(src) + '" alt="">';
          prev.hidden = false;
        } else {
          prev.innerHTML = '';
          prev.hidden = true;
        }
      }
      function sync() {
        api.updateBlock(block.id, {
          id: block.id,
          type: 'image',
          src: url.value.trim(),
          alt: alt.value.trim(),
          caption: cap.value.trim()
        });
        refreshPrev();
        api.syncTextarea();
      }
      function applyImage(pathOrUrl, displayUrl) {
        url.value = pathOrUrl || displayUrl || '';
        sync();
      }
      fileIn.addEventListener('change', function () {
        if (!fileIn.files || !fileIn.files[0]) return;
        if (!window.ShirazMedia || !window.ShirazMedia.upload) {
          alert('ماژول آپلود آماده نیست');
          return;
        }
        uploadBtn.classList.add('is-busy');
        window.ShirazMedia.upload(fileIn.files[0]).then(function (res) {
          applyImage(res.path, res.url);
          uploadBtn.classList.remove('is-busy');
        }).catch(function (err) {
          alert(err.message || 'آپلود ناموفق');
          uploadBtn.classList.remove('is-busy');
        });
        fileIn.value = '';
      });
      libBtn.addEventListener('click', function () {
        if (!window.ShirazMedia || !window.ShirazMedia.openLibrary) {
          alert('کتابخانه در دسترس نیست');
          return;
        }
        window.ShirazMedia.openLibrary('insert', function (item) {
          applyImage(item.path, item.url);
        });
      });
      url.addEventListener('input', sync);
      alt.addEventListener('input', sync);
      cap.addEventListener('input', sync);
      refreshPrev();
      box.appendChild(actions);
      box.appendChild(url);
      box.appendChild(alt);
      box.appendChild(cap);
      box.appendChild(prev);
      container.appendChild(box);
      return;
    }

    if (type === 'code' || type === 'html') {
      var ta = el('textarea', 'be-code');
      ta.dir = type === 'code' ? 'ltr' : 'ltr';
      ta.placeholder = type === 'code' ? 'کد…' : 'HTML خام…';
      ta.value = block.text || '';
      ta.addEventListener('input', function () {
        api.updateBlock(block.id, {
          id: block.id,
          type: type,
          text: ta.value
        });
        api.syncTextarea();
      });
      // auto height
      ta.addEventListener('input', function () {
        ta.style.height = 'auto';
        ta.style.height = Math.max(100, ta.scrollHeight) + 'px';
      });
      container.appendChild(ta);
      setTimeout(function () {
        ta.style.height = 'auto';
        ta.style.height = Math.max(100, ta.scrollHeight) + 'px';
      }, 0);
      return;
    }

    if (type === 'list' || type === 'olist') {
      var listTa = el('textarea', 'be-list');
      listTa.placeholder = 'هر خط یک مورد…';
      listTa.value = block.html || block.text || '';
      listTa.addEventListener('input', function () {
        api.updateBlock(block.id, {
          id: block.id,
          type: type,
          html: listTa.value
        });
        api.syncTextarea();
      });
      container.appendChild(listTa);
      return;
    }

    // contenteditable rich-ish text
    var ce = el('div', 'be-ce be-ce--' + type);
    ce.contentEditable = 'true';
    ce.dataset.placeholder = placeholderFor(type);
    ce.innerHTML = block.html || '';
    ce.addEventListener('input', function () {
      api.updateBlock(block.id, {
        id: block.id,
        type: type,
        html: ce.innerHTML
      });
      api.syncTextarea();
    });
    ce.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey && (type === 'h2' || type === 'h3' || type === 'h4')) {
        e.preventDefault();
        api.insertAfter(block.id, { id: uid(), type: 'paragraph', html: '' });
        api.focusNext(block.id);
      }
      // Ctrl/Cmd+B bold
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
        e.preventDefault();
        document.execCommand('bold');
      }
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'i') {
        e.preventDefault();
        document.execCommand('italic');
      }
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        var url = prompt('آدرس لینک:', 'https://');
        if (url) document.execCommand('createLink', false, url);
      }
    });
    container.appendChild(ce);
  }

  function placeholderFor(type) {
    switch (type) {
      case 'h2': return 'عنوان اصلی را بنویسید…';
      case 'h3': return 'زیرعنوان…';
      case 'h4': return 'عنوان کوچک…';
      case 'quote': return 'نقل‌قول…';
      default: return 'متن را بنویسید… برای خط جدید Enter';
    }
  }

  function mount(root) {
    var textarea = root.querySelector('textarea[name="body"], #post-body');
    if (!textarea) return;

    var initialHtml = textarea.value;
    var state = { blocks: htmlToBlocks(initialHtml) };

    var shell = el('div', 'be-shell');
    var tip = el('div', 'be-tip',
      'هر بخش یک <b>بلاک</b> است — مثل ویرگول. با <b>+</b> بلاک جدید بسازید، نوعش را عوض کنید، بکشید جابه‌جا کنید. ' +
      '<span class="be-tip-keys">Bold: Ctrl+B · Italic: Ctrl+I · لینک: Ctrl+K</span>'
    );

    var palette = el('div', 'be-palette');
    TYPES.forEach(function (t) {
      var b = el('button', 'be-chip', '<span class="be-chip-ico">' + t.icon + '</span>' + t.label);
      b.type = 'button';
      b.addEventListener('click', function () {
        if (t.id === 'toc') {
          var exists = state.blocks.some(function (x) { return x.type === 'toc'; });
          if (exists) {
            api.syncTextarea();
            return;
          }
          state.blocks.unshift({ id: uid(), type: 'toc' });
          api.rerender();
          api.syncTextarea();
          return;
        }
        var block = { id: uid(), type: t.id };
        if (t.id === 'image') { block.src = ''; block.alt = ''; block.caption = ''; }
        else if (t.id === 'code' || t.id === 'html') block.text = '';
        else if (t.id === 'hr') { /* nothing */ }
        else if (t.id === 'list' || t.id === 'olist') block.html = '';
        else block.html = '';
        state.blocks.push(block);
        api.rerender();
        api.focus(block.id);
        api.syncTextarea();
      });
      palette.appendChild(b);
    });

    var canvas = el('div', 'be-canvas');
    shell.appendChild(tip);
    shell.appendChild(palette);
    shell.appendChild(canvas);

    textarea.classList.add('be-source');
    textarea.setAttribute('aria-hidden', 'true');
    textarea.tabIndex = -1;
    textarea.style.position = 'absolute';
    textarea.style.left = '-9999px';
    textarea.style.height = '1px';
    textarea.style.width = '1px';
    textarea.style.opacity = '0';

    root.insertBefore(shell, textarea);

    var api = {
      count: function () { return state.blocks.length; },
      getBlock: function (id) {
        return state.blocks.find(function (b) { return b.id === id; });
      },
      updateBlock: function (id, data) {
        state.blocks = state.blocks.map(function (b) {
          return b.id === id ? Object.assign({}, b, data, { id: id }) : b;
        });
      },
      remove: function (id) {
        state.blocks = state.blocks.filter(function (b) { return b.id !== id; });
        if (!state.blocks.length) {
          state.blocks = [{ id: uid(), type: 'paragraph', html: '' }];
        }
        api.rerender();
        api.syncTextarea();
      },
      move: function (id, dir) {
        var i = state.blocks.findIndex(function (b) { return b.id === id; });
        var j = i + dir;
        if (i < 0 || j < 0 || j >= state.blocks.length) return;
        var tmp = state.blocks[i];
        state.blocks[i] = state.blocks[j];
        state.blocks[j] = tmp;
        api.rerender();
        api.syncTextarea();
      },
      moveTo: function (fromId, toId) {
        var from = state.blocks.findIndex(function (b) { return b.id === fromId; });
        var to = state.blocks.findIndex(function (b) { return b.id === toId; });
        if (from < 0 || to < 0 || from === to) return;
        var item = state.blocks.splice(from, 1)[0];
        state.blocks.splice(to, 0, item);
        api.rerender();
        api.syncTextarea();
      },
      insertAfter: function (id, block) {
        var i = state.blocks.findIndex(function (b) { return b.id === id; });
        if (i < 0) state.blocks.push(block);
        else state.blocks.splice(i + 1, 0, block);
        api.rerender();
        api.syncTextarea();
      },
      focus: function (id) {
        setTimeout(function () {
          var node = canvas.querySelector('.be-block[data-id="' + id + '"] .be-ce, .be-block[data-id="' + id + '"] textarea, .be-block[data-id="' + id + '"] input');
          if (node) node.focus();
        }, 20);
      },
      focusNext: function (id) {
        var i = state.blocks.findIndex(function (b) { return b.id === id; });
        if (i >= 0 && state.blocks[i + 1]) api.focus(state.blocks[i + 1].id);
      },
      syncTextarea: function () {
        textarea.value = blocksToHtml(state.blocks);
      },
      rerender: function () {
        canvas.innerHTML = '';
        state.blocks.forEach(function (b) {
          canvas.appendChild(createBlockEl(b, api));
        });
      }
    };

    api.rerender();
    api.syncTextarea();

    var form = root.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        api.syncTextarea();
      });
    }
  }

  function boot() {
    document.querySelectorAll('[data-block-editor]').forEach(function (root) {
      if (root.dataset.beMounted === '1') return;
      root.dataset.beMounted = '1';
      mount(root);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
  document.addEventListener('admin:navigated', boot);
  window.AdminBlocks = { boot: boot };
})();
