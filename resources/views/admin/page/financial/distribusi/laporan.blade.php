<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Log Detil Alokasi Laba</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tanggal Alokasi</th>
                            <th>Periode</th>
                            <th>Peruntukan</th>
                            <th>Jumlah (Rp)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alokasiLogs as $alokasi)
                            <tr>
                                <td>{{ $alokasi->created_at->format('d M Y') }}</td>
                                <td><small>{{ $alokasi->distribusiLaba->tanggal_mulai->format('d/m/Y') }}</small></td>
                                <td>{{ Str::title(str_replace('_', ' ', $alokasi->role)) }}</td>
                                <td class="text-right">{{ number_format($alokasi->jumlah) }}</td>
                                <td>
                                    @if ($alokasi->status == 'dialokasikan')
                                        <span class="badge badge-warning">Dialokasikan</span>
                                    @else
                                        <span class="badge badge-success">Sudah Dicairkan</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
