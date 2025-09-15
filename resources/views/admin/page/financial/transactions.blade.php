<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Filter Transaksi</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('financial.transactions') }}" method="GET">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Tahun</label>
                                <select name="year" class="form-control">
                                    @foreach ($years as $y)
                                        <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                                            {{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bulan</label>
                                <select name="month" class="form-control">
                                    <option value="">Semua Bulan</option>
                                    @foreach (['01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr', '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Ags', '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des'] as $num => $name)
                                        <option value="{{ $num }}" {{ $num == $month ? 'selected' : '' }}>
                                            {{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Tipe Transaksi</label>
                                <select name="type" class="form-control">
                                    <option value="">Semua Tipe</option>
                                    <option value="Pemasukan" {{ $type == 'Pemasukan' ? 'selected' : '' }}>Pemasukan
                                    </option>
                                    <option value="Pengeluaran" {{ $type == 'Pengeluaran' ? 'selected' : '' }}>
                                        Pengeluaran</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Sumber Data</label>
                                <select name="source" class="form-control">
                                    <option value="">Semua Sumber</option>
                                    <option value="modal" {{ $source == 'modal' ? 'selected' : '' }}>Modal
                                    </option>
                                    <option value="service" {{ $source == 'service' ? 'selected' : '' }}>Service
                                    </option>
                                    <option value="sales" {{ $source == 'sales' ? 'selected' : '' }}>Penjualan
                                    </option>
                                    <option value="operational" {{ $source == 'operational' ? 'selected' : '' }}>
                                        Operasional</option>
                                    <option value="store" {{ $source == 'store' ? 'selected' : '' }}>Toko</option>
                                    <option value="distribusi" {{ $source == 'distribusi' ? 'selected' : '' }}>
                                        Distribusi</option>
                                    <option value="pembelian" {{ $source == 'pembelian' ? 'selected' : '' }}>
                                        Pembelian</option>
                                    <option value="manual" {{ $source == 'manual' ? 'selected' : '' }}>Manual
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-filter mr-1"></i>
                                    Filter</button>
                                <a href="{{ route('financial.transactions') }}" class="btn btn-default"><i
                                        class="fas fa-sync-alt mr-1"></i> Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Histori Transaksi</h3>
            </div>
            <div class="card-body">
                {{-- Table-responsive sudah ada di dalam datatables, jadi bisa dihapus --}}
                <table id="tabel-buku-besar" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th class="text-right">Debit (Masuk)</th>
                            <th class="text-right">Kredit (Keluar)</th>
                            <th class="text-right">Saldo</th>
                            <th class="text-center">Sumber</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $index => $tx)
                            <tr>
                                <td>{{ $transactions->firstItem() + $index }}</td>
                                <td>{{ \Carbon\Carbon::parse($tx->tanggal)->format('d/m/Y H:i') }}</td>
                                <td>{{ $tx->deskripsi }}</td>
                                <td class="text-right">
                                    @if ($tx->debit > 0)
                                        <span class="text-success font-weight-bold">Rp
                                            {{ number_format($tx->debit, 0, ',', '.') }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if ($tx->kredit > 0)
                                        <span class="text-danger font-weight-bold">Rp
                                            {{ number_format($tx->kredit, 0, ',', '.') }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-right">Rp {{ number_format($tx->saldo, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    @if (str_contains($tx->sourceable_type, 'TransaksiModal'))
                                        <span class="badge badge-dark">Modal</span>
                                    @elseif (str_contains($tx->sourceable_type, 'Sevices') || str_contains($tx->sourceable_type, 'Pengambilan'))
                                        <span class="badge badge-primary">Service</span>
                                    @elseif (str_contains($tx->sourceable_type, 'Penjualan'))
                                        <span class="badge badge-info">Penjualan</span>
                                    @elseif (str_contains($tx->sourceable_type, 'PengeluaranOperasional'))
                                        <span class="badge badge-warning">Operasional</span>
                                    @elseif (str_contains($tx->sourceable_type, 'PengeluaranToko'))
                                        <span class="badge badge-secondary">Toko</span>
                                    @elseif (str_contains($tx->sourceable_type, 'Pembelian'))
                                        <span class="badge badge-success">Pembelian</span>
                                    @elseif (str_contains($tx->sourceable_type, 'DistribusiLaba'))
                                        <span class="badge"
                                            style="background-color: #6f42c1; color: white;">Distribusi</span>
                                    @else
                                        <span class="badge badge-light">Manual</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (!$tx->sourceable_type)
                                        <div class="btn-group">
                                            <a href="{{ route('financial.edit', $tx->id) }}"
                                                class="btn btn-sm btn-warning" title="Edit"><i
                                                    class="fas fa-edit"></i></a>
                                            {{-- Tombol hapus tidak perlu diubah --}}
                                        </div>
                                    @else
                                        <button type="button" class="btn btn-sm btn-secondary"
                                            title="Transaksi sistem tidak dapat diubah/dihapus" disabled><i
                                                class="fas fa-lock"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada transaksi yang cocok dengan
                                    filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                {{-- Paginasi bawaan Laravel akan kita sembunyikan karena DataTables sudah punya paginasi sendiri --}}
                {{-- {{ $transactions->appends(request()->query())->links() }} --}}
            </div>
        </div>

    </div>
</section>

@foreach ($transactions as $tx)
    @if (!$tx->sourceable_type)
        <div class="modal fade" id="delete-modal-{{ $tx->id }}">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Konfirmasi Hapus</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Anda yakin ingin menghapus transaksi manual: "{{ $tx->deskripsi }}"?</p>
                        <p class="text-danger"><strong>Peringatan Keras:</strong> Menghapus data secara permanen
                            akan merusak urutan saldo dan tidak disarankan. Sebaiknya buat transaksi koreksi.</p>
                    </div>
                    <div class="modal-footer">
                        <form action="{{-- route('financial.destroy', $tx->id) --}}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger" disabled>Ya, Hapus (Fitur
                                Dinonaktifkan)</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
