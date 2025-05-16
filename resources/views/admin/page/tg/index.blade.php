<a href="{{ route('admin.tg.create') }}" class="btn btn-primary mb-3">Tambah Data HP</a>
<a href="{{ route('admin.tg.cross-table') }}" class="btn btn-info mb-3 ml-2">Lihat Tabel Silang</a>

@foreach ($groups as $groupName => $positions)
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h4>{{ $groupName ?? 'Ungrouped' }}</h4>
        </div>
        <div class="card-body">
            @foreach ($positions as $position)
                <h5 class="mt-3">Posisi Kamera: {{ $position->position }}</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Brand</th>
                            <th>Tipe</th>
                            <th>Ukuran Layar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($position->hpDatas as $hp)
                            <tr>
                                <td>{{ $hp->brand->name }}</td>
                                <td>{{ $hp->type }}</td>
                                <td>{{ $hp->screenSize->size }}</td>
                                <td>
                                    <a href="{{ route('admin.tg.edit', $hp->id) }}"
                                        class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('admin.tg.destroy', $hp->id) }}" method="POST"
                                        style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Yakin ingin menghapus?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        </div>
    </div>
@endforeach
