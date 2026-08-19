@extends('layouts.admin')
@section('title', $type==='page' ? 'صفحات' : 'مطالب')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap">
  <h1 style="margin:0">{{ $type==='page' ? 'صفحات' : 'مطالب' }}</h1>
  <a class="btn" href="{{ route('admin.posts.create',['type'=>$type]) }}">+ جدید</a>
</div>

<form class="card" method="get" action="{{ route('admin.posts.index') }}" style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.55rem;align-items:end">
  <input type="hidden" name="type" value="{{ $type }}">
  <div style="flex:1;min-width:160px">
    <label>جستجو</label>
    <input type="search" name="q" value="{{ $q }}" placeholder="عنوان، اسلاگ…">
  </div>
  <div style="min-width:140px">
    <label>وضعیت</label>
    <select name="status">
      <option value="">همه</option>
      @foreach(['published'=>'منتشر','draft'=>'پیش‌نویس','hidden'=>'مخفی'] as $k=>$v)
        <option value="{{ $k }}" @selected($status===$k)>{{ $v }}</option>
      @endforeach
    </select>
  </div>
  <button class="btn light" type="submit">فیلتر</button>
</form>

<form method="post" action="{{ route('admin.posts.bulk') }}" id="bulk-form">
  @csrf
  <input type="hidden" name="type" value="{{ $type }}">
  <div class="card" style="margin-top:.75rem">
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center;margin-bottom:.75rem">
      <strong>عملیات گروهی:</strong>
      <select name="action" required>
        <option value="">انتخاب…</option>
        <option value="publish">انتشار</option>
        <option value="draft">پیش‌نویس</option>
        <option value="hide">مخفی</option>
        <option value="delete">حذف</option>
      </select>
      <button class="btn" type="submit" onclick="return confirm('اجرا شود؟')">اجرا</button>
    </div>
    <table>
      <tr>
        <th style="width:2rem"><input type="checkbox" id="check-all"></th>
        <th>عنوان</th><th>اسلاگ</th><th>وضعیت</th><th>نویسنده</th><th>انتشار</th><th></th>
      </tr>
      @foreach($posts as $p)
        <tr>
          <td><input type="checkbox" name="ids[]" value="{{ $p->id }}" class="row-check"></td>
          <td>{{ $p->title }}</td>
          <td dir="ltr">{{ $p->slug }}</td>
          <td>{{ $p->status }}</td>
          <td>{{ $p->author?->name }}</td>
          <td style="white-space:nowrap">{{ $p->published_at ? jdate($p->published_at) : '—' }}</td>
          <td>
            @if($p->status==='published')
              <a href="{{ url('/'.$p->slug) }}" target="_blank">نمایش</a> |
            @endif
            <a href="{{ route('admin.posts.edit',$p) }}">ویرایش</a>
          </td>
        </tr>
      @endforeach
    </table>
    <div style="margin-top:1rem">{!! $posts->links() !!}</div>
  </div>
</form>
@endsection
@push('scripts')
<script>
document.getElementById('check-all')?.addEventListener('change', function () {
  document.querySelectorAll('.row-check').forEach(function (c) { c.checked = this.checked; }.bind(this));
});
</script>
@endpush
