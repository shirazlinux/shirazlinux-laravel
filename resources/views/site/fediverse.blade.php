@extends('layouts.site')
@php
  use App\Support\Seo;
  $pageTitle = 'فدیورس: شبکه اجتماعی آزاد و غیرمتمرکز';
  $metaTitle = 'فدیورس چیست؟ شبکه اجتماعی آزاد و غیرمتمرکز';
  $metaDesc = 'فدیورس شبکه اجتماعی غیرمتمرکز روی پروتکل آزاد اکتیویتی‌پاب است، بدون تبلیغ و الگوریتم. جایگزین‌های آزاد، سرورهای فارسی و شروع در سه قدم.';
  $pageOg = Seo::absolute('media/posts/fediverse/cover.jpg');
  $pageUrl = url('/fediverse');
  $crumbItems = [
    ['name' => 'خانه', 'url' => url('/')],
    ['name' => $pageTitle, 'url' => $pageUrl],
  ];
  $faqs = [
    ['q' => 'فدیورس رایگان است؟', 'a' => 'ساختن حساب در بیشتر سرورها رایگان است. هزینه سرورها را معمولا مدیران و کاربران با حمایت داوطلبانه می‌پردازند. اگر سروری را دوست داشتید، حمایت از آن کمک می‌کند سرپا بماند.'],
    ['q' => 'اگر سرورم تعطیل شود چه می‌شود؟', 'a' => 'این ریسک واقعی است. برای همین بهتر است سروری با مدیران فعال و قوانین روشن انتخاب کنید. ماستادون امکان انتقال حساب و دنبال‌کننده‌ها به سرور دیگر را دارد و می‌شود از داده‌ها خروجی گرفت. پس هر چند وقت یک بار خروجی بگیرید.'],
    ['q' => 'برای دنبال کردن کسی باید در همان سرور باشم؟', 'a' => 'نه. شما از هر سرور فدیورس می‌توانید کاربران سرورهای دیگر را دنبال کنید. کافی است نشانی کامل او را مثل @Shirazlinux@mastodon.social در جستجوی سرور خودتان وارد کنید.'],
    ['q' => 'فدیورس روی اینترنت داخلی ایران کار می‌کند؟', 'a' => 'جواب قطعی نداریم و وضعیت هر سرور فرق دارد. بعضی سرورهای خارجی ممکن است فیلتر باشند و در زمان قطعی اینترنت بین‌الملل، ارتباط با سرورهای خارج از ایران هم قطع می‌شود. حتی اگر سروری در آن شرایط باز بماند، احتمالا فقط کاربران همان سرور یا سرورهای در دسترس را می‌بینید و فدراسیون با بقیه شبکه کامل کار نمی‌کند. پیش از انتخاب سرور، وضعیت دسترسی را از مدیران همان سرور بپرسید.'],
    ['q' => 'با ماستادون حریم خصوصی کامل دارم؟', 'a' => 'نه کامل. نوشته‌های عمومی را همه می‌بینند و مدیران سرور به داده‌های حساب شما دسترسی فنی دارند. پیام مستقیم در ماستادون رمزگذاری سرتاسری ندارد. برای گفتگوی خصوصی از پیام‌رسان‌های آزاد و رمزگذاری‌شده استفاده کنید.'],
    ['q' => 'آیا فدیورس همان بلواسکای است؟', 'a' => 'نه. بلواسکای (Bluesky) پروتکل دیگری به نام AT Protocol دارد و بخشی از فدیورس نیست، هرچند ابزارهایی برای پل زدن بین این دو شبکه ساخته شده است.'],
  ];
@endphp
@section('title', $metaTitle)
@section('meta_description', $metaDesc)
@section('og_type', 'website')
@section('og_image', $pageOg)
@section('canonical', $pageUrl)

@push('seo')
<link rel="stylesheet" href="{{ asset('css/fediverse.css') }}?v=20261024">
@if(setting('seo_jsonld_enabled', true))
@php
  $pageLd = [
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $pageTitle,
    'headline' => $pageTitle,
    'url' => $pageUrl,
    'description' => $metaDesc,
    'inLanguage' => 'fa-IR',
    'isPartOf' => ['@type' => 'WebSite', 'name' => setting('site_name'), 'url' => url('/')],
    'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $pageOg],
  ];
  $faqLd = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => collect($faqs)->map(fn ($f) => [
      '@type' => 'Question',
      'name' => $f['q'],
      'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
    ])->all(),
  ];
  $crumbs = Seo::breadcrumbJsonLd($crumbItems);
