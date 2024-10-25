<form action="{{ route('store_pengeluaran_toko') }}" method="POST">
    @csrf
    @method('POST')
    <div class="formpengeluaran d-none">

        <input type="text" value="{{ $kode_service }}" name="kode_service" id="kode_service" class="form-control" hidden>
        <input type="date" value="{{ date('Y-m-d') }}" name="tgl_service" id="tgl_service" class="form-control" hidden>
        <input type="text" value="{{ auth()->user()->name }}" class="form-control" hidden>
        <div class="input-group my-2">
            <label class="input-group-text" for="id_kategorilaci">Penyimpanan</label>
            <select name="id_kategorilaci" class="form-control" required>
                <option value="" disabled selected>--Pilih Kategori Laci--</option>
                @foreach ($listLaci as $kategori)
                    <option value="{{ $kategori->id }}">{{ $kategori->name_laci }}</option>
                @endforeach
            </select>
        </div>
        <div class="input-group my-2">
            <span class="input-group-text">Judul</span>
            <input type="text" name="nama_pengeluaran" id="nama_pengeluaran" placeholder="pengeluaran"
                class="form-control" value=" " required>
            <input type="date" name="tanggal_pengeluaran" id="tanggal_pengeluaran" class="form-control"
                value="{{ date('Y-m-d') }}" readonly>
        </div>
        <div class="input-group my-2">
            <span class="input-group-text" style="color: red;">Jumlah</span>
            <input type="number" name="jumlah_pengeluaran" id="jumlah_pengeluaran" placeholder="Rp."
                class="form-control" value=" " required>
        </div>
        <div class="input-group my-2" id="keteranganGrup">
            <span class="input-group-text">Keterangan</span>
            <textarea name="catatan_pengeluaran" id="catatan_pengeluaran" class="form-control" required>-</textarea>
        </div>


        <div class="d-flex align-item-center">
            <button type="submit" class="btn btn-primary form-control">Simpan</button>
        </div>
    </div>
</form>
