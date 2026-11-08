<div class="table-responsive">
    <table class="table" id="dataTable">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->email }}</td>
                    <td>
                        @switch((string)$item->jabatan)
                            @case('0')
                                Administrator
                                @break
                            @case('1')
                                Owner
                                @break
                            @case('2')
                                Kasir
                                @break
                            @case('3')
                                Teknisi
                                @break
                            @default
                                -
                        @endswitch
                    </td>
                    <td>
                        @if ($item->status_user == '1')
                            <span class="badge badge-success">Aktif</span>
                        @else
                            <span class="badge badge-danger">Tidak Aktif</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('owner.edit', $item->id_user) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
