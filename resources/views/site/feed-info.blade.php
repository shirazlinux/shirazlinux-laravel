@extends('layouts.site')
@section('title','خوراک و نقشه سایت')
@section('content')
<div class="container"><div class="article">
<h1>خوراک و نقشه سایت</h1>
<ul>
<li><a href="{{ route('feed') }}">feed.xml (RSS)</a></li>
<li><a href="{{ route('rss') }}">rss.xml</a></li>
<li><a href="{{ route('sitemap') }}">sitemap.xml</a></li>
<li><a href="{{ url('/robots.txt') }}">robots.txt</a></li>
</ul>
</div></div>
@endsection
