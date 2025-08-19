<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card text-center">
                <div class="card-header">
                    <h3 class="card-title">Pembayaran untuk {{ $plan->name }}</h3>
                </div>
                <div class="card-body">
                    <p>Silakan pindai kode QRIS di bawah ini. <br>
                        <strong>Nominal pembayaran akan muncul secara otomatis.</strong>
                    </p>

                    <div class="my-3">
                        <img src="{!! $qrCodeImage !!}" alt="QRIS Code" style="width:250px; height:250px;">
                    </div>

                    <h3 class="my-3">Total Pembayaran (Termasuk Kode Unik):</h3>
                    <h1 class="text-primary">
                        Rp {{ number_format($payment->unique_amount, 0, ',', '.') }}
                    </h1>

                    <p class="mt-4 text-muted">Setelah pembayaran berhasil, langganan Anda akan aktif secara otomatis.
                    </p>
                </div>
                {{-- TAMBAHKAN BAGIAN INI UNTUK TOMBOL BATAL --}}
                <div class="card-footer">
                    <form action="{{ route('subscriptions.payment.cancel', $payment->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Batal & Pilih Paket Lain</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
