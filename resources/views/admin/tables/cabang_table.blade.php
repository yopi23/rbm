<div class="table-responsive">
    <table class="table" id="dataTable">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Cabang</th>
                <th>Alamat</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->nama_cabang }}</td>
                    <td>{{ $item->alamat_cabang ?? '-' }}</td>
                    <td>
                        @if ($item->is_active)
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Tidak Aktif</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('cabang.destroy', $item->id) }}" onsubmit="return confirm('Apakah Anda yakin ?')" method="POST">
                            @csrf
                            @method('DELETE')
                            <a href="{{ route('cabang.edit', $item->id) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