@endphp
<script type="application/ld+json">{!! json_encode($pageLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
<script type="application/ld+json">{!! json_encode($crumbs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endif
@endpush

@section('content')
<div class="container">
    @include('site.partials.breadcrumbs', ['items' => $crumbItems])
    <article class="article fedi-page">
        <h1>{{ $pageTitle }}</h1>
        <figure class="article-cover">
            <img src="{{ asset('media/posts/fediverse/cover.jpg') }}" alt="ماموت ماستادون و نمادهای نرم‌افزارهای فدیورس روی شاخه درختی کنار رودخانه"
                 width="1200" height="630" decoding="async" fetchpriority="high">
            <figcaption><em>تصویر کاور: <a href="https://commons.wikimedia.org/wiki/File:My_Neighbor_Mastodon_(and_Fediverse).jpg" target="_blank" rel="noopener">My Neighbor Mastodon (and Fediverse)</a>، اثر <a href="https://www.davidrevoy.com/article1043/my-neighbor-mastodon-and-fediverse" target="_blank" rel="noopener">David Revoy</a>، <a href="https://creativecommons.org/licenses/by-sa/4.0/" target="_blank" rel="noopener">CC BY-SA 4.0</a>، برش داده شده</em></figcaption>
        </figure>
        <div class="article-body prose">
            <p>شبکه‌های اجتماعی بزرگ مال یک شرکت‌اند. همان شرکت تصمیم می‌گیرد چه ببینید، چه چیزی پنهان شود و داده‌هایتان کجا برود. فدیورس (Fediverse) راه دیگری است. شبکه‌ای از هزاران سرور مستقل که با هم حرف می‌زنند و هیچ‌کس مالک کل آن نیست.</p>

            <nav class="post-toc" aria-label="فهرست مطالب">
                <p class="post-toc-title">فهرست مطالب</p>
                <ol>
                    <li><a href="#what">فدیورس چیست؟</a></li>
                    <li><a href="#why">چرا برای آزادی کاربر مهم است؟</a></li>
                    <li><a href="#alternatives">جایگزین‌های آزاد</a></li>
                    <li><a href="#persian">سرورهای فارسی</a></li>
                    <li><a href="#start">شروع در سه قدم</a></li>
                    <li><a href="#shirazlinux">شیرازلینوکس در فدیورس</a></li>
                    <li><a href="#articles">مطالب مرتبط</a></li>
                    <li><a href="#faq">پرسش‌های رایج</a></li>
                </ol>
            </nav>

            <h2 id="what">فدیورس چیست؟</h2>
            <p>اسم فدیورس از ترکیب فدراسیون و یونیورس آمده. یعنی جهانی از سرورهای جدا که به هم وصل‌اند. هر سرور را یک فرد، یک جامعه یا یک گروه کوچک اداره می‌کند و قوانین خودش را دارد. ولی کاربرانش می‌توانند با کاربران سرورهای دیگر گفتگو کنند، درست مثل ایمیل که از جیمیل به هر سرویس دیگری هم می‌رسد.</p>
            <p>این ارتباط با یک پروتکل آزاد و استاندارد به نام اکتیویتی‌پاب (ActivityPub) انجام می‌شود که کنسرسیوم وب جهانی (W3C) آن را منتشر کرده. هر نرم‌افزاری که این پروتکل را بفهمد می‌تواند به شبکه بپیوندد.</p>
            <p>در فدیورس تبلیغ هدفمند و الگوریتمی که برای نگه داشتن شما طراحی شده باشد پیدا نمی‌کنید. خط زمانی به ترتیب زمان است و فقط چیزی را می‌بینید که خودتان دنبال کرده‌اید.</p>

            <h2 id="why">چرا برای آزادی کاربر مهم است؟</h2>
            <p>در <a href="{{ url('/what-is-free-software') }}">نرم‌افزار آزاد</a> حرف اصلی این است که کاربر باید کنترل ابزارش را داشته باشد، نه سازنده. شبکه اجتماعی هم یک ابزار است. وقتی همه چیز روی سرورهای یک شرکت است، کاربر هیچ کنترلی ندارد. حسابش ممکن است بی‌توضیح بسته شود، قوانین یک‌شبه عوض شود و داده‌هایش برای تبلیغ فروخته شود.</p>
            <p>فدیورس این رابطه را برعکس می‌کند. نرم‌افزارهایش آزادند و کدشان در دسترس همه است. می‌توانید سرورتان را انتخاب کنید، اگر راضی نبودید به سرور دیگری بروید و حتی سرور خودتان را راه بیندازید. داده‌هایتان در اختیار یک غول تبلیغاتی نیست و می‌توانید از آن‌ها خروجی بگیرید.</p>
            <p>این همان چیزی است که جنبش نرم‌افزار آزاد از روز اول می‌خواست. کاربرانی که به هم وصل‌اند، بدون این‌که کسی وسطشان بایستد و قانون بگذارد.</p>

            <h2 id="alternatives">جایگزین‌های آزاد</h2>
            <p>برای بیشتر شبکه‌های آشنا یک جایگزین آزاد در فدیورس هست. همه با هم حرف می‌زنند، پس مثلا از ماستادون هم می‌شود ویدیوهای یک کانال پیرتیوب را دنبال کرد.</p>
            <div class="fedi-alts">
                <a class="fedi-alt" href="https://joinmastodon.org/" target="_blank" rel="noopener">
                    <strong>ماستادون (Mastodon)</strong>
                    <span class="fedi-alt-instead">به جای ایکس</span>
                    <span>نوشته‌های کوتاه، هشتگ و گفتگو. شناخته‌شده‌ترین نرم‌افزار فدیورس.</span>
                </a>
                <a class="fedi-alt" href="https://pixelfed.org/" target="_blank" rel="noopener">
                    <strong>پیکسل‌فد (Pixelfed)</strong>
                    <span class="fedi-alt-instead">به جای اینستاگرام</span>
                    <span>اشتراک عکس، بدون تبلیغ و با خط زمانی به ترتیب زمان.</span>
                </a>
                <a class="fedi-alt" href="https://joinpeertube.org/" target="_blank" rel="noopener">
                    <strong>پیرتیوب (PeerTube)</strong>
                    <span class="fedi-alt-instead">به جای یوتیوب</span>
                    <span>ویدیو و پخش زنده روی سرورهای مستقل.</span>
                </a>
                <a class="fedi-alt" href="https://join-lemmy.org/" target="_blank" rel="noopener">
                    <strong>لمی (Lemmy)</strong>
                    <span class="fedi-alt-instead">به جای ردیت</span>
                    <span>انجمن‌های موضوعی با رای دادن و بحث.</span>
                </a>
            </div>

            <h2 id="persian">سرورهای فارسی</h2>
            <p>چند سرور ماستادون با رابط و جامعه فارسی‌زبان هم هست. اگر می‌خواهید بیشتر با فارسی‌زبان‌ها در ارتباط باشید، این‌ها جای خوبی برای شروع‌اند:</p>
            <ul>
                <li><a href="https://persadon.com/" target="_blank" rel="noopener">persadon.com</a> (پرسادون)</li>
                <li><a href="https://khiar.net/" target="_blank" rel="noopener">khiar.net</a> (خیار)</li>
            </ul>
            <p>قبل از ثبت‌نام، صفحه قوانین و درباره هر سرور را بخوانید. سرورهای دیگر را هم می‌توانید در <a href="https://joinmastodon.org/servers" target="_blank" rel="noopener">فهرست سرورهای ماستادون</a> پیدا کنید.</p>

            <h2 id="start">شروع در سه قدم</h2>
            <ol class="fedi-steps">
                <li><strong>یک سرور انتخاب کنید.</strong> سروری که قوانینش را قبول دارید و مدیرانش فعال‌اند. نگران انتخاب اشتباه نباشید، بعدا می‌شود جابه‌جا شد.</li>
                <li><strong>حساب بسازید.</strong> نام کاربری، ایمیل و رمز. نشانی شما چیزی مثل @name@server می‌شود. برای موبایل هم برنامه‌های آزاد زیادی هست.</li>
                <li><strong>دنبال کنید.</strong> چند هشتگ و چند حساب مورد علاقه‌تان را پیدا کنید. شبکه با دنبال کردن آدم‌ها جان می‌گیرد، نه با الگوریتم.</li>
            </ol>

            <h2 id="shirazlinux">شیرازلینوکس در فدیورس</h2>
            <div class="fedi-follow">
                <div>
                    <p class="fedi-follow-handle" dir="ltr">@Shirazlinux@mastodon.social</p>
                    <p>خبرهای جامعه، نشست‌ها و پست‌های نرم‌افزار آزاد را در ماستادون هم منتشر می‌کنیم. از هر سرور فدیورس می‌توانید دنبالمان کنید.</p>
                </div>
                <a class="btn-brand fedi-follow-btn" href="https://mastodon.social/@Shirazlinux" target="_blank" rel="me noopener">دنبال کردن در ماستادون</a>
            </div>

            <h2 id="articles">مطالب مرتبط</h2>
            @if($fediPosts->isNotEmpty())
                <div class="grid cards-history fedi-posts">
                    @foreach($fediPosts as $post)
                        @include('site.partials.card', ['post' => $post, 'compact' => true, 'imageAspect' => 'wide'])
                    @endforeach
                </div>
            @else
                <p class="muted">هنوز مطلبی با این برچسب منتشر نشده است.</p>
            @endif
            <p><a href="{{ route('tags.show', 'fediverse') }}">همه مطالب فدیورس</a></p>

            <h2 id="faq">پرسش‌های رایج</h2>
            @foreach($faqs as $faq)
                <h3>{{ $faq['q'] }}</h3>
                <p>{{ $faq['a'] }}</p>
            @endforeach
        </div>
    </article>
</div>
@endsection
