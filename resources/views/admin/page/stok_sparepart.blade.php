<div class="card card-success card-outline">
    <div class="card-header">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#stok" data-toggle="tab">Stok</a></li>
            <li class="nav-item"><a class="nav-link" href="#restok" data-toggle="tab">Restok</a></li>
            <li class="nav-item"><a class="nav-link" href="#retur" data-toggle="tab">Retur Sparepart</a></li>
            <li class="nav-item"><a class="nav-link" href="#rusak" data-toggle="tab">Rusak</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="stok">
                <form method="GET" action="{{ route('stok_sparepart') }}">
                    <div class="row justify-content-center">
                        <div class="col-auto">
                            <select name="filter_kategori" id="filter-kategori" class="form-control">
                                <option value="">Semua Kategori</option>
                                @foreach ($data_kategori as $kategori)
                                    <option value="{{ $kategori->id }}"
                                        {{ request('filter_kategori') == $kategori->id ? 'selected' : '' }}>
                                        {{ $kategori->nama_kategori }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <select name="filter_spl" id="filter-spl" class="form-control">
                                <option value="">Semua SPL</option>
                                @foreach ($data_spl as $spl)
                                    <option value="{{ $spl->id }}"
                                        {{ request('filter_spl') == $spl->id ? 'selected' : '' }}>
                                        {{ $spl->nama_supplier }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </form>
                <div class="row">
                    <div class="col-md-12">
                        <table class="table" id="TABLES_1">
                            <thead>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Barang</th>
                                <th>Stok</th>
                                <th>Terjual</th>
                                <th>Terpakai</th>
                                <th class="col-md-2">Opsi</th>
                            </thead>
                            <tbody>
                                @foreach ($data_sparepart as $index => $sparepart)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $sparepart->kode_sparepart }}</td>
                                        <td>{{ $sparepart->nama_sparepart }}</td>
                                        <td>{{ $sparepart->stok_sparepart }}</td>
                                        <td>
                                            @php
                                                $terjual = $view_terjual->firstWhere('kode_sparepart', $sparepart->id);
                                                $total_terjual = $terjual ? $terjual->total_terjual : 0;
                                            @endphp
                                            {{ $total_terjual }}
                                        </td>
                                        <td>
                                            @php
                                                $terpakai = $view_terpakai->firstWhere(
                                                    'kode_sparepart',
                                                    $sparepart->id,
                                                );
                                                $total_terpakai = $terpakai ? $terpakai->total_terpakai : 0;
                                            @endphp
                                            {{ $total_terpakai }}
                                        </td>
                                        <td>
                                            <form id="orderForm" method="POST" action="{{ route('order.store') }}">
                                                @csrf
                                                <div class="input-group">
                                                    <input type="number" name="qty" class="form-control"
                                                        id="qty_order" required>
                                                    <input type="hidden" name="id_barang"
                                                        value="{{ $sparepart->id }}">
                                                    <button type="submit" class="btn btn-success"><i
                                                            class="fa fa-plus"></i></button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $data_sparepart->links() }}
                        </div>
                    </div>
                </div>

            </div>
            <div class="tab-pane " id="restok">
                <div class="row">
                    <div class="col-md-12">
                        <a href="{{ route('create_sparepart_restok') }}" class="btn btn-success"><i
                                class="fas fa-plus"></i> Restok</a>
                        <hr>
                        <table class="table" id="TABLES_2">
                            <thead>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Nama Barang</th>
                                <th>Restok</th>
                                <th>Supplier</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </thead>
                            <tbody>
                                @foreach ($data_sparepart_restok as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->kode_restok }}</td>
                                        <td>{{ $item->tgl_restok }}</td>
                                        <td>{{ $item->nama_sparepart }}</td>
                                        <td>{{ $item->jumlah_restok }}</td>
                                        <td>{{ $item->nama_supplier }}</td>
                                        <td>{{ $item->catatan_restok }}</td>
                                        <td>
                                            @switch($item->status_restok)
                                                @case('Pending')
                                                    <span class="badge badge-warning">Pending</span>
                                                @break;
                                                @case('Cancel')
                                                    <span class="badge badge-warning">Cancel</span>
                                                @break;
                                                @case('Success')
                                                    <span class="badge badge-success">Success</span>
                                                @break;
                                            @endswitch
                                        </td>
                                        <td>
                                            <form action="{{ route('delete_sparepart_restok', $item->id_restok) }}"
                                                onsubmit="return confirm('Apakah Anda yakin ?')" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                @if ($item->status_restok != 'Success')
                                                    <a href="{{ route('edit_sparepart_restok', $item->id_restok) }}"
                                                        class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                                    <button type="submit" class="btn btn-sm btn-danger"><i
                                                            class="fas fa-trash"></i></button>
                                                @endif
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane " id="retur">
                <div class="row">
                    <div class="col-md-12">
                        <a href="{{ route('create_sparepart_retur') }}" class="btn btn-success"><i
                                class="fas fa-plus"></i> Tambah Retur</a>
                        <hr>

                        <table class="table" id="TABLES_2">
                            <thead>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Jumlah</th>
                                <th>Supplier</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </thead>
                            <tbody>
                                @foreach ($data_sparepart_retur as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->tgl_retur_barang }}</td>
                                        <td>{{ $item->nama_sparepart }}</td>
                                        <td>{{ $item->jumlah_retur }}</td>
                                        <td>{{ $item->nama_supplier }}</td>
                                        <td>{{ $item->catatan_retur }}</td>
                                        <td>
                                            @switch($item->status_retur)
                                                @case ('0')
                                                    <span class="badge badge-warning">Pending</span>
                                                @break;
                                                @case ('1')
                                                    <span class="badge badge-success">Success</span>
                                                @break;
                                            @endswitch
                                        </td>
                                        <td>
                                            @if ($item->status_retur == '0')
                                                <form action="{{ route('ubah_status_retur', $item->id_retur) }}"
                                                    onsubmit="return confirm('Apa Kamu Yakin ?')" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status_retur" id="status_retur"
                                                        value="1">
                                                    <button class="btn btn-sm btn-success"
                                                        type="submit">Selesai</button>
                                                </form>
                                            @endif

                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane " id="rusak">
                <a href="{{ route('create_sparepart_rusak') }}" class="btn btn-success"><i class="fas fa-plus"></i>
                    Tambah</a>
                <hr>
                <table class="table" id="TABLES_1">
                    <thead>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Jumlah</th>
                        <th>Catatan</th>
                        <th>Aksi</th>
                    </thead>
                    <tbody>
                        @foreach ($data_sparepart_rusak as $item)
                            <tr>
                                <td>{{ $loop->index + 1 }}</td>
                                <td>{{ $item->tgl_rusak_barang }}</td>
                                <td>{{ $item->kode_sparepart }}</td>
                                <td>{{ $item->nama_sparepart }}</td>
                                <td>{{ $item->jumlah_rusak }}</td>
                                <td>{{ $item->catatan_rusak }}</td>
                                <td>
                                    <form action="{{ route('delete_sparepart_rusak', $item->id_rusak) }}"
                                        onsubmit="return confirm('Apakah Anda yakin ?')" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <a href="{{ route('edit_sparepart_rusak', $item->id_rusak) }}"
                                            class="btn btn-warning"><i class="fas fa-edit"></i></a>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    // Event listener untuk mengupdate tabel berdasarkan filter
    document.getElementById('filter-kategori').addEventListener('change', function() {
        this.form.submit();
    });

    document.getElementById('filter-spl').addEventListener('change', function() {
        this.form.submit();
    });
</script>
