@extends('front.layout.app')
@section('content-app')

<style>
#yy-wrap {
    font-family:'Segoe UI',system-ui,-apple-system,sans-serif;
    color:var(--yy-text); background:var(--yy-dark); min-height:100vh;
}
#yy-wrap .yy-hero {
    position:relative; min-height:300px;
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#ffffff 0%,#f3f4f6 50%,#ffffff 100%);
    overflow:hidden; padding:52px 20px;
}
#yy-wrap .yy-hero::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse 65% 55% at 70% 45%,rgba(0,201,167,.10) 0%,transparent 70%);
    pointer-events:none;
}
#yy-wrap .yy-hero::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:1px;
    background:linear-gradient(90deg,transparent,var(--yy-accent),transparent);
}
#yy-wrap .yy-hero-inner { position:relative; z-index:1; text-align:center; max-width:820px; width:100%; }
#yy-wrap .yy-hero h1 { font-size:clamp(1.7rem,3.5vw,2.4rem); font-weight:700; color:var(--yy-white); margin-bottom:6px; }
#yy-wrap .yy-hero h1 span { color:var(--yy-accent); }
#yy-wrap .yy-hero h2 { font-size:1rem; color:var(--yy-dim); font-weight:400; margin-bottom:24px; }
#yy-wrap .yy-search-bar { display:flex; gap:10px; flex-wrap:wrap; justify-content:center; }
#yy-wrap .yy-search-bar input[type="text"] {
    flex:1 1 200px; max-width:480px;
    background:#ffffff; border:1px solid var(--yy-border);
    border-radius:10px; padding:12px 18px;
    color:var(--yy-white); font-size:.92rem; outline:none; transition:border-color .25s;
}
#yy-wrap .yy-search-bar input[type="text"]::placeholder { color:var(--yy-dim); }
#yy-wrap .yy-search-bar input[type="text"]:focus { border-color:var(--yy-accent); }
#yy-wrap .yy-search-bar input[type="text"].yy-invite { flex:0 1 200px; max-width:200px; }
#yy-wrap .yy-search-bar input[type="submit"] {
    background:var(--yy-accent); color:#0f1117; border:none; border-radius:10px;
    padding:12px 26px; font-weight:600; font-size:.92rem; cursor:pointer;
    transition:background .25s, transform .15s;
}
#yy-wrap .yy-search-bar input[type="submit"]:hover { background:var(--yy-accent2); transform:translateY(-1px); }
#yy-wrap .yy-brands {
    background:#ffffff; border-bottom:1px solid var(--yy-border);
    padding:18px 0; display:flex; justify-content:center; gap:40px; flex-wrap:wrap;
}
#yy-wrap .yy-brands img { height:30px; width:80px; object-fit:contain; filter:grayscale(100%) opacity(0.6); transition:opacity .3s, filter .3s; }
#yy-wrap .yy-brands img:hover { opacity:1; filter:grayscale(0%); }

/* ── Category Filter Tabs ── */
#yy-wrap .yy-category-tabs {
    background:#ffffff;
    border-bottom:1px solid var(--yy-border);
    padding:16px 24px;
}
#yy-wrap .yy-category-tabs-inner {
    max-width:1150px;
    margin:0 auto;
    display:flex;
    align-items:center;
    gap:8px;
    overflow-x:auto;
    scrollbar-width:none;
    -ms-overflow-style:none;
    padding-bottom:2px;
}
#yy-wrap .yy-category-tabs-inner::-webkit-scrollbar { display:none; }
#yy-wrap .yy-category-tabs-inner .yy-tab-label {
    font-size:.82rem;
    font-weight:600;
    color:var(--yy-dim);
    margin-right:8px;
    white-space:nowrap;
    display:flex;
    align-items:center;
    gap:5px;
}
#yy-wrap .yy-category-tabs-inner .yy-tab-label i { font-size:.95rem; }
#yy-wrap .yy-cat-tab {
    display:inline-flex;
    align-items:center;
    gap:5px;
    padding:7px 18px;
    border-radius:20px;
    font-size:.84rem;
    font-weight:500;
    color:var(--yy-text);
    background:rgba(0,0,0,.04);
    border:1px solid transparent;
    cursor:pointer;
    text-decoration:none;
    white-space:nowrap;
    transition:all .25s ease;
}
#yy-wrap .yy-cat-tab:hover {
    background:rgba(0,201,167,.08);
    border-color:rgba(0,201,167,.2);
    color:var(--yy-accent);
}
#yy-wrap .yy-cat-tab.active {
    background:rgba(0,201,167,.12);
    border-color:var(--yy-accent);
    color:var(--yy-accent);
    font-weight:600;
    box-shadow:0 2px 8px rgba(0,201,167,.15);
}
#yy-wrap .yy-cat-tab .yy-tab-count {
    font-size:.72rem;
    font-weight:700;
    background:rgba(0,0,0,.08);
    color:var(--yy-dim);
    padding:1px 7px;
    border-radius:10px;
    min-width:20px;
    text-align:center;
    transition:all .25s;
}
#yy-wrap .yy-cat-tab.active .yy-tab-count {
    background:var(--yy-accent);
    color:#fff;
}

