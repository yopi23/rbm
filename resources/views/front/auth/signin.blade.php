@extends('front.layout.app')
@section('content-app')

<style>
/* ═══ SIGNIN PAGE ═══ */
#yy-auth-page {
    /* Inherit global variables from app.blade.php (Light Theme) */
    font-family:'Plus Jakarta Sans','Segoe UI',system-ui,sans-serif;
    color:var(--yy-text);
    min-height: calc(100vh - 68px);   /* minus navbar */
    background: var(--yy-dark);
    display:flex; align-items:center; justify-content:center;
    padding: 60px 20px;
    position:relative;
    overflow:hidden;
}

/* subtle background glow */
#yy-auth-page::before {
    content:'';
    position:absolute;
    width:600px; height:600px;
    border-radius:50%;
    background:radial-gradient(circle, rgba(0,201,167,.08) 0%, transparent 70%);
    top:-120px; left:-100px;
    pointer-events:none;
}
#yy-auth-page::after {
    content:'';
    position:absolute;
    width:500px; height:500px;
    border-radius:50%;
    background:radial-gradient(circle, rgba(0,168,130,.06) 0%, transparent 70%);
    bottom:-140px; right:-80px;
    pointer-events:none;
}

/* ── Card wrapper ── */
.yy-auth-card {
    position:relative; z-index:1;
    width:100%; max-width:420px;
    background:var(--yy-card);
    border:1px solid var(--yy-border);
    border-radius:22px;
    padding:48px 40px 40px;
    box-shadow: 0 24px 64px rgba(0,0,0,.08);
}

/* ── Logo on card ── */
.yy-auth-logo {
    display:flex; align-items:center; justify-content:center; gap:10px;
    margin-bottom:32px;
}
.yy-auth-logo-mark {
    width:42px; height:42px; border-radius:12px;
    background:linear-gradient(135deg,var(--yy-accent),var(--yy-accent2));
    display:flex; align-items:center; justify-content:center;
    font-size:1.3rem; color:#0f1117; font-weight:700;
}
.yy-auth-logo-text {
    font-size:1.35rem; font-weight:700; color:var(--yy-white);
}
.yy-auth-logo-text span { color:var(--yy-accent); }

/* ── Title ── */
.yy-auth-title {
    text-align:center; margin-bottom:28px;
}
.yy-auth-title h2 {
    font-size:1.45rem; font-weight:700; color:var(--yy-white); margin-bottom:6px;
}
.yy-auth-title p {
    font-size:.84rem; color:var(--yy-dim); margin:0;
}

/* ── Flash / Alert ── */
.yy-auth-flash {
    margin-bottom:20px; border-radius:10px; padding:12px 16px;
    font-size:.84rem; display:flex; align-items:center; gap:9px;
}
.yy-auth-flash.error { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.25); color:#f87171; }
.yy-auth-flash.success { background:rgba(0,201,167,.1); border:1px solid rgba(0,201,167,.25); color:var(--yy-accent); }

/* ── Form groups ── */
.yy-auth-group { margin-bottom:18px; }
.yy-auth-group label {
    display:block; font-size:.78rem; font-weight:600;
    color:var(--yy-dim); text-transform:uppercase; letter-spacing:.9px;
    margin-bottom:8px;
}
.yy-auth-group .yy-input-wrap {
    position:relative;
}
.yy-auth-group .yy-input-icon {
    position:absolute; left:14px; top:50%; transform:translateY(-50%);
    color:var(--yy-dim); font-size:1rem; pointer-events:none;
    transition:color .25s;
}
.yy-auth-group input {
    width:100%; box-sizing:border-box;
    background:#ffffff;
    border:1px solid var(--yy-border);
    border-radius:11px;
    padding:13px 16px 13px 42px;
    color:var(--yy-text); font-size:.9rem;
    font-family:inherit;
    outline:none;
    transition:border-color .25s, box-shadow .25s, background .25s;
}
.yy-auth-group input::placeholder { color:var(--yy-dim); }
.yy-auth-group input:focus {
    border-color:var(--yy-accent);
    box-shadow:0 0 0 3px rgba(0,201,167,.15);
    background:#ffffff;
}
/* focus → icon turns accent */
.yy-auth-group input:focus ~ .yy-input-icon,
.yy-auth-group .yy-input-wrap:focus-within .yy-input-icon {
    color:var(--yy-accent);
}

/* validation error */
.yy-auth-group input.is-invalid {
    border-color:var(--yy-red);
    box-shadow:0 0 0 3px rgba(239,68,68,.15);
}
.yy-auth-group .yy-field-err {
    margin-top:6px; font-size:.76rem; color:#f87171;
    display:flex; align-items:center; gap:5px;
}

/* ── Submit ── */
.yy-auth-submit {
    width:100%; padding:14px; border:none; border-radius:11px;
    background:linear-gradient(135deg,var(--yy-accent),var(--yy-accent2));
    color:#0f1117; font-weight:700; font-size:.94rem;
    font-family:inherit; cursor:pointer;
    margin-top:8px;
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
    display:flex; align-items:center; gap:12px;
    margin:22px 0;
}
.yy-auth-divider::before, .yy-auth-divider::after {
    content:''; flex:1; height:1px; background:var(--yy-border);
}
.yy-auth-divider span { font-size:.78rem; color:var(--yy-dim); white-space:nowrap; }

/* ── Bottom link ── */
.yy-auth-bottom {
    text-align:center; font-size:.84rem; color:var(--yy-dim);
}
.yy-auth-bottom a {
    color:var(--yy-accent); font-weight:600; text-decoration:none;
    transition:color .2s;
}
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
            <h2>Selamat Datang Kembali</h2>
            <p>Masuk ke akun Anda untuk melanjutkan</p>
        </div>

        <!-- Flash Messages -->
        @if (session('error'))
            <div class="yy-auth-flash error">
                <i class="bx bx-error-circle"></i> {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="yy-auth-flash success">
                <i class="bx bx-check-circle"></i> {{ session('success') }}
            </div>
        @endif

        <!-- Form -->
        <form action="{{ route('authenticate') }}" method="post">
            @csrf

            <!-- Email -->
            <div class="yy-auth-group">
                <label>Email</label>
                <div class="yy-input-wrap">
                    <i class="bx bx-envelope yy-input-icon"></i>
                    <input type="email" name="email" id="email"
                        class="@error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                        placeholder="anda@email.com"
                        autofocus autocomplete="off">
                </div>
                @error('email')
                    <div class="yy-field-err"><i class="bx bx-error-circle"></i> {{ $message }}</div>
                @enderror
            </div>

            <!-- Password -->
            <div class="yy-auth-group">
                <label>Password</label>
                <div class="yy-input-wrap">
                    <i class="bx bx-lock yy-input-icon"></i>
                    <input type="password" name="password" id="password"
                        class="@error('password') is-invalid @enderror"
                        placeholder="••••••••"
                        autocomplete="off">
                </div>
                @error('password')
                    <div class="yy-field-err"><i class="bx bx-error-circle"></i> {{ $message }}</div>
                @enderror
            </div>

            <!-- Submit -->
            <button type="submit" class="yy-auth-submit">
                <i class="bx bx-log-in"></i> Sign In
            </button>
        </form>

        <!-- Divider -->
        <div class="yy-auth-divider"><span>atau</span></div>

        <!-- Bottom -->
        <div class="yy-auth-bottom">
            Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
        </div>

    </div>
</div>

@endsection
