@extends('front.layout.app')
@section('content-app')

<style>
#yy-wrap {
    font-family:'Segoe UI',system-ui,-apple-system,sans-serif;
    color:var(--yy-text); background:var(--yy-dark); min-height:100vh;
}
/* hero */
#yy-wrap .yy-hero {
    position:relative; min-height:280px;
    display:flex; align-items:center; justify-content:center;
    background:linear-gradient(135deg,#ffffff 0%,#f3f4f6 50%,#ffffff 100%);
    overflow:hidden; padding:48px 20px;
}
#yy-wrap .yy-hero::before {
    content:''; position:absolute; inset:0;
    background:radial-gradient(ellipse 60% 50% at 40% 55%,rgba(0,201,167,.10) 0%,transparent 70%);
    pointer-events:none;
}
#yy-wrap .yy-hero::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:1px;
    background:linear-gradient(90deg,transparent,var(--yy-accent),transparent);
}
#yy-wrap .yy-hero-inner { position:relative; z-index:1; text-align:center; max-width:780px; width:100%; }
#yy-wrap .yy-hero h1 { font-size:clamp(1.6rem,3vw,2.2rem); font-weight:700; color:var(--yy-white); margin-bottom:6px; }
#yy-wrap .yy-hero h1 span { color:var(--yy-accent); }
#yy-wrap .yy-hero h2 { font-size:.92rem; color:var(--yy-dim); font-weight:400; margin-bottom:22px; }

/* search row */
#yy-wrap .yy-search-row { display:flex; gap:10px; flex-wrap:wrap; justify-content:center; }
#yy-wrap .yy-search-row select,
#yy-wrap .yy-search-row input[type="text"] {
    background:#ffffff; border:1px solid var(--yy-border);
    border-radius:10px; padding:12px 16px;
    color:var(--yy-text); font-size:.9rem; outline:none;
    transition:border-color .25s;
    font-family:inherit;
}
#yy-wrap .yy-search-row select { flex:0 1 220px; min-width:180px; -webkit-appearance:none; appearance:none; cursor:pointer; }
#yy-wrap .yy-search-row select option { background:#ffffff; color:var(--yy-text); }
#yy-wrap .yy-search-row input[type="text"] { flex:1 1 240px; max-width:400px; }
#yy-wrap .yy-search-row input::placeholder { color:var(--yy-dim); }
#yy-wrap .yy-search-row input:focus,
#yy-wrap .yy-search-row select:focus { border-color:var(--yy-accent); }
#yy-wrap .yy-search-row input[type="submit"] {
    background:var(--yy-accent); color:#0f1117; border:none; border-radius:10px;
    padding:12px 28px; font-weight:600; font-size:.92rem; cursor:pointer;
    transition:background .25s, transform .15s;
}
#yy-wrap .yy-search-row input[type="submit"]:hover { background:var(--yy-accent2); transform:translateY(-1px); }

/* brands */
#yy-wrap .yy-brands {
    background:#ffffff; border-bottom:1px solid var(--yy-border);
    padding:18px 0; display:flex; justify-content:center; gap:40px; flex-wrap:wrap;
}
#yy-wrap .yy-brands img { height:28px; width:auto; filter:grayscale(100%) opacity(0.6); transition:opacity .3s, filter .3s; }
#yy-wrap .yy-brands img:hover { opacity:1; filter:grayscale(0%); }