#yy-wrap .yy-content { padding:56px 0 80px; }
#yy-wrap .yy-product-grid {
    display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr));
    gap:22px; max-width:1150px; margin:0 auto; padding:0 24px;
}
#yy-wrap .yy-prod-card {
    background:var(--yy-card); border:1px solid var(--yy-border); border-radius:14px;
    overflow:hidden; display:flex; flex-direction:column;
    transition:transform .25s, border-color .25s, box-shadow .25s;
}
#yy-wrap .yy-prod-card:hover {
    transform:translateY(-4px); border-color:var(--yy-accent);
    box-shadow:0 8px 28px rgba(0,201,167,.13);
}
#yy-wrap .yy-prod-img { width:100%; aspect-ratio:1; object-fit:cover; display:block; background:#f3f4f6; }
#yy-wrap .yy-prod-body { padding:18px; flex:1; display:flex; flex-direction:column; }
#yy-wrap .yy-prod-body h5 { font-size:.95rem; color:var(--yy-text); margin-bottom:3px; font-weight:600; }
#yy-wrap .yy-prod-body .yy-cat { font-size:.8rem; color:var(--yy-dim); margin-bottom:10px; }
#yy-wrap .yy-prod-body .yy-price { font-size:1.15rem; font-weight:700; color:var(--yy-accent); margin-bottom:auto; }
#yy-wrap .yy-prod-footer {
    display:flex; align-items:center; justify-content:space-between;
    margin-top:14px; padding-top:12px; border-top:1px solid var(--yy-border);
}
#yy-wrap .yy-stock-badge { font-size:.75rem; font-weight:600; padding:3px 10px; border-radius:20px; }
#yy-wrap .yy-stock-badge.available { background:rgba(0,201,167,.15); color:var(--yy-accent); }
#yy-wrap .yy-stock-badge.empty { background:rgba(239,68,68,.15); color:#f87171; }
#yy-wrap .yy-btn-order {
    background:var(--yy-accent); color:#0f1117; border:none; border-radius:8px;
    padding:7px 18px; font-weight:600; font-size:.82rem; cursor:pointer; transition:background .2s;
}
#yy-wrap .yy-btn-order:hover { background:var(--yy-accent2); }
#yy-wrap .yy-btn-order:disabled { background:rgba(239,68,68,.35); color:#fff; cursor:not-allowed; }
#yy-wrap .yy-prod-link { text-decoration:none; color:inherit; display:flex; flex-direction:column; height:100%; }
#yy-wrap .yy-prod-card { cursor:pointer; }
#yy-wrap .yy-btn-order-label {
    color:var(--yy-accent); font-weight:600; font-size:.82rem;
    display:flex; align-items:center; gap:2px; transition:gap .2s;
}
#yy-wrap .yy-prod-card:hover .yy-btn-order-label { gap:6px; }
#yy-wrap .yy-alert {
    max-width:600px; margin:60px auto; text-align:center;
    background:var(--yy-card); border:1px solid var(--yy-border); border-radius:14px; padding:44px 28px;
}
#yy-wrap .yy-alert-icon { font-size:2.2rem; color:var(--yy-dim); margin-bottom:12px; }
#yy-wrap .yy-alert p { color:var(--yy-dim); font-size:.95rem; margin:0; }
#yy-wrap .yy-alert.danger .yy-alert-icon { color:#f87171; }

/* Responsive for category tabs */
@media(max-width:600px) {
    #yy-wrap .yy-category-tabs { padding:12px 16px; }
    #yy-wrap .yy-cat-tab { padding:6px 14px; font-size:.8rem; }
    #yy-wrap .yy-category-tabs-inner .yy-tab-label { display:none; }
}
</style>

<div id="yy-wrap">

<!-- HERO + SEARCH -->
<section class="yy-hero">
    <div class="yy-hero-inner" data-aos="fade-in" data-aos-delay="150">
        <h1>Butuh <span>Sparepart</span> HP?</h1>
        <h2>LCD, Casing, Modul dan Berbagai Lainnya</h2>
        <form action="{{ route('spareparts') }}" method="GET">
            <div class="yy-search-bar">
                <input type="text" name="q"
                    @if (isset($request->q) != null) value="{{ $request->q }}" @endif
                    placeholder="Cari sparepart...">
                <input type="text" name="ref" class="yy-invite"
                    @if (isset($request->ref) != null) value="{{ $request->ref }}" @endif
                    placeholder="Kode Invite">
                <input type="submit" value="Cari">
            </div>
        </form>
    </div>
