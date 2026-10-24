@extends('layouts.admin')
@section('title','کادر اجرایی')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
  <h1 style="margin:0">کادر اجرایی</h1>
  <a class="btn" href="{{ route('admin.crew.create') }}">+ عضو جدید</a>
</div>

<form class="card" method="post" action="{{ route('admin.crew.intro') }}" style="margin-top:1rem">
  @csrf
  <h2 style="margin:0 0 .7rem;font-size:1.05rem">متن معرفی صفحه</h2>
  <label>عنوان صفحه</label>
  <input name="crew_page_title" value="{{ old('crew_page_title', setting('crew_page_title', 'تیم اجرایی شیرازلینوکس')) }}">
  <label>معرفی</label>
  <textarea name="crew_page_intro" rows="5">{{ old('crew_page_intro', setting('crew_page_intro')) }}</textarea>
  <button class="btn" type="submit" style="margin-top:.7rem">ذخیره معرفی</button>
</form>

<div class="card">
<table>
<tr><th></th><th>نام</th><th>نقش</th><th>ترتیب</th><th>وضعیت</th><th></th></tr>
@forelse($crew as $member)
<tr>
  <td>
    @if($member->photoUrl())
      <img src="{{ $member->photoUrl() }}" alt="" width="40" height="40" style="width:40px;height:40px;object-fit:cover;border-radius:999px">
    @endif
  </td>
  <td>{{ $member->name }}</td>
  <td>{{ $member->role ?: '—' }}</td>
  <td>{{ $member->sort }}</td>
  <td>{{ $member->former ? 'سابق' : ($member->active ? 'فعلی' : 'مخفی') }}</td>
  <td><a href="{{ route('admin.crew.edit', $member) }}">ویرایش</a></td>
</tr>
@empty
<tr><td colspan="6">هنوز عضوی نیست.</td></tr>
@endforelse
</table>
</div>
@endsection
