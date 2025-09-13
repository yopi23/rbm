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
            {{-- Menampilkan pesan error dari validasi controller --}}
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

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
                                value="{{ old('tgl_pengeluaran', $data->tgl_pengeluaran ?? now()->format('Y-m-d')) }}"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="nama_pengeluaran">Nama/Deskripsi Pengeluaran</label>
                            <input type="text" name="nama_pengeluaran" id="nama_pengeluaran" class="form-control"
                                value="{{ old('nama_pengeluaran', $data->nama_pengeluaran ?? '') }}"
                                placeholder="Contoh: Beli Token Listrik 100rb" required>
                        </div>

                        <div class="form-group">
                            <label for="beban_operasional_id">Kategori Beban (Jatah Bulanan)</label>
                            <select name="beban_operasional_id" id="beban_operasional_id" class="form-control">
                                <option value="" data-sisa="0">-- Pilih Jatah Beban (atau biarkan kosong untuk
                                    pengeluaran lain) --</option>
                                @if (isset($daftarBeban))
                                    @foreach ($daftarBeban as $beban)
                                        <option value="{{ $beban->id }}"
                                            {{ old('beban_operasional_id', $selectedBebanId ?? '') == $beban->id ? 'selected' : '' }}
                                            data-nama="{{ strtolower($beban->nama_beban) }}"
                                            data-sisa="{{ $beban->sisa_jatah }}">
                                            {{ $beban->nama_beban }} (Sisa: Rp
                                            {{ number_format($beban->sisa_jatah) }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <input type="hidden" name="kategori_text" id="kategori_text"
                            value="{{ old('kategori_text', $data->kategori ?? '') }}">

                        <div class="form-group" id="pegawai-form-group" style="display: none;">
                            <label for="kode_pegawai">Pegawai</label>
                            <select name="kode_pegawai" id="kode_pegawai" class="form-control">
                                <option value="">-- Pilih Pegawai --</option>
                                @foreach ($user as $item)
                                    <option value="{{ $item->id }}"
                                        @if (old('kode_pegawai', $data->kode_pegawai ?? '') == $item->id) selected @endif>{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="jml_pengeluaran">Jumlah (Rp)</label>
                            <input type="number" name="jml_pengeluaran" id="jml_pengeluaran" class="form-control"
                                value="{{ old('jml_pengeluaran', $data->jml_pengeluaran ?? '0') }}" required>
                            {{-- Info sisa jatah akan muncul di sini --}}
                            <small id="info-sisa-jatah" class="form-text text-muted"></small>
                        </div>

                        <div class="form-group">
                            <label for="desc_pengeluaran">Catatan Tambahan</label>
                            <textarea class="form-control" name="desc_pengeluaran" id="desc_pengeluaran" rows="3">{{ old('desc_pengeluaran', $data->desc_pengeluaran ?? '') }}</textarea>
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
            const bebanSelect = document.getElementById('beban_operasional_id');
            const jumlahInput = document.getElementById('jml_pengeluaran');
            const infoSisaJatah = document.getElementById('info-sisa-jatah');
            const pegawaiFormGroup = document.getElementById('pegawai-form-group');

            function updateFormBasedOnCategory() {
                const selectedOption = bebanSelect.options[bebanSelect.selectedIndex];
                const namaBeban = selectedOption.getAttribute('data-nama');
                const sisaJatah = parseInt(selectedOption.getAttribute('data-sisa')) || 0;

                // 1. Tampilkan/Sembunyikan Pilihan Pegawai
                if (namaBeban === 'penggajian') {
                    pegawaiFormGroup.style.display = 'block';
                } else {
                    pegawaiFormGroup.style.display = 'none';
                }

                // 2. Update info sisa jatah & batasi input jumlah
                if (bebanSelect.value) { // Jika kategori dipilih
                    jumlahInput.setAttribute('max', sisaJatah);
                    infoSisaJatah.textContent =
                        `Sisa jatah untuk kategori ini adalah Rp ${sisaJatah.toLocaleString('id-ID')}.`;
                } else { // Jika tidak ada kategori dipilih
                    jumlahInput.removeAttribute('max');
                    infoSisaJatah.textContent = '';
                }
            }

            // Jalankan saat halaman dimuat
            updateFormBasedOnCategory();

            // Tambahkan event listener
            bebanSelect.addEventListener('change', updateFormBasedOnCategory);

            // Beri peringatan jika user memasukkan angka > max
            jumlahInput.addEventListener('input', function(e) {
                const max = parseInt(e.target.getAttribute('max'));
                const value = parseInt(e.target.value);
                if (!isNaN(max) && !isNaN(value) && value > max) {
                    infoSisaJatah.innerHTML =
                        `<strong class="text-danger">Jumlah melebihi sisa jatah (Rp ${max.toLocaleString('id-ID')})!</strong>`;
                } else {
                    // Kembalikan ke info normal jika sudah benar
                    updateFormBasedOnCategory();
                }
            });
        });
    </script>
@endsection
@include('admin.component.footer')
