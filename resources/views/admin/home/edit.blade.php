@extends('layouts.admin')
@section('title', 'صفحه اصلی و اسلایدر')
@section('content')
@php
  $v = fn ($k, $d = '') => old($k, $settings[$k] ?? $d);
  $slides = old('slides', $slides);
@endphp

<div class="settings-page" style="max-width:980px">
  <div class="settings-hero">
    <p class="composer-kicker">مدیریت وب‌سایت</p>
    <h1 class="composer-title" style="margin:0">صفحه اصلی و اسلایدر</h1>
    <p class="settings-lead">متن معرفی خانه، نمایش بخش‌ها و اسلایدر بالای صفحه را اینجا ویرایش کنید.</p>
  </div>

  <form class="settings-card" method="post" action="{{ route('admin.home.update') }}" enctype="multipart/form-data">
    @csrf

    <h2 class="settings-section-title">معرفی صفحه اصلی</h2>
    <div class="settings-grid">
      <div class="settings-field settings-field--full">
        <label>عنوان بزرگ</label>
        <input name="home_intro_title" value="{{ $v('home_intro_title') }}">
      </div>
      <div class="settings-field settings-field--full">
        <label>متن معرفی</label>
        <textarea name="home_intro_text" rows="4">{{ $v('home_intro_text') }}</textarea>
      </div>
    </div>

    <h3 class="settings-subtitle">نمایش بخش‌ها</h3>
    <div class="settings-toggles">
      @foreach([
        'home_show_slider' => ['اسلایدر','اسلایدهای بالای صفحه'],
        'home_show_edu' => ['آموزش نرم‌افزار آزاد','چهار آزادی'],
        'home_show_history' => ['تاریخچه','کارت‌های تاریخ جنبش'],
        'home_show_projects' => ['پروژه‌ها','پروژه‌های لانچ‌شده'],
        'home_show_videos' => ['ویدیوها','بخش ویدیو'],
      ] as $key => [$t,$d])
        <label class="rail-toggle settings-toggle">
          <input type="checkbox" name="{{ $key }}" value="1" @checked(old($key, $settings[$key] ?? true))>
          <span class="rail-toggle-ui" aria-hidden="true"></span>
          <span class="rail-toggle-text"><strong>{{ $t }}</strong><small>{{ $d }}</small></span>
        </label>
      @endforeach
    </div>

    <h3 class="settings-subtitle">اسلایدر</h3>
    <p class="settings-help">حداکثر ۱۲ اسلاید. تصویر را آپلود کنید یا مسیر/URL بگذارید. لینک می‌تواند داخلی باشد مثل <code dir="ltr">/tags/event</code>.</p>

    <div id="slides-list" class="slides-list">
      @foreach($slides as $i => $slide)
        @include('admin.home.partials.slide-row', ['i' => $i, 'slide' => $slide])
      @endforeach
    </div>

    <template id="slide-row-template">
      @include('admin.home.partials.slide-row', ['i' => '__I__', 'slide' => [
        'image' => '', 'title' => '', 'alt' => '', 'url' => '', 'enabled' => true,
      ]])
    </template>

    <div style="margin:.85rem 0 0;display:flex;flex-wrap:wrap;gap:.5rem">
      <button type="button" class="btn light" id="add-slide">+ اسلاید جدید</button>
      <button type="submit" class="btn">ذخیره صفحه اصلی</button>
      <a class="btn light" href="{{ route('home') }}" target="_blank">مشاهده خانه</a>
    </div>
  </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var list = document.getElementById('slides-list');
  var tpl = document.getElementById('slide-row-template');
  var addBtn = document.getElementById('add-slide');
  if (!list || !tpl || !addBtn) return;

  function reindex() {
    Array.prototype.forEach.call(list.querySelectorAll('[data-slide-row]'), function (row, idx) {
      row.querySelectorAll('[name]').forEach(function (el) {
        el.name = el.name.replace(/slides\[[^\]]+\]/, 'slides[' + idx + ']');
      });
      var badge = row.querySelector('[data-slide-num]');
      if (badge) badge.textContent = (idx + 1);
    });
  }

  addBtn.addEventListener('click', function () {
    var html = tpl.innerHTML.replace(/__I__/g, String(list.children.length));
    var wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    list.appendChild(wrap.firstElementChild);
    reindex();
  });

  list.addEventListener('click', function (e) {
    var rm = e.target.closest('[data-slide-remove]');
    if (rm) {
      var row = rm.closest('[data-slide-row]');
      if (row) row.remove();
      reindex();
      return;
    }
    var up = e.target.closest('[data-slide-up]');
    var down = e.target.closest('[data-slide-down]');
    var row2 = e.target.closest('[data-slide-row]');
    if (!row2) return;
    if (up && row2.previousElementSibling) {
      list.insertBefore(row2, row2.previousElementSibling);
      reindex();
    }
    if (down && row2.nextElementSibling) {
      list.insertBefore(row2.nextElementSibling, row2);
      reindex();
    }
  });

  list.addEventListener('change', function (e) {
    var input = e.target;
    if (!input.matches('input[type=file][data-slide-file]')) return;
    var row = input.closest('[data-slide-row]');
    var prev = row && row.querySelector('[data-slide-prev]');
    if (input.files && input.files[0] && prev) {
      var url = URL.createObjectURL(input.files[0]);
      prev.hidden = false;
      prev.innerHTML = '<img src="' + url + '" alt="">';
    }
  });
})();
</script>
@endpush
