@extends('layouts.admin')
@section('title','کاربران و نقش‌ها')
@section('content')
<h1>کاربران و نقش‌ها</h1>
<p class="jdp-hint" style="margin-top:0">برای ورود به پنل باید اینجا کاربر بسازید. صفحه نویسندگان فقط نام نمایشی روی سایت است و ورود ندارد. تا وقتی با حساب مدیر داخل هستید همه بخش‌ها باز است. پنل محدود نویسنده را فقط بعد از خروج و ورود با ایمیل همان کاربر می‌بینید. کاربر تازه بار اول باید رمز موقت را عوض کند، بعد کارش شروع می‌شود.</p>

<div class="role-guide">
  <div class="role-guide-card role-guide-card--writer">
    <h2>نویسنده</h2>
    <p>وارد پنل می‌شود، مطلب می‌نویسد و منتشر می‌کند. فقط مطالب خودش را می‌بیند و ویرایش می‌کند. تنظیمات، نظرات، کاربران و بقیه بخش‌ها برایش بسته است.</p>
  </div>
  <div class="role-guide-card">
    <h2>مدیر</h2>
    <p>به همه بخش‌های پنل دسترسی دارد: صفحات، نظرات، تنظیمات، رسانه، کاربران و بقیه.</p>
  </div>
</div>

<div class="card">
  <h2 style="margin-top:0">ساخت کاربر نویسنده</h2>
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
        <div class="role-pick">
          <label class="role-pick-item">
            <input type="radio" name="role" value="writer" checked>
            <span><strong>نویسنده</strong><small>فقط مطالب خودش</small></span>
          </label>
          <label class="role-pick-item">
            <input type="radio" name="role" value="admin">
            <span><strong>مدیر</strong><small>دسترسی کامل پنل</small></span>
          </label>
        </div>
      </div>
    </div>
    <button class="btn" type="submit" style="margin-top:.85rem">ایجاد کاربر</button>
  </form>
</div>

<div class="card">
  <h2 style="margin-top:0">کاربران فعلی</h2>
  @foreach($users as $u)
    <article class="user-card">
      <div class="user-card-head">
        <strong>{{ $u->name }}</strong>
        <span class="user-role-pill {{ $u->isWriter() ? 'is-writer' : 'is-admin' }}">{{ $u->roleLabel() }}</span>
        <span dir="ltr" style="font-size:.85rem;color:#78716c">{{ $u->email }}</span>
      </div>
      <form method="post" action="{{ route('admin.users.update', $u) }}">
        @csrf @method('PUT')
        <div class="row">
          <div><label>نام</label><input name="name" value="{{ $u->name }}" required></div>
          <div><label>ایمیل</label><input type="email" name="email" value="{{ $u->email }}" dir="ltr" required></div>
          <div><label>رمز جدید (اختیاری)</label><input type="password" name="password" minlength="8" placeholder="خالی = بدون تغییر"></div>
        </div>
        <label>نقش</label>
        <div class="role-pick" style="max-width:36rem">
          <label class="role-pick-item">
            <input type="radio" name="role" value="writer" @checked($u->isWriter())>
            <span><strong>نویسنده</strong><small>فقط مطالب خودش</small></span>
          </label>
          <label class="role-pick-item">
            <input type="radio" name="role" value="admin" @checked($u->isAdmin())>
            <span><strong>مدیر</strong><small>دسترسی کامل پنل</small></span>
          </label>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.75rem">
          <button class="btn" type="submit">ذخیره</button>
        </div>
      </form>
      @if($u->id !== auth()->id())
        <form method="post" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('حذف کاربر؟')" style="margin-top:.45rem">
          @csrf @method('DELETE')
          <button class="btn red" type="submit" style="padding:.3rem .55rem;font-size:.8rem">حذف</button>
        </form>
      @endif
    </article>
  @endforeach
</div>
@endsection
