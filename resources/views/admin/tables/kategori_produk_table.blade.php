<div class="table-responsive">
    <table class="table" id="dataTable">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Image</th>
                <th>Nama</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($kategori as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if ($item->foto_kategori && $item->foto_kategori != '-')
                            <img src="{{ asset('public/uploads/' . $item->foto_kategori) }}" class="img" width="100%" height="100%">
                        @else
                            <img src="{{ asset('public/img/no_image.png') }}" width="100%" height="100%" class="img" id="view-img">
                        @endif
                    </td>
                    <td>{{ $item->nama_kategori }}</td>
                    <td>
                        <form action="{{ route('DeleteKategoriProduk', $item->id) }}" onsubmit="return confirm('Apakah Anda yakin ?')" method="POST">
                            @csrf
                            @method('DELETE')
                            <a href="{{ route('EditKategoriProduk', $item->id) }}" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
