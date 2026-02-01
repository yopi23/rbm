@section('page', $page)
@include('admin.component.header')
@include('admin.component.navbar')
@include('admin.component.sidebar')

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ $page }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('service_board.index') }}">Service Board</a></li>
                        <li class="breadcrumb-item active">{{ $page }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Form Penerimaan Unit</h3>
                        </div>
                        <form action="{{ route('service_board.store') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Kode Service</label>
                                            <input type="text" name="kode_service" class="form-control" value="{{ $kode_service }}" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label>Nama Pelanggan <span class="text-danger">*</span></label>
                                            <input type="text" name="nama_pelanggan" class="form-control" required placeholder="Nama Pelanggan">
                                        </div>
                                        <div class="form-group">
                                            <label>No. Telp / WA <span class="text-danger">*</span></label>
                                            <input type="text" name="no_telp" class="form-control" required placeholder="08...">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Type Unit / Device <span class="text-danger">*</span></label>
                                            <input type="text" name="type_unit" class="form-control" required placeholder="Contoh: Samsung A50, iPhone 11">
                                        </div>
                                        <div class="form-group">
                                            <label>Kelengkapan</label>
                                            <input type="text" name="kelengkapan" class="form-control" placeholder="Contoh: Unit Only, Sim Tray, Case">
                                        </div>
                                        <div class="form-group">
                                            <label>Teknisi</label>
                                            <select name="id_teknisi" class="form-control">
                                                <option value="">-- Pilih Teknisi (Opsional) --</option>
                                                @foreach($teknisi as $t)
                                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                <h5 class="mt-3">Keamanan Perangkat</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Tipe Kunci</label>
                                            <select name="tipe_sandi" class="form-control" id="tipe_sandi">
                                                <option value="None">Tidak Ada</option>
                                                <option value="PIN">PIN (Angka)</option>
                                                <option value="Pola">Pola (Pattern)</option>
                                                <option value="Password">Password (Kata Sandi)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Kode / Sandi</label>
                                            <input type="text" name="isi_sandi" class="form-control" placeholder="Masukkan PIN / Deskripsi Pola / Password">
                                            <small class="text-muted">Untuk Pola, gunakan urutan angka 1-9 (Contoh: 1-2-3-6-9)</small>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                <div class="form-group">
                                    <label>Keluhan / Kerusakan</label>
                                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Deskripsikan kerusakan..."></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Uang Muka (DP)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">Rp</span>
                                        </div>
                                        <input type="number" name="dp" class="form-control" value="0">
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer text-right">
                                <a href="{{ route('service_board.index') }}" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary">Simpan & Terima Unit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@include('admin.component.footer')
