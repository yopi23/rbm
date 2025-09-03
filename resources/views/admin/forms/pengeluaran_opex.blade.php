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
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('pengeluaran_operasional') }}">Pengeluaran
                                Operasional</a></li>
                        <li class="breadcrumb-item active">{{ isset($data) ? 'Edit' : 'Tambah' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ isset($data) ? 'Edit' : 'Form Tambah' }} Pengeluaran</h3>
                </div>
                <form
                    action="{{ isset($data) ? route('update_pengeluaran_opex', $data->id) : route('store_pengeluaran_opex') }}"
                    method="POST">
                    @csrf
                    @if (isset($data))
                        @method('PUT')
                    @endif

                    <div class="card-body">
                        <div class="form-group">
                            <label for="tgl_pengeluaran">Tanggal</label>
                            <input type="date" name="tgl_pengeluaran" id="tgl_pengeluaran" class="form-control"
                                value="{{ $data->tgl_pengeluaran ?? now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="nama_pengeluaran">Nama Pengeluaran</label>
                            <input type="text" name="nama_pengeluaran" id="nama_pengeluaran" class="form-control"
                                value="{{ $data->nama_pengeluaran ?? '' }}" placeholder="Contoh: Pembayaran Sewa Ruko"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="kategori">Kategori</label>
                            <select name="kategori" id="kategori" class="form-control" required>
                                <option value="">-- Pilih Kategori --</option>
                                {{-- Opsi standar yang selalu ada --}}
                                <option value="Penggajian"
                                    {{ isset($data) && $data->kategori == 'Penggajian' ? 'selected' : '' }}>Penggajian
                                </option>

                                {{-- Loop untuk menampilkan kategori dari master data beban operasional --}}
                                @if (isset($kategoriOpex))
                                    @foreach ($kategoriOpex as $kategori)
                                        <option value="{{ $kategori }}"
                                            {{ isset($data) && $data->kategori == $kategori ? 'selected' : '' }}>
                                            {{ ucfirst($kategori) }}</option>
                                    @endforeach
                                @endif

                                <option value="Lainnya"
                                    {{ isset($data) && $data->kategori == 'Lainnya' ? 'selected' : '' }}>Lainnya
                                </option>
                            </select>
                        </div>
                        <div class="form-group" id="pegawai-form-group" style="display: none;">
                            <label for="kode_pegawai">Pegawai</label>
                            <select name="kode_pegawai" id="kode_pegawai" class="form-control">
                                <option value="">-- Pilih Pegawai --</option>
                                @foreach ($user as $item)
                                    <option value="{{ $item->id }}"
                                        @if (isset($data) && $data->kode_pegawai == $item->id) selected @endif>{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="jml_pengeluaran">Jumlah (Rp)</label>
                            <input type="number" name="jml_pengeluaran" id="jml_pengeluaran" class="form-control"
                                value="{{ $data->jml_pengeluaran ?? '0' }}" required>
                        </div>
                        <div class="form-group">
                            <label for="desc_pengeluaran">Catatan / Deskripsi</label>
                            <textarea class="form-control" name="desc_pengeluaran" id="desc_pengeluaran" rows="3">{{ $data->desc_pengeluaran ?? '' }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <a href="{{ route('pengeluaran_operasional') }}" class="btn btn-danger">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const kategoriSelect = document.getElementById('kategori');
            const pegawaiFormGroup = document.getElementById('pegawai-form-group');

            function togglePegawaiSelect() {
                if (kategoriSelect.value === 'Penggajian') {
                    pegawaiFormGroup.style.display = 'block';
                } else {
                    pegawaiFormGroup.style.display = 'none';
                }
            }

            // Jalankan saat halaman pertama kali dimuat
            togglePegawaiSelect();

            // Tambahkan event listener untuk memantau perubahan
            kategoriSelect.addEventListener('change', togglePegawaiSelect);
        });
    </script>
@endsection
@include('admin.component.footer')
