<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Customer</h3>
                    <div class="card-tools">
                        <a href="{{ route('customer.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert"
                                aria-hidden="true">&times;</button>
                            <h5><i class="icon fas fa-ban"></i> Error!</h5>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('customer.update', $customer->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="kode_toko">Kode toko</label>
                            <input type="text" class="form-control" id="kode_toko" name="kode_toko"
                                value="{{ $customer->kode_toko }}" readonly>
                        </div>
                        <div class="form-group">
                            <label for="nama_pelanggan">Nama Pelanggan</label>
                            <input type="text" class="form-control" id="nama_pelanggan" name="nama_pelanggan"
                                value="{{ old('nama_pelanggan', $customer->nama_pelanggan) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="nama_toko">Nama Toko</label>
                            <input type="text" class="form-control" id="nama_toko" name="nama_toko"
                                value="{{ old('nama_toko', $customer->nama_toko) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="alamat_toko">Alamat Toko</label>
                            <textarea class="form-control" id="alamat_toko" name="alamat_toko" rows="3" required>{{ old('alamat_toko', $customer->alamat_toko) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="status_toko">Status Toko</label>
                            <select class="form-control" id="status_toko" name="status_toko" required>
                                <option value="biasa"
                                    {{ old('status_toko', $customer->status_toko) == 'biasa' ? 'selected' : '' }}>
                                    Pelanggan Biasa</option>
                                <option value="konter"
                                    {{ old('status_toko', $customer->status_toko) == 'konter' ? 'selected' : '' }}>
                                    Konter</option>
                                <option value="glosir"
                                    {{ old('status_toko', $customer->status_toko) == 'glosir' ? 'selected' : '' }}>
                                    Glosir</option>
                                <option value="super"
                                    {{ old('status_toko', $customer->status_toko) == 'super' ? 'selected' : '' }}>
                                    Super</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="nomor_toko">Nomor Toko</label>
                            <input type="text" class="form-control" id="nomor_toko" name="nomor_toko"
                                value="{{ old('nomor_toko', $customer->nomor_toko) }}" required>
                            <small class="form-text text-muted">Masukkan nomor telepon toko atau pelanggan.</small>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ route('customer.index') }}" class="btn btn-default">Batal</a>
                        </div>
                    </form>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>
