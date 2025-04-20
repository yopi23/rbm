<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Daftar Customer</h3>
                    <div class="card-tools">
                        <a href="{{ route('customer.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Customer
                        </a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert"
                                aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-check"></i> Sukses!</h5>
                            {{ session('success') }}
                        </div>
                    @endif

                    <table id="customerTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Toko</th>
                                <th>Nama Pelanggan</th>
                                <th>Nama Toko</th>
                                <th>Status Toko</th>
                                <th>Nomor Toko</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customers as $index => $customer)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $customer->kode_toko }}</td>
                                    <td>{{ $customer->nama_pelanggan }}</td>
                                    <td>{{ $customer->nama_toko }}</td>
                                    <td>
                                        @if ($customer->status_toko == 'biasa')
                                            <span class="badge badge-secondary">Pelanggan Biasa</span>
                                        @elseif ($customer->status_toko == 'konter')
                                            <span class="badge badge-info">Konter</span>
                                        @elseif ($customer->status_toko == 'glosir')
                                            <span class="badge badge-primary">Glosir</span>
                                        @elseif ($customer->status_toko == 'super')
                                            <span class="badge badge-success">Super</span>
                                        @endif
                                    </td>
                                    <td>{{ $customer->nomor_toko }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('customer.show', $customer->id) }}"
                                                class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('customer.edit', $customer->id) }}"
                                                class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('customer.destroy', $customer->id) }}" method="POST"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus customer ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>

<script>
    $(function() {
        $("#customerTable").DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
        }).buttons().container().appendTo('#customerTable_wrapper .col-md-6:eq(0)');
    });
</script>
