{{-- معرفی کوتاه نرم‌افزار آزاد در صفحهٔ اصلی --}}
<section class="edu-block edu-block--compact" aria-label="نرم‌افزار آزاد چیست">
    <h2 class="main-heading">نرم‌افزار آزاد چیست؟</h2>
    <p class="edu-subhead">
        <a href="{{ url('/what-is-free-software') }}">نرم‌افزار آزاد</a>
        نرم‌افزاری است که چهار آزادی مهم به تو می‌دهد:
        استفاده، فهم و تغییر، اشتراک‌گذاری، و همکاری با دیگران.
        اینجا «آزاد» یعنی آزادی، نه رایگان بودن به‌تنهایی.
    </p>

    <div class="freedoms-grid">
        <div class="freedom-card freedom-card--0">
            <div class="freedom-badge" aria-hidden="true">۰</div>
            <div class="freedom-icon" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="24" cy="24" r="18"/>
                    <path d="M20 16.5v15l13-7.5-13-7.5z" fill="currentColor" stroke="none"/>
                </svg>
            </div>
            <h3 class="freedom-title">استفاده</h3>
            <p class="freedom-description">برای هر کاری که می‌خواهی اجرا کنی</p>
        </div>

        <div class="freedom-card freedom-card--1">
            <div class="freedom-badge" aria-hidden="true">۱</div>
            <div class="freedom-icon" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 34l2.2-8.2L30.5 11.5a3.2 3.2 0 0 1 4.5 0l1.5 1.5a3.2 3.2 0 0 1 0 4.5L22.2 31.8 14 34z"/>
                    <path d="M27.5 14.5l6 6"/>
                    <path d="M12 40h24"/>
                </svg>
            </div>
            <h3 class="freedom-title">فهم و تغییر</h3>
            <p class="freedom-description">کد منبع را ببینی و اصلاح کنی</p>
        </div>

        <div class="freedom-card freedom-card--2">
            <div class="freedom-badge" aria-hidden="true">۲</div>
            <div class="freedom-icon" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="34" cy="12" r="5"/>
                    <circle cx="12" cy="24" r="5"/>
                    <circle cx="34" cy="36" r="5"/>
                    <path d="M16.8 21.5L29 14.5M16.8 26.5L29 33.5"/>
                </svg>
            </div>
            <h3 class="freedom-title">اشتراک‌گذاری</h3>
            <p class="freedom-description">نسخه‌اش را به دیگران بدهی</p>
        </div>

        <div class="freedom-card freedom-card--3">
            <div class="freedom-badge" aria-hidden="true">۳</div>
            <div class="freedom-icon" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="17" cy="16" r="5"/>
                    <circle cx="31" cy="16" r="5"/>
                    <path d="M8 36c1.2-5.5 5.2-8.5 9-8.5s7.8 3 9 8.5"/>
                    <path d="M22 36c1.2-5.5 5.2-8.5 9-8.5s7.8 3 9 8.5"/>
                    <path d="M24 22v6M21 25h6"/>
                </svg>
            </div>
            <h3 class="freedom-title">همکاری</h3>
            <p class="freedom-description">بهبودها را منتشر کنی تا جامعه هم بهره ببرد</p>
        </div>
    </div>

    <div class="edu-more-row">
        <a class="btn-brand" href="{{ url('/what-is-free-software') }}">بیشتر بخوان</a>
        <a class="btn-outline" href="{{ url('/fsf-history-redirect') }}">تاریخچه</a>
        <a class="btn-outline" href="{{ route('tags.show', 'videos') }}">ویدیوها</a>
    </div>
</section>
