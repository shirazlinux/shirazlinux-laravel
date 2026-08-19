<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>{{ url('/') }}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>
@foreach($posts as $p)
  <url>
    <loc>{{ url('/'.$p->slug) }}</loc>
    <lastmod>{{ $p->updated_at?->toAtomString() ?? now()->toAtomString() }}</lastmod>
    <changefreq>{{ $p->type === 'page' ? 'monthly' : 'weekly' }}</changefreq>
  </url>
@endforeach
@foreach($tags as $t)
  <url><loc>{{ url('/tags/'.$t->slug) }}</loc><changefreq>weekly</changefreq></url>
@endforeach
</urlset>