</section>

<!-- BRANDS -->
<div class="yy-brands">
    <img src="{{ asset('img/ip.png') }}"      alt="iPhone">
    <img src="{{ asset('img/oppo.png') }}"    alt="Oppo">
    <img src="{{ asset('img/samsung.png') }}" alt="Samsung">
    <img src="{{ asset('img/vivo.png') }}"    alt="Vivo">
    <img src="{{ asset('img/xiaomi.png') }}"  alt="Xiaomi">
    <img src="{{ asset('img/huawei.png') }}"  alt="Huawei">
</div>

<!-- CATEGORY FILTER TABS -->
@if(isset($categories) && $categories->count() > 0)
<div class="yy-category-tabs">
    <div class="yy-category-tabs-inner">
        <span class="yy-tab-label"><i class="bx bx-filter-alt"></i> Kategori:</span>

        @php
            $activeKategori = $request->kategori ?? null;
            // Build base URL params (preserve q and ref)
            $baseParams = [];
            if($request->filled('q')) $baseParams['q'] = $request->q;
            if($request->filled('ref')) $baseParams['ref'] = $request->ref;
        @endphp

        <a href="{{ route('spareparts', $baseParams) }}"
           class="yy-cat-tab {{ !$activeKategori ? 'active' : '' }}" data-no-spa>
            Semua
            <span class="yy-tab-count">{{ $data->count() > 0 || !$activeKategori ? $data->count() : '' }}</span>
        </a>

        @foreach($categories as $cat)
            @php
                $catParams = array_merge($baseParams, ['kategori' => $cat->id]);
            @endphp
            <a href="{{ route('spareparts', $catParams) }}"
               class="yy-cat-tab {{ $activeKategori == $cat->id ? 'active' : '' }}" data-no-spa>
                {{ $cat->nama_kategori }}
            </a>
        @endforeach
    </div>
</div>
@endif

<!-- SPAREPART GRID -->
<section class="yy-content">
    @if (isset($data) != null)
        @if ($data->isEmpty())
            <div class="yy-alert danger">
                <div class="yy-alert-icon"><i class="bx bx-search"></i></div>
                <p>Sparepart tidak ditemukan. Coba kata pencarian lain.</p>
            </div>
        @else
            <div class="yy-product-grid">
                @foreach ($data as $item)
                <div class="yy-prod-card" data-aos="fade-up" data-aos-delay="80">
                    <a href="{{ route('sparepart.detail', $item->id_produk) }}{{ isset($request->ref) ? '?ref='.$request->ref : '' }}" class="yy-prod-link" style="text-decoration:none; color:inherit;">
                        @if ($item->foto_sparepart != '-')
                            <img src="{{ asset('uploads/' . $item->foto_sparepart) }}" class="yy-prod-img" alt="{{ $item->nama_sparepart }}">
                        @else
                            <img src="{{ asset('img/no_image.png') }}" class="yy-prod-img" alt="No Image">
                        @endif

                        <div class="yy-prod-body" style="padding-bottom: 0;">
                            <h5>{{ $item->nama_sparepart }}</h5>
                            <div class="yy-cat">{{ $item->nama_kategori }}</div>
                            @if ($ismember)
                                <div class="yy-price">Rp {{ number_format($item->harga_ecer) }},-</div>
                            @endif
                        </div>
                    </a>

                    <div class="yy-prod-footer" style="margin: 14px 18px 18px 18px; padding-top: 12px; border-top: 1px solid var(--yy-border); display: flex; align-items: center; justify-content: space-between;">
                        <span class="yy-stock-badge {{ $item->stok_sparepart > 0 ? 'available' : 'empty' }}">
                            {{ $item->stok_sparepart > 0 ? 'Tersedia' : 'Kosong' }}
                        </span>
                        
                        <form action="{{ route('add_sparepart_cart', $item->id_produk) }}" method="POST" class="ajax-cart-form" style="margin:0;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="kode_invite" value="{{ $request->ref ?? '' }}">
                            <button type="submit" class="yy-btn-order" {{ $item->stok_sparepart <= 0 ? 'disabled style="opacity:.4;cursor:not-allowed;"' : '' }}>
                                <i class="bx bx-cart"></i> Pesan
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    @else
        <div class="yy-alert">
            <div class="yy-alert-icon"><i class="bx bx-package"></i></div>
            <p>Belum ada sparepart yang ditampilkan.</p>
        </div>
    @endif
</section>

</div>
@endsection
