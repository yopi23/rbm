@extends('admin.layout.app')

@section('content-app')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ $page ?? 'Tutup Shift' }}</h1>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
            <div class="card-header">
                <h3 class="card-title">Tutup Shift</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h5>Info Kas System</h5>
                    <p>Modal Awal: Rp {{ number_format($shift->modal_awal, 0, ',', '.') }}</p>
                    <p>Estimasi Saldo Akhir: Rp {{ number_format($expectedCash, 0, ',', '.') }}</p>
                </div>

                <form action="{{ route('shift.update', $shift->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="saldo_akhir_aktual">Total Uang Fisik di Laci (Actual Cash)</label>
                        <input type="number" name="saldo_akhir_aktual" id="saldo_akhir_aktual" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="note">Catatan (Opsional)</label>
                        <textarea name="note" id="note" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Apakah anda yakin ingin menutup shift ini? Aksi ini tidak dapat dibatalkan.')">Tutup Shift & Cetak Laporan</button>
                </form>
            </div>
            </div>
        </div>
    </div>
        </div>
    </section>
</div>
@endsection
