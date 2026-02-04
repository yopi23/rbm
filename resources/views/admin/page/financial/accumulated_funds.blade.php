<div class="row">
    {{-- Summary Cards --}}
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>Rp {{ number_format($totalSinkingFund, 0, ',', '.') }}</h3>
                <p>Total Sinking Fund (Beban Ops)</p>
            </div>
            <div class="icon">
                <i class="fas fa-piggy-bank"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>Rp {{ number_format($totalTechnicianBalance, 0, ',', '.') }}</h3>
                <p>Total Saldo Teknisi (Komisi)</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-12">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>Rp {{ number_format($totalAccumulated, 0, ',', '.') }}</h3>
                <p>Total Dana Terkumpul (Liabilitas)</p>
            </div>
            <div class="icon">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Sinking Funds Table --}}
    <div class="col-md-6">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Rincian Sinking Fund (Beban Operasional)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nama Beban</th>
                            <th class="text-right">Alokasi/Bulan</th>
                            <th class="text-right">Dana Terkumpul</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sinkingFunds as $fund)
                        <tr>
                            <td>{{ $fund->nama_beban }}</td>
                            <td class="text-right">Rp {{ number_format($fund->nominal, 0, ',', '.') }}</td>
                            <td class="text-right font-weight-bold">Rp {{ number_format($fund->current_balance, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">Tidak ada data beban operasional.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-right">Total</th>
                            <th class="text-right">Rp {{ number_format($totalSinkingFund, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Technician Balance Table --}}
    <div class="col-md-6">
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title">Rincian Saldo Teknisi</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nama Teknisi</th>
                            <th class="text-right">Saldo Saat Ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($technicians as $tech)
                        <tr>
                            <td>{{ $tech->fullname }}</td>
                            <td class="text-right font-weight-bold">Rp {{ number_format($tech->saldo, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center">Tidak ada data teknisi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-right">Total</th>
                            <th class="text-right">Rp {{ number_format($totalTechnicianBalance, 0, ',', '.') }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="card-footer">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> Penarikan pending (Rp {{ number_format($pendingWithdrawals, 0, ',', '.') }}) sudah mengurangi saldo teknisi.
                </small>
            </div>
        </div>
    </div>
</div>
