@extends('front.layout.app')
@section('content-app')

<style>
/* ─── YOYOYCELL GLOBAL CONTENT STYLES ─── */
#content-app-wrapper {
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    color: var(--yy-text);
    background: var(--yy-dark);
}

/* ── Hero ── */
#content-app-wrapper .yy-hero {
    position: relative;
    min-height: 380px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ffffff 0%, #f3f4f6 50%, #ffffff 100%);
    overflow: hidden;
    padding: 60px 20px;
}
#content-app-wrapper .yy-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 80% 60% at 20% 50%, rgba(0,201,167,.12) 0%, transparent 70%),
        radial-gradient(ellipse 60% 50% at 80% 60%, rgba(0,168,130,.08) 0%, transparent 70%);
    pointer-events: none;
}
#content-app-wrapper .yy-hero::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--yy-accent), transparent);
}
#content-app-wrapper .yy-hero-inner {
    position: relative; z-index: 1; text-align: center; max-width: 780px;
}
#content-app-wrapper .yy-hero h1 {
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 700;
    color: var(--yy-white);
    margin-bottom: 10px;
    letter-spacing: -0.5px;
}
#content-app-wrapper .yy-hero h1 span { color: var(--yy-accent); }
#content-app-wrapper .yy-hero h2 {
    font-size: clamp(1rem, 2vw, 1.25rem);
    font-weight: 400;
    color: var(--yy-text-dim);
    margin: 0;
}

/* ── Brand Bar (logos) ── */
#content-app-wrapper .yy-brands {
    background: #ffffff;
    border-top: 1px solid var(--yy-border);
    border-bottom: 1px solid var(--yy-border);
    padding: 22px 0;
    overflow: hidden;
}
#content-app-wrapper .yy-brands .yy-brands-track {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 48px;
    flex-wrap: wrap;
}
#content-app-wrapper .yy-brands img {
    height: 36px;
    width: auto;
    filter: grayscale(100%) opacity(0.6);
    transition: opacity .3s, filter .3s;
}
#content-app-wrapper .yy-brands img:hover { opacity: 1; filter: grayscale(0%); }

/* ── Section Titles ── */
#content-app-wrapper .yy-section-title {
    text-align: center;
    margin-bottom: 48px;
}
#content-app-wrapper .yy-section-title h2 {
    font-size: 1.9rem; font-weight: 700; color: var(--yy-white); margin-bottom: 8px;
}
#content-app-wrapper .yy-section-title p {
    color: var(--yy-text-dim); font-size: .95rem; max-width: 560px; margin: 0 auto;
}
#content-app-wrapper .yy-section-title .yy-line {
    width: 48px; height: 3px;
    background: var(--yy-accent); border-radius: 2px;
    margin: 14px auto 0;
}

/* ── About ── */
#content-app-wrapper .yy-about {
    background: var(--yy-dark);
    padding: 80px 0;
}
#content-app-wrapper .yy-about-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 24px;
}
@media(max-width:768px){ #content-app-wrapper .yy-about-grid { grid-template-columns:1fr; gap:36px; } }
#content-app-wrapper .yy-about-left h2 {
    font-size: 1.7rem; font-weight: 700; color: var(--yy-white); margin-bottom: 6px;
}
#content-app-wrapper .yy-about-left h3 {
    font-size: 1.05rem; color: var(--yy-accent); font-weight: 500; margin-bottom: 0;
}
#content-app-wrapper .yy-about-right p {
    line-height: 1.7; margin-bottom: 18px; font-size: .95rem;
}
#content-app-wrapper .yy-about-right ul {
    list-style: none; padding: 0; margin-bottom: 18px;
}
#content-app-wrapper .yy-about-right ul li {
    padding: 7px 0; border-bottom: 1px solid var(--yy-border); font-size: .93rem;
    display: flex; align-items: flex-start; gap: 10px;
}
#content-app-wrapper .yy-about-right ul li:last-child { border-bottom: none; }
#content-app-wrapper .yy-about-right ul li .yy-check {
    display: inline-flex; align-items: center; justify-content: center;
    width: 20px; height: 20px; min-width: 20px;
    background: rgba(0,201,167,.15); border-radius: 50%;
    color: var(--yy-accent); font-size: .75rem;
}
#content-app-wrapper .yy-about-right .yy-tagline {
    font-style: italic; color: var(--yy-accent); font-size: .92rem; margin-bottom: 0;
}

