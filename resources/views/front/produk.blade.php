@extends('front.layout.app')
@section('content-app')

<style>
#yy-wrap {
    font-family:'Segoe UI',system-ui,-apple-system,sans-serif;
    color:var(--yy-text); background:var(--yy-dark); min-height:100vh;
}
/* hero */
#yy-wrap .yy-hero {
    position:relative; min-height:300px;
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#ffffff 0%,#f3f4f6 50%,#ffffff 100%);
    overflow:hidden; padding:52px 20px;
}
#yy-wrap .yy-hero::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse 70% 55% at 30% 50%,rgba(0,201,167,.11) 0%,transparent 70%);
    pointer-events:none;
}
#yy-wrap .yy-hero::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:1px;
    background:linear-gradient(90deg,transparent,var(--yy-accent),transparent);
}
#yy-wrap .yy-hero-inner { position:relative; z-index:1; text-align:center; max-width:820px; width:100%; }
#yy-wrap .yy-hero h1 {
    font-size:clamp(1.7rem,3.5vw,2.4rem); font-weight:700; color:var(--yy-white);
    margin-bottom:6px;
}
#yy-wrap .yy-hero h1 span { color:var(--yy-accent); }
#yy-wrap .yy-hero h2 { font-size:1rem; color:var(--yy-dim); font-weight:400; margin-bottom:24px; }

/* search bar */
#yy-wrap .yy-search-bar {
    display:flex; gap:10px; flex-wrap:wrap; justify-content:center;
}
#yy-wrap .yy-search-bar input[type="text"] {
    flex:1 1 200px; max-width:480px;
    background:#ffffff; border:1px solid var(--yy-border);
    border-radius:10px; padding:12px 18px;
    color:var(--yy-text); font-size:.92rem; outline:none;
    transition:border-color .25s;
}
#yy-wrap .yy-search-bar input[type="text"]::placeholder { color:var(--yy-dim); }
#yy-wrap .yy-search-bar input[type="text"]:focus { border-color:var(--yy-accent); }
#yy-wrap .yy-search-bar input[type="text"].yy-invite { flex:0 1 200px; max-width:200px; }
#yy-wrap .yy-search-bar button, #yy-wrap .yy-search-bar input[type="submit"] {
    background:var(--yy-accent); color:#0f1117; border:none; border-radius:10px;
    padding:12px 26px; font-weight:600; font-size:.92rem; cursor:pointer;
    transition:background .25s, transform .15s;
}
#yy-wrap .yy-search-bar button:hover, #yy-wrap .yy-search-bar input[type="submit"]:hover {
    background:var(--yy-accent2); transform:translateY(-1px);
}

/* brands strip */
#yy-wrap .yy-brands {
    background:#ffffff; border-bottom:1px solid var(--yy-border);
    padding:18px 0; display:flex; justify-content:center; gap:40px; flex-wrap:wrap;
}
#yy-wrap .yy-brands img {
    height:30px; width:auto; filter:grayscale(100%) opacity(0.6); transition:opacity .3s, filter .3s;
}
#yy-wrap .yy-brands img:hover { opacity:1; filter:grayscale(0%); }

/* product grid */
#yy-wrap .yy-content { padding:56px 0 80px; }
#yy-wrap .yy-product-grid {
    display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr));
    gap:22px; max-width:1150px; margin:0 auto; padding:0 24px;
}
/* product card */
#yy-wrap .yy-prod-card {
    background:var(--yy-card); border:1px solid var(--yy-border); border-radius:14px;
    overflow:hidden; display:flex; flex-direction:column;
    transition:transform .25s, border-color .25s, box-shadow .25s;
}
#yy-wrap .yy-prod-card:hover {
    transform:translateY(-4px); border-color:var(--yy-accent);
    box-shadow:0 8px 28px rgba(0,201,167,.13);
}
#yy-wrap .yy-prod-img {
    width:100%; aspect-ratio:1; object-fit:cover; display:block;
    background:#f3f4f6;
}
#yy-wrap .yy-prod-body { padding:18px; flex:1; display:flex; flex-direction:column; }
#yy-wrap .yy-prod-body h5 {
    font-size:.95rem; color:var(--yy-white); margin-bottom:3px; font-weight:600;
}
#yy-wrap .yy-prod-body .yy-cat {
    font-size:.8rem; color:var(--yy-dim); margin-bottom:10px;
}
#yy-wrap .yy-prod-body .yy-price {
    font-size:1.15rem; font-weight:700; color:var(--yy-accent); margin-bottom:auto;
}
#yy-wrap .yy-prod-footer {
    display:flex; align-items:center; justify-content:space-between;
    margin-top:14px; padding-top:12px; border-top:1px solid var(--yy-border);
}
#yy-wrap .yy-stock-badge {
    font-size:.75rem; font-weight:600; padding:3px 10px; border-radius:20px;
}
#yy-wrap .yy-stock-badge.available { background:rgba(0,201,167,.15); color:var(--yy-accent); }
#yy-wrap .yy-stock-badge.empty { background:rgba(239,68,68,.15); color:#f87171; }
#yy-wrap .yy-btn-order {
    background:var(--yy-accent); color:#0f1117; border:none; border-radius:8px;
    padding:7px 18px; font-weight:600; font-size:.82rem; cursor:pointer;
    transition:background .2s;
}
#yy-wrap .yy-btn-order:hover { background:var(--yy-accent2); }

