<section class="content">
    <div class="container-fluid">
        <div class="mb-3">
            <a href="{{ route('asets.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle mr-1"></i>
                Tambah Aset Baru</a>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Aset Perusahaan</h3>
            </div>
            <div class="card-body">
                <table id="tabel-aset" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nama Aset</th>
                            <th>Nilai Perolehan (Rp)</th>
                            <th>Penyusutan (Rp)</th>
                            <th>Nilai Buku Saat Ini (Rp)</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($asets as $aset)
                            <tr>
                                <td>{{ $aset->nama_aset }}</td>
                                <td>{{ number_format($aset->nilai_perolehan) }}</td>
                                <td><small>{{ number_format($aset->penyusutan_terakumulasi) }}</small></td>
                                <td class="font-weight-bold">{{ number_format($aset->nilai_buku) }}</td>
                                <td></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
