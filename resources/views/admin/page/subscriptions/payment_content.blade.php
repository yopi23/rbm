<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card text-center">
                <div class="card-header">
                    <h3 class="card-title">Instruksi Pembayaran untuk {{ $plan->name }}</h3>
                </div>
                <div class="card-body">
                    <p>Silakan pindai kode QRIS di bawah ini menggunakan aplikasi pembayaran favorit Anda.</p>

                    {{-- Ganti dengan path ke gambar QRIS statis Anda --}}

                    <img src="{{ asset('images/qris-static.png') }}" alt="QRIS Code" class="img-fluid my-3"
                        style="max-width: 250px;">

                    <div class="alert alert-warning">
                        <h4 class="alert-heading">PENTING!</h4>
                        <p>Pastikan Anda mentransfer dengan nominal yang <strong>tepat</strong> untuk verifikasi
                            otomatis.</p>
                    </div>

                    <h3 class="my-3">Nominal Transfer:</h3>
                    <h1 class="text-danger" style="font-size: 2.5rem; letter-spacing: 2px;">
                        Rp {{ number_format($payment->unique_amount, 0, ',', '.') }}
                    </h1>

                    <p class="mt-4">Setelah pembayaran berhasil, langganan Anda akan aktif secara otomatis dalam
                        beberapa menit.</p>
                    <a href="{{ route('subscriptions.index') }}" class="btn btn-secondary mt-2">Kembali ke Halaman
                        Langganan</a>
                </div>
            </div>
        </div>
    </div>
</div>
