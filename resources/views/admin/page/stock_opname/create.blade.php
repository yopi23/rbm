<!-- resources/views/admin/page/stock_opname/create.blade.php -->

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-plus mr-1"></i>
                    Buat Periode Stock Opname Baru
                </h3>
            </div>
            <form action="{{ route('stock-opname.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="kode_periode">Kode Periode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('kode_periode') is-invalid @enderror"
                                    id="kode_periode" name="kode_periode"
                                    value="{{ old('kode_periode', $kode_periode) }}" readonly>
                                <small class="form-text text-muted">Kode periode akan digenerate otomatis</small>
                                @error('kode_periode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="nama_periode">Nama Periode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nama_periode') is-invalid @enderror"
                                    id="nama_periode" name="nama_periode" value="{{ old('nama_periode') }}"
                                    placeholder="Contoh: Stock Opname Bulanan April 2025" required>
                                @error('nama_periode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tanggal_mulai">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                    id="tanggal_mulai" name="tanggal_mulai"
                                    value="{{ old('tanggal_mulai', date('Y-m-d')) }}" required>
                                @error('tanggal_mulai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="tanggal_selesai">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date"
                                    class="form-control @error('tanggal_selesai') is-invalid @enderror"
                                    id="tanggal_selesai" name="tanggal_selesai"
                                    value="{{ old('tanggal_selesai', date('Y-m-d', strtotime('+7 days'))) }}" required>
                                @error('tanggal_selesai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="catatan">Catatan (Opsional)</label>
                        <textarea class="form-control @error('catatan') is-invalid @enderror" id="catatan" name="catatan" rows="3"
                            placeholder="Masukkan catatan atau instruksi untuk stock opname ini">{{ old('catatan') }}</textarea>
                        @error('catatan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        Setelah membuat periode stock opname, semua sparepart akan otomatis ditambahkan ke dalam daftar
                        yang perlu diperiksa.
                        Stock opname akan dimulai dalam status <strong>Draft</strong> dan dapat mulai diproses setelah
                        ini.
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('stock-opname.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-primary float-right">
                        <i class="fas fa-save mr-1"></i> Simpan dan Buat
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(function() {
        // Validasi tanggal selesai harus >= tanggal mulai
        $('#tanggal_mulai, #tanggal_selesai').change(function() {
            let tanggalMulai = $('#tanggal_mulai').val();
            let tanggalSelesai = $('#tanggal_selesai').val();

            if (tanggalMulai && tanggalSelesai) {
                if (new Date(tanggalSelesai) < new Date(tanggalMulai)) {
                    $('#tanggal_selesai').val(tanggalMulai);
                    toastr.warning('Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
                }
            }
        });
    });
</script>
