@extends('layouts.admin')
@section('title', $post->exists ? 'ویرایش' : 'ایجاد')
@section('content')
@php
  $isPage = old('type', $post->type ?? 'post') === 'page';
  $status = old('status', $post->status ?? 'draft');
@endphp

<form class="composer" method="post" action="{{ $post->exists ? route('admin.posts.update',$post) : route('admin.posts.store') }}">
@csrf
@if($post->exists) @method('PUT') @endif

<div class="composer-top">
  <div>
    <p class="composer-kicker">{{ $isPage ? 'صفحه' : 'مطلب' }}</p>
    <h1 class="composer-title">{{ $post->exists ? 'ویرایش' : 'نوشتن' }}</h1>
  </div>
  <div class="composer-top-actions">
    @if($post->exists && !empty($previewUrl))
      <a class="btn light" href="{{ $previewUrl }}" target="_blank">پیش‌نمایش</a>
    @endif
    @if($post->exists && $post->status==='published')
      <a class="btn light" href="{{ url('/'.$post->slug) }}" target="_blank">نمایش عمومی</a>
    @endif
    <button class="btn" type="submit">ذخیره</button>
  </div>
</div>

<div class="composer-grid">
  {{-- Main writing column --}}
  <div class="composer-main">
    <div class="composer-paper">
      <label class="sr-only" for="post-title">عنوان</label>
      <input class="composer-headline" id="post-title" name="title"
             value="{{ old('title',$post->title) }}" required
             placeholder="عنوان مطلب را بنویسید…">

      <div class="composer-slug-row">
        <span class="composer-slug-label">آدرس</span>
        <input class="composer-slug" name="slug" value="{{ old('slug',$post->slug) }}"
               dir="ltr" placeholder="auto-from-title">
      </div>

      <label class="composer-sublabel" for="post-excerpt">خلاصه</label>
      <textarea id="post-excerpt" name="excerpt" class="composer-excerpt"
                placeholder="یک یا دو جمله برای کارت‌ها و نتایج جستجو…">{{ old('excerpt',$post->excerpt) }}</textarea>

      <div class="editor-wrap" data-block-editor>
        <div class="editor-label-row">
          <label for="post-body">متن</label>
          <span class="editor-hint">بلاک‌به‌بلاک — مثل ویرگول</span>
        </div>
        <textarea id="post-body" name="body">{{ old('body',$post->body) }}</textarea>
      </div>
    </div>
  </div>

  {{-- Right settings rail --}}
  <aside class="composer-rail" aria-label="تنظیمات انتشار">
    <section class="rail-card rail-card--publish">
      <header class="rail-card-head">
        <h2>انتشار</h2>
        <span class="rail-status rail-status--{{ $status }}">
          @if($status==='published') منتشر
          @elseif($status==='hidden') مخفی
          @else پیش‌نویس
          @endif
        </span>
      </header>

      <div class="rail-field">
        <label for="post-status">وضعیت</label>
        <div class="rail-seg" data-seg="status">
          @foreach(['draft'=>'پیش‌نویس','published'=>'منتشر','hidden'=>'مخفی'] as $k=>$v)
            <label class="rail-seg-item">
              <input type="radio" name="status" value="{{ $k }}" @checked($status===$k)>
              <span>{{ $v }}</span>
            </label>
          @endforeach
        </div>
      </div>

      <div class="rail-field">
        <label for="post-type">نوع</label>
        <div class="rail-seg" data-seg="type">
          <label class="rail-seg-item">
            <input type="radio" name="type" value="post" @checked(old('type',$post->type ?? 'post')==='post')>
            <span>مطلب</span>
          </label>
          <label class="rail-seg-item">
            <input type="radio" name="type" value="page" @checked(old('type',$post->type ?? 'post')==='page')>
            <span>صفحه</span>
          </label>
        </div>
      </div>

      <div class="rail-field">
        <label for="post-author">نویسنده</label>
        <select id="post-author" name="author_id">
          <option value="">— انتخاب —</option>
          @foreach($authors as $a)
            <option value="{{ $a->id }}" @selected((string)old('author_id',$post->author_id)===(string)$a->id)>{{ $a->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="rail-field">
        <label>تاریخ انتشار <small>(شمسی)</small></label>
        <div class="jdp-field" data-jdp>
          <input class="jdp-input" type="text" name="published_at" id="published_at"
                 dir="ltr" autocomplete="off" placeholder="1404/05/26 14:30"
                 value="{{ old('published_at', $post->published_at ? jdate_input($post->published_at) : '') }}">
          <button type="button" class="jdp-btn" data-jdp-toggle title="تقویم شمسی" aria-label="تقویم شمسی">📅</button>
        </div>
        <p class="rail-hint">خالی = هنگام انتشار خودکار</p>
      </div>

      <div class="rail-toggles">
        <label class="rail-toggle">
          <input type="checkbox" name="featured" value="1" @checked(old('featured',(bool)$post->featured))>
          <span class="rail-toggle-ui" aria-hidden="true"></span>
          <span class="rail-toggle-text">
            <strong>ویژه</strong>
            <small>نمایش برجسته</small>
          </span>
        </label>
        <label class="rail-toggle">
          <input type="checkbox" name="exclude_homepage" value="1" @checked(old('exclude_homepage',(bool)$post->exclude_homepage))>
          <span class="rail-toggle-ui" aria-hidden="true"></span>
          <span class="rail-toggle-text">
            <strong>حذف از خانه</strong>
            <small>در صفحه اصلی نیاید</small>
          </span>
        </label>
      </div>

      <button class="btn rail-save" type="submit">ذخیره تغییرات</button>
    </section>

    <section class="rail-card" data-media-panel>
      <header class="rail-card-head">
        <h2>تصاویر</h2>
        <button type="button" class="btn light media-lib-open" data-media-open style="padding:.28rem .55rem;font-size:.78rem">کتابخانه</button>
      </header>

      <div class="media-drop" data-media-drop data-media-target="featured">
        <input type="file" accept="image/*" hidden data-media-file>
        <div class="media-drop-inner">
          <strong>تصویر شاخص</strong>
          <span>بکشید و رها کنید یا کلیک کنید</span>
          <small>JPG، PNG، WebP — تا ۸MB</small>
        </div>
        <div class="rail-cover-prev" data-cover-prev @if(!old('featured_image',$post->featured_image)) hidden @endif>
          @if(old('featured_image',$post->featured_image))
            @php
              $fi = old('featured_image',$post->featured_image);
              $fiUrl = str_starts_with($fi, 'http') ? $fi : asset(ltrim($fi, '/'));
            @endphp
            <img src="{{ $fiUrl }}" alt="">
          @endif
        </div>
      </div>
      <input name="featured_image" value="{{ old('featured_image',$post->featured_image) }}"
             dir="ltr" placeholder="یا مسیر/URL دستی"
             data-cover-input class="media-path-input">
      <p class="rail-hint">برای داخل متن: بلاک «تصویر» یا دکمه کتابخانه</p>
    </section>

    <section class="rail-card rail-card--tags">
      <div class="tag-picker tag-picker--rail" data-tag-picker>
        <div class="tag-picker-head">
          <label for="tag-picker-filter">تگ‌ها</label>
          <span class="tag-picker-count" data-tag-count></span>
        </div>
        <input type="search" id="tag-picker-filter" class="tag-picker-filter" placeholder="جستجو…" autocomplete="off" data-tag-filter>
        <div class="tag-picker-list" role="group" aria-label="انتخاب تگ‌ها">
          @foreach($tags as $tag)
            @php $checked = in_array($tag->id, old('tags', $selectedTags)); @endphp
            <label class="tag-chip{{ $checked ? ' is-on' : '' }}" data-tag-name="{{ $tag->name }}">
              <input type="checkbox" name="tags[]" value="{{ $tag->id }}" @checked($checked)>
              <span class="tag-chip-dot" aria-hidden="true"></span>
              <span class="tag-chip-text">{{ $tag->name }}</span>
            </label>
          @endforeach
        </div>
        @if($tags->isEmpty())
          <p class="tag-picker-empty">تگی نیست. از منوی تگ‌ها بسازید.</p>
        @endif
      </div>
    </section>

    <section class="rail-card">
      <header class="rail-card-head"><h2>سئو</h2></header>
      <div class="rail-field">
        <label>Meta title</label>
        <input name="meta_title" value="{{ old('meta_title',$post->meta_title) }}" placeholder="اختیاری">
      </div>
      <div class="rail-field" style="margin-bottom:0">
        <label>Meta description</label>
        <textarea name="meta_description" class="rail-meta-desc" placeholder="اختیاری">{{ old('meta_description',$post->meta_description) }}</textarea>
      </div>
    </section>

    @if($post->exists && isset($revisions) && $revisions->isNotEmpty())
      <section class="rail-card">
        <header class="rail-card-head"><h2>تاریخچه ویرایش</h2></header>
        <ul class="rev-list">
          @foreach($revisions as $rev)
            <li>
              <strong>{{ jdate($rev->created_at, 'Y/m/d H:i') }}</strong>
              <span>{{ $rev->user?->name ?? 'سیستم' }} · {{ $rev->status }}</span>
            </li>
          @endforeach
        </ul>
      </section>
    @endif

    @if($post->exists)
      <button type="button" class="rail-danger-link" form="post-delete-form"
              onclick="if(confirm('حذف شود؟')) document.getElementById('post-delete-form').submit()">
        حذف این {{ $isPage ? 'صفحه' : 'مطلب' }}
      </button>
    @endif
  </aside>
</div>
</form>

@if($post->exists)
<form id="post-delete-form" method="post" action="{{ route('admin.posts.destroy',$post) }}" class="sr-only" aria-hidden="true">
@csrf @method('DELETE')
</form>
@endif

{{-- Media library modal --}}
<div class="media-modal" data-media-modal hidden>
  <div class="media-modal-backdrop" data-media-close></div>
  <div class="media-modal-panel" role="dialog" aria-modal="true" aria-label="کتابخانه تصاویر">
    <header class="media-modal-head">
      <h2>تصاویر مطلب</h2>
      <button type="button" class="be-tool" data-media-close aria-label="بستن">×</button>
    </header>
    <div class="media-modal-toolbar">
      <label class="btn light media-upload-btn">
        آپلود تصویر
        <input type="file" accept="image/*" hidden data-media-modal-file>
      </label>
      <span class="jdp-hint" data-media-status style="margin:0">انتخاب برای تصویر شاخص یا درج در متن</span>
    </div>
    <div class="media-grid" data-media-grid></div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  window.SHIRAZ_MEDIA = {
    uploadUrl: @json(route('admin.media.store')),
    listUrl: @json(route('admin.media.index')),
    csrf: @json(csrf_token()),
    assetBase: @json(rtrim(url('/'), '/') . '/')
  };
</script>
<script src="{{ asset('js/admin-blocks.js') }}?v=20260818j" defer></script>
<script src="{{ asset('js/admin-media.js') }}?v=20260818j" defer></script>
<script>
(function () {
  function bootTagPicker(root) {
    var filter = root.querySelector('[data-tag-filter]');
    var countEl = root.querySelector('[data-tag-count]');
    var chips = Array.prototype.slice.call(root.querySelectorAll('.tag-chip'));
    function updateCount() {
      var n = chips.filter(function (c) {
        var inp = c.querySelector('input');
        return inp && inp.checked;
      }).length;
      if (countEl) countEl.textContent = n ? (n + ' انتخاب') : 'بدون تگ';
    }
    chips.forEach(function (chip) {
      var inp = chip.querySelector('input');
      if (!inp) return;
      function sync() {
        chip.classList.toggle('is-on', inp.checked);
        updateCount();
      }
      inp.addEventListener('change', sync);
    });
    if (filter) {
      filter.addEventListener('input', function () {
        var q = (filter.value || '').trim().toLowerCase();
        chips.forEach(function (chip) {
          var name = (chip.getAttribute('data-tag-name') || '').toLowerCase();
          chip.classList.toggle('is-hidden', q !== '' && name.indexOf(q) === -1);
        });
      });
    }
    updateCount();
  }

  function bootSegLabels() {
    document.querySelectorAll('.rail-seg').forEach(function (seg) {
      seg.querySelectorAll('input').forEach(function (inp) {
        inp.addEventListener('change', function () {
          if (seg.getAttribute('data-seg') === 'status') {
            var badge = document.querySelector('.rail-status');
            if (!badge) return;
            badge.className = 'rail-status rail-status--' + inp.value;
            badge.textContent = inp.value === 'published' ? 'منتشر' : (inp.value === 'hidden' ? 'مخفی' : 'پیش‌نویس');
          }
        });
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-tag-picker]').forEach(bootTagPicker);
    bootSegLabels();
  });
})();
</script>
@endpush
