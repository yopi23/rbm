<div class="table-responsive">
    <table class="table" id="dataEditTable">
        <thead>
            <tr>
                <th width="1%"><input type="checkbox" id="select-all-checkbox"></th>
                <th width="5%">No</th>
                <th>Kode</th>
                <th>Nama Sparepart</th>
                <th>Sub Kategori</th>
                <th>Harga Beli</th>
                <th>Harga Jual</th>
                <th>Stok</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sparepart_data as $index => $item)
                <tr>
                    <td><input type="checkbox" class="item-checkbox" value="{{ $item->id }}"></td>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->kode_sparepart }}</td>
                    <td>{{ $item->nama_sparepart }}</td>
                    <td>{{ optional($item->subKategori)->nama_sub_kategori ?? '-' }}</td>
                    <td>Rp.{{ number_format($item->harga_beli) }}</td>
                    <td>Rp.{{ number_format($item->harga_jual) }}</td>
                    <td>{{ $item->stok_sparepart }}</td>
                    <td>
                        <form action="{{ route('DeleteSparepart', $item->id) }}" onsubmit="return confirm('Anda yakin?')" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <a href="{{ route('EditSparepart', $item->id) }}" class="btn btn-sm btn-warning my-1"><i class="fas fa-edit"></i></a>
                            <button type="submit" class="btn btn-sm btn-danger my-1"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
