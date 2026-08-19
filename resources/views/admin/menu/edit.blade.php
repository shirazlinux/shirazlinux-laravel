@extends('layouts.admin')
@section('title','منوی سایت')
@section('content')
<div class="settings-page" style="max-width:900px">
  <div class="settings-hero">
    <p class="composer-kicker">ساختار سایت</p>
    <h1 class="composer-title" style="margin:0">منوی اصلی</h1>
    <p class="settings-lead">آیتم‌های لینک یا گروه (زیرمنو). ترتیب از بالا به پایین همان ترتیب نمایش است.</p>
  </div>

  <form class="settings-card" method="post" action="{{ route('admin.menu.update') }}" id="menu-form">
    @csrf
    <input type="hidden" name="items_json" id="items_json">

    <div id="menu-builder" class="menu-builder"></div>

    <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:1rem">
      <button type="button" class="btn light" id="add-link">+ لینک</button>
      <button type="button" class="btn light" id="add-group">+ گروه زیرمنو</button>
      <button type="submit" class="btn">ذخیره منو</button>
    </div>
  </form>

  <form method="post" action="{{ route('admin.menu.reset') }}" style="margin-top:.8rem" onsubmit="return confirm('منو به پیش‌فرض برگردد؟')">
    @csrf
    <button class="btn gray" type="submit">بازنشانی به پیش‌فرض</button>
  </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var initial = @json($items);
  var root = document.getElementById('menu-builder');
  var form = document.getElementById('menu-form');
  var hidden = document.getElementById('items_json');
  var state = Array.isArray(initial) ? JSON.parse(JSON.stringify(initial)) : [];

  function esc(s) {
    return String(s || '').replace(/[&<>"']/g, function (c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
    });
  }

  function render() {
    root.innerHTML = '';
    state.forEach(function (item, idx) {
      var card = document.createElement('div');
      card.className = 'menu-item-card';
      var type = item.type === 'group' ? 'group' : 'link';
      var head = '<div class="menu-item-head"><strong>' + (type === 'group' ? 'گروه' : 'لینک') + ' #' + (idx+1) + '</strong>' +
        '<div class="menu-item-tools">' +
        '<button type="button" class="btn light" data-up="' + idx + '" style="padding:.2rem .45rem">↑</button>' +
        '<button type="button" class="btn light" data-down="' + idx + '" style="padding:.2rem .45rem">↓</button>' +
        '<button type="button" class="btn red" data-del="' + idx + '" style="padding:.2rem .45rem">حذف</button></div></div>';
      var body = '<label>عنوان</label><input data-f="label" data-i="' + idx + '" value="' + esc(item.label) + '">';
      if (type === 'link') {
        body += '<label>آدرس</label><input data-f="url" data-i="' + idx + '" dir="ltr" value="' + esc(item.url || '/') + '">';
        body += '<label class="slide-enabled"><input type="checkbox" data-f="cta" data-i="' + idx + '"' + (item.cta ? ' checked' : '') + '> دکمهٔ برجسته (CTA)</label>';
      } else {
        body += '<div class="menu-children" data-children="' + idx + '">';
        (item.children || []).forEach(function (ch, cidx) {
          body += '<div class="menu-child-row">' +
            '<input data-cf="label" data-i="' + idx + '" data-c="' + cidx + '" placeholder="عنوان" value="' + esc(ch.label) + '">' +
            '<input data-cf="url" data-i="' + idx + '" data-c="' + cidx + '" dir="ltr" placeholder="/path" value="' + esc(ch.url) + '">' +
            '<button type="button" class="btn light" data-cdel="' + idx + '" data-c="' + cidx + '" style="padding:.25rem .45rem">×</button></div>';
        });
        body += '<button type="button" class="btn light" data-cadd="' + idx + '" style="margin-top:.4rem;padding:.3rem .55rem;font-size:.82rem">+ زیرآیتم</button></div>';
      }
      card.innerHTML = head + body;
      root.appendChild(card);
    });
  }

  root.addEventListener('input', function (e) {
    var t = e.target;
    if (t.matches('[data-f]')) {
      var i = +t.getAttribute('data-i');
      var f = t.getAttribute('data-f');
      if (f === 'cta') state[i].cta = t.checked;
      else state[i][f] = t.value;
    }
    if (t.matches('[data-cf]')) {
      var i2 = +t.getAttribute('data-i');
      var c = +t.getAttribute('data-c');
      var cf = t.getAttribute('data-cf');
      state[i2].children = state[i2].children || [];
      state[i2].children[c][cf] = t.value;
    }
  });
  root.addEventListener('click', function (e) {
    var t = e.target.closest('button');
    if (!t) return;
    if (t.hasAttribute('data-del')) {
      state.splice(+t.getAttribute('data-del'), 1);
      render();
    }
    if (t.hasAttribute('data-up')) {
      var i = +t.getAttribute('data-up');
      if (i > 0) { var tmp = state[i-1]; state[i-1]=state[i]; state[i]=tmp; render(); }
    }
    if (t.hasAttribute('data-down')) {
      var j = +t.getAttribute('data-down');
      if (j < state.length-1) { var t2 = state[j+1]; state[j+1]=state[j]; state[j]=t2; render(); }
    }
    if (t.hasAttribute('data-cadd')) {
      var k = +t.getAttribute('data-cadd');
      state[k].children = state[k].children || [];
      state[k].children.push({label:'', url:'/'});
      render();
    }
    if (t.hasAttribute('data-cdel')) {
      var a = +t.getAttribute('data-cdel');
      var b = +t.getAttribute('data-c');
      state[a].children.splice(b, 1);
      render();
    }
  });

  document.getElementById('add-link').addEventListener('click', function () {
    state.push({type:'link', label:'آیتم جدید', url:'/', cta:false});
    render();
  });
  document.getElementById('add-group').addEventListener('click', function () {
    state.push({type:'group', label:'گروه جدید', children:[{label:'زیرآیتم', url:'/'}]});
    render();
  });
  form.addEventListener('submit', function () {
    hidden.value = JSON.stringify(state);
  });
  render();
})();
</script>
@endpush
