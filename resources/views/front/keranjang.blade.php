@extends('front.layout.app')
@section('content-app')

<style>
#yy-wrap {
    font-family:'Segoe UI',system-ui,-apple-system,sans-serif;
    color:var(--yy-text); background:var(--yy-dark); min-height:100vh;
}
/* hero */
#yy-wrap .yy-hero {
    position:relative; min-height:220px;
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#ffffff 0%,#f3f4f6 50%,#ffffff 100%);
    overflow:hidden; padding:48px 20px;
}
#yy-wrap .yy-hero::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse 60% 55% at 50% 50%,rgba(0,201,167,.05) 0%,transparent 70%);
    pointer-events:none;
}
#yy-wrap .yy-hero::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:1px;
    background:linear-gradient(90deg,transparent,var(--yy-accent),transparent);
}
#yy-wrap .yy-hero-inner { position:relative; z-index:1; text-align:center; }
#yy-wrap .yy-hero h1 { font-size:clamp(1.6rem,3vw,2.2rem); font-weight:700; color:var(--yy-white); margin-bottom:4px; }
#yy-wrap .yy-hero h1 span { color:var(--yy-accent); }
#yy-wrap .yy-hero h2 { font-size:.95rem; color:var(--yy-dim); font-weight:400; margin:0; }

/* brands */
#yy-wrap .yy-brands {
    background:#ffffff; border-bottom:1px solid var(--yy-border);
    padding:18px 0; display:flex; justify-content:center; gap:40px; flex-wrap:wrap;
}
#yy-wrap .yy-brands img { height:28px; width:80px; object-fit:contain; filter:grayscale(100%) opacity(0.6); transition:opacity .3s, filter .3s; }
#yy-wrap .yy-brands img:hover { opacity:1; filter:grayscale(0%); }

/* alerts */
#yy-wrap .yy-flash {
    max-width:900px; margin:24px auto 0; padding:0 24px;
}
#yy-wrap .yy-flash-item {
    padding:12px 18px; border-radius:10px; font-size:.88rem;
    display:flex; align-items:center; gap:10px; margin-bottom:8px;
}
#yy-wrap .yy-flash-item.success { background:rgba(0,201,167,.12); border:1px solid rgba(0,201,167,.25); color:var(--yy-accent); }
#yy-wrap .yy-flash-item.error { background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.25); color:#f87171; }

/* main content */
#yy-wrap .yy-content { padding:40px 0 80px; }
#yy-wrap .yy-cart-wrap { max-width:960px; margin:0 auto; padding:0 24px; }

/* cart card */
#yy-wrap .yy-cart-card {
    background:var(--yy-card); border:1px solid var(--yy-border); border-radius:16px;
    overflow:hidden;
}
#yy-wrap .yy-cart-header {
    padding:20px 28px; border-bottom:1px solid var(--yy-border);
    display:flex; align-items:center; gap:12px;
}
#yy-wrap .yy-cart-header-icon {
    width:40px; height:40px; border-radius:12px;
    background:rgba(0,201,167,.12);
    display:flex; align-items:center; justify-content:center;
    color:var(--yy-accent); font-size:1.2rem;
}
#yy-wrap .yy-cart-header h5 { font-size:1.05rem; color:var(--yy-white); margin:0; font-weight:600; }
#yy-wrap .yy-cart-header small { color:var(--yy-dim); font-size:.82rem; }

/* table */
#yy-wrap .yy-cart-table { width:100%; border-collapse:collapse; }
#yy-wrap .yy-cart-table thead th {
    text-align:left; padding:14px 20px; font-size:.77rem;
    color:var(--yy-dim); font-weight:600; text-transform:uppercase; letter-spacing:1px;
    border-bottom:1px solid var(--yy-border); background:#f9fafb;
}
#yy-wrap .yy-cart-table thead th:last-child { text-align:center; width:70px; }
#yy-wrap .yy-cart-table thead th.yy-right { text-align:right; }
#yy-wrap .yy-cart-table tbody tr { border-bottom:1px solid var(--yy-border); transition:background .2s; }
#yy-wrap .yy-cart-table tbody tr:last-child { border-bottom:none; }
#yy-wrap .yy-cart-table tbody tr:hover { background:rgba(0,0,0,.02); }
#yy-wrap .yy-cart-table td { padding:16px 20px; vertical-align:middle; }

/* row product col */
#yy-wrap .yy-cart-product { display:flex; align-items:center; gap:14px; }
#yy-wrap .yy-cart-thumb {
    width:52px; height:52px; border-radius:10px; object-fit:cover;
    background:#f3f4f6; flex-shrink:0;
}
#yy-wrap .yy-cart-product-name { font-size:.92rem; color:var(--yy-white); font-weight:500; }