/* result section */
#yy-wrap .yy-result { padding:44px 0 80px; }
#yy-wrap .yy-result-grid {
    display:grid; grid-template-columns:320px 1fr; gap:28px;
    max-width:1060px; margin:0 auto; padding:0 24px; align-items:start;
}
@media(max-width:780px){ #yy-wrap .yy-result-grid { grid-template-columns:1fr; } }

/* ── QR Card ── */
#yy-wrap .yy-card {
    background:var(--yy-card); border:1px solid var(--yy-border); border-radius:16px; overflow:hidden;
}
#yy-wrap .yy-qr-card { text-align:center; }
#yy-wrap .yy-qr-box {
    padding:28px 24px 20px;
    background:#ffffff; border-bottom:1px solid var(--yy-border);
    display:flex; flex-direction:column; align-items:center;
}
#yy-wrap .yy-qr-box svg { max-width:160px; width:100%; }
#yy-wrap .yy-qr-box .yy-code {
    margin-top:14px; font-size:1.1rem; font-weight:700; color:var(--yy-text);
    letter-spacing:2px; text-transform:uppercase;
}

/* status badge */
#yy-wrap .yy-status-box { padding:22px 20px; }
#yy-wrap .yy-status-badge {
    display:inline-block; padding:8px 24px; border-radius:24px;
    font-size:1rem; font-weight:700; text-transform:uppercase; letter-spacing:1.2px;
    border:2px solid;
}
#yy-wrap .yy-status-badge.selesai { background:rgba(0,201,167,.12); border-color:var(--yy-accent); color:var(--yy-accent); }
#yy-wrap .yy-status-badge.proses { background:rgba(245,158,11,.12); border-color:#f59e0b; color:#fbbf24; }
#yy-wrap .yy-status-badge.default { background:rgba(139,92,246,.12); border-color:#8b5cf6; color:#a78bfa; }

/* ── Detail Card ── */
#yy-wrap .yy-card-header {
    padding:18px 24px; border-bottom:1px solid var(--yy-border);
    display:flex; align-items:center; gap:12px;
}
#yy-wrap .yy-card-header-icon {
    width:38px; height:38px; border-radius:11px;
    background:rgba(0,201,167,.12);
    display:flex; align-items:center; justify-content:center;
    color:var(--yy-accent); font-size:1.05rem;
}
#yy-wrap .yy-card-header h5 { font-size:1rem; color:var(--yy-text); margin:0; font-weight:600; }

/* detail rows */
#yy-wrap .yy-detail-body { padding:22px 24px; }
#yy-wrap .yy-detail-row {
    display:flex; justify-content:space-between; align-items:baseline;
    padding:10px 0; border-bottom:1px solid var(--yy-border);
}
#yy-wrap .yy-detail-row:last-child { border-bottom:none; }
#yy-wrap .yy-detail-row .yy-dlabel { font-size:.82rem; color:var(--yy-dim); }
#yy-wrap .yy-detail-row .yy-dvalue { font-size:.9rem; color:var(--yy-text); font-weight:500; text-align:right; }
#yy-wrap .yy-detail-row .yy-dvalue.yy-price-accent { color:var(--yy-accent); font-weight:700; font-size:1rem; }

/* meta row (code + date) */
#yy-wrap .yy-meta-row {
    display:flex; justify-content:space-between; padding:10px 24px;
    background:#f9fafb; border-top:1px solid var(--yy-border);
}
#yy-wrap .yy-meta-row span { font-size:.78rem; color:var(--yy-dim); }
#yy-wrap .yy-meta-row .yy-code-sm { color:var(--yy-accent); font-weight:600; }

/* sparepart mini table */
#yy-wrap .yy-sp-section { padding:22px 24px 0; }
#yy-wrap .yy-sp-section h6 { font-size:.82rem; color:var(--yy-dim); text-transform:uppercase; letter-spacing:.8px; margin-bottom:14px; }
#yy-wrap .yy-sp-table { width:100%; border-collapse:collapse; }
#yy-wrap .yy-sp-table thead th {
    text-align:left; padding:8px 0; font-size:.74rem;
    color:var(--yy-dim); font-weight:600; border-bottom:1px solid var(--yy-border);
    text-transform:uppercase; letter-spacing:.7px;
}
#yy-wrap .yy-sp-table tbody td {
    padding:9px 0; font-size:.87rem; color:var(--yy-text);
    border-bottom:1px solid rgba(42,45,58,.5);
}
#yy-wrap .yy-sp-table tbody tr:last-child td { border-bottom:none; }
#yy-wrap .yy-sp-table .yy-sp-no { color:var(--yy-dim); width:28px; }
#yy-wrap .yy-sp-table .yy-sp-qty { text-align:right; color:var(--yy-accent); font-weight:600; }

