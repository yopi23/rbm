<form action="{{ route('create_service_in_dashboard') }}" method="POST">
    @csrf
    @method('POST')
    <div class="formservice d-none">

        <input type="text" value="{{ $kode_service }}" name="kode_service" id="kode_service" class="form-control" hidden>
        <input type="date" value="{{ date('Y-m-d') }}" name="tgl_service" id="tgl_service" class="form-control" hidden>
        <input type="text" value="{{ auth()->user()->name }}" class="form-control" hidden>

        <div class="input-group my-2">
            <span class="input-group-text">Nama</span>
            <input type="text" name="nama_pelanggan" id="nama_pelanggan" class="form-control" autofocus>
            <span class="input-group-text">No Tlp.</span>
            <input type="text" name="no_telp" id="no_telp" value="0" class="form-control" autocomplete="off">
        </div>
        <div class="input-group my-2" id="typeGrup">
            <span class="input-group-text">Type</span>
            <input type="text" name="type_unit" id="type_unit" class="form-control">
        </div>

        <div class="input-group my-2" id="keteranganGrup">
            <span class="input-group-text">Keterangan</span>
            <textarea class="form-control" name="ket" id="ket" aria-label="With textarea"></textarea>
        </div>
        <div class="table-responsive border border-primary rounded p-3">
            <label>saran harga</label>
            <select name="kode_part[0]" id="kode_part[0]" class="form-control select-bootstrap kode_part">
                <option value="">-- Pilih Sparepart --
                </option>
                @forelse ($sparepart as $item)
                    <option value="{{ $item->id }}" data-stok="{{ $item->stok_sparepart }}"
                        data-harga="{{ $item->harga_jual + $item->harga_pasang }}"
                        {{ $item->stok_sparepart <= 0 ? 'disabled' : '' }}>
                        {{ $item->nama_sparepart . ' ' . '(Rp.' . number_format($item->harga_jual + $item->harga_pasang) . ')' }}
                        {{ $item->stok_sparepart <= 0 ? '( Stok Kosong )' : '' }}
                    </option>
                @empty
                @endforelse
            </select>
            <div class="input-group">
                <input type="text" name="harga_kode_part[0]" id="harga_kode_part[0]" class="form-control harga_spart"
                    readonly>

                <input type="number" value="1" name="qty_kode_part[0]" id="qty_kode_part[0]"
                    class="form-control qty_spart">
            </div>

        </div>
        <div class="input-group my-2">
            <span class="input-group-text">Biaya</span>
            <input type="text" value="0" class="form-control" name="biaya_servis" id="biaya_servis" hidden>
            <input type="text" class="form-control biaya-input" name="in_biaya_servis" id="in_biaya_servis">

            <span class="input-group-text">DP</span>
            <input type="text" class="form-control" name="dp" id="dp" value="0" hidden>
            <input type="text" class="form-control dp-input" name="in_dp" id="in_dp">
        </div>
        <div class="d-flex align-item-center">
            <button type="submit" class="btn btn-primary form-control">Simpan</button>
        </div>
    </div>
</form>
