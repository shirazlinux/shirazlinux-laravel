<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="theme-color" content="#F1592D">
<title>عوض کردن رمز | شیرازلینوکس</title>
<link rel="shortcut icon" href="{{ asset('media/website/webicon320.png') }}" type="image/x-icon">
<link rel="icon" href="{{ asset('media/website/webicon320.png') }}" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --brand:#F1592D;--brand-dark:#c2410c;--bg:#f4f1ec;--card:#fff;
  --text:#1c1917;--muted:#78716c;--border:#e7e0d8;
  --err:#b91c1c;--err-bg:#fef2f2;--err-border:#fecaca;
  --radius:20px;--shadow:0 18px 50px rgba(40,30,20,.12);
  --font:Vazirmatn,Tahoma,system-ui,sans-serif;
}
*{box-sizing:border-box}
html,body{margin:0;min-height:100%}
body{
  font-family:var(--font);color:var(--text);
  background:
    radial-gradient(1200px 600px at 100% -10%, rgba(241,89,45,.16), transparent 55%),
    linear-gradient(165deg, #faf7f3 0%, var(--bg) 45%, #efe8df 100%);
  min-height:100vh;display:grid;place-items:center;padding:1.5rem 1rem 2rem;
}
.shell{width:min(100%,420px);display:flex;flex-direction:column;gap:1rem}
.brand-bar{display:flex;align-items:center;justify-content:center;text-decoration:none;color:inherit}
.brand-bar img{height:44px;width:auto;max-width:130px;object-fit:contain;filter:brightness(0)}
.card{
  background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
  padding:1.65rem 1.5rem 1.45rem;box-shadow:var(--shadow);position:relative;overflow:hidden;
}
.card::before{
  content:"";position:absolute;inset-inline:0;top:0;height:4px;
  background:linear-gradient(90deg, var(--brand) 0%, #fb923c 55%, #fdba74 100%);
}
.kicker{margin:0 0 .35rem;font-size:.78rem;font-weight:800;color:var(--brand)}
h1{margin:0 0 .35rem;font-size:1.45rem;font-weight:800;line-height:1.35}
.lead{margin:0 0 1.25rem;color:var(--muted);font-size:.92rem;line-height:1.7}
.err{
  display:flex;align-items:flex-start;gap:.55rem;
  background:var(--err-bg);border:1px solid var(--err-border);color:var(--err);
  border-radius:12px;padding:.7rem .85rem;margin:0 0 1rem;font-size:.9rem;font-weight:600;line-height:1.55;
}
.field{margin:0 0 .9rem}
.field label{display:block;margin:0 0 .4rem;font-weight:700;font-size:.88rem;color:#44403c}
.field input[type=password]{
  width:100%;padding:.72rem .85rem;border:1.5px solid var(--border);border-radius:12px;
  font:inherit;font-size:.95rem;background:#fafaf9;color:var(--text);
  direction:ltr;text-align:left;unicode-bidi:isolate;
}
.field input:focus{outline:none;border-color:#fdba74;background:#fff;box-shadow:0 0 0 4px rgba(241,89,45,.14)}
.btn{
  width:100%;border:0;background:linear-gradient(180deg,#ff6a3d 0%,var(--brand) 55%,#e04e24 100%);
  color:#fff;padding:.85rem 1rem;border-radius:12px;font:inherit;font-size:1rem;font-weight:800;
  cursor:pointer;box-shadow:0 8px 20px rgba(241,89,45,.28);
}
.meta{margin-top:1.15rem;padding-top:1rem;border-top:1px solid #f0ebe4;font-size:.84rem;color:var(--muted)}
.meta button{
  background:none;border:0;padding:0;color:#c2410c;font:inherit;font-weight:700;cursor:pointer;
}
.hint{text-align:center;font-size:.8rem;color:#a8a29e;margin:.15rem 0 0}
</style>
</head>
<body>
<div class="shell">
  <a class="brand-bar" href="{{ route('home') }}" title="بازگشت به سایت">
    <img src="{{ asset('media/website/logo.png') }}" alt="شیرازلینوکس" width="120" height="44">
  </a>
  <div class="card">
    <p class="kicker">ورود اول</p>
    <h1>رمز را عوض کنید</h1>
    <p class="lead">قبل از شروع کار در پنل، یک رمز جدید برای خودتان بگذارید. رمز موقت را نگه ندارید.</p>
    @if($errors->any())
      <div class="err" role="alert">{{ $errors->first() }}</div>
    @endif
    <form method="post" action="{{ route('admin.password.update') }}" autocomplete="on">
      @csrf
      <div class="field">
        <label for="current_password">رمز فعلی</label>
        <input id="current_password" type="password" name="current_password" dir="ltr"
               required autofocus autocomplete="current-password">
      </div>
      <div class="field">
        <label for="password">رمز جدید</label>
        <input id="password" type="password" name="password" dir="ltr"
               required minlength="8" autocomplete="new-password">
      </div>
      <div class="field">
        <label for="password_confirmation">تکرار رمز جدید</label>
        <input id="password_confirmation" type="password" name="password_confirmation" dir="ltr"
               required minlength="8" autocomplete="new-password">
      </div>
      <button class="btn" type="submit">ذخیره و ورود به پنل</button>
    </form>
    <div class="meta">
      <form method="post" action="{{ route('admin.logout') }}">@csrf
        <button type="submit">خروج</button>
      </form>
    </div>
  </div>
  <p class="hint">sudoshz.ir · شیرازلینوکس</p>
</div>
</body>
</html>
