@extends('front.layout.app')
@section('content-app')

<style>
/* ═══ SIGNUP PAGE ═══ */
#yy-auth-page {
    /* Inherit global variables from app.blade.php (Light Theme) */
    font-family:'Plus Jakarta Sans','Segoe UI',system-ui,sans-serif;
    color:var(--yy-text);
    min-height:calc(100vh - 68px);
    background:var(--yy-dark);
    display:flex; align-items:flex-start; justify-content:center;
    padding:60px 20px 80px;
    position:relative;
    overflow:hidden;
}
#yy-auth-page::before {
    content:''; position:absolute;
    width:600px; height:600px; border-radius:50%;
    background:radial-gradient(circle,rgba(0,201,167,.07) 0%,transparent 70%);
    top:-100px; right:-120px; pointer-events:none;
}
#yy-auth-page::after {
    content:''; position:absolute;
    width:480px; height:480px; border-radius:50%;
    background:radial-gradient(circle,rgba(0,168,130,.05) 0%,transparent 70%);
    bottom:-100px; left:-80px; pointer-events:none;
}

/* ── Card ── */
.yy-auth-card {
    position:relative; z-index:1;
    width:100%; max-width:460px;
    background:var(--yy-card);
    border:1px solid var(--yy-border);
    border-radius:22px;
    padding:48px 40px 40px;
    box-shadow:0 24px 64px rgba(0,0,0,.08);
}

/* ── Logo ── */
.yy-auth-logo {
    display:flex; align-items:center; justify-content:center; gap:10px;
    margin-bottom:28px;
}
.yy-auth-logo-mark {
    width:42px; height:42px; border-radius:12px;
    background:linear-gradient(135deg,var(--yy-accent),var(--yy-accent2));
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; color:#0f1117; font-weight:700;
}
.yy-auth-logo-text { font-size:1.35rem; font-weight:700; color:var(--yy-white); }
.yy-auth-logo-text span { color:var(--yy-accent); }

/* ── Title ── */
.yy-auth-title { text-align:center; margin-bottom:26px; }
.yy-auth-title h2 { font-size:1.4rem; font-weight:700; color:var(--yy-white); margin-bottom:5px; }
.yy-auth-title p { font-size:.84rem; color:var(--yy-dim); margin:0; }

/* ── Flash ── */
.yy-auth-flash {
    margin-bottom:18px; border-radius:10px; padding:11px 15px;
    font-size:.84rem; display:flex; align-items:center; gap:9px;
}
.yy-auth-flash.error { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.25); color:#f87171; }
.yy-auth-flash.success { background:rgba(0,201,167,.1); border:1px solid rgba(0,201,167,.25); color:var(--yy-accent); }

/* ── Form groups ── */
.yy-auth-group { margin-bottom:16px; }
.yy-auth-group label {
    display:block; font-size:.77rem; font-weight:600;
    color:var(--yy-dim); text-transform:uppercase; letter-spacing:.9px;
    margin-bottom:7px;
}
.yy-auth-group .yy-input-wrap { position:relative; }
.yy-auth-group .yy-input-icon {
    position:absolute; left:14px; top:50%; transform:translateY(-50%);
    color:var(--yy-dim); font-size:1rem; pointer-events:none;
    transition:color .25s;
}
.yy-auth-group input,
.yy-auth-group textarea {
    width:100%; box-sizing:border-box;
    background:#ffffff;
    border:1px solid var(--yy-border);
    border-radius:11px;
    padding:13px 16px 13px 42px;
    color:var(--yy-text); font-size:.9rem;
    font-family:inherit; outline:none;
    transition:border-color .25s, box-shadow .25s, background .25s;
}
.yy-auth-group textarea { resize:vertical; min-height:78px; padding-top:14px; }
.yy-auth-group input::placeholder,
.yy-auth-group textarea::placeholder { color:var(--yy-dim); }
.yy-auth-group input:focus,
.yy-auth-group textarea:focus {
    border-color:var(--yy-accent);
    box-shadow:0 0 0 3px rgba(0,201,167,.15);
    background:#ffffff;
}
.yy-auth-group .yy-input-wrap:focus-within .yy-input-icon { color:var(--yy-accent); }

/* ── Invite code box ── */
.yy-invite-box {
    margin-top:6px; margin-bottom:16px;
    background:rgba(0,201,167,.06);
    border:1px solid rgba(0,201,167,.2);
    border-radius:14px;
    padding:20px 20px 18px;
}
.yy-invite-box .yy-invite-header {
    display:flex; align-items:center; gap:10px; margin-bottom:10px;
}
.yy-invite-box .yy-invite-icon {
    width:34px; height:34px; border-radius:9px;
    background:rgba(0,201,167,.15);
    display:flex; align-items:center; justify-content:center;
    color:var(--yy-accent); font-size:1rem; flex-shrink:0;
}
.yy-invite-box .yy-invite-header h6 {
    font-size:.88rem; color:var(--yy-white); font-weight:600; margin:0;
}
.yy-invite-box .yy-invite-header p {
    font-size:.78rem; color:var(--yy-dim); margin:2px 0 0;
}
/* invite input – no left-icon padding override */
.yy-invite-box .yy-input-wrap input {
    padding-left:16px; /* no icon in this one */
}
/* AJAX feedback area */
#message_kode_invite { min-height:20px; margin-bottom:8px; font-size:.8rem; }
#message_kode_invite .text-success,
#message_kode_invite .text-danger { display:flex; align-items:center; gap:5px; }
/* override any AdminLTE leftover colours that might leak */
#message_kode_invite .text-success { color:var(--yy-accent) !important; }
#message_kode_invite .text-danger  { color:#f87171 !important; }