/* ── Stats ── */
#content-app-wrapper .yy-stats {
    background: linear-gradient(135deg, #ffffff, #f9fafb);
    border-top: 1px solid var(--yy-border);
    border-bottom: 1px solid var(--yy-border);
    padding: 56px 0;
}
#content-app-wrapper .yy-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
    max-width: 900px;
    margin: 0 auto;
    padding: 0 24px;
}
@media(max-width:600px){ #content-app-wrapper .yy-stats-grid { grid-template-columns: repeat(2,1fr); } }
#content-app-wrapper .yy-stat-item { text-align: center; }
#content-app-wrapper .yy-stat-item .yy-stat-number {
    font-size: 2.4rem; font-weight: 700; color: var(--yy-white);
    line-height: 1;
}
#content-app-wrapper .yy-stat-item .yy-stat-number span { color: var(--yy-accent); }
#content-app-wrapper .yy-stat-item .yy-stat-label {
    margin-top: 8px; font-size: .88rem; color: var(--yy-text-dim); text-transform: uppercase; letter-spacing: 1.2px;
}

/* ── Why Us ── */
#content-app-wrapper .yy-whyus {
    background: var(--yy-dark); padding: 80px 0;
}
#content-app-wrapper .yy-whyus-grid {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 24px;
    max-width: 1050px; margin: 0 auto; padding: 0 24px;
}
@media(max-width:700px){ #content-app-wrapper .yy-whyus-grid { grid-template-columns:1fr; } }
#content-app-wrapper .yy-whyus-card {
    background: var(--yy-card);
    border: 1px solid var(--yy-border);
    border-radius: 14px;
    padding: 36px 28px;
    transition: transform .25s, border-color .25s, box-shadow .25s;
    position: relative;
    overflow: hidden;
}
#content-app-wrapper .yy-whyus-card::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, var(--yy-accent), var(--yy-accent2));
    opacity: 0; transition: opacity .3s;
}
#content-app-wrapper .yy-whyus-card:hover::before { opacity: 1; }
#content-app-wrapper .yy-whyus-card:hover {
    transform: translateY(-4px);
    border-color: var(--yy-accent);
    box-shadow: 0 8px 32px rgba(0,201,167,.12);
}
#content-app-wrapper .yy-whyus-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(0,201,167,.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: var(--yy-accent);
    margin-bottom: 20px;
}
#content-app-wrapper .yy-whyus-card h4 {
    font-size: 1.05rem; font-weight: 600; color: var(--yy-white); margin-bottom: 8px;
}
#content-app-wrapper .yy-whyus-card p {
    font-size: .9rem; color: var(--yy-text-dim); margin: 0; line-height: 1.6;
}

/* ── CTA ── */
#content-app-wrapper .yy-cta {
    background: linear-gradient(135deg, #e6fffa 0%, #ffffff 60%);
    border-top: 1px solid var(--yy-border);
    border-bottom: 1px solid var(--yy-border);
    padding: 72px 0;
    text-align: center;
}
#content-app-wrapper .yy-cta h3 { font-size: 1.7rem; color: var(--yy-white); margin-bottom: 8px; }
#content-app-wrapper .yy-cta p { color: var(--yy-text-dim); margin-bottom: 24px; }
#content-app-wrapper .yy-btn-accent {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--yy-accent); color: #0f1117;
    padding: 13px 32px; border-radius: 10px;
    font-weight: 600; font-size: .95rem;
    text-decoration: none;
    transition: background .25s, transform .2s, box-shadow .25s;
    border: none; cursor: pointer;
}
#content-app-wrapper .yy-btn-accent:hover {
    background: var(--yy-accent2);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,201,167,.3);
    color: #fff;
}

