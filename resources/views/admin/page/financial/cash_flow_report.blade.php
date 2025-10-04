<section class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Filter Laporan</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('financial.cashFlowReport') }}" method="GET">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="start_date">Tanggal Awal</label>
                                <input type="date" class="form-control" id="start_date" name="start_date"
                                    value="{{ $reportData['startDate'] }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="end_date">Tanggal Akhir</label>
                                <input type="date" class="form-control" id="end_date" name="end_date"
                                    value="{{ $reportData['endDate'] }}">
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>
                                    Tampilkan Laporan</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Ringkasan Arus Kas</h3>
                <div class="card-tools">
                    <span class="badge badge-info">Periode:
                        {{ \Carbon\Carbon::parse($reportData['startDate'])->format('d M Y') }} -
                        {{ \Carbon\Carbon::parse($reportData['endDate'])->format('d M Y') }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-6">
                        <div class="small-box bg-light">
                            <div class="inner">
                                <h3>Rp {{ number_format($reportData['saldoAwal'], 0, ',', '.') }}</h3>
                                <p>Saldo Awal Periode</p>
                            </div>
                            <div class="icon"><i class="fas fa-wallet"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3>Rp {{ number_format($reportData['totalKasMasuk'], 0, ',', '.') }}</h3>
                                <p>Total Kas Masuk</p>
                            </div>
                            <div class="icon"><i class="fas fa-arrow-down"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3>Rp {{ number_format($reportData['totalKasKeluar'], 0, ',', '.') }}</h3>
                                <p>Total Kas Keluar</p>
                            </div>
                            <div class="icon"><i class="fas fa-arrow-up"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3>Rp {{ number_format($reportData['saldoAkhir'], 0, ',', '.') }}</h3>
                                <p>Saldo Akhir Periode</p>
                            </div>
                            <div class="icon"><i class="fas fa-university"></i></div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <h4>Rincian Kas Masuk (Pemasukan)</h4>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr class="bg-success-light">
                                    <th>Kategori Pemasukan</th>
                                    <th class="text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reportData['detailKasMasuk'] as $kategori => $total)
                                    <tr>
                                        <td>{{ $kategori }}</td>
                                        <td class="text-right">Rp {{ number_format($total, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">Tidak ada kas masuk pada periode ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th class="text-right">Total Kas Masuk</th>
                                    <th class="text-right">Rp
                                        {{ number_format($reportData['totalKasMasuk'], 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="col-md-6">
                        <h4>Rincian Kas Keluar (Pengeluaran)</h4>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr class="bg-danger-light">
                                    <th>Kategori Pengeluaran</th>
                                    <th class="text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reportData['detailKasKeluar'] as $kategori => $total)
                                    <tr>
                                        <td>{{ $kategori }}</td>
                                        <td class="text-right">Rp {{ number_format($total, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">Tidak ada kas keluar pada periode
                                            ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th class="text-right">Total Kas Keluar</th>
                                    <th class="text-right">Rp
                                        {{ number_format($reportData['totalKasKeluar'], 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<style>
    /* Style ringan untuk header tabel kas masuk/keluar */
    .bg-success-light {
        background-color: #dff0d8 !important;
    }

    .bg-danger-light {
        background-color: #f2dede !important;
    }
</style>
