@extends('front.layout.app')
@section('content-app')

<style>
/* ═══════════════════════════════════════════
   DETAIL PAGE – Marketplace Style
   ═══════════════════════════════════════════ */
#sp-detail {
    font-family:'Plus Jakarta Sans','Segoe UI',system-ui,sans-serif;
    color:var(--yy-text); background:var(--yy-dark); min-height:100vh;
    padding: 32px 0 80px;
}
#sp-detail .sp-container {
    max-width:1100px; margin:0 auto; padding:0 20px;
}

/* Breadcrumb */
.sp-breadcrumb {
    display:flex; align-items:center; gap:6px;
    font-size:.82rem; color:var(--yy-dim);
    margin-bottom:28px; flex-wrap:wrap;
}
.sp-breadcrumb a { color:var(--yy-accent); transition:opacity .2s; }
.sp-breadcrumb a:hover { opacity:.75; }
.sp-breadcrumb i { font-size:.7rem; color:var(--yy-dim); }

/* Main Grid */
.sp-main {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:40px;
    align-items:start;
}
@media(max-width:768px) {
    .sp-main { grid-template-columns:1fr; gap:24px; }
}

/* ── Gallery ── */
.sp-gallery { position:sticky; top:90px; }
.sp-gallery-main {
    position:relative; width:100%;
    aspect-ratio:1; border-radius:16px; overflow:hidden;
    background:#fff; border:1px solid var(--yy-border);
    cursor:zoom-in;
}
.sp-gallery-main img {
    width:100%; height:100%; object-fit:contain; display:block;
    transition:transform .4s ease;
}
.sp-gallery-main:hover img { transform:scale(1.03); }
.sp-gallery-main .sp-photo-count {
    position:absolute; bottom:12px; right:12px;
    background:rgba(0,0,0,.6); color:#fff;
    font-size:.72rem; font-weight:600;
    padding:4px 10px; border-radius:20px;
    pointer-events:none;
}

.sp-thumbs {
    display:flex; gap:10px; margin-top:14px; overflow-x:auto;
    padding-bottom:6px;
    scrollbar-width:thin;
    scrollbar-color:var(--yy-accent) transparent;
}
.sp-thumbs::-webkit-scrollbar { height:4px; }
.sp-thumbs::-webkit-scrollbar-thumb { background:var(--yy-accent); border-radius:4px; }
.sp-thumb {
    flex:0 0 72px; height:72px; border-radius:10px;
    overflow:hidden; cursor:pointer;
    border:2px solid transparent;
    transition:border-color .2s, opacity .2s;
    background:#fff;
}
.sp-thumb:hover { opacity:.8; }
.sp-thumb.active { border-color:var(--yy-accent); }
.sp-thumb img { width:100%; height:100%; object-fit:cover; display:block; }

/* ── Info Panel ── */
.sp-info {}
.sp-category-badge {
    display:inline-flex; align-items:center; gap:5px;
    background:rgba(0,201,167,.1); color:var(--yy-accent);
    font-size:.76rem; font-weight:600;
    padding:5px 12px; border-radius:20px;
    margin-bottom:12px;
}
.sp-category-badge i { font-size:.8rem; }

.sp-title {
    font-size:clamp(1.4rem,3vw,1.8rem); font-weight:700;
    color:var(--yy-white); line-height:1.3;
    margin-bottom:8px;
}

.sp-code {
    font-size:.8rem; color:var(--yy-dim);
    margin-bottom:20px;
}
.sp-code span { font-family:monospace; background:rgba(0,0,0,.05); padding:2px 8px; border-radius:6px; }

/* Price Section */
.sp-price-box {
    background:#fff; border:1px solid var(--yy-border);
    border-radius:14px; padding:20px 24px;
    margin-bottom:24px;
}
.sp-price-row {
    display:flex; align-items:baseline; gap:12px; flex-wrap:wrap;
}
.sp-price-main {
    font-size:1.8rem; font-weight:800;
    color:var(--yy-accent);
    letter-spacing:-0.5px;
}
.sp-price-label {
    font-size:.78rem; color:var(--yy-dim); font-weight:500;
}
.sp-price-hidden {
    display:flex; align-items:center; gap:8px;
    color:var(--yy-dim); font-size:.88rem;
}
.sp-price-hidden i { font-size:1.1rem; }

