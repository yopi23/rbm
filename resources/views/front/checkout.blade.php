@extends('front.layout.app')
@section('content-app')

<style>
#yy-wrap {
    font-family:'Segoe UI',system-ui,-apple-system,sans-serif;
    color:var(--yy-text); background:var(--yy-dark); min-height:100vh;
}
/* hero */
#yy-wrap .yy-hero {
    position:relative; min-height:200px;
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#ffffff 0%,#f3f4f6 50%,#ffffff 100%);
    overflow:hidden; padding:44px 20px;
}
#yy-wrap .yy-hero::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse 55% 50% at 60% 50%,rgba(0,201,167,.05) 0%,transparent 70%);
    pointer-events:none;
}
#yy-wrap .yy-hero::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:1px;
    background:linear-gradient(90deg,transparent,var(--yy-accent),transparent);
}
#yy-wrap .yy-hero-inner { position:relative; z-index:1; text-align:center; }
#yy-wrap .yy-hero h1 { font-size:clamp(1.5rem,2.8vw,2rem); font-weight:700; color:var(--yy-white); margin-bottom:4px; }
#yy-wrap .yy-hero h1 span { color:var(--yy-accent); }
#yy-wrap .yy-hero h2 { font-size:.92rem; color:var(--yy-dim); font-weight:400; margin:0; }

/* brands */
#yy-wrap .yy-brands {
    background:#ffffff; border-bottom:1px solid var(--yy-border);
    padding:16px 0; display:flex; justify-content:center; gap:40px; flex-wrap:wrap;
}
#yy-wrap .yy-brands img { height:26px; width:auto; filter:grayscale(100%) opacity(0.6); transition:opacity .3s, filter .3s; }
#yy-wrap .yy-brands img:hover { opacity:1; filter:grayscale(0%); }

/* checkout layout */
#yy-wrap .yy-checkout-wrap {
    max-width:1060px; margin:0 auto; padding:48px 24px 80px;
    display:grid; grid-template-columns:1.45fr 1fr; gap:32px; align-items:start;
}
@media(max-width:750px){ #yy-wrap .yy-checkout-wrap { grid-template-columns:1fr; } }

/* ── ORDER SUMMARY CARD ── */
#yy-wrap .yy-card {
    background:var(--yy-card); border:1px solid var(--yy-border); border-radius:16px; overflow:hidden;
}
#yy-wrap .yy-card-header {
    padding:18px 24px; border-bottom:1px solid var(--yy-border);
    display:flex; align-items:center; gap:12px;
}
#yy-wrap .yy-card-header-icon {
    width:38px; height:38px; border-radius:11px;
    background:rgba(0,201,167,.12);
    display:flex; align-items:center; justify-content:center;
    color:var(--yy-accent); font-size:1.1rem;
}
#yy-wrap .yy-card-header h5 { font-size:1rem; color:var(--yy-white); margin:0; font-weight:600; }

/* order table */
#yy-wrap .yy-order-table { width:100%; border-collapse:collapse; }
#yy-wrap .yy-order-table thead th {
    text-align:left; padding:12px 20px; font-size:.74rem;
    color:var(--yy-dim); font-weight:600; text-transform:uppercase; letter-spacing:.9px;
    border-bottom:1px solid var(--yy-border); background:#f9fafb;
}
#yy-wrap .yy-order-table thead th.yy-r { text-align:right; }
#yy-wrap .yy-order-table thead th.yy-c { text-align:center; }
#yy-wrap .yy-order-table tbody tr { border-bottom:1px solid var(--yy-border); }
#yy-wrap .yy-order-table tbody tr:last-child { border-bottom:none; }
#yy-wrap .yy-order-table td { padding:14px 20px; vertical-align:middle; }
#yy-wrap .yy-order-product { display:flex; align-items:center; gap:12px; }
#yy-wrap .yy-order-thumb { width:44px; height:44px; border-radius:9px; object-fit:cover; background:#f3f4f6; flex-shrink:0; }
#yy-wrap .yy-order-name { font-size:.88rem; color:var(--yy-white); font-weight:500; }
#yy-wrap .yy-order-table .yy-r { text-align:right; }
#yy-wrap .yy-order-table .yy-c { text-align:center; }
#yy-wrap .yy-price-sm { color:var(--yy-text); font-size:.86rem; }
#yy-wrap .yy-qty-sm { color:var(--yy-white); font-weight:600; font-size:.88rem; }
#yy-wrap .yy-sub-sm { color:var(--yy-accent); font-weight:700; font-size:.9rem; }
/* total footer */
#yy-wrap .yy-order-total {
    padding:16px 20px; background:#f9fafb; border-top:2px solid var(--yy-border);
    display:flex; justify-content:space-between; align-items:center;
}
#yy-wrap .yy-order-total .yy-total-label { font-size:.82rem; color:var(--yy-dim); text-transform:uppercase; letter-spacing:.9px; }
#yy-wrap .yy-order-total .yy-total-val { font-size:1.3rem; font-weight:700; color:var(--yy-white); }
#yy-wrap .yy-order-total .yy-total-val span { color:var(--yy-accent); }