/* empty / alert */
#yy-wrap .yy-alert {
    max-width:600px; margin:60px auto; text-align:center;
    background:var(--yy-card); border:1px solid var(--yy-border); border-radius:14px;
    padding:44px 28px;
}
#yy-wrap .yy-alert-icon { font-size:2.2rem; color:var(--yy-dim); margin-bottom:12px; }
#yy-wrap .yy-alert p { color:var(--yy-dim); font-size:.95rem; margin:0; }
#yy-wrap .yy-alert.danger .yy-alert-icon { color:#f87171; }
</style>

<div id="yy-wrap">

<!-- HERO + SEARCH -->
<section class="yy-hero">
    <div class="yy-hero-inner" data-aos="fade-in" data-aos-delay="150">
        <h1>Produk <span>Kami</span></h1>
        <h2>Handphone, Laptop, Aksesori dan Berbagai Lainnya</h2>
        <form action="{{ route('product') }}" method="GET">
            <div class="yy-search-bar">
                <input type="text" name="q"
                    @if (isset($request->q) != null) value="{{ $request->q }}" @endif
                    placeholder="Cari produk...">
                <input type="text" name="ref" class="yy-invite"
                    @if (isset($request) != null) value="{{ $request->ref }}" @endif
                    placeholder="Kode Invite">
                <input type="submit" value="Cari">
            </div>
        </form>
    </div>
</section>

<!-- BRANDS -->
<div class="yy-brands">
    <img src="{{ asset('public/') }}/img/ip.png"      alt="iPhone">
    <img src="{{ asset('public/') }}/img/oppo.png"    alt="Oppo">
    <img src="{{ asset('public/') }}/img/samsung.png" alt="Samsung">
    <img src="{{ asset('public/') }}/img/vivo.png"    alt="Vivo">
    <img src="{{ asset('public/') }}/img/xiaomi.png"  alt="Xiaomi">
    <img src="{{ asset('public/') }}/img/huawei.png"  alt="Huawei">
</div>

<!-- PRODUCT GRID -->
<section class="yy-content">
    @if (isset($data) != null)
        @forelse ($data as $item)
            <div class="yy-product-grid" style="display:none;"></div><!-- trigger once -->
        @empty
        @endforelse

        <div class="yy-product-grid">
            @foreach ($data as $item)
            <div class="yy-prod-card" data-aos="fade-up" data-aos-delay="80">
                <!-- Image -->
                @if ($item->foto_barang != '-')
                    <img src="{{ asset('public/uploads/' . $item->foto_barang) }}" class="yy-prod-img" alt="{{ $item->nama_barang }}">
                @else
                    <img src="{{ asset('public/img/no_image.png') }}" class="yy-prod-img" alt="No Image">
                @endif

                <div class="yy-prod-body">
                    <h5>{{ $item->nama_barang }}</h5>
                    <div class="yy-cat">{{ $item->nama_kategori }}</div>
                    @if ($ismember)
                        <div class="yy-price">Rp {{ number_format($item->harga_jual_barang) }},-</div>
                    @endif

                    <div class="yy-prod-footer">
                        <span class="yy-stock-badge {{ $item->stok_barang > 0 ? 'available' : 'empty' }}">
                            {{ $item->stok_barang > 0 ? 'Tersedia' : 'Kosong' }}
                        </span>
                        @if ($ismember)
                            <form action="{{ route('add_produk_cart', $item->id_produk) }}" method="POST" style="margin:0;">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="kode_invite" value="{{ $request->ref }}">
                                <button class="yy-btn-order" {{ $item->stok_barang <= 0 ? 'disabled style="opacity:.4;cursor:not-allowed;"' : '' }}>
                                    <i class="bx bx-cart"></i> Pesan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- empty state if @forelse triggered --}}
        @if ($data->isEmpty())
            <div class="yy-alert danger">
                <div class="yy-alert-icon"><i class="bx bx-search"></i></div>
                <p>Produk tidak ditemukan. Coba kata pencarian lain.</p>
            </div>
        @endif
    @else
        <div class="yy-alert">
            <div class="yy-alert-icon"><i class="bx bx-package"></i></div>
            <p>Belum ada produk yang ditampilkan.</p>
        </div>
    @endif
</section>

</div>
@endsection
