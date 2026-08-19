@php
  $slide = $slide ?? [];
  $img = $slide['image'] ?? '';
  $imgUrl = $img === '' ? '' : (str_starts_with($img, 'http') ? $img : asset(ltrim($img, '/')));
  $enabled = array_key_exists('enabled', $slide) ? (bool) $slide['enabled'] : true;
@endphp
<div class="slide-row" data-slide-row>
  <div class="slide-row-head">
    <strong>اسلاید <span data-slide-num>{{ is_numeric($i) ? ((int)$i + 1) : $i }}</span></strong>
    <div class="slide-row-actions">
      <button type="button" class="btn light" data-slide-up style="padding:.25rem .45rem;font-size:.8rem" title="بالا">↑</button>
      <button type="button" class="btn light" data-slide-down style="padding:.25rem .45rem;font-size:.8rem" title="پایین">↓</button>
      <label class="slide-enabled">
        <input type="checkbox" name="slides[{{ $i }}][enabled]" value="1" @checked($enabled)>
        فعال
      </label>
      <button type="button" class="btn light" data-slide-remove style="padding:.25rem .55rem;font-size:.8rem">حذف</button>
    </div>
  </div>
  <div class="slide-row-grid">
    <div class="slide-row-media">
      <div class="slide-prev" data-slide-prev @if($imgUrl==='') hidden @endif>
        @if($imgUrl!=='')
          <img src="{{ $imgUrl }}" alt="">
        @endif
      </div>
      <label class="btn light" style="width:100%;text-align:center;cursor:pointer">
        انتخاب تصویر
        <input type="file" name="slides[{{ $i }}][file]" accept="image/*" hidden data-slide-file>
      </label>
      <input type="text" name="slides[{{ $i }}][image]" value="{{ $img }}" dir="ltr" placeholder="media/slider/… یا URL">
    </div>
    <div class="slide-row-fields">
      <label>عنوان</label>
      <input type="text" name="slides[{{ $i }}][title]" value="{{ $slide['title'] ?? '' }}" placeholder="عنوان روی اسلاید">
      <label>متن جایگزین (alt)</label>
      <input type="text" name="slides[{{ $i }}][alt]" value="{{ $slide['alt'] ?? '' }}" placeholder="توضیح تصویر برای دسترس‌پذیری/سئو">
      <label>لینک</label>
      <input type="text" name="slides[{{ $i }}][url]" value="{{ $slide['url'] ?? '' }}" dir="ltr" placeholder="/tags/event یا https://…">
    </div>
  </div>
</div>