/* ── FORM CARD ── */
#yy-wrap .yy-form-group { padding:20px 24px 0; }
#yy-wrap .yy-form-group label {
    display:block; font-size:.8rem; color:var(--yy-dim);
    text-transform:uppercase; letter-spacing:.8px; font-weight:600;
    margin-bottom:7px;
}
#yy-wrap .yy-form-group input[type="text"],
#yy-wrap .yy-form-group input[type="number"],
#yy-wrap .yy-form-group textarea {
    width:100%; box-sizing:border-box;
    background:rgba(0,0,0,.02); border:1px solid var(--yy-border);
    border-radius:10px; padding:11px 16px;
    color:var(--yy-white); font-size:.9rem; outline:none;
    transition:border-color .25s, box-shadow .2s;
    font-family:inherit;
}
#yy-wrap .yy-form-group input::placeholder,
#yy-wrap .yy-form-group textarea::placeholder { color:var(--yy-dim); }
#yy-wrap .yy-form-group input:focus,
#yy-wrap .yy-form-group textarea:focus {
    border-color:var(--yy-accent);
    box-shadow:0 0 0 3px rgba(0,201,167,.15);
}
#yy-wrap .yy-form-group textarea { resize:vertical; min-height:90px; }
#yy-wrap .yy-form-group textarea.yy-short { min-height:70px; }

/* submit */
#yy-wrap .yy-form-footer { padding:24px; }
#yy-wrap .yy-btn-submit {
    width:100%; padding:14px; border:none; border-radius:10px;
    background:var(--yy-accent); color:#0f1117;
    font-weight:700; font-size:.95rem; cursor:pointer;
    display:flex; align-items:center; justify-content:center; gap:8px;
    transition:background .25s, transform .15s, box-shadow .25s;
}
#yy-wrap .yy-btn-submit:hover {
    background:var(--yy-accent2); transform:translateY(-2px);
    box-shadow:0 6px 18px rgba(0,201,167,.3); color:#fff;
}

/* step indicator */
#yy-wrap .yy-steps {
    display:flex; justify-content:center; gap:0; padding:28px 24px 8px;
}
#yy-wrap .yy-step {
    display:flex; align-items:center; gap:8px; color:var(--yy-dim); font-size:.8rem;
    text-transform:uppercase; letter-spacing:.7px;
}
#yy-wrap .yy-step .yy-step-dot {
    width:26px; height:26px; border-radius:50%; border:2px solid var(--yy-border);
    display:flex; align-items:center; justify-content:center;
    font-size:.72rem; font-weight:700; color:var(--yy-dim);
}
#yy-wrap .yy-step.done .yy-step-dot {
    background:var(--yy-accent); border-color:var(--yy-accent); color:#0f1117;
}
#yy-wrap .yy-step.done { color:var(--yy-accent); }
#yy-wrap .yy-step.active .yy-step-dot {
    border-color:var(--yy-accent); color:var(--yy-accent);
}
#yy-wrap .yy-step.active { color:var(--yy-white); font-weight:600; }
#yy-wrap .yy-step-line {
    width:50px; height:2px; background:var(--yy-border); margin:0 4px;
}
#yy-wrap .yy-step-line.done { background:var(--yy-accent); }
</style>

<div id="yy-wrap">

<!-- HERO -->
<section class="yy-hero">
    <div class="yy-hero-inner" data-aos="fade-in" data-aos-delay="150">
        <h1>Checkout <span>Pesanan</span></h1>
        <h2>Lengkapi data dan selesaikan pesanan Anda</h2>
    </div>
</section>

<!-- BRANDS -->
<div class="yy-brands">
    <img src="{{asset('public/')}}/img/ip.png"      alt="iPhone">
    <img src="{{asset('public/')}}/img/oppo.png"    alt="Oppo">
    <img src="{{asset('public/')}}/img/samsung.png" alt="Samsung">
    <img src="{{asset('public/')}}/img/vivo.png"    alt="Vivo">
    <img src="{{asset('public/')}}/img/xiaomi.png"  alt="Xiaomi">
    <img src="{{asset('public/')}}/img/huawei.png"  alt="Huawei">
</div>

<!-- STEP INDICATOR -->
<div class="yy-steps">
    <div class="yy-step done"><div class="yy-step-dot"><i class="bx bx-check"></i></div> Keranjang</div>
    <div class="yy-step-line done"></div>
    <div class="yy-step active"><div class="yy-step-dot">2</div> Checkout</div>
    <div class="yy-step-line"></div>
    <div class="yy-step"><div class="yy-step-dot">3</div> Selesai</div>
