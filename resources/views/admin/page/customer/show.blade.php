<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detail Customer</h3>
                    <div class="card-tools">
                        <a href="{{ route('customer.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px">Kode toko</th>
                                    <td>{{ $customer->kode_toko }}</td>
                                </tr>
                                <tr>
                                    <th>Nama Pelanggan</th>
                                    <td>{{ $customer->nama_pelanggan }}</td>
                                </tr>
                                <tr>
                                    <th>Nama Toko</th>
                                    <td>{{ $customer->nama_toko }}</td>
                                </tr>
                                <tr>
                                    <th>Alamat Toko</th>
                                    <td>{{ $customer->alamat_toko }}</td>
                                </tr>
                                <tr>
                                    <th>Status Toko</th>
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
                                </tr>
                                <tr>
                                    <th>Nomor Toko</th>
                                    <td>{{ $customer->nomor_toko }}</td>
                                </tr>
                                <tr>
                                    <th>Tanggal Dibuat</th>
                                    <td>{{ $customer->created_at->format('d F Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Terakhir Diperbarui</th>
                                    <td>{{ $customer->updated_at->format('d F Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('customer.edit', $customer->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form action="{{ route('customer.destroy', $customer->id) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus customer ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </form>
                    </div>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>