/* ── Team ── */
#content-app-wrapper .yy-team { background: var(--yy-dark); padding: 80px 0; }
#content-app-wrapper .yy-team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 28px;
    max-width: 1100px; margin: 0 auto; padding: 0 24px;
}
#content-app-wrapper .yy-team-card {
    background: var(--yy-card);
    border: 1px solid var(--yy-border);
    border-radius: 16px;
    overflow: hidden;
    transition: transform .25s, border-color .25s;
}
#content-app-wrapper .yy-team-card:hover {
    transform: translateY(-4px);
    border-color: var(--yy-accent);
}
#content-app-wrapper .yy-team-photo {
    width: 100%; height: 200px;
    background-size: cover; background-position: center;
    position: relative;
}
#content-app-wrapper .yy-team-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
#content-app-wrapper .yy-team-photo .yy-jabatan-badge {
    position: absolute; bottom: 12px; left: 12px;
    background: rgba(0,201,167,.9); color: #0f1117;
    padding: 4px 12px; border-radius: 20px;
    font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .8px;
}
#content-app-wrapper .yy-team-info {
    padding: 20px 22px 22px;
}
#content-app-wrapper .yy-team-info h4 {
    font-size: 1.05rem; color: var(--yy-white); margin-bottom: 12px;
}
#content-app-wrapper .yy-team-socials {
    display: flex; gap: 10px;
}
#content-app-wrapper .yy-team-socials a {
    width: 34px; height: 34px; border-radius: 8px;
    background: rgba(0,0,0,.05); border: 1px solid var(--yy-border);
    display: flex; align-items: center; justify-content: center;
    color: var(--yy-text-dim); font-size: .85rem;
    text-decoration: none; transition: background .2s, color .2s, border-color .2s;
}
#content-app-wrapper .yy-team-socials a:hover {
    background: rgba(0,201,167,.15); border-color: var(--yy-accent); color: var(--yy-accent);
}

/* ── Contact ── */
#content-app-wrapper .yy-contact {
    background: #f9fafb;
    border-top: 1px solid var(--yy-border);
    padding: 72px 0 60px;
}
#content-app-wrapper .yy-contact-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 52px;
    max-width: 900px; margin: 0 auto; padding: 0 24px;
}
@media(max-width:640px){ #content-app-wrapper .yy-contact-grid { grid-template-columns:1fr; } }
#content-app-wrapper .yy-contact-left h2 {
    font-size: 1.7rem; color: var(--yy-white); margin-bottom: 10px;
}
#content-app-wrapper .yy-contact-left p {
    color: var(--yy-text-dim); font-size: .93rem; line-height: 1.6;
}
#content-app-wrapper .yy-contact-item {
    display: flex; align-items: flex-start; gap: 16px; margin-bottom: 24px;
}
#content-app-wrapper .yy-contact-icon {
    width: 44px; height: 44px; min-width: 44px; border-radius: 12px;
    background: rgba(0,201,167,.1);
    display: flex; align-items: center; justify-content: center;
    color: var(--yy-accent); font-size: 1.1rem;
}
#content-app-wrapper .yy-contact-item h4 {
    font-size: .92rem; color: var(--yy-white); margin-bottom: 2px;
}
#content-app-wrapper .yy-contact-item p {
    font-size: .88rem; color: var(--yy-text-dim); margin: 0;
}
</style>

<div id="content-app-wrapper">

<!-- ── HERO ── -->
<section class="yy-hero">
    <div class="yy-hero-inner" data-aos="fade-in" data-aos-delay="200">
        <h1>SELAMAT DATANG DI <span>YOYOYCELL</span></h1>
        <h2>Service &amp; Grosir Sparepart, Handphone dan Lainnya</h2>
    </div>
</section>

