<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Hutang Belum Lunas</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nota</th>
                            <th>Supplier</th>
                            <th>Total Hutang</th>
                            <th>Jatuh Tempo</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hutang as $item)
                            <tr>
                                <td>{{ $item->kode_nota }}</td>
                                <td>{{ $item->supplier->nama_supplier ?? 'N/A' }}</td>
                                <td>Rp {{ number_format($item->total_hutang, 0, ',', '.') }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->tgl_jatuh_tempo)->format('d/m/Y') }}</td>
                                <td>
                                    <form action="{{ route('hutang.bayar', $item->id) }}" method="POST"
                                        onsubmit="return confirm('Anda yakin ingin melunasi hutang ini? Transaksi pengeluaran akan dicatat di buku besar.');">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">Bayar Lunas</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada hutang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
