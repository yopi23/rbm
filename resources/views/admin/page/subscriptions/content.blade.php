{{-- File ini HANYA berisi konten HTML, tanpa layout --}}
<div class="container-fluid">
    {{-- Bagian Status Langganan --}}
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Status Langganan Anda</h3>
        </div>
        <div class="card-body">
            @if ($subscription && $subscription->status == 'active' && $subscription->expires_at->isFuture())
                <h5 class="card-title text-success">LANGGANAN AKTIF</h5>
                <p>Paket Anda: <strong>{{ $subscription->plan->name }}</strong></p>
                <p>Berlaku hingga: <strong>{{ $subscription->expires_at->format('d F Y H:i') }}</strong></p>
            @else
                <h5 class="card-title text-danger">LANGGANAN TIDAK AKTIF</h5>
                <p>Anda belum memiliki paket langganan aktif. Silakan aktifkan menggunakan token atau pilih paket di
                    bawah.</p>
            @endif
        </div>
    </div>

    @if ($pendingPayments->isNotEmpty())
        <div class="card mb-4 card-warning">
            <div class="card-header">
                <h3 class="card-title">Tagihan yang Belum Dibayar</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Paket</th>
                            <th>Total Tagihan</th>
                            <th style="width: 250px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingPayments as $payment)
                            <tr>
                                <td>{{ $payment->subscriptionPlan->name }}</td>
                                <td><strong>Rp {{ number_format($payment->unique_amount, 0, ',', '.') }}</strong></td>
                                <td>
                                    {{-- Tombol Lanjutkan Pembayaran --}}
                                    <a href="{{ route('subscriptions.payment', $payment->subscription_plan_id) }}"
                                        class="btn btn-sm btn-success">
                                        Lanjutkan Pembayaran
                                    </a>
                                    {{-- Tombol Batal --}}
                                    <form action="{{ route('subscriptions.payment.cancel', $payment->id) }}"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('Apakah Anda yakin ingin membatalkan tagihan ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Batalkan</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Bagian Aktivasi Token --}}
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Punya Token Aktivasi?</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('subscriptions.activate') }}" method="POST">
                @csrf
                <div class="input-group">
                    <input type="text" name="token" class="form-control" placeholder="Masukkan kode token Anda"
                        required>
                    <button class="btn btn-primary" type="submit">Aktivasi</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Bagian Pilihan Paket --}}
    <h3 class="text-center my-4">Pilih Paket Langganan</h3>
    <div class="row">
        @foreach ($plans as $plan)
            <div class="col-md-4 mb-3">
                <div class="card text-center h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">{{ $plan->name }}</h5>
                        <h3 class="card-text my-3">Rp {{ number_format($plan->price, 0, ',', '.') }}</h3>
                        <p class="text-muted">{{ $plan->duration_in_months }} Bulan Akses</p>
                        <a href="{{ route('subscriptions.payment', $plan->id) }}" class="btn btn-success mt-auto">Beli
                            Sekarang</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