/* price / qty / subtotal */
#yy-wrap .yy-cart-table .yy-right { text-align:right; }
#yy-wrap .yy-cart-table .yy-center { text-align:center; }
#yy-wrap .yy-price-val { color:var(--yy-text); font-size:.9rem; }
#yy-wrap .yy-qty-val { color:var(--yy-white); font-weight:600; font-size:.92rem; }
#yy-wrap .yy-subtotal-val { color:var(--yy-accent); font-weight:700; font-size:.95rem; }

/* yy-wrap base overrides for keranjang */
#yy-wrap { padding-top: 80px; }

.yy-qty-wrapper {
    display: flex; align-items: center; justify-content: space-between;
    width: 95px; background: #f1f5f9; border: 1px solid #e2e8f0;
    border-radius: 20px; padding: 3px; margin: 0 auto;
}
.yy-qty-btn {
    width: 28px; height: 28px; border: none; border-radius: 50%;
    background: transparent; cursor: pointer; display: flex; align-items: center;
    justify-content: center; font-size: 1.1rem; color: #64748b; transition: all 0.2s ease;
}
.yy-qty-btn:hover {
    background: #ffffff; color: var(--yy-accent); box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}
.yy-qty-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.yy-qty-val { font-weight: 600; font-size: 0.95rem; color: var(--yy-text); width: 24px; text-align: center; user-select: none; }

/* delete btn */
#yy-wrap .yy-btn-del {
    width:34px; height:34px; border-radius:8px; border:1px solid rgba(239,68,68,.3);
    background:rgba(239,68,68,.08); color:#f87171;
    display:flex; align-items:center; justify-content:center;
    cursor:pointer; font-size:.95rem; transition:background .2s, border-color .2s;
    margin:0 auto;
}
#yy-wrap .yy-btn-del:hover { background:rgba(239,68,68,.2); border-color:rgba(239,68,68,.5); }

/* footer / total row */
#yy-wrap .yy-cart-footer {
    padding:20px 24px; border-top:2px solid var(--yy-border);
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:16px;
    background:#f9fafb;
}
#yy-wrap .yy-cart-footer .yy-total-label { font-size:.88rem; color:var(--yy-dim); text-transform:uppercase; letter-spacing:1px; }
#yy-wrap .yy-cart-footer .yy-total-value { font-size:1.4rem; font-weight:700; color:var(--yy-white); }
#yy-wrap .yy-cart-footer .yy-total-value span { color:var(--yy-accent); }
#yy-wrap .yy-btn-checkout {
    display:inline-flex; align-items:center; gap:8px;
    background:var(--yy-accent); color:#0f1117; border:none; border-radius:10px;
    padding:12px 28px; font-weight:600; font-size:.92rem;
    text-decoration:none; cursor:pointer;
    transition:background .25s, transform .15s, box-shadow .25s;
}
#yy-wrap .yy-btn-checkout:hover {
    background:var(--yy-accent2); transform:translateY(-2px);
    box-shadow:0 6px 18px rgba(0,201,167,.25); color:#fff;
}

/* empty cart */
#yy-wrap .yy-empty-cart {
    max-width:500px; margin:60px auto; text-align:center;
    background:var(--yy-card); border:1px solid var(--yy-border); border-radius:16px;
    padding:56px 28px;
}
#yy-wrap .yy-empty-cart-icon { font-size:3rem; color:var(--yy-dim); margin-bottom:16px; }
#yy-wrap .yy-empty-cart h4 { color:var(--yy-text); font-size:1.1rem; margin-bottom:6px; }
#yy-wrap .yy-empty-cart p { color:var(--yy-dim); font-size:.9rem; margin:0; }

/* responsive */
@media(max-width:600px){
    #yy-wrap .yy-cart-table thead th,
    #yy-wrap .yy-cart-table td { padding:12px 10px; font-size:.82rem; }
    #yy-wrap .yy-cart-thumb { width:40px; height:40px; }
}
</style>

<div id="yy-wrap">

<!-- HERO -->
<section class="yy-hero">
    <div class="yy-hero-inner" data-aos="fade-in" data-aos-delay="150">
        <h1><span>Keranjang</span> Saya</h1>
        <h2>Pesanan sementara Anda akan tampil di sini</h2>
    </div>
</section>

<!-- BRANDS -->
<div class="yy-brands">
    <img src="{{asset('img/ip.png')}}"      alt="iPhone">
    <img src="{{asset('img/oppo.png')}}"    alt="Oppo">
    <img src="{{asset('img/samsung.png')}}" alt="Samsung">
    <img src="{{asset('img/vivo.png')}}"    alt="Vivo">
    <img src="{{asset('img/xiaomi.png')}}"  alt="Xiaomi">
    <img src="{{asset('img/huawei.png')}}"  alt="Huawei">
