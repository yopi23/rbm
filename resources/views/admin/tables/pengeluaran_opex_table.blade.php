<div class="table-responsive">
    <table class="table" id="dataTable">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Tanggal</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Catatan</th>
                <th>Jumlah</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data_opex as $index => $item)
                @php
                    $kategori = (strtolower($item->kategori) == 'penggajian' && $item->pegawai) ? $item->kategori . ' (' . $item->pegawai->name . ')' : $item->kategori;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->tgl_pengeluaran }}</td>
                    <td>{{ $item->nama_pengeluaran }}</td>
                    <td>{{ $kategori }}</td>
                    <td>{{ $item->desc_pengeluaran }}</td>
                    <td>Rp.{{ number_format($item->jml_pengeluaran) }},-</td>
                    <td>
                        <form action="{{ route('delete_pengeluaran_opex', $item->id) }}" onsubmit="return confirm('Apakah Anda yakin ?')" method="POST">
                            @csrf
                            @method('DELETE')
                            <a href="{{ route('edit_pengeluaran_opex', $item->id) }}" class="btn btn-warning"><i class="fas fa-edit"></i></a>
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
