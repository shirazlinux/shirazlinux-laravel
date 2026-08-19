@extends('layouts.admin')
@section('title', 'آمار بازدید')
@section('content')
@php
  $ranges = [
    '24h' => '۲۴ ساعت',
    '7d' => '۷ روز',
    '30d' => '۳۰ روز',
    '90d' => '۹۰ روز',
  ];
  $fa = fn ($n) => \App\Support\JalaliDate::toPersianDigits((string) number_format((int) $n));
  $s = $local['stats'] ?? ['pageviews'=>0,'visitors'=>0,'visits'=>0,'bounces'=>0];
  $series = $local['pageviews'] ?? [];
  $maxY = max(1, ...array_map(fn ($r) => (int) ($r['y'] ?? 0), $series ?: [['y' => 0]]));
@endphp
<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:1rem">
  <div>
    <h1 style="margin:0">آمار بازدید</h1>
    <p class="jdp-hint" style="margin:.35rem 0 0">شمارش روی همین سایت (بدون نیاز به توکن Umami)</p>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:.4rem;align-items:center">
    @foreach($ranges as $k => $label)
      <a class="btn {{ $range === $k ? '' : 'light' }}" href="{{ route('admin.stats', ['range' => $k]) }}" style="padding:.35rem .7rem;font-size:.88rem">{{ $label }}</a>
    @endforeach
    <a class="btn gray" href="{{ $umamiUrl }}" target="_blank" rel="noopener" style="padding:.35rem .7rem;font-size:.88rem">ورود به Umami ↗</a>
  </div>
</div>

<div class="stats" style="margin:0 0 1rem">
  <div class="stat"><b>{{ $fa($s['pageviews'] ?? 0) }}</b>بازدید صفحه</div>
  <div class="stat"><b>{{ $fa($s['visitors'] ?? 0) }}</b>بازدیدکننده یکتا</div>
  <div class="stat"><b>{{ $fa($s['visits'] ?? 0) }}</b>نشست</div>
  <div class="stat"><b>{{ $fa($s['bounces'] ?? 0) }}</b>تک‌صفحه (تقریبی)</div>
</div>

<div class="card">
  <h2 style="margin-top:0">روند بازدید — {{ $ranges[$range] ?? $range }}</h2>
  @if(empty($series) || collect($series)->sum('y') === 0)
    <p class="jdp-hint" style="margin:0">هنوز بازدیدی ثبت نشده. با باز کردن چند صفحهٔ عمومی سایت، از همین لحظه آمار جمع می‌شود.</p>
  @else
    <div class="umami-bars" dir="ltr">
      @foreach($series as $row)
        @php $h = max(4, (int) round(((int)$row['y'] / $maxY) * 120)); @endphp
        <div class="umami-bar-col" title="{{ $row['x'] }}: {{ $row['y'] }}">
          <div class="umami-bar" style="height:{{ $h }}px"></div>
          <span class="umami-bar-y">{{ $row['y'] }}</span>
          <span class="umami-bar-x">{{ \Illuminate\Support\Str::limit(preg_replace('/T.*/','', (string)$row['x']), 10, '') }}</span>
        </div>
      @endforeach
    </div>
  @endif
</div>

<div class="row" style="margin-top:1rem">
  <div class="card" style="margin:0">
    <h2 style="margin-top:0">صفحات پربازدید</h2>
    @include('admin.partials.umami-metric-table', ['rows' => $local['pages'] ?? [], 'empty' => 'هنوز صفحه‌ای نیست'])
  </div>
  <div class="card" style="margin:0">
    <h2 style="margin-top:0">ارجاع‌دهنده‌ها</h2>
    @include('admin.partials.umami-metric-table', ['rows' => $local['referrers'] ?? [], 'empty' => 'ارجاع خارجی نیست'])
  </div>
</div>

<div class="card" style="margin-top:1rem">
  <h2 style="margin-top:0">Umami (umami.sudoshz.ir)</h2>
  <p class="jdp-hint" style="margin:0 0 .65rem">
    ردیاب Umami روی سایت فعال است (Website ID:
    <code dir="ltr">{{ $websiteId }}</code>).
    آمار پیشرفتهٔ Umami داخل همان پنل با <strong>ورود به حساب Umami</strong> دیده می‌شود —
    Website ID به‌تنهایی برای API کافی نیست.
  </p>
  <a class="btn light" href="{{ $umamiUrl }}" target="_blank" rel="noopener">باز کردن پنل Umami</a>
  @if($umamiConfigured && ($umami['ok'] ?? false))
    <p class="jdp-hint" style="margin:.75rem 0 0;color:#047857">توکن API هم وصل است؛ در صورت نیاز می‌توانیم دادهٔ Umami را هم این‌جا ادغام کنیم.</p>
  @endif
</div>
@endsection
