@section('todolist', 'active')
@section('droptodo', 'active')
@section('maintodo', 'menu-open')

<div class="card card-success card-outline">
    <div class="card-header">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link active" href="#antri" data-toggle="tab">Antrian</a></li>
            <li class="nav-item"><a class="nav-link" href="#diproses" data-toggle="tab">Diproses</a></li>
            <li class="nav-item"><a class="nav-link" href="#selesai" data-toggle="tab">Selesai</a></li>
            <li class="nav-item"><a class="nav-link" href="#batal" data-toggle="tab">Batal</a></li>
        </ul>

    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="antri">
                <div class="form-group col-4">
                    <select name="teknisi" id="teknisi" class="form-control" required>
                        <option value="" disabled selected style="color: #a9a9a9;">---
                            Pilih Teknisi ---</option>
                        @if (isset($user))
                            @foreach ($user as $users)
                                <option class="my2" value="{{ $users->kode_user }}">
                                    {{ $users->fullname }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <div class="invalid-feedback">
                        Pilih Teknisi.
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table" id="TABLES_1">
                        <thead>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Nama</th>
                            <th>Telpon</th>
                            <th>Unit</th>
                            <th>Biaya</th>
                            <th>Keterangan</th>
                            <th>Aksi</th>
                        </thead>
                        <tbody>
                            @forelse ($antrian as $item)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $item->tgl_service }}</td>
                                    <td>{{ $item->nama_pelanggan }}</td>
                                    <td>{{ $item->no_telp }}</td>
                                    <td>{{ $item->type_unit }}</td>
                                    <td>Rp.{{ number_format($item->total_biaya) }},-</td>
                                    <td>{{ $item->keterangan }}</td>
                                    <td>
                                        <form action="{{ route('proses_service', $item->id) }}"
                                            onsubmit="return confirm('Apakah Kamu yakin ingin memproses Service ini ?')"
                                            method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status_services" id="status_services"
                                                value="Diproses">
                                            <input type="hidden" name="teknisi" id="teknisi-{{ $item->id }}"
                                                value="">
                                            <button type="submit" class="btn btn-sm btn-primary mt-2"
                                                onclick="setTeknisi('{{ $item->id }}')">Proses</button>
                                        </form>
                                        <br>
                                        <form action="{{ route('proses_service', $item->id) }}"
                                            onsubmit="return confirm('Apakah Kamu yakin ingin Membatalkan Service ini ?')"
                                            method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status_services" id="status_services"
                                                value="Cancel">
                                            <button type="submit" class="btn btn-sm btn-danger">Batal</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane" id="diproses">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table" id="TABLES_2">
                            <thead>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Type Unit</th>
                                <th>Keterangan</th>
                                <th>Biaya</th>
                                <th>Sparepart</th>
                                <th>Teknisi</th>
                                <th>Aksi</th>
                            </thead>
                            <tbody>
                                @forelse ($proses as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->nama_pelanggan }}<br>{{ $item->no_telp }}</td>
                                        <td>{{ $item->type_unit }}</td>
                                        <td>{{ $item->keterangan }}</td>
                                        <td>Rp.{{ number_format($item->total_biaya) }}</td>
                                        <td>Rp.{{ number_format($item->total_harga_part) }}
                                        </td>
                                        <td>

                                            {{ $item->name }}

                                        </td>
                                        <td>
                                            <form id="formSelesai{{ $loop->index }}"
                                                action="{{ route('proses_service', $item->id_service) }}"
                                                onsubmit="return confirm('Apakah Kamu yakin ingin Menyelesaikan Service ini ?')"
                                                method="POST">
                                                @csrf
                                                @method('PUT')
                                                <a href="{{ route('detail_service', $item->id_service) }}"
                                                    class="btn btn-info btn-sm mt-2">Detail</a>
                                                @if ($this_user->jabatan == '1' || $this_user->jabatan == '2')
                                                    <input type="hidden" name="status_services" id="status_services"
                                                        value="Selesai">
                                                    <button type="button"
                                                        onclick="return confirmSelesai({{ $loop->index }},'{{ $item->nama_pelanggan }}')"
                                                        class="btn btn-sm btn-success mt-2">Selesai</button>
                                                    {{-- <button type="submit"
                                                        class="btn btn-sm btn-success mt-2">Selesai</button> --}}
                                                @endif
                                            </form>

                                            <form action="{{ route('oper_service', $item->id_service) }}"
                                                onsubmit="return confirm('Apakah Kamu yakin ingin Mengoper Service ini ?')"
                                                method="POST">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status_services" id="status_services"
                                                    value="Antri">
                                                <button type="submit" class="btn btn-warning btn-sm mt-2">Oper</button>
                                            </form>
                                            <form action="{{ route('proses_service', $item->id_service) }}"
                                                onsubmit="return confirm('Apakah Kamu yakin ingin Membatalkan Service ini ?')"
                                                method="POST">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status_services" id="status_services"
                                                    value="Cancel">
                                                <button type="submit" class="btn btn-sm btn-danger mt-2">Batal</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="selesai">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table" id="TABLES_3">
                            <thead>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nama Pelanggan</th>
                                <th>Telpon</th>
                                <th>Type Unit</th>
                                <th>Keterangan</th>
                                <th>Total Biaya</th>
                                <th>Teknisi</th>
                                <th>Print</th>
                            </thead>
                            <tbody>

                                @forelse ($selesai as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->tgl_service }}</td>
                                        <td>{{ $item->nama_pelanggan }}</td>
                                        <td>{{ $item->no_telp }}</td>
                                        <td>{{ $item->type_unit }}</td>
                                        <td>{{ $item->keterangan }}</td>
                                        <td>Rp.{{ number_format($item->total_biaya) }}</td>
                                        <td>{{ $item->name }}</td>
                                        <td>
                                            <a href="{{ route('nota_tempel_selesai', $item->id_service) }}"
                                                target="_blank" class="btn btn-sm btn-primary mt-2"><i
                                                    class="fas fa-print"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="tab-pane" id="batal">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table" id="TABLES_4">
                            <thead>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nama Pelanggan</th>
                                <th>Telpon</th>
                                <th>Type Unit</th>
                                <th>Keterangan</th>
                                <th>Teknisi</th>
                            </thead>
                            <tbody>
                                @forelse ($batal as $item)
                                    <tr>
                                        <td>{{ $loop->index + 1 }}</td>
                                        <td>{{ $item->tgl_service }}</td>
                                        <td>{{ $item->nama_pelanggan }}</td>
                                        <td>{{ $item->no_telp }}</td>
                                        <td>{{ $item->type_unit }}</td>
                                        <td>{{ $item->keterangan }}</td>
                                        <td>{{ $item->name }}</td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">

    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>


<script>
    function confirmSelesai(index, pelanggan) {
        const username = "{{ strtoupper($item->name) }}";
        Swal.fire({
            title: 'Apakah kamu yakin?',
            html: "Anda ingin menyelesaikan pekerjaan untuk perangkat <strong style='font-size: 16pt'>" +
                pelanggan + "</strong> dengan Akun <strong style='font-size: 18pt'>" +
                username + "</strong>?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, selesaikan!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Lanjutkan proses pengiriman form jika pengguna mengonfirmasi
                document.getElementById('formSelesai' + index).submit();
            }
        });
        // Mencegah pengiriman form secara langsung
        return false;
    }
</script>
{{-- teknisi --}}
<script>
    function setTeknisi(itemId) {
        const teknisiSelect = document.getElementById('teknisi');
        const hiddenInput = document.getElementById('teknisi-' + itemId);
        hiddenInput.value = teknisiSelect.value;
    }
</script>
{{-- end teknisi --}}