</div>

<!-- FLASH MESSAGES -->
@if(session('error') || session('success'))
<div class="yy-flash">
    @if(session('error'))
        <div class="yy-flash-item error"><i class="bx bx-error-circle"></i> {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="yy-flash-item success"><i class="bx bx-check-circle"></i> {{ session('success') }}</div>
    @endif
</div>
@endif

<!-- CART CONTENT -->
<section class="yy-content">
    <div class="yy-cart-wrap">
        @if (session('cart_produk') || session('cart_sparepart'))
        @php
            $total = 0;
            $itemCount = 0;
        @endphp

        <div class="yy-cart-card" data-aos="fade-up">
            <!-- Header -->
            <div class="yy-cart-header">
                <div class="yy-cart-header-icon"><i class="bx bxs-cart"></i></div>
                <div>
                    <h5>Pesanan Anda</h5>
                    <small>Review dan kelola item keranjang Anda</small>
                </div>
            </div>

            <!-- Table -->
            <table class="yy-cart-table">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Produk</th>
                        @if($isMember)
                        <th class="yy-right">Harga</th>
                        @endif
                        <th class="yy-center">Qty</th>
                        @if($isMember)
                        <th class="yy-right">Subtotal</th>
                        @endif
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp

                    {{-- ── Produk ── --}}
                    @if (session('cart_produk'))
                    @foreach (session('cart_produk') as $id => $item)
                        @php
                            $total += $item['price'] * $item['qty'];
                            $itemCount++;
                        @endphp
                        <tr>
                            <td><span style="color:var(--yy-dim);font-size:.82rem;">{{$no++}}</span></td>
                            <td>
                                <div class="yy-cart-product">
                                    @if ($item['photo'] != '-')
                                        <img src="{{asset('uploads/'.$item['photo'])}}" class="yy-cart-thumb" alt="">
                                    @else
                                        <img src="{{asset('img/no_image.png')}}" class="yy-cart-thumb" alt="">
                                    @endif
                                    <span class="yy-cart-product-name">{{$item['name']}}</span>
                                </div>
                            </td>
                            @if($isMember)
                            <td class="yy-right"><span class="yy-price-val">Rp {{number_format($item['price'])}},-</span></td>
                            @endif
                            <td class="yy-center">
                                <div class="yy-qty-wrapper">
                                    <button class="yy-qty-btn" onclick="updateCartQty('{{ route('update_qty_produk', $id) }}', 'minus', this)"><i class="bx bx-minus"></i></button>
                                    <span class="yy-qty-val">{{$item['qty']}}</span>
                                    <button class="yy-qty-btn" onclick="updateCartQty('{{ route('update_qty_produk', $id) }}', 'plus', this)"><i class="bx bx-plus"></i></button>
                                </div>
                            </td>
                            @if($isMember)
                            <td class="yy-right"><span class="yy-subtotal-val">Rp {{number_format($item['price'] * $item['qty'])}},-</span></td>
                            @endif
                            <td>
                                <form action="{{route('delete_produk_cart',$id)}}" method="POST" class="ajax-cart-delete-form" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="yy-btn-del" title="Hapus"><i class="bx bxs-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @endif

                    {{-- ── Sparepart ── --}}
                    @if (session('cart_sparepart'))
                    @foreach (session('cart_sparepart') as $id => $item)
                        @php
                            $total += $item['price'] * $item['qty'];
                            $itemCount++;
                        @endphp
                        <tr>
                            <td><span style="color:var(--yy-dim);font-size:.82rem;">{{$no++}}</span></td>
                            <td>
                                <div class="yy-cart-product">
                                    @if ($item['photo'] != '-')
                                        <img src="{{asset('uploads/'.$item['photo'])}}" class="yy-cart-thumb" alt="">
                                    @else
                                        <img src="{{asset('img/no_image.png')}}" class="yy-cart-thumb" alt="">
                                    @endif
                                    <span class="yy-cart-product-name">{{$item['name']}}</span>
                                </div>
                            </td>
                            @if($isMember)
                            <td class="yy-right"><span class="yy-price-val">Rp {{number_format($item['price'])}},-</span></td>
                            @endif
                            <td class="yy-center">
                                <div class="yy-qty-wrapper">
                                    <button class="yy-qty-btn" onclick="updateCartQty('{{ route('update_qty_sparepart', $id) }}', 'minus', this)"><i class="bx bx-minus"></i></button>
                                    <span class="yy-qty-val">{{$item['qty']}}</span>
                                    <button class="yy-qty-btn" onclick="updateCartQty('{{ route('update_qty_sparepart', $id) }}', 'plus', this)"><i class="bx bx-plus"></i></button>
                                </div>
                            </td>
                            @if($isMember)
                            <td class="yy-right"><span class="yy-subtotal-val">Rp {{number_format($item['price'] * $item['qty'])}},-</span></td>
                            @endif
                            <td>
                                <form action="{{route('delete_sparepart_cart',$id)}}" method="POST" class="ajax-cart-delete-form" style="margin:0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="yy-btn-del" title="Hapus"><i class="bx bxs-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>

            <!-- Footer -->
            <div class="yy-cart-footer">
                @if($isMember)
                <div>
                    <div class="yy-total-label">Total ({{ $itemCount }} item)</div>
                    <div class="yy-total-value">Rp <span>{{number_format($total)}}</span>,-</div>
                </div>
                <a href="{{route('checkout')}}" class="yy-btn-checkout">
                    <i class="bx bxs-basket"></i> Lanjut Checkout
                </a>
                @else
                @php
                    $waText = "Halo Admin, saya ingin memesan:\n\n";
                    $i = 1;
                    if(session('cart_produk')){
                        foreach(session('cart_produk') as $it) {
                            $waText .= $i++ . ". " . $it['name'] . " - " . $it['qty'] . " pcs\n";
                        }
                    }
                    if(session('cart_sparepart')){
                        foreach(session('cart_sparepart') as $it) {
                            $waText .= $i++ . ". " . $it['name'] . " - " . $it['qty'] . " pcs\n";
                        }
                    }
                    $waText .= "\nMohon info harga dan ketersediaan. Terima kasih!";
                    
                    $phone = isset($toko) && $toko->nomor_cs ? $toko->nomor_cs : '6285603124871';
                    $phone = preg_replace('/[^0-9]/', '', $phone);
                    if(substr($phone, 0, 1) == '0') {
                        $phone = '62' . substr($phone, 1);
                    }
                @endphp
                <div>
                    <div class="yy-total-label">Total Item</div>
                    <div class="yy-total-value"><span>{{ $itemCount }}</span> Item</div>
                </div>
                <a href="https://wa.me/{{ $phone }}?text={{ urlencode($waText) }}" target="_blank" class="yy-btn-checkout" style="background:#25D366; color:#fff;">
                    <i class="bx bxl-whatsapp" style="font-size:1.2rem;"></i> Pesan via WhatsApp
                </a>
                @endif
            </div>
        </div>

        @else
        <!-- Empty Cart -->
        <div class="yy-empty-cart" data-aos="fade-up">
            <div class="yy-empty-cart-icon"><i class="bx bx-cart"></i></div>
            <h4>Keranjang Kosong</h4>
            <p>Belum ada produk yang ditambahkan. Yuk belanja sekarang!</p>
        </div>
        @endif
    </div>
</section>

</div>

<div id="yy-scripts-wrapper">
<script>
    function updateCartUI(data, row = null) {
        // Update badges
        document.querySelectorAll('.yy-cart-badge, .yy-mobile-cart-badge').forEach(b => {
            b.textContent = data.cart_count;
        });

        // Update totals
        const isMember = {{ $isMember ? 'true' : 'false' }};
        const totalValueEl = document.querySelector('.yy-cart-footer .yy-total-value span');
        const totalLabelEl = document.querySelector('.yy-cart-footer .yy-total-label');
        
        if(isMember && totalValueEl) {
            totalValueEl.textContent = data.cart_total_formatted;
            if(totalLabelEl) totalLabelEl.textContent = 'Total (' + data.cart_count + ' item)';
        } else if(totalValueEl) {
            totalValueEl.textContent = data.cart_count;
        }

        // If cart is empty, reload to show empty state
        if(data.cart_count === 0) {
            window.location.reload();
        }

        // Update row specific values if provided
        if(row && data.qty !== undefined) {
            const qtyVal = row.querySelector('.yy-qty-val');
            if(qtyVal) qtyVal.textContent = data.qty;
            const subtotalVal = row.querySelector('.yy-subtotal-val');
            if(subtotalVal) subtotalVal.textContent = 'Rp ' + data.item_subtotal_formatted + ',-';
        }
    }

    function updateCartQty(url, action, btn) {
        const row = btn.closest('tr');
        const fd = new FormData();
        fd.append('_token', '{{ csrf_token() }}');
        fd.append('_method', 'PUT');
        fd.append('action', action);

        btn.disabled = true;

        fetch(url, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if(data.success) {
                updateCartUI(data, row);
            }
        })
        .catch(err => {
            console.error(err);
            btn.disabled = false;
        });
    }

    // Handle AJAX delete form
    document.querySelectorAll('.ajax-cart-delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const row = this.closest('tr');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i>';

            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    row.remove();
                    updateCartUI(data);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bx bxs-trash"></i>';
                }
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = '<i class="bx bxs-trash"></i>';
            });
        });
    });
</script>
</div>

@endsection
