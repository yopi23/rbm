<div class="table-responsive">
    <table class="table" id="dataTable">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Tanggal</th>
                <th>Nama</th>
                <th>Catatan</th>
                <th>Harga</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data_pengeluaran as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->tanggal_pengeluaran }}</td>
                    <td>{{ $item->nama_pengeluaran }}</td>
                    <td>{{ $item->catatan_pengeluaran }}</td>
                    <td>Rp.{{ number_format($item->jumlah_pengeluaran) }},-</td>
                    <td>
                        <form action="{{ route('delete_pengeluaran_toko', $item->id) }}" onsubmit="return confirm('Apakah Anda yakin ?')" method="POST">
                            @csrf
                            @method('DELETE')
                            <a href="{{ route('edit_pengeluaran_toko', $item->id) }}" class="btn btn-warning"><i class="fas fa-edit"></i></a>
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