</div>

<!-- CHECKOUT GRID -->
<div class="yy-checkout-wrap">

    <!-- LEFT: Order Summary -->
    <div class="yy-card" data-aos="fade-right">
        <div class="yy-card-header">
            <div class="yy-card-header-icon"><i class="bx bxs-receipt"></i></div>
            <h5>Ringkasan Pesanan</h5>
        </div>

        @php $total = 0; $no = 1; @endphp

        <table class="yy-order-table">
            <thead>
                <tr>
                    <th style="width:36px">#</th>
                    <th>Produk</th>
                    <th class="yy-r">Harga</th>
                    <th class="yy-c">Qty</th>
                    <th class="yy-r">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @if (session('cart_produk'))
                @foreach (session('cart_produk') as $id => $item)
                    @php $total += $item['price'] * $item['qty']; @endphp
                    <tr>
                        <td><span style="color:var(--yy-dim);font-size:.8rem;">{{$no++}}</span></td>
                        <td>
                            <div class="yy-order-product">
                                @if ($item['photo'] != '-')
                                    <img src="{{asset('public/uploads/'.$item['photo'])}}" class="yy-order-thumb" alt="">
                                @else
                                    <img src="{{asset('public/img/no_image.png')}}" class="yy-order-thumb" alt="">
                                @endif
                                <span class="yy-order-name">{{$item['name']}}</span>
                            </div>
                        </td>
                        <td class="yy-r"><span class="yy-price-sm">Rp {{number_format($item['price'])}},-</span></td>
                        <td class="yy-c"><span class="yy-qty-sm">{{$item['qty']}}</span></td>
                        <td class="yy-r"><span class="yy-sub-sm">Rp {{number_format($item['price'] * $item['qty'])}},-</span></td>
                    </tr>
                @endforeach
                @endif

                @if (session('cart_sparepart'))
                @foreach (session('cart_sparepart') as $id => $item)
                    @php $total += $item['price'] * $item['qty']; @endphp
                    <tr>
                        <td><span style="color:var(--yy-dim);font-size:.8rem;">{{$no++}}</span></td>
                        <td>
                            <div class="yy-order-product">
                                @if ($item['photo'] != '-')
                                    <img src="{{asset('public/uploads/'.$item['photo'])}}" class="yy-order-thumb" alt="">
                                @else
                                    <img src="{{asset('public/img/no_image.png')}}" class="yy-order-thumb" alt="">
                                @endif
                                <span class="yy-order-name">{{$item['name']}}</span>
                            </div>
                        </td>
                        <td class="yy-r"><span class="yy-price-sm">Rp {{number_format($item['price'])}},-</span></td>
                        <td class="yy-c"><span class="yy-qty-sm">{{$item['qty']}}</span></td>
                        <td class="yy-r"><span class="yy-sub-sm">Rp {{number_format($item['price'] * $item['qty'])}},-</span></td>
                    </tr>
                @endforeach
                @endif
            </tbody>
        </table>

        <div class="yy-order-total">
            <span class="yy-total-label">Total</span>
            <span class="yy-total-val">Rp <span>{{number_format($total)}}</span>,-</span>
        </div>
    </div>

    <!-- RIGHT: Pemesan Form -->
    <div class="yy-card" data-aos="fade-left" data-aos-delay="100">
        <div class="yy-card-header">
            <div class="yy-card-header-icon"><i class="bx bxs-user"></i></div>
            <h5>Data Pemesan</h5>
        </div>

        <form action="{{route('buat_pesanan')}}" method="POST">
            @csrf

            <div class="yy-form-group">
                <label>Nama</label>
                <input type="text" name="nama_pemesan" id="nama_pemesan" placeholder="Nama lengkap" required>
            </div>
            <div class="yy-form-group">
                <label>No Telepon</label>
                <input type="number" name="no_telp" id="no_telp" placeholder="08xxxxxxxxx" required>
            </div>
            <input type="hidden" value="-" name="email" id="email">
            <div class="yy-form-group">
                <label>Alamat Pengiriman</label>
                <textarea name="alamat" id="alamat" placeholder="Alamat lengkap Anda..." required></textarea>
            </div>
            <div class="yy-form-group">
                <label>Catatan Pesanan <span style="color:var(--yy-dim);font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                <textarea name="catatan_pesanan" id="catatan_pesanan" class="yy-short" placeholder="Tambahkan catatan untuk kami..."></textarea>
            </div>

            <div class="yy-form-footer">
                <button type="submit" class="yy-btn-submit">
                    <i class="bx bx-send"></i> Kirim Pesanan
                </button>
            </div>
        </form>
    </div>

</div><!-- end checkout-wrap -->

</div>
@endsection
