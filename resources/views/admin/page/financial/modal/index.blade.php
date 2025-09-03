<section class="content">
    <div class="container-fluid">
        <div class="mb-3">
            <a href="{{ route('modal.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle mr-1"></i> Tambah
                Transaksi Modal</a>
            <a href="{{ route('financial.index') }}" class="btn btn-secondary"><i class="fas fa-tachometer-alt mr-1"></i>
                Dashboard Keuangan</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Histori Transaksi Modal</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Jenis Transaksi</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transaksiModal as $index => $transaksi)
                                <tr>
                                    <td>{{ $transaksiModal->firstItem() + $index }}</td>
                                    <td>{{ \Carbon\Carbon::parse($transaksi->tanggal)->format('d F Y') }}</td>
                                    <td>
                                        @if ($transaksi->jenis_transaksi == 'setoran_awal')
                                            <span class="badge badge-primary">Setoran Awal</span>
                                        @elseif($transaksi->jenis_transaksi == 'tambahan_modal')
                                            <span class="badge badge-success">Tambahan Modal</span>
                                        @else
                                            <span class="badge badge-danger">Penarikan Modal (Prive)</span>
                                        @endif
                                    </td>
                                    <td><span class="font-weight-bold">Rp
                                            {{ number_format($transaksi->jumlah, 0, ',', '.') }}</span></td>
                                    <td>{{ $transaksi->keterangan ?? '-' }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('modal.edit', $transaksi->id) }}"
                                                class="btn btn-sm btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" title="Hapus/Batalkan"
                                                data-toggle="modal" data-target="#deleteModal-{{ $transaksi->id }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Belum ada transaksi modal yang tercatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @foreach ($transaksiModal as $transaksi)
                            <div class="modal fade" id="deleteModal-{{ $transaksi->id }}" tabindex="-1" role="dialog">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Konfirmasi Pembatalan</h5>
                                            <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Anda yakin ingin membatalkan transaksi ini?</p>
                                            <p class="text-danger">Aksi ini akan membuat jurnal balik di buku besar
                                                untuk menetralkan transaksi ini. Data asli akan dihapus.</p>
                                        </div>
                                        <div class="modal-footer">
                                            <form action="{{ route('modal.destroy', $transaksi->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-secondary"
                                                    data-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-danger">Ya, Batalkan
                                                    Transaksi</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                {{ $transaksiModal->links() }}
            </div>
        </div>
    </div>
</section>
