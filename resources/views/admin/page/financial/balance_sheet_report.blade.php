<section class="content">
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Filter Laporan</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('financial.balanceSheetReport') }}" method="GET">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="as_of_date">Laporan per Tanggal</label>
                                <input type="date" class="form-control" id="as_of_date" name="as_of_date"
                                    value="{{ $reportData['asOfDate'] }}">
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
                <h3 class="card-title">Neraca Keuangan per Tanggal
                    {{ \Carbon\Carbon::parse($reportData['asOfDate'])->format('d M Y') }}</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-outline card-success">
                            <div class="card-header">
                                <h3 class="card-title">ASET (ASSETS)</h3>
                            </div>
                            <table class="table table-striped">
                                <tr>
                                    <th colspan="2" class="bg-light">Aset Lancar (Current Assets)</th>
                                </tr>
                                <tr>
                                    <td class="pl-4">Kas & Bank</td>
                                    <td class="text-right">Rp
                                        {{ number_format($reportData['aset']['kas'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-4">Persediaan Barang (Stok)</td>
                                    <td class="text-right">Rp
                                        {{ number_format($reportData['aset']['nilaiStok'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <th colspan="2" class="bg-light">Aset Tetap (Fixed Assets)</th>
                                </tr>
                                <tr>
                                    <td class="pl-4">Peralatan & Mesin <small class="text-muted">*nilai
                                            perolehan</small></td>
                                    <td class="text-right">Rp
                                        {{ number_format($reportData['aset']['asetTetap'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="bg-success">
                                    <th>TOTAL ASET</th>
                                    <th class="text-right">Rp
                                        {{ number_format($reportData['aset']['total'], 0, ',', '.') }}</th>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-outline card-warning">
                            <div class="card-header">
                                <h3 class="card-title">KEWAJIBAN (LIABILITIES) & MODAL (EQUITY)</h3>
                            </div>
                            <table class="table table-striped">
                                <tr>
                                    <th colspan="2" class="bg-light">Kewajiban (Liabilities)</th>
                                </tr>
                                <tr>
                                    <td class="pl-4">Utang Komisi Teknisi</td>
                                    <td class="text-right">Rp
                                        {{ number_format($reportData['kewajiban']['utangKomisi'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-4">Utang Distribusi Laba</td>
                                    <td class="text-right">Rp
                                        {{ number_format($reportData['kewajiban']['utangDistribusi'], 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="pl-4">Utang Lainnya</td>
                                    <td class="text-right">Rp
                                        {{ number_format($reportData['kewajiban']['utangLainnya'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="bg-warning">
                                    <th>Total Kewajiban</th>
                                    <th class="text-right">Rp
                                        {{ number_format($reportData['kewajiban']['total'], 0, ',', '.') }}</th>
                                </tr>

                                <tr>
                                    <th colspan="2" class="bg-light">Modal (Equity)</th>
                                </tr>
                                <tr>
                                    <td class="pl-4">Modal Disetor</td>
                                    <td class="text-right">Rp
                                        {{ number_format($reportData['modal']['modalDisetor'], 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td class="pl-4">Laba Ditahan</td>
                                    <td class="text-right">Rp
                                        {{ number_format($reportData['modal']['labaDitahan'], 0, ',', '.') }}</td>
                                </tr>
                                <tr class="bg-info">
                                    <th>Total Modal</th>
                                    <th class="text-right">Rp
                                        {{ number_format($reportData['modal']['total'], 0, ',', '.') }}</th>
                                </tr>

                                <tr class="bg-primary">
                                    <th>TOTAL KEWAJIBAN & MODAL</th>
                                    <th class="text-right">Rp
                                        {{ number_format($reportData['totalKewajibanDanModal'], 0, ',', '.') }}</th>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="alert {{ abs($reportData['selisih']) < 1000 ? 'alert-success' : 'alert-danger' }} mt-3">
                    <h5><i class="icon fas fa-balance-scale"></i> Verifikasi Keseimbangan Neraca</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p>Total Aset: <strong class="float-right">Rp
                                    {{ number_format($reportData['aset']['total'], 0, ',', '.') }}</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p>Total Kewajiban + Modal: <strong class="float-right">Rp
                                    {{ number_format($reportData['totalKewajibanDanModal'], 0, ',', '.') }}</strong>
                            </p>
                        </div>
                    </div>
                    <hr>
                    <p class="mb-0">Selisih (Rounding): <strong class="float-right">Rp
                            {{ number_format($reportData['selisih'], 0, ',', '.') }}</strong></p>
                    <small>*Selisih kecil dapat terjadi karena pembulatan atau data snapshot. Selisih besar menandakan
                        ada transaksi yang belum tercatat dengan benar.</small>
                </div>

            </div>
        </div>
    </div>
</section>