/* note */
#yy-wrap .yy-note {
    margin:16px 24px 22px; padding:12px 16px; border-radius:10px;
    background:rgba(245,158,11,.08); border:1px solid rgba(245,158,11,.2);
    font-size:.78rem; color:#fbbf24;
}

/* ── Garansi cards ── */
#yy-wrap .yy-garansi-list { padding:20px 24px; }
#yy-wrap .yy-garansi-item {
    background:rgba(0,0,0,.03); border:1px solid var(--yy-border);
    border-radius:12px; padding:18px 20px; margin-bottom:14px;
}
#yy-wrap .yy-garansi-item:last-child { margin-bottom:0; }
#yy-wrap .yy-garansi-item h6 {
    font-size:.92rem; color:var(--yy-white); margin-bottom:6px; font-weight:600;
}
#yy-wrap .yy-garansi-item p { font-size:.86rem; color:var(--yy-text); margin-bottom:8px; line-height:1.5; }
#yy-wrap .yy-garansi-item .yy-garansi-exp {
    font-size:.76rem; color:var(--yy-accent);
    display:flex; align-items:center; gap:6px;
}
</style>

<div id="yy-wrap">

<!-- HERO + SEARCH -->
<section class="yy-hero">
    <div class="yy-hero-inner" data-aos="fade-in" data-aos-delay="150">
        <h1>Cek Status <span>Service</span></h1>
        <h2>Masukkan kode invoice untuk melacak status servis Anda</h2>
        <form action="{{route('service')}}" method="GET">
            <div class="yy-search-row">
                <select name="type_search" id="type_search">
                    <option value="service" {{isset($request->type_search) != null && $request->type_search == 'service' ? 'selected' : ''}}>Cek Status Service</option>
                    <option value="garansi" {{isset($request->type_search) != null && $request->type_search == 'garansi' ? 'selected' : ''}}>Cek Garansi</option>
                </select>
                <input type="text" name="q" value="{{isset($request->q) != null ? $request->q : ''}}" placeholder="Kode Service / Invoice...">
                <input type="submit" value="Cek">
            </div>
        </form>
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

