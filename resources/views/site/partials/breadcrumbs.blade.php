{{-- Visual breadcrumbs (also paired with BreadcrumbList JSON-LD on pages that need it) --}}
@if(!empty($items) && is_array($items))
<nav class="breadcrumbs" aria-label="مسیر صفحه">
  <ol class="breadcrumbs-list">
    @foreach($items as $i => $item)
      <li class="breadcrumbs-item">
        @if(!$loop->last && !empty($item['url']))
          <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
          <span class="breadcrumbs-sep" aria-hidden="true">/</span>
        @else
          <span aria-current="page">{{ $item['name'] }}</span>
        @endif
      </li>
    @endforeach
  </ol>
</nav>
@endif
