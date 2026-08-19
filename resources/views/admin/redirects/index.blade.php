@extends('layouts.admin')
@section('title','ریدایرکت‌ها')
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
  <div>
    <h1 style="margin:0">ریدایرکت‌ها</h1>
    <p class="jdp-hint" style="margin:.35rem 0 0">هدایت ۳۰۱/۳۰۲ برای اسلاگ‌های قدیمی و لینک‌های شکسته</p>
  </div>
</div>

<form class="card" method="post" action="{{ route('admin.redirects.store') }}">
  @csrf
  <div class="row">
    <div>
      <label>از مسیر</label>
      <input name="from_path" dir="ltr" placeholder="/old-slug یا old-slug" required>
    </div>
    <div>
      <label>به آدرس</label>
      <input name="to_url" dir="ltr" placeholder="/new-slug یا https://…" required>
    </div>
  </div>
  <div class="row">
    <div>
      <label>کد</label>
      <select name="status_code">
        <option value="301">301 دائمی</option>
        <option value="302">302 موقت</option>
      </select>
    </div>
    <div style="display:flex;align-items:flex-end">
      <button class="btn" type="submit">افزودن / به‌روزرسانی</button>
    </div>
  </div>
</form>

<div class="card">
  <table>
    <tr><th>از</th><th>به</th><th>کد</th><th></th></tr>
    @foreach($redirects as $r)
      <tr>
        <td dir="ltr">{{ $r->from_path }}</td>
        <td dir="ltr">{{ $r->to_url }}</td>
        <td>{{ $r->status_code }}</td>
        <td>
          <form method="post" action="{{ route('admin.redirects.destroy', $r) }}" onsubmit="return confirm('حذف؟')">
            @csrf @method('DELETE')
            <button class="btn red" type="submit" style="padding:.25rem .5rem;font-size:.8rem">حذف</button>
          </form>
        </td>
      </tr>
    @endforeach
  </table>
  <div style="margin-top:1rem">{!! $redirects->links() !!}</div>
</div>
@endsection