/* ── Submit ── */
.yy-auth-submit {
    width:100%; padding:14px; border:none; border-radius:11px;
    background:linear-gradient(135deg,var(--yy-accent),var(--yy-accent2));
    color:#0f1117; font-weight:700; font-size:.94rem;
    font-family:inherit; cursor:pointer; margin-top:4px;
    transition:transform .2s, box-shadow .25s, filter .2s;
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.yy-auth-submit:hover {
    transform:translateY(-2px);
    box-shadow:0 6px 22px rgba(0,201,167,.3);
    filter:brightness(1.05);
}
.yy-auth-submit:active { transform:translateY(0); }

/* ── Divider ── */
.yy-auth-divider {
    display:flex; align-items:center; gap:12px; margin:20px 0;
}
.yy-auth-divider::before, .yy-auth-divider::after {
    content:''; flex:1; height:1px; background:var(--yy-border);
}
.yy-auth-divider span { font-size:.78rem; color:var(--yy-dim); white-space:nowrap; }

/* ── Bottom link ── */
.yy-auth-bottom { text-align:center; font-size:.84rem; color:var(--yy-dim); }
.yy-auth-bottom a { color:var(--yy-accent); font-weight:600; text-decoration:none; transition:color .2s; }
.yy-auth-bottom a:hover { color:var(--yy-accent2); text-decoration:underline; }
</style>

<div id="yy-auth-page">
    <div class="yy-auth-card" data-aos="fade-up" data-aos-delay="100">

        <!-- Logo -->
        <div class="yy-auth-logo">
            <div class="yy-auth-logo-mark">Y</div>
            <div class="yy-auth-logo-text">YOYOY<span>CELL</span></div>
        </div>

        <!-- Title -->
        <div class="yy-auth-title">
            <h2>Buat Akun Baru</h2>
            <p>Isi data diri Anda untuk mendaftar</p>
        </div>

        <!-- Flash -->
        @if(session('error'))
            <div class="yy-auth-flash error">
                <i class="bx bx-error-circle"></i> {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="yy-auth-flash success">
                <i class="bx bx-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        <!-- Form -->
        <form action="{{route('sign_up')}}" method="post">
            @csrf

            <!-- Nama -->
            <div class="yy-auth-group">
                <label>Nama</label>
                <div class="yy-input-wrap">
                    <i class="bx bx-user yy-input-icon"></i>
                    <input type="text" name="nama" id="nama"
                        placeholder="Nama lengkap" required autofocus>
                </div>
            </div>

            <!-- Alamat -->
            <div class="yy-auth-group">
                <label>Alamat</label>
                <div class="yy-input-wrap">
                    <i class="bx bx-map yy-input-icon"></i>
                    <textarea name="alamat_user" id="alamat_user"
                        placeholder="Alamat lengkap Anda..." required></textarea>
                </div>
            </div>

            <!-- No Telp -->
            <div class="yy-auth-group">
                <label>No Telepon</label>
                <div class="yy-input-wrap">
                    <i class="bx bx-phone yy-input-icon"></i>
                    <input type="number" name="no_telp" id="no_telp"
                        placeholder="08xxxxxxxxx" required>
                </div>
            </div>

            <!-- Email -->
            <div class="yy-auth-group">
                <label>Email</label>
                <div class="yy-input-wrap">
                    <i class="bx bx-envelope yy-input-icon"></i>
                    <input type="email" name="email" id="email"
                        placeholder="anda@email.com" required>
                </div>
            </div>

            <!-- Password -->
            <div class="yy-auth-group">
                <label>Password</label>
                <div class="yy-input-wrap">
                    <i class="bx bx-lock yy-input-icon"></i>
                    <input type="password" name="password" id="password"
                        placeholder="••••••••" required>
                </div>
            </div>

            <!-- ── Invite Code Box ── -->
            <div class="yy-invite-box">
                <div class="yy-invite-header">
                    <div class="yy-invite-icon"><i class="bx bx-gift"></i></div>
                    <div>
                        <h6>Punya Kode Invite?</h6>
                        <p>Masukkan kode untuk mendapatkan keuntungan ekstra</p>
                    </div>
                </div>
                <div id="message_kode_invite"></div>
                <div class="yy-input-wrap">
                    <input type="text" name="kode_invite" id="kode_invite"
                        placeholder="Kode invite Anda...">
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="yy-auth-submit">
                <i class="bx bx-user-plus"></i> Daftar Sekarang
            </button>
        </form>

        <!-- Divider -->
        <div class="yy-auth-divider"><span>atau</span></div>

        <!-- Bottom -->
        <div class="yy-auth-bottom">
            Sudah punya akun? <a href="{{route('login')}}">Sign in di sini</a>
        </div>

    </div>
</div>

@endsection

{{-- ── AJAX kode invite (jQuery) — kept in content-script exactly as original ── --}}
@section('content-script')
<script>
    $('#kode_invite').on('keyup', function(){
        var val = $(this).val();
        $.ajax({
            type: 'GET',
            url : '{{ route('search_kode_invite') }}',
            data: { 'search': val },
            success: function(data){
                $('#message_kode_invite').html(data);
            }
        });
    });
</script>
@endsection
