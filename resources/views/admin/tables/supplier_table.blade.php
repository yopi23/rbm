<div class="table-responsive">
    <table class="table" id="dataTable">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama</th>
                <th>Alamat</th>
                <th>No Telp</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->nama_supplier }}</td>
                    <td>{{ $item->alamat_supplier }}</td>
                    <td>{{ $item->no_telp_supplier }}</td>
                    <td>
                        <form action="{{ route('supplier.destroy', $item->id) }}" onsubmit="return confirm('Apakah Anda yakin ?')" method="POST">
                            @csrf
                            @method('DELETE')
                            <a href="{{ route('supplier.edit', $item->id) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