<!-- ── BRAND LOGOS ── -->
<section class="yy-brands">
    <div class="yy-brands-track">
        <img src="{{asset('public/')}}/img/ip.png"      alt="iPhone">
        <img src="{{asset('public/')}}/img/oppo.png"    alt="Oppo">
        <img src="{{asset('public/')}}/img/samsung.png" alt="Samsung">
        <img src="{{asset('public/')}}/img/vivo.png"    alt="Vivo">
        <img src="{{asset('public/')}}/img/xiaomi.png"  alt="Xiaomi">
        <img src="{{asset('public/')}}/img/huawei.png"  alt="Huawei">
    </div>
</section>

<!-- ── ABOUT ── -->
<section class="yy-about">
    <div class="yy-about-grid">
        <div class="yy-about-left" data-aos="fade-right" data-aos-delay="100">
            <h2>KAMI MENERIMA SERVICE<br>HP DAN LAPTOP</h2>
            <h3>Sparepart &amp; Aksesori Lengkap</h3>
        </div>
        <div class="yy-about-right" data-aos="fade-left" data-aos-delay="200">
            <p>
                YOYOYCELL adalah tempat perbaikan gadget terbaik dan lengkap. Sebagian besar pengerjaan bisa
                ditunggu dengan suku cadang yang siap dan tenaga ahli yang kompeten. Kami juga melayani grosir
                dan eceran sejak tahun <strong style="color:var(--yy-accent)">2016</strong>.
            </p>
            <ul>
                <li>
                    <span class="yy-check"><i class="ri-check-line"></i></span>
                    HP &amp; Laptop Mati Total, Mentok Logo, Ganti IC, Touchscreen, LCD
                </li>
                <li>
                    <span class="yy-check"><i class="ri-check-line"></i></span>
                    Masalah Jaringan, Lupa Sandi (ketentuan berlaku), dll.
                </li>
                <li>
                    <span class="yy-check"><i class="ri-check-line"></i></span>
                    Jual Beli HP dan Laptop
                </li>
            </ul>
            <p class="yy-tagline">Utamakan bertanya — kami siap melayani Anda.</p>
        </div>
    </div>
</section>

<!-- ── STATS ── -->
<section class="yy-stats">
    <div class="yy-stats-grid">
        <div class="yy-stat-item" data-aos="zoom-in" data-aos-delay="100">
            <div class="yy-stat-number">
                <span data-purecounter-start="0" data-purecounter-end="{{$service}}" data-purecounter-duration="1" class="purecounter">{{$service}}</span><span>+</span>
            </div>
            <div class="yy-stat-label">Service</div>
        </div>
        <div class="yy-stat-item" data-aos="zoom-in" data-aos-delay="200">
            <div class="yy-stat-number">
                <span data-purecounter-start="0" data-purecounter-end="{{$sparepart}}" data-purecounter-duration="1" class="purecounter">{{$sparepart}}</span><span>+</span>
            </div>
            <div class="yy-stat-label">Sparepart</div>
        </div>
        <div class="yy-stat-item" data-aos="zoom-in" data-aos-delay="300">
            <div class="yy-stat-number">
                <span data-purecounter-start="0" data-purecounter-end="{{$datateam->count()}}" data-purecounter-duration="1" class="purecounter">{{$datateam->count()}}</span><span>+</span>
            </div>
            <div class="yy-stat-label">Staff</div>
        </div>
        <div class="yy-stat-item" data-aos="zoom-in" data-aos-delay="400">
            <div class="yy-stat-number">
                <span data-purecounter-start="0" data-purecounter-end="{{$produk}}" data-purecounter-duration="1" class="purecounter">{{$produk}}</span><span>+</span>
            </div>
            <div class="yy-stat-label">Produk</div>
        </div>
    </div>
</section>

