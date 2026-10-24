@if($crew->isNotEmpty())
<section class="crew-block" aria-labelledby="{{ $crewHeadingId ?? 'crew-heading' }}">
  @if(!empty($crewTitle) || !empty($crewLead))
    <div class="section-head">
      <h2 class="section-title" id="{{ $crewHeadingId ?? 'crew-heading' }}">{{ $crewTitle ?? 'کادر اجرایی' }}</h2>
      @if(!empty($crewMoreUrl))
        <a class="section-more" href="{{ $crewMoreUrl }}">همه</a>
      @endif
    </div>
    @if(!empty($crewLead))
      <p class="muted section-lead">{{ $crewLead }}</p>
    @endif
  @endif
  <div class="crew-grid">
    @foreach($crew as $member)
      @php $profile = method_exists($member, 'profileUrl') ? $member->profileUrl() : null; @endphp
      <article class="crew-card">
        @if($profile)
          <a class="crew-card-link" href="{{ $profile }}">
        @endif
        @if($member->photoUrl())
          <img class="crew-card-photo" src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" width="160" height="160" loading="lazy" decoding="async">
        @else
          <div class="crew-card-photo crew-card-photo--empty" aria-hidden="true">{{ mb_substr($member->name, 0, 1) }}</div>
        @endif
        <h3 class="crew-card-name">{{ $member->name }}</h3>
        @if($member->role)
          <p class="crew-card-role">{{ $member->role }}</p>
        @endif
        @if($member->former && $member->tenure)
          <p class="crew-card-tenure">مدت همکاری: {{ $member->tenure }}</p>
        @endif
        @if(!empty($showBio) && $member->bio)
          <p class="crew-card-bio">{{ $member->bio }}</p>
        @endif
        @if($profile)
          </a>
        @endif
        @php
          $links = array_filter([
            'وب' => $member->website ?? null,
            'تلگرام' => $member->telegram ?? null,
            'ماستادون' => $member->mastodon ?? null,
          ]);
        @endphp
        @if($links)
          <p class="crew-card-links">
            @foreach($links as $label => $url)
              <a href="{{ $url }}" target="_blank" rel="noopener">{{ $label }}</a>
            @endforeach
          </p>
        @endif
      </article>
    @endforeach
  </div>
</section>
@endif
