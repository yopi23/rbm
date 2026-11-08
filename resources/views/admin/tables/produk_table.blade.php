<div class="table-responsive">
    <table class="table" id="dataTable">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Barcode</th>
                <th>Image</th>
                <th>Kode Barang</th>
                <th>Nama</th>
                <th>Harga Beli</th>
                <th>Harga Jual</th>
                <th>Kondisi</th>
                <th>Stok</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data_barang as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{!! \Milon\Barcode\Facades\DNS1DFacade::getBarcodeHTML($item->kode_barang, 'C39', 1, 100) !!}</td>
                    <td>
                        @if ($item->foto_barang && $item->foto_barang != '-')
                            <img src="{{ asset('public/uploads/' . $item->foto_barang) }}" class="img" width="100%" height="100%">
                        @else
                            <img src="{{ asset('public/img/no_image.png') }}" width="100%" height="100%" class="img" id="view-img">
                        @endif
                    </td>
                    <td>{{ $item->kode_barang }}</td>
                    <td>{{ $item->nama_barang }}</td>
                    <td>Rp.{{ number_format($item->harga_beli_barang) }},-</td>
                    <td>Rp.{{ number_format($item->harga_jual_barang) }},-</td>
                    <td>{{ $item->kondisi_barang }}</td>
                    <td>{{ $item->stok_barang }}</td>
                    <td>
                        <form action="{{ route('delete_produk', $item->id) }}" onsubmit="return confirm('Apakah Anda yakin ?')" method="POST">
                            @csrf
                            @method('DELETE')
                            <a href="{{ route('edit_produk', $item->id) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