<!-- ── WHY US ── -->
<section class="yy-whyus">
    <div class="yy-section-title" data-aos="fade-up">
        <h2>Kenapa Harus YOYOYCELL?</h2>
        <p>Keunggulan yang membedakan kami dari service lain</p>
        <div class="yy-line"></div>
    </div>
    <div class="yy-whyus-grid">
        <div class="yy-whyus-card" data-aos="zoom-in" data-aos-delay="100">
            <div class="yy-whyus-icon"><i class="bx bx-receipt"></i></div>
            <h4>Bisa Ditunggu</h4>
            <p>Sebagian besar pengerjaan bisa ditunggu langsung di toko.</p>
        </div>
        <div class="yy-whyus-card" data-aos="zoom-in" data-aos-delay="200">
            <div class="yy-whyus-icon"><i class="bx bx-cube-alt"></i></div>
            <h4>Tenaga Ahli</h4>
            <p>Ditangani oleh teknisi yang berpengalaman dan kompeten.</p>
        </div>
        <div class="yy-whyus-card" data-aos="zoom-in" data-aos-delay="300">
            <div class="yy-whyus-icon"><i class="bx bx-star"></i></div>
            <h4>Kualitas Terbaik</h4>
            <p>Suku cadang dan produk kami dipilih yang terbaik untuk Anda.</p>
        </div>
    </div>
</section>

<!-- ── CTA ── -->
<section class="yy-cta">
    <div data-aos="zoom-in">
        <h3>Ingin Cek Status Service?</h3>
        <p>Masukkan kode invoice Anda dan pantau status servis secara real-time.</p>
        <a href="{{route('service')}}" class="yy-btn-accent">
            <i class="bx bx-search"></i> Cek Sekarang
        </a>
    </div>
</section>

<!-- ── TEAM ── -->
<section class="yy-team">
    <div class="yy-section-title" data-aos="fade-up">
        <h2>Tim Profesional Kami</h2>
        <p>Tenaga ahli dan kompeten yang siap melayani Anda</p>
        <div class="yy-line"></div>
    </div>
    <div class="yy-team-grid">
        @foreach ($datateam as $item)
        <div class="yy-team-card" data-aos="zoom-in" data-aos-delay="100">
            <div class="yy-team-photo">
                @if ($item->foto_user != '-')
                    <div style="background-image:url('{{asset('public')}}/uploads/{{$item->foto_user}}'); height:200px; background-size:cover; background-position:center;"></div>
                @else
                    <img src="{{asset('public/')}}img/user-default.png" alt="">
                @endif
                <div class="yy-jabatan-badge">
                    @switch($item->jabatan)
                        @case(0) Administrator @break
                        @case(1) Owner @break
                        @case(2) Kasir @break
                        @case(3) Teknisi @break
                    @endswitch
                </div>
            </div>
            <div class="yy-team-info">
                <h4>{{$item->name}}</h4>
                <div class="yy-team-socials">
                    <a href="{{$item->link_twitter}}"><i class="ri-twitter-fill"></i></a>
                    <a href="{{$item->link_facebook}}"><i class="ri-facebook-fill"></i></a>
                    <a href="{{$item->link_instagram}}"><i class="ri-instagram-fill"></i></a>
                    <a href="{{$item->link_linkedin}}"><i class="ri-linkedin-box-fill"></i></a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</section>

<!-- ── CONTACT ── -->
<section class="yy-contact">
    <div class="yy-contact-grid">
        <div class="yy-contact-left" data-aos="fade-right">
            <h2>Hubungi Kami</h2>
            <p>Untuk info detail mengenai servis dan stok HP, hubungi kami atau langsung datang ke toko.</p>
        </div>
        <div data-aos="fade-left" data-aos-delay="100">
            <div class="yy-contact-item">
                <div class="yy-contact-icon"><i class="bi bi-geo-alt"></i></div>
                <div>
                    <h4>Alamat</h4>
                    <p>JL. Raya Sagaraten</p>
                </div>
            </div>
            <div class="yy-contact-item">
                <div class="yy-contact-icon"><i class="bi bi-telephone"></i></div>
                <div>
                    <h4>Telepon</h4>
                    <p>+62 8560 3124 871</p>
                </div>
            </div>
        </div>
    </div>
</section>

</div><!-- end #content-app-wrapper -->
@endsection
