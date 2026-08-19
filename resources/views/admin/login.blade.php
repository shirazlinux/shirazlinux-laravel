<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="theme-color" content="#F1592D">
<title>ورود به پنل | شیرازلینوکس</title>
<link rel="shortcut icon" href="{{ asset('media/website/webicon320.png') }}" type="image/x-icon">
<link rel="icon" href="{{ asset('media/website/webicon320.png') }}" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --brand:#F1592D;
  --brand-dark:#c2410c;
  --brand-soft:#fff7ed;
  --brand-border:#fed7aa;
  --bg:#f4f1ec;
  --card:#fff;
  --text:#1c1917;
  --muted:#78716c;
  --border:#e7e0d8;
  --err:#b91c1c;
  --err-bg:#fef2f2;
  --err-border:#fecaca;
  --radius:20px;
  --shadow:0 18px 50px rgba(40,30,20,.12);
  --font:Vazirmatn,Tahoma,system-ui,sans-serif;
}
*{box-sizing:border-box}
html,body{margin:0;min-height:100%}
body{
  font-family:var(--font);
  color:var(--text);
  background:
    radial-gradient(1200px 600px at 100% -10%, rgba(241,89,45,.16), transparent 55%),
    radial-gradient(900px 500px at -10% 110%, rgba(194,65,12,.1), transparent 50%),
    linear-gradient(165deg, #faf7f3 0%, var(--bg) 45%, #efe8df 100%);
  min-height:100vh;
  display:grid;
  place-items:center;
  padding:1.5rem 1rem 2rem;
}
.shell{
  width:min(100%, 420px);
  display:flex;
  flex-direction:column;
  gap:1rem;
}
.brand-bar{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:.75rem;
  text-decoration:none;
  color:inherit;
}
.brand-bar img{
  height:44px;width:auto;max-width:130px;object-fit:contain;
  filter:brightness(0);
}
.brand-bar span{
  font-weight:800;font-size:1.05rem;color:var(--brand-dark);
  letter-spacing:-.01em;
}
.card{
  background:var(--card);
  border:1px solid var(--border);
  border-radius:var(--radius);
  padding:1.65rem 1.5rem 1.45rem;
  box-shadow:var(--shadow);
  position:relative;
  overflow:hidden;
}
.card::before{
  content:"";
  position:absolute;inset-inline:0;top:0;height:4px;
  background:linear-gradient(90deg, var(--brand) 0%, #fb923c 55%, #fdba74 100%);
}
.kicker{
  margin:0 0 .35rem;
  font-size:.78rem;font-weight:800;color:var(--brand);
  letter-spacing:.04em;
}
h1{
  margin:0 0 .35rem;
  font-size:1.45rem;font-weight:800;line-height:1.35;
  color:var(--text);
}
.lead{
  margin:0 0 1.25rem;
  color:var(--muted);font-size:.92rem;line-height:1.7;
}
.err{
  display:flex;align-items:flex-start;gap:.55rem;
  background:var(--err-bg);border:1px solid var(--err-border);
  color:var(--err);border-radius:12px;
  padding:.7rem .85rem;margin:0 0 1rem;
  font-size:.9rem;font-weight:600;line-height:1.55;
}
.err-ico{
  flex:0 0 auto;width:1.15rem;height:1.15rem;margin-top:.1rem;
  border-radius:999px;background:var(--err);color:#fff;
  display:grid;place-items:center;font-size:.72rem;font-weight:800;
}
.field{margin:0 0 .9rem}
.field label{
  display:block;margin:0 0 .4rem;
  font-weight:700;font-size:.88rem;color:#44403c;
}
.field input[type=email],
.field input[type=password]{
  width:100%;
  padding:.72rem .85rem;
  border:1.5px solid var(--border);
  border-radius:12px;
  font:inherit;font-size:.95rem;
  background:#fafaf9;
  color:var(--text);
  transition:border-color .15s, box-shadow .15s, background .15s;
}
.field input:hover{border-color:#d6d3d1;background:#fff}
.field input:focus{
  outline:none;
  border-color:#fdba74;
  background:#fff;
  box-shadow:0 0 0 4px rgba(241,89,45,.14);
}
.field input::placeholder{color:#a8a29e}
.row-remember{
  display:flex;align-items:center;justify-content:space-between;
  gap:.75rem;margin:.15rem 0 1.15rem;
}
.remember{
  display:inline-flex;align-items:center;gap:.5rem;
  margin:0;cursor:pointer;user-select:none;
  font-weight:600;font-size:.88rem;color:#57534e;
}
.remember input{
  position:absolute;opacity:0;pointer-events:none;width:0;height:0;
}
.remember-ui{
  width:18px;height:18px;border-radius:6px;
  border:1.5px solid #d6d3d1;background:#fff;
  display:inline-grid;place-items:center;
  transition:background .15s,border-color .15s;
  flex:0 0 auto;
}
.remember-ui::after{
  content:"";
  width:9px;height:5px;
  border-inline-start:2px solid #fff;
  border-bottom:2px solid #fff;
  transform:rotate(-45deg) translateY(-1px);
  opacity:0;
}
.remember input:checked + .remember-ui{
  background:var(--brand);border-color:var(--brand);
}
.remember input:checked + .remember-ui::after{opacity:1}
.remember input:focus-visible + .remember-ui{
  box-shadow:0 0 0 3px rgba(241,89,45,.2);
}
.btn{
  width:100%;
  border:0;
  background:linear-gradient(180deg, #ff6a3d 0%, var(--brand) 55%, #e04e24 100%);
  color:#fff;
  padding:.85rem 1rem;
  border-radius:12px;
  font:inherit;font-size:1rem;font-weight:800;
  cursor:pointer;
  box-shadow:0 8px 20px rgba(241,89,45,.28);
  transition:transform .12s, box-shadow .15s, filter .15s;
}
.btn:hover{
  filter:brightness(1.03);
  box-shadow:0 10px 26px rgba(241,89,45,.34);
}
.btn:active{transform:translateY(1px)}
.btn:focus-visible{
  outline:none;
  box-shadow:0 0 0 4px rgba(241,89,45,.25), 0 8px 20px rgba(241,89,45,.28);
}
.meta{
  margin-top:1.15rem;
  padding-top:1rem;
  border-top:1px solid #f0ebe4;
  display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.5rem;
  font-size:.84rem;color:var(--muted);
}
.meta a{
  color:var(--brand-dark);font-weight:700;text-decoration:none;
}
.meta a:hover{color:var(--brand);text-decoration:underline}
.hint{
  text-align:center;
  font-size:.8rem;color:#a8a29e;
  margin:.15rem 0 0;
}
@media (max-width:420px){
  .card{padding:1.35rem 1.15rem 1.25rem;border-radius:16px}
  h1{font-size:1.28rem}
}
</style>
</head>
<body>
<div class="shell">
  <a class="brand-bar" href="{{ route('home') }}" title="بازگشت به سایت">
    <img src="{{ asset('media/website/logo.png') }}" alt="شیرازلینوکس" width="120" height="44">
  </a>

  <div class="card">
    <p class="kicker">پنل مدیریت</p>
    <h1>ورود به پنل</h1>
    <p class="lead">برای مدیریت مطالب، تنظیمات و رسانه‌های سایت وارد شوید.</p>

    @if($errors->any())
      <div class="err" role="alert">
        <span class="err-ico" aria-hidden="true">!</span>
        <span>{{ $errors->first() }}</span>
      </div>
    @endif

    <form method="post" action="{{ route('admin.login.submit') }}" autocomplete="on">
      @csrf
      <div class="field">
        <label for="email">ایمیل</label>
        <input id="email" type="email" name="email"
               value="{{ old('email') }}"
               placeholder="you@example.com"
               required autofocus autocomplete="username">
      </div>
      <div class="field">
        <label for="password">رمز عبور</label>
        <input id="password" type="password" name="password"
               placeholder="••••••••"
               required autocomplete="current-password">
      </div>

      <div class="row-remember">
        <label class="remember">
          <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
          <span class="remember-ui" aria-hidden="true"></span>
          <span>مرا به خاطر بسپار</span>
        </label>
      </div>

      <button class="btn" type="submit">ورود به پنل</button>
    </form>

    <div class="meta">
      <span>دسترسی فقط برای مدیران</span>
      <a href="{{ route('home') }}">← بازگشت به سایت</a>
    </div>
  </div>

  <p class="hint">sudoshz.ir · شیرازلینوکس</p>
</div>
</body>
</html>
