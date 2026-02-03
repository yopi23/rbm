<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>YOYOYCELL – Service &amp; Grosir Gadget</title>

    <!-- Favicons -->
    <link href="{{ asset('img/yoygreen.png') }}" rel="icon">
    <link href="{{ asset('front/assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

    <!-- ─── Fonts ─── -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- ─── Icons ─── -->
    <!-- Boxicons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
    <!-- Remix Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- ─── AOS (Animate on Scroll) ─── -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">

    <!-- ─── jQuery (dibutuhkan AJAX di signup) ─── -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

    <!-- ─── Layout & Navbar Styles ─── -->
    <style>
        /* ═══════════════════════════════════════════
           GLOBAL RESET & BASE
           ═══════════════════════════════════════════ */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --yy-dark:      #f9fafb;
            --yy-nav-bg:    #ffffff;
            --yy-card:      #ffffff;
            --yy-border:    #e5e7eb;
            --yy-accent:    #00c9a7;
            --yy-accent2:   #00a882;
            --yy-text:      #1f2937;
            --yy-dim:       #6b7280;
            --yy-white:     #111827;
            --yy-red:       #ef4444;
            --nav-h:        68px;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif;
            background: var(--yy-dark);
            color: var(--yy-text);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; }
        ul { list-style: none; }

        /* ═══════════════════════════════════════════
           TOP NAVBAR
           ═══════════════════════════════════════════ */
        #yy-navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--nav-h);
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--yy-border);
            z-index: 9999;
            transition: background .35s, box-shadow .35s;
        }
        /* slightly darker + shadow on scroll (JS adds .scrolled) */
        #yy-navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 24px rgba(0,0,0,.08);
        }

        .yy-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
        }

        /* ── Logo ── */
        .yy-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .yy-logo-mark {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--yy-accent), var(--yy-accent2));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: #0f1117; font-weight: 700;
        }
        .yy-logo-text {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--yy-white);
            letter-spacing: -0.3px;
        }
        .yy-logo-text span { color: var(--yy-accent); }

        /* ── Desktop Nav Links ── */
        .yy-nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .yy-nav-links a {
            position: relative;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: .88rem;
            font-weight: 500;
            color: var(--yy-text);
            transition: color .25s, background .25s;
            display: flex; align-items: center; gap: 6px;
        }
        .yy-nav-links a i { font-size: .95rem; }
        .yy-nav-links a:hover,
        .yy-nav-links a.active {
            color: var(--yy-white);
            background: rgba(0,0,0,.05);
        }
        .yy-nav-links a.active {
            color: var(--yy-accent);
            background: rgba(0,201,167,.1);
        }
        /* active left-bar indicator */
        .yy-nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: 0; left: 50%; transform: translateX(-50%);
            width: 20px; height: 2px;
            background: var(--yy-accent);
            border-radius: 2px;
        }

        /* ── Cart Badge ── */
        .yy-cart-link { position: relative; }
        .yy-cart-badge {
            position: absolute;
            top: 2px; right: 2px;
            min-width: 18px; height: 18px;
            background: var(--yy-red);
            color: #fff;
            font-size: .65rem; font-weight: 700;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            padding: 0 5px;
            pointer-events: none;
        }

        /* ── Right actions (desktop) ── */
        .yy-nav-right {
            display: flex; align-items: center; gap: 8px;
        }

        /* ── Hamburger (mobile) ── */
        .yy-hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            background: none; border: none; cursor: pointer;
            padding: 6px;
        }
        .yy-hamburger span {
            width: 24px; height: 2px;
            background: var(--yy-text);
            border-radius: 2px;
            transition: transform .3s, opacity .3s;
        }
        .yy-hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .yy-hamburger.open span:nth-child(2) { opacity: 0; }
        .yy-hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        /* ═══════════════════════════════════════════
           MOBILE DRAWER
           ═══════════════════════════════════════════ */
        .yy-mobile-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 9998;
            opacity: 0; pointer-events: none;
            transition: opacity .3s;
        }
        .yy-mobile-overlay.open { opacity: 1; pointer-events: auto; }

        .yy-mobile-nav {
            position: fixed;
            top: 0; right: -300px;
            width: 280px; height: 100%;
            background: #ffffff;
            border-left: 1px solid var(--yy-border);
            z-index: 9999;
            transition: right .3s cubic-bezier(.4,0,.2,1);
            display: flex; flex-direction: column;
            padding-top: var(--nav-h);
        }
        .yy-mobile-nav.open { right: 0; }

        .yy-mobile-nav-links {
            padding: 16px 16px;
            flex: 1;
            overflow-y: auto;
        }
        .yy-mobile-nav-links a {
            display: flex; align-items: center; gap: 12px;
            padding: 13px 16px;
            border-radius: 10px;
            font-size: .9rem; font-weight: 500;
            color: var(--yy-text);
            margin-bottom: 2px;
            transition: background .2s, color .2s;
            position: relative;
        }
        .yy-mobile-nav-links a i { font-size: 1.1rem; width: 20px; text-align: center; }
        .yy-mobile-nav-links a:hover { background: rgba(0,0,0,.05); color: var(--yy-white); }
        .yy-mobile-nav-links a.active {
            background: rgba(0,201,167,.1);
            color: var(--yy-accent);
        }
        .yy-mobile-nav-links .yy-mobile-cart-badge {
            margin-left: auto;
            min-width: 20px; height: 20px;
            background: var(--yy-red); color: #fff;
            font-size: .68rem; font-weight: 700;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            padding: 0 6px;
        }

        .yy-mobile-nav-footer {
            padding: 20px 16px;
            border-top: 1px solid var(--yy-border);
        }
        .yy-mobile-nav-footer p {
            font-size: .75rem; color: var(--yy-dim); text-align: center;
        }

        /* ═══════════════════════════════════════════
           PAGE BODY OFFSET (fixed navbar)
           ═══════════════════════════════════════════ */
        #yy-page {
            padding-top: var(--nav-h);
            min-height: 100vh;
        }

        /* ═══════════════════════════════════════════
           FOOTER
           ═══════════════════════════════════════════ */
        #yy-footer {
            background: #f9fafb;
            border-top: 1px solid var(--yy-border);
            padding: 44px 0 24px;
            margin-top: auto;
        }
        .yy-footer-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1.6fr 1fr 1fr 1fr;
            gap: 40px;
        }
        @media(max-width:860px) {
            .yy-footer-inner { grid-template-columns: 1fr 1fr; }
        }
        @media(max-width:520px) {
            .yy-footer-inner { grid-template-columns: 1fr; gap: 28px; }
        }

        /* footer brand col */
        .yy-footer-brand .yy-logo { margin-bottom: 14px; }
        .yy-footer-brand p {
            font-size: .83rem; color: var(--yy-dim); line-height: 1.65; max-width: 280px;
        }
        .yy-footer-socials {
            display: flex; gap: 8px; margin-top: 18px;
        }
        .yy-footer-socials a {
            width: 36px; height: 36px; border-radius: 9px;
            background: rgba(0,0,0,.05); border: 1px solid var(--yy-border);
            display: flex; align-items: center; justify-content: center;
            color: var(--yy-dim); font-size: .9rem;
            transition: background .2s, color .2s, border-color .2s;
        }
        .yy-footer-socials a:hover {
            background: rgba(0,201,167,.12); border-color: var(--yy-accent); color: var(--yy-accent);
        }

        /* footer link cols */
        .yy-footer-col h4 {
            font-size: .78rem; color: var(--yy-accent);
            text-transform: uppercase; letter-spacing: 1.2px;
            font-weight: 600; margin-bottom: 16px;
        }
        .yy-footer-col ul li { margin-bottom: 10px; }
        .yy-footer-col ul li a {
            font-size: .85rem; color: var(--yy-dim);
            transition: color .2s, padding-left .2s;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .yy-footer-col ul li a:hover { color: var(--yy-accent); padding-left: 4px; }
        .yy-footer-col ul li a i { font-size: .75rem; }

        /* contact col */
        .yy-footer-contact-item {
            display: flex; align-items: flex-start; gap: 10px; margin-bottom: 14px;
        }
        .yy-footer-contact-item i {
            color: var(--yy-accent); font-size: .92rem; margin-top: 2px; flex-shrink: 0;
        }
        .yy-footer-contact-item span { font-size: .84rem; color: var(--yy-dim); line-height: 1.5; }

        /* bottom bar */
        .yy-footer-bottom {
            max-width: 1100px; margin: 32px auto 0;
            padding: 18px 24px 0;
            border-top: 1px solid var(--yy-border);
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 8px;
        }
        .yy-footer-bottom p {
            font-size: .77rem; color: var(--yy-dim);
        }
        .yy-footer-bottom .yy-heart { color: var(--yy-red); }

        /* ═══════════════════════════════════════════
           RESPONSIVE – hide desktop nav on mobile
           ═══════════════════════════════════════════ */
        @media(max-width: 768px) {
            .yy-nav-links  { display: none; }
            .yy-nav-right  { display: none; }
            .yy-hamburger  { display: flex; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════
     NAVBAR
     ═══════════════════════════════════════════ -->
<nav id="yy-navbar">
    <div class="yy-nav-inner">

        <!-- Logo -->
        <a href="{{ route('home') }}" class="yy-logo">
            <div class="yy-logo-mark">Y</div>
            <div class="yy-logo-text">YOYOY<span>CELL</span></div>
        </a>

        <!-- Desktop Links -->
        <div class="yy-nav-links">
            <a href="{{ route('home') }}"
               class="{{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="bx bx-home"></i> Home
            </a>
            <a href="{{ route('product') }}"
               class="{{ request()->routeIs('product') ? 'active' : '' }}">
                <i class="bx bx-phone"></i> Produk
            </a>
            <a href="{{ route('spareparts') }}"
               class="{{ request()->routeIs('spareparts') ? 'active' : '' }}">
                <i class="bx bx-cube"></i> Sparepart
            </a>
            <a href="{{ route('service') }}"
               class="{{ request()->routeIs('service') ? 'active' : '' }}">
                <i class="bx bx-wrench"></i> Service
            </a>
        </div>

        <!-- Desktop Right (cart) -->
        <div class="yy-nav-right">
            <a href="{{ route('cart') }}" class="yy-cart-link"
               style="padding:8px 14px; border-radius:8px; color:var(--yy-text); font-size:.88rem; font-weight:500; display:flex; align-items:center; gap:6px; transition:background .2s, color .2s;"
               onmouseover="this.style.background='rgba(0,0,0,.05)';this.style.color='var(--yy-accent)'"
               onmouseout="this.style.background='transparent';this.style.color='var(--yy-text)'">
                <i class="bx bx-cart" style="font-size:.95rem;"></i>
                @php
                    $cartCount = 0;
                    if(session('cart_produk'))    $cartCount += count(session('cart_produk'));
                    if(session('cart_sparepart')) $cartCount += count(session('cart_sparepart'));
                @endphp
                @if($cartCount > 0)
                    <span class="yy-cart-badge">{{ $cartCount }}</span>
                @endif
            </a>

            <!-- Auth Button -->
            @if (Auth::check())
                <a href="{{ route('dashboard') }}"
                   style="margin-left:8px; padding:8px 16px; border-radius:8px; background:var(--yy-accent); color:#fff; font-size:.88rem; font-weight:600; display:flex; align-items:center; gap:6px; transition:opacity .2s;"
                   onmouseover="this.style.opacity='.9'"
                   onmouseout="this.style.opacity='1'">
                   <i class="bx bx-grid-alt"></i> Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                   style="margin-left:8px; padding:8px 16px; border-radius:8px; background:var(--yy-accent); color:#fff; font-size:.88rem; font-weight:600; display:flex; align-items:center; gap:6px; transition:opacity .2s;"
                   onmouseover="this.style.opacity='.9'"
                   onmouseout="this.style.opacity='1'">
                   <i class="bx bx-log-in"></i> Sign In
                </a>
            @endif
        </div>

        <!-- Hamburger (mobile) -->
        <button class="yy-hamburger" id="yy-hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- ═══════════════════════════════════════════
     MOBILE DRAWER
     ═══════════════════════════════════════════ -->
<div class="yy-mobile-overlay" id="yy-overlay"></div>
<div class="yy-mobile-nav" id="yy-mobile-nav">
    <div class="yy-mobile-nav-links">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="bx bx-home"></i> Home
        </a>
        <a href="{{ route('product') }}" class="{{ request()->routeIs('product') ? 'active' : '' }}">
            <i class="bx bx-phone"></i> Produk
        </a>
        <a href="{{ route('spareparts') }}" class="{{ request()->routeIs('spareparts') ? 'active' : '' }}">
            <i class="bx bx-cube"></i> Sparepart
        </a>
        <a href="{{ route('service') }}" class="{{ request()->routeIs('service') ? 'active' : '' }}">
            <i class="bx bx-wrench"></i> Service
        </a>
        <a href="{{ route('cart') }}" class="{{ request()->routeIs('cart') ? 'active' : '' }}">
            <i class="bx bx-cart"></i> Keranjang
            @if($cartCount > 0)
                <span class="yy-mobile-cart-badge">{{ $cartCount }}</span>
            @endif
        </a>

        @if (Auth::check())
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bx bx-grid-alt"></i> Dashboard
            </a>
        @else
            <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'active' : '' }}">
                <i class="bx bx-log-in"></i> Sign In
            </a>
        @endif
    </div>
    <div class="yy-mobile-nav-footer">
        <p>&copy; 2024 YOYOYCELL. Semua hak dilindungi.</p>
    </div>
</div>

<!-- ═══════════════════════════════════════════
     PAGE CONTENT
     ═══════════════════════════════════════════ -->
<div id="yy-page">
    @yield('content-app')
</div>

<!-- ═══════════════════════════════════════════
     FOOTER
     ═══════════════════════════════════════════ -->
<footer id="yy-footer">
    <div class="yy-footer-inner">

        <!-- Brand -->
        <div class="yy-footer-brand">
            <a href="{{ route('home') }}" class="yy-logo">
                <div class="yy-logo-mark">Y</div>
                <div class="yy-logo-text">YOYOY<span>CELL</span></div>
            </a>
            <p>Tempat perbaikan gadget terpercaya sejak 2016. Layanan service, grosir sparepart &amp; jual beli HP &amp; Laptop.</p>
            <div class="yy-footer-socials">
                <a href="#"><i class="ri-facebook-fill"></i></a>
                <a href="#"><i class="ri-instagram-fill"></i></a>
                <a href="#"><i class="ri-twitter-fill"></i></a>
                <a href="#"><i class="ri-whatsapp-fill"></i></a>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="yy-footer-col">
            <h4>Menu</h4>
            <ul>
                <li><a href="{{ route('home') }}"><i class="bx bx-chevron-right"></i> Home</a></li>
                <li><a href="{{ route('product') }}"><i class="bx bx-chevron-right"></i> Produk</a></li>
                <li><a href="{{ route('spareparts') }}"><i class="bx bx-chevron-right"></i> Sparepart</a></li>
                <li><a href="{{ route('service') }}"><i class="bx bx-chevron-right"></i> Status Service</a></li>
            </ul>
        </div>

        <!-- Layanan -->
        <div class="yy-footer-col">
            <h4>Layanan</h4>
            <ul>
                <li><a href="#"><i class="bx bx-chevron-right"></i> Service HP</a></li>
                <li><a href="#"><i class="bx bx-chevron-right"></i> Service Laptop</a></li>
                <li><a href="#"><i class="bx bx-chevron-right"></i> Grosir Sparepart</a></li>
                <li><a href="#"><i class="bx bx-chevron-right"></i> Jual Beli HP</a></li>
            </ul>
        </div>

        <!-- Kontak -->
        <div class="yy-footer-col">
            <h4>Kontak</h4>
            <div class="yy-footer-contact-item">
                <i class="bi bi-geo-alt"></i>
                <span>JL. Raya Sagaraten</span>
            </div>
            <div class="yy-footer-contact-item">
                <i class="bi bi-telephone"></i>
                <span>+62 8560 3124 871</span>
            </div>
            <div class="yy-footer-contact-item">
                <i class="bi bi-clock"></i>
                <span>Mo – Sa: 08.00 – 19.00</span>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="yy-footer-bottom">
        <p>&copy; 2024 YOYOYCELL. All Rights Reserved. <span class="d-none d-sm-inline">| Developed by <a href="#" style="color:var(--yy-accent);">PE Engine</a></span></p>
        <p style="display:flex; align-items:center; gap:4px;">
            Made with <i class="bx bxs-heart yy-heart"></i> in Indonesia
        </p>
    </div>
</footer>

<!-- ═══════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════ -->
<!-- AOS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<!-- PureCounter -->
<script src="https://cdn.jsdelivr.net/npm/purecounter@1.5.0/dist/purecounter.vanilla.js"></script>

<script>
// ── AOS init ──
AOS.init({
    duration: 600,
    easing: 'ease-out-cubic',
    once: true,
    mirror: false
});

// ── PureCounter init ──
function initPureCounter() {
    if (typeof PureCounter === 'function') {
        new PureCounter();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPureCounter);
} else {
    initPureCounter();
}

// Fallback for late script loading
window.addEventListener('load', initPureCounter);

// ── Navbar scroll effect ──
(function(){
    var nav = document.getElementById('yy-navbar');
    window.addEventListener('scroll', function(){
        nav.classList.toggle('scrolled', window.scrollY > 40);
    });
})();

// ── Mobile nav toggle ──
(function(){
    var hamburger = document.getElementById('yy-hamburger');
    var mobileNav = document.getElementById('yy-mobile-nav');
    var overlay   = document.getElementById('yy-overlay');

    function openMenu(){
        hamburger.classList.add('open');
        mobileNav.classList.add('open');
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeMenu(){
        hamburger.classList.remove('open');
        mobileNav.classList.remove('open');
        overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', function(e){
        e.stopPropagation();
        mobileNav.classList.contains('open') ? closeMenu() : openMenu();
    });
    overlay.addEventListener('click', closeMenu);

    // close on nav-link click
    mobileNav.querySelectorAll('a').forEach(function(link){
        link.addEventListener('click', closeMenu);
    });
})();
</script>

<!-- ── Extra scripts block (child views can push here) ── -->
@stack('scripts')

<!-- ── Child-page inline scripts (e.g. signup AJAX) ── -->
<div id="yy-scripts-wrapper">
@yield('content-script')
</div>

<script>
// ── SPA Navigation (Simple PJAX) ──
(function(){
    // Progress Bar
    const bar = document.createElement('div');
    bar.id = 'yy-progress-bar';
    Object.assign(bar.style, {
        position:'fixed', top:'0', left:'0', height:'3px', width:'0%',
        background:'var(--yy-accent)', zIndex:'99999', transition:'width .3s ease', pointerEvents:'none'
    });
    document.body.appendChild(bar);

    function startLoad() {
        bar.style.width = '30%';
        bar.style.opacity = '1';
    }
    function endLoad() {
        bar.style.width = '100%';
        setTimeout(() => {
            bar.style.opacity = '0';
            setTimeout(() => { bar.style.width = '0%'; }, 300);
        }, 300);
    }

    // Execute scripts in container
    function runScripts(container) {
        container.querySelectorAll('script').forEach(oldScript => {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.textContent = oldScript.textContent;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    // Intercept Clicks
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link) return;

        const url = new URL(link.href);
        // Ignore external, hash-only, different origin, or special links
        if (url.origin !== window.location.origin || 
            link.target === '_blank' || 
            link.hasAttribute('data-no-spa') ||
            url.pathname === window.location.pathname && url.search === window.location.search) {
            return;
        }

        e.preventDefault();
        const href = link.href;

        startLoad();

        // Active state update (visual feedback)
        document.querySelectorAll('.yy-nav-links a, .yy-mobile-nav-links a').forEach(a => {
            a.classList.toggle('active', a.href === href);
        });

        fetch(href)
            .then(res => {
                if(!res.ok) throw new Error('Network response was not ok');
                return res.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // 1. Replace Content
                const newContent = doc.getElementById('yy-page');
                const oldContent = document.getElementById('yy-page');
                if (newContent && oldContent) {
                    oldContent.innerHTML = newContent.innerHTML;
                    // Re-init AOS
                    if (window.AOS) setTimeout(() => window.AOS.init(), 100); 
                    // Re-init PureCounter
                    if (typeof PureCounter === 'function') new PureCounter();
                }

                // 2. Replace & Run Scripts
                const newScripts = doc.getElementById('yy-scripts-wrapper');
                const oldScripts = document.getElementById('yy-scripts-wrapper');
                if (newScripts && oldScripts) {
                    oldScripts.innerHTML = newScripts.innerHTML;
                    runScripts(oldScripts);
                }

                // 3. Update Title
                document.title = doc.title;

                // 4. Update History
                window.history.pushState({}, '', href);

                // 5. Scroll Top
                window.scrollTo(0, 0);

                // 6. Close Mobile Menu
                if(document.body.style.overflow === 'hidden') {
                     // Click overlay to close
                     const overlay = document.getElementById('yy-overlay');
                     if(overlay) overlay.click();
                }

                endLoad();
            })
            .catch(err => {
                console.error('SPA Load Error:', err);
                window.location.href = href; // Fallback
            });
    });

    // Handle Back/Forward
    window.addEventListener('popstate', () => window.location.reload());
})();
</script>

</body>
</html>
