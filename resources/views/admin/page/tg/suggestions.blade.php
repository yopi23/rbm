<h3>Daftar Saran HP</h3>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Brand</th>
            <th>Type</th>
            <th>Ukuran Layar</th>
            <th>Posisi Kamera</th>
            <th>Catatan</th>
            <th>Dikirim Oleh</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($suggestions as $s)
            <tr>
                <td>{{ $s->brand }}</td>
                <td>{{ $s->type }}</td>
                <td>{{ $s->screen_size }}</td>
                <td>{{ $s->camera_position }}</td>
                <td>{{ $s->note }}</td>
                <td>{{ $s->submitted_by }}</td>
                <td>
                    <a href="{{ url('/hp/approve/' . $s->id) }}" class="btn btn-success btn-sm">Approve</a>
                    <a href="{{ url('/hp/reject/' . $s->id) }}" class="btn btn-danger btn-sm"
                        onclick="return confirm('Apakah Anda yakin ingin menolak saran ini?')">Reject</a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