<!-- ── SERVICE RESULT ── -->
@if (isset($data) != null && $request->type_search == 'service')
<section class="yy-result">
    <div class="yy-result-grid">

        <!-- QR + Status -->
        <div class="yy-card yy-qr-card" data-aos="fade-right">
            <div class="yy-qr-box">
                {!! $data['qr'] !!}
                <div class="yy-code">{{ $data['data']->kode_service }}</div>
            </div>
            <div class="yy-status-box">
                @php
                    $statusRaw = strtolower($data['data']->status_services);
                    $statusClass = str_contains($statusRaw, 'selesai') ? 'selesai'
                        : (str_contains($statusRaw, 'proses') ? 'proses' : 'default');
                @endphp
                <span class="yy-status-badge {{ $statusClass }}">{{ $data['data']->status_services }}</span>
            </div>
        </div>

        <!-- Detail -->
        <div data-aos="fade-left" data-aos-delay="100">
            <div class="yy-card" style="margin-bottom:22px;">
                <div class="yy-card-header">
                    <div class="yy-card-header-icon"><i class="bx bx-clipboard-data"></i></div>
                    <h5>Rincian Service</h5>
                </div>

                <!-- meta -->
                <div class="yy-meta-row">
                    <span class="yy-code-sm">#{{ $data['data']->kode_service }}</span>
                    <span>{{ $data['data']->created_at }}</span>
                </div>

                <div class="yy-detail-body">
                    <div class="yy-detail-row">
                        <span class="yy-dlabel">Nama Pelanggan</span>
                        <span class="yy-dvalue">{{ $data['data']->nama_pelanggan }}</span>
                    </div>
                    <div class="yy-detail-row">
                        <span class="yy-dlabel">No Telepon</span>
                        <span class="yy-dvalue">{{ $data['data']->no_telp }}</span>
                    </div>
                    <div class="yy-detail-row">
                        <span class="yy-dlabel">Unit</span>
                        <span class="yy-dvalue">{{ $data['data']->type_unit }}</span>
                    </div>
                    <div class="yy-detail-row">
                        <span class="yy-dlabel">Teknisi</span>
                        <span class="yy-dvalue">{{ $data['teknisi'] }}</span>
                    </div>
                    <div class="yy-detail-row">
                        <span class="yy-dlabel">Keterangan</span>
                        <span class="yy-dvalue">{{ $data['data']->keterangan ?: '—' }}</span>
                    </div>
                    <div class="yy-detail-row">
                        <span class="yy-dlabel">Total Biaya</span>
                        <span class="yy-dvalue yy-price-accent">Rp {{ number_format($data['data']->total_biaya) }},-</span>
                    </div>
                </div>
            </div>

            <!-- Sparepart Used -->
            <div class="yy-card">
                <div class="yy-card-header">
                    <div class="yy-card-header-icon"><i class="bx bx-cube"></i></div>
                    <h5>Sparepart Digunakan</h5>
                </div>
                <div class="yy-sp-section">
                    <table class="yy-sp-table">
                        <thead>
                            <tr>
                                <th class="yy-sp-no">#</th>
                                <th>Nama Sparepart</th>
                                <th class="yy-sp-qty" style="text-align:right;">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $no = 1; @endphp
                            @foreach ($data['detail'] as $item)
                            <tr>
                                <td class="yy-sp-no">{{ $no++ }}</td>
                                <td>{{ $item->nama_sparepart }}</td>
                                <td class="yy-sp-qty">{{ $item->qty_part }}</td>
                            </tr>
                            @endforeach
                            @foreach ($data['detail_luar'] as $item)
                            <tr>
                                <td class="yy-sp-no">{{ $no++ }}</td>
                                <td>{{ $item->nama_part }}</td>
                                <td class="yy-sp-qty">{{ $item->qty_part }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="yy-note">
                    <i class="bx bx-info-circle"></i>
                    Jika service belum selesai, harga yang tertera bisa berubah sesuai kebutuhan.
                </div>
            </div>
        </div>
    </div>
</section>
@endif

<!-- ── GARANSI RESULT ── -->
@if (isset($data) != null && $request->type_search == 'garansi')
<section class="yy-result">
    <div class="yy-result-grid">

        <!-- QR -->
        <div class="yy-card yy-qr-card" data-aos="fade-right">
            <div class="yy-qr-box">
                {!! $data['qr'] !!}
                <div class="yy-code">{{ $request->q }}</div>
            </div>
        </div>

        <!-- Garansi List -->
        <div class="yy-card" data-aos="fade-left" data-aos-delay="100">
            <div class="yy-card-header">
                <div class="yy-card-header-icon"><i class="bx bx-shield"></i></div>
                <h5>Informasi Garansi</h5>
            </div>
            <div class="yy-garansi-list">
                @foreach ($data['data'] as $item)
                    <div class="yy-garansi-item">
                        <h6>{{ $item->nama_garansi }}</h6>
                        <p>{{ $item->catatan_garansi }}</p>
                        <div class="yy-garansi-exp">
                            <i class="bx bx-calendar"></i>
                            Berlaku hingga <strong>{{ $item->tgl_exp_garansi }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

<!-- Spacer if no result yet -->
@if (!isset($data))
<div style="height:60px;"></div>
@endif

</div>
@endsection
