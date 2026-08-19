@extends('layouts.admin')
@section('title','نویسندگان')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
  <h1 style="margin:0">نویسندگان</h1>
  <a class="btn" href="{{ route('admin.authors.create') }}">+ نویسنده جدید</a>
</div>
<div class="card" style="margin-top:1rem">
<table>
<tr><th>نام</th><th>اسلاگ</th><th>مطالب</th><th></th></tr>
@foreach($authors as $author)
<tr>
  <td>{{ $author->name }}</td>
  <td dir="ltr">{{ $author->slug }}</td>
  <td>{{ $author->posts_count }}</td>
  <td>
    <a href="{{ route('authors.show', $author->slug) }}" target="_blank">نمایش</a> |
    <a href="{{ route('admin.authors.edit', $author) }}">ویرایش</a>
  </td>
</tr>
@endforeach
</table>
<div style="margin-top:1rem">{!! $authors->links() !!}</div>
</div>
@endsection