/* Stock badge */
.sp-stock-row {
    display:flex; align-items:center; gap:12px;
    margin-bottom:24px;
}
.sp-stock-badge {
    font-size:.82rem; font-weight:600;
    padding:6px 14px; border-radius:20px;
    display:inline-flex; align-items:center; gap:5px;
}
.sp-stock-badge.in-stock { background:rgba(0,201,167,.12); color:var(--yy-accent); }
.sp-stock-badge.out-stock { background:rgba(239,68,68,.12); color:#ef4444; }
.sp-stock-badge i { font-size:.9rem; }

/* Description */
.sp-desc-title {
    font-size:.9rem; font-weight:700;
    color:var(--yy-white); margin-bottom:8px;
    display:flex; align-items:center; gap:6px;
}
.sp-desc-title i { color:var(--yy-accent); }
.sp-desc-content {
    font-size:.88rem; color:var(--yy-dim);
    line-height:1.75;
    background:#fff; border:1px solid var(--yy-border);
    border-radius:14px; padding:20px 24px;
    margin-bottom:24px;
    white-space:pre-line;
}

/* CTA */
.sp-actions { display:flex; gap:12px; flex-wrap:wrap; }
.sp-btn {
    display:inline-flex; align-items:center; gap:8px;
    padding:14px 28px; border-radius:12px;
    font-size:.92rem; font-weight:600;
    border:none; cursor:pointer;
    transition:transform .15s, box-shadow .2s, background .2s;
}
.sp-btn:hover { transform:translateY(-2px); }
.sp-btn:active { transform:translateY(0); }

.sp-btn-primary {
    background:var(--yy-accent); color:#fff;
    box-shadow:0 4px 16px rgba(0,201,167,.25);
}
.sp-btn-primary:hover { background:var(--yy-accent2); box-shadow:0 6px 24px rgba(0,201,167,.35); }
.sp-btn-primary:disabled {
    background:rgba(0,0,0,.1); color:var(--yy-dim);
    box-shadow:none; cursor:not-allowed; transform:none;
}

.sp-btn-outline {
    background:transparent; color:var(--yy-accent);
    border:2px solid var(--yy-accent);
}
.sp-btn-outline:hover { background:rgba(0,201,167,.08); }

/* Back link */
.sp-back {
    display:inline-flex; align-items:center; gap:6px;
    font-size:.85rem; color:var(--yy-accent); font-weight:500;
    margin-top:28px; transition:gap .2s;
}
.sp-back:hover { gap:10px; }

/* ═══════════════════════════════════════════
   LIGHTBOX
   ═══════════════════════════════════════════ */
.sp-lightbox {
    position:fixed; inset:0;
    background:rgba(0,0,0,.88);
    z-index:99999;
    display:none; /* JS will toggle */
    align-items:center; justify-content:center;
    flex-direction:column;
}
.sp-lightbox.open { display:flex; }

.sp-lightbox-close {
    position:absolute; top:20px; right:24px;
    background:rgba(255,255,255,.15); border:none;
    color:#fff; font-size:1.4rem;
    width:44px; height:44px; border-radius:50%;
    cursor:pointer; display:flex; align-items:center; justify-content:center;
    transition:background .2s;
    z-index:10;
}
.sp-lightbox-close:hover { background:rgba(255,255,255,.25); }

.sp-lightbox-img-wrap {
    max-width:90vw; max-height:78vh;
    display:flex; align-items:center; justify-content:center;
    position:relative;
}
.sp-lightbox-img-wrap img {
    max-width:100%; max-height:78vh;
    object-fit:contain; border-radius:8px;
    box-shadow:0 8px 48px rgba(0,0,0,.4);
    transition:opacity .3s;
}

.sp-lightbox-nav {
    position:absolute; top:50%; transform:translateY(-50%);
    background:rgba(255,255,255,.15); border:none;
    color:#fff; font-size:1.5rem;
    width:48px; height:48px; border-radius:50%;
    cursor:pointer; display:flex; align-items:center; justify-content:center;
    transition:background .2s;
}
.sp-lightbox-nav:hover { background:rgba(255,255,255,.3); }
.sp-lightbox-prev { left:-64px; }
.sp-lightbox-next { right:-64px; }
@media(max-width:768px) {
    .sp-lightbox-prev { left:8px; }
    .sp-lightbox-next { right:8px; }
}

.sp-lightbox-counter {
    color:rgba(255,255,255,.6); font-size:.82rem; font-weight:500;
    margin-top:16px;
}

.sp-lightbox-thumbs {
    display:flex; gap:8px; margin-top:14px;
    overflow-x:auto; max-width:90vw;
    padding-bottom:6px;
}
.sp-lightbox-thumb {
    flex:0 0 56px; height:56px; border-radius:8px;
    overflow:hidden; cursor:pointer;
    border:2px solid transparent;
    opacity:.5; transition:opacity .2s, border-color .2s;
}
.sp-lightbox-thumb.active { border-color:var(--yy-accent); opacity:1; }
.sp-lightbox-thumb img { width:100%; height:100%; object-fit:cover; }
</style>

<div id="sp-detail">
<div class="sp-container">

<!-- Breadcrumb -->
<div class="sp-breadcrumb" data-aos="fade-in">
    <a href="{{ route('spareparts') }}{{ $ref ? '?ref='.$ref : '' }}">Sparepart</a>
    <i class="bx bx-chevron-right"></i>
    @if($sparepart->kategori)
        <span>{{ $sparepart->kategori->nama_kategori }}</span>
        <i class="bx bx-chevron-right"></i>
    @endif
    <span>{{ $sparepart->nama_sparepart }}</span>
</div>

<div class="sp-main">
    <!-- ═══ LEFT: Gallery ═══ -->
    <div class="sp-gallery" data-aos="fade-right" data-aos-delay="100">
        @php
            $photos = $sparepart->photos; // array from accessor
            $hasPhotos = !empty($photos);
        @endphp

        <div class="sp-gallery-main" onclick="openLightbox(0)">
            @if($hasPhotos)
                <img src="{{ asset('uploads/' . $photos[0]) }}" alt="{{ $sparepart->nama_sparepart }}" id="sp-main-img">
                @if(count($photos) > 1)
                    <div class="sp-photo-count"><i class="bx bx-images"></i> {{ count($photos) }} foto</div>
                @endif
            @else
                <img src="{{ asset('img/no_image.png') }}" alt="No Image" id="sp-main-img">
            @endif
        </div>

        @if($hasPhotos && count($photos) > 1)
        <div class="sp-thumbs">
            @foreach($photos as $idx => $photo)
                <div class="sp-thumb {{ $idx === 0 ? 'active' : '' }}" onclick="selectThumb({{ $idx }})">
                    <img src="{{ asset('uploads/' . $photo) }}" alt="Foto {{ $idx + 1 }}">
                </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- ═══ RIGHT: Info ═══ -->
    <div class="sp-info" data-aos="fade-left" data-aos-delay="200">
        @if($sparepart->kategori)
            <div class="sp-category-badge">
                <i class="bx bx-category"></i>
                {{ $sparepart->kategori->nama_kategori }}
            </div>
        @endif

        <h1 class="sp-title">{{ $sparepart->nama_sparepart }}</h1>

        @if($sparepart->kode_sparepart)
            <div class="sp-code">Kode: <span>{{ $sparepart->kode_sparepart }}</span></div>
        @endif

        <!-- Stock -->
        <div class="sp-stock-row">
            @if($sparepart->stok_sparepart > 0)
                <span class="sp-stock-badge in-stock">
                    <i class="bx bx-check-circle"></i> Tersedia
                </span>
            @else
                <span class="sp-stock-badge out-stock">
                    <i class="bx bx-x-circle"></i> Stok Kosong
                </span>
            @endif
        </div>

        <!-- Price -->
        <div class="sp-price-box">
            @if($ismember)
                <div class="sp-price-row">
                    <div class="sp-price-main">Rp {{ number_format($sparepart->harga_ecer) }},-</div>
                    <div class="sp-price-label">per unit</div>
                </div>
            @else
                <div class="sp-price-hidden">
                    <i class="bx bx-lock-alt"></i>
                    <span>Masukkan kode invite untuk melihat harga</span>
                </div>
            @endif
        </div>

        <!-- Description -->
        @if($sparepart->desc_sparepart && $sparepart->desc_sparepart != '-')
            <div class="sp-desc-title"><i class="bx bx-info-circle"></i> Deskripsi Produk</div>
            <div class="sp-desc-content">{{ $sparepart->desc_sparepart }}</div>
        @endif

        <!-- Actions -->
        <div class="sp-actions">
            <form action="{{ route('add_sparepart_cart', $sparepart->id) }}" method="POST" class="ajax-cart-form" style="margin:0;">
                @csrf
                @method('PUT')
                <input type="hidden" name="kode_invite" value="{{ $ref ?? '' }}">
                <button type="submit" class="sp-btn sp-btn-primary" {{ $sparepart->stok_sparepart <= 0 ? 'disabled' : '' }}>
                    <i class="bx bx-cart-add"></i>
                    {{ $sparepart->stok_sparepart > 0 ? 'Tambah ke Keranjang' : 'Stok Habis' }}
                </button>
            </form>
        </div>

        <a href="{{ route('spareparts') }}{{ $ref ? '?ref='.$ref : '' }}" class="sp-back">
            <i class="bx bx-arrow-back"></i> Kembali ke Daftar Sparepart
        </a>
    </div>
</div>

</div><!-- sp-container -->
</div><!-- sp-detail -->

<!-- ═══════════════════════════════════════════
     LIGHTBOX OVERLAY
     ═══════════════════════════════════════════ -->
<div class="sp-lightbox" id="sp-lightbox">
    <button class="sp-lightbox-close" onclick="closeLightbox()">
        <i class="bx bx-x"></i>
    </button>

    <div class="sp-lightbox-img-wrap">
        <button class="sp-lightbox-nav sp-lightbox-prev" onclick="lightboxNav(-1)">
            <i class="bx bx-chevron-left"></i>
        </button>
        <img src="" alt="Zoom" id="sp-lightbox-img">
        <button class="sp-lightbox-nav sp-lightbox-next" onclick="lightboxNav(1)">
            <i class="bx bx-chevron-right"></i>
        </button>
    </div>

    <div class="sp-lightbox-counter" id="sp-lightbox-counter"></div>

    <div class="sp-lightbox-thumbs" id="sp-lightbox-thumbs"></div>
</div>

@endsection

@section('content-script')
<script>
(function(){
    // Photo data from server
    var photos = @json($sparepart->photos ?? []);
    var photoUrls = photos.map(function(p){ return '{{ asset("uploads") }}/' + p; });
    var currentIndex = 0;

    // ── Thumbnail selection (main page gallery) ──
    window.selectThumb = function(idx){
        if(!photoUrls[idx]) return;
        currentIndex = idx;
        document.getElementById('sp-main-img').src = photoUrls[idx];
        document.querySelectorAll('.sp-thumb').forEach(function(el, i){
            el.classList.toggle('active', i === idx);
        });
    };

    // ── Lightbox ──
    var lightbox = document.getElementById('sp-lightbox');
    var lbImg = document.getElementById('sp-lightbox-img');
    var lbCounter = document.getElementById('sp-lightbox-counter');
    var lbThumbs = document.getElementById('sp-lightbox-thumbs');

    function renderLightboxThumbs(){
        lbThumbs.innerHTML = '';
        photoUrls.forEach(function(url, i){
            var div = document.createElement('div');
            div.className = 'sp-lightbox-thumb' + (i === currentIndex ? ' active' : '');
            div.onclick = function(){ lightboxGo(i); };
            var img = document.createElement('img');
            img.src = url;
            div.appendChild(img);
            lbThumbs.appendChild(div);
        });
    }

    function updateLightbox(){
        if(!photoUrls.length) return;
        lbImg.src = photoUrls[currentIndex];
        lbCounter.textContent = (currentIndex + 1) + ' / ' + photoUrls.length;
        // Update thumb active
        lbThumbs.querySelectorAll('.sp-lightbox-thumb').forEach(function(el, i){
            el.classList.toggle('active', i === currentIndex);
        });
    }

    window.openLightbox = function(idx){
        if(!photoUrls.length) return;
        currentIndex = idx;
        renderLightboxThumbs();
        updateLightbox();
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeLightbox = function(){
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
    };

    window.lightboxNav = function(dir){
        currentIndex = (currentIndex + dir + photoUrls.length) % photoUrls.length;
        updateLightbox();
        // Sync main gallery thumb
        selectThumb(currentIndex);
    };

    window.lightboxGo = function(idx){
        currentIndex = idx;
        updateLightbox();
        selectThumb(idx);
    };

    // Close on backdrop click
    lightbox.addEventListener('click', function(e){
        if(e.target === lightbox) closeLightbox();
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e){
        if(!lightbox.classList.contains('open')) return;
        if(e.key === 'Escape') closeLightbox();
        if(e.key === 'ArrowLeft') lightboxNav(-1);
        if(e.key === 'ArrowRight') lightboxNav(1);
    });

    // Touch swipe support
    var touchStartX = 0;
    lightbox.addEventListener('touchstart', function(e){
        touchStartX = e.changedTouches[0].screenX;
    }, {passive: true});
    lightbox.addEventListener('touchend', function(e){
        var diff = e.changedTouches[0].screenX - touchStartX;
        if(Math.abs(diff) > 50){
            lightboxNav(diff > 0 ? -1 : 1);
        }
    }, {passive: true});
})();
</script>
@endsection
