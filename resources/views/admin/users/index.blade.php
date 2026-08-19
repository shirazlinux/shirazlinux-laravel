@extends('layouts.admin')
@section('title','کاربران')
@section('content')
<h1>کاربران ادمین</h1>
<div class="card">
  <h2 style="margin-top:0">کاربر جدید</h2>
  <form method="post" action="{{ route('admin.users.store') }}">
    @csrf
    <div class="row">
      <div><label>نام</label><input name="name" required></div>
      <div><label>ایمیل</label><input type="email" name="email" dir="ltr" required></div>
    </div>
    <div class="row">
      <div><label>رمز</label><input type="password" name="password" required minlength="8"></div>
      <div>
        <label>نقش</label>
        <select name="role">
          <option value="admin">مدیر</option>
          <option value="editor">ویرایشگر</option>
        </select>
      </div>
    </div>
    <button class="btn" type="submit">ایجاد</button>
  </form>
</div>

<div class="card">
  <table>
    <tr><th>نام</th><th>ایمیل</th><th>نقش</th><th>تغییر</th></tr>
    @foreach($users as $u)
      <tr>
        <td colspan="4" style="padding:0;border:0">
          <form method="post" action="{{ route('admin.users.update', $u) }}" class="user-edit-row">
            @csrf @method('PUT')
            <div class="row" style="padding:.55rem .2rem;align-items:end">
              <div><label>نام</label><input name="name" value="{{ $u->name }}" required></div>
              <div><label>ایمیل</label><input type="email" name="email" value="{{ $u->email }}" dir="ltr" required></div>
              <div><label>رمز جدید (اختیاری)</label><input type="password" name="password" minlength="8" placeholder="خالی = بدون تغییر"></div>
              <div>
                <label>نقش</label>
                <select name="role">
                  <option value="admin" @selected(($u->role ?? 'admin')==='admin')>مدیر</option>
                  <option value="editor" @selected(($u->role ?? '')==='editor')>ویرایشگر</option>
                </select>
              </div>
              <div style="display:flex;gap:.35rem;flex-wrap:wrap">
                <button class="btn" type="submit" style="padding:.4rem .7rem">ذخیره</button>
              </div>
            </div>
          </form>
          @if($u->id !== auth()->id())
            <form method="post" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('حذف کاربر؟')" style="padding:0 .2rem .6rem">
              @csrf @method('DELETE')
              <button class="btn red" type="submit" style="padding:.3rem .55rem;font-size:.8rem">حذف</button>
            </form>
          @endif
        </td>
      </tr>
    @endforeach
  </table>
</div>
@endsection
