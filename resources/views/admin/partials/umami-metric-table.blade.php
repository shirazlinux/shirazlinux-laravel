@php
  $fa = fn ($n) => \App\Support\JalaliDate::toPersianDigits((string) number_format((int) $n));
  $rows = $rows ?? [];
  $max = max(1, ...array_map(fn ($r) => (int) ($r['y'] ?? 0), $rows ?: [['y' => 0]]));
@endphp
@if(empty($rows))
  <p class="jdp-hint" style="margin:0">{{ $empty ?? '—' }}</p>
@else
  <table class="umami-metric">
    @foreach($rows as $row)
      <tr>
        <td class="umami-metric-name" title="{{ $row['x'] }}">{{ \Illuminate\Support\Str::limit($row['x'] === '' ? '(direct)' : $row['x'], 42) }}</td>
        <td class="umami-metric-bar-cell">
          <div class="umami-metric-bar" style="width:{{ max(6, (int) round(((int)$row['y'] / $max) * 100)) }}%"></div>
        </td>
        <td class="umami-metric-val">{{ $fa($row['y']) }}</td>
      </tr>
    @endforeach
  </table>
@endif
