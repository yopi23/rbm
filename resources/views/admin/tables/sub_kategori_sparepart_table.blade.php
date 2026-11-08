<div class="table-responsive">
    <table class="table" id="dataTable">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Image</th>
                <th width="20%">Kategori</th>
                <th width="40%">Nama Sub Kategori</th>
                <th width="20%">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sub_kategori as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if ($item->foto_sub_kategori && $item->foto_sub_kategori != '-')
                            <img src="{{ asset('public/uploads/' . $item->foto_sub_kategori) }}" class="img" width="100%" height="100%">
                        @else
                            <img src="{{ asset('public/img/no_image.png') }}" width="100%" height="100%" class="img" id="view-img">
                        @endif
                    </td>
                    <td>{{ optional($item->kategori)->nama_kategori }}</td>
                    <td>{{ $item->nama_sub_kategori }}</td>
                    <td>
                        <form action="{{ route('DeleteSubKategoriSparepart', $item->id) }}" onsubmit="return confirm('Apakah Anda yakin ?')" method="POST">
                            @csrf
                            @method('DELETE')
                            <a href="{{ route('EditSubKategoriSparepart', $item->id) }}" class="btn btn-warning"><i class="fas fa-edit"></i></a>
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
