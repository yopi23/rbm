<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Pembelian</h3>
                <div class="card-tools">
                    <a href="{{ route('pembelian.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Tambah Pembelian Baru
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-check"></i> Sukses!</h5>
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5><i class="icon fas fa-ban"></i> Error!</h5>
                        {{ session('error') }}
                    </div>
                @endif

                <table id="tabel-pembelian" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Kode Pembelian</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pembelians as $pembelian)
                            <tr>
                                <td>{{ $pembelian->kode_pembelian }}</td>
                                <td>{{ date('d-m-Y', strtotime($pembelian->tanggal_pembelian)) }}</td>
                                <td>{{ $pembelian->supplier ?? '-' }}</td>
                                <td>Rp {{ number_format($pembelian->total_harga, 0, ',', '.') }}</td>
                                <td>
                                    @if ($pembelian->status == 'draft')
                                        <span class="badge badge-warning">Draft</span>
                                    @else
                                        <span class="badge badge-success">Selesai</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('pembelian.show', $pembelian->id) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if ($pembelian->status == 'draft')
                                        <a href="{{ route('pembelian.edit', $pembelian->id) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(function() {
            $('#tabel-pembelian').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
            });
        });
    </script>
@endpush
