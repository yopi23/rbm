@extends('admin.layout.app')
@section('content-app')
@section('Laci', 'active')

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ $page }}</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
                        <li class="breadcrumb-item active">{{ $page }}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
            <!-- Form untuk filter tanggal -->
            <form action="{{ route('laci.form') }}" method="GET">
                <div class="row">
                    @if ($this_user->jabatan == '1')
                        <div class="col-md-4 col-sm-12 my-2">
                            <input type="date"
                                value="{{ isset($request->tgl_awal) ? $request->tgl_awal : '' }}"name="tgl_awal"
                                id="tgl_awal" class="form-control" hidden>
                            <input type="date"
                                value="{{ isset($request->tgl_akhir) ? $request->tgl_akhir : '' }}"name="tgl_akhir"
                                id="tgl_akhir" class="form-control" hidden>

                            <div
                                id="reportrange"style="background: #fff; cursor: pointer; padding: 5px 10px; border: 1px solid #ccc; width: 100%">
                                <i class="fa fa-calendar"></i>&nbsp;
                                <span></span> <i class="fa fa-caret-down"></i>
                            </div>
                        </div>
                        <div class="col-sm-4 my-2">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    @endif
                    <div class="col text-right">
                        <strong
                            style="font-family: 'Courier New', Courier, monospace;"class="pt-3 pb-3">@php echo date('l,d-M-Y') @endphp</strong>
                    </div>
                </div>
            </form>

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            <!-- Small boxes (Stat box) -->
            {{-- @if ($this_user->jabatan == '1' || $this_user->jabatan == '2') --}}
            <div class="row">
                @foreach ($listLaci as $laci)
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="info-box mb-3">

                            <div class="info-box-content">
                                <h5><span class="info-box-text"><strong>{{ $laci['name_laci'] }}</strong></span></h5>
                                <span class="info-box-number" style="color: rgb(0, 138, 57)">Uang Masuk: Rp.
                                    {{ number_format($laci['total_uang_masuk']) }}</span>
                                <div class="progress">
                                    <div class="progress-bar bg-info" style="width: 70%"></div>
                                </div>

                                <span class="progress-description" style="color: red">Uang Keluar: Rp.
                                    {{ number_format($laci['total_uang_keluar']) }} </span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                        <!-- /.info-box -->
                    </div>
                @endforeach

            </div>
            {{-- @endif --}}
            <div class="card card-outline card-success">
                @if ($this_user->jabatan == '1')
                    <div class="card-header">
                        <div class="row">
                            <div class="col">
                                <button class="btn btn-primary" data-toggle="modal" data-target="#mdkategori">Tambah
                                    Laci</button>
                            </div>
                            <div class="col-8">
                                <form action="{{ route('delete_kategori_laci') }}" method="POST"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <!-- Ini untuk menyatakan bahwa metode HTTP yang digunakan adalah DELETE -->
                                    <div class="form-group">
                                        <select name="id_kategorilaci" class="form-control" required>
                                            <option value="">Pilih Kategori Laci</option>
                                            @foreach ($allLaci as $kategori)
                                                <option value="{{ $kategori->id }}">
                                                    {{ $kategori->name_laci }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-danger">Hapus Laci</button>
                            </div>

                            </form>

                        </div>
                    </div>
                @endif
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover ">
                            <div class="thead">
                                <tr>
                                    <th>#</th>
                                    <th>Laci</th>
                                    <th>Masuk</th>
                                    <th>Keluar</th>
                                    <th>Keterangan</th>
                                    <th>Tanggal</th>
                                </tr>
                            </div>
                            <div class="tbody">
                                @php
                                    $totalmasuk = 0;
                                    $totalkeluar = 0;
                                @endphp
                                @foreach ($riwayat as $index => $rincian)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $rincian->name_laci }}</td>
                                        <td>
                                            @if ($rincian->masuk > 0)
                                                <span style="color: rgb(0, 138, 57)">+
                                                    {{ number_format($rincian->masuk) }}</span>
                                            @else
                                                <span style="color: rgb(0, 138, 57)">+0</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($rincian->keluar > 0)
                                                <span style="color: red">-{{ number_format($rincian->keluar) }}</span>
                                            @else
                                                <span style="color: red">-0</span>
                                            @endif
                                        </td>

                                        <td>{{ $rincian->keterangan }}</td>
                                        <td>{{ $rincian->updated_at }}</td>
                                    </tr>
                                    @php
                                        $totalmasuk += $rincian->masuk;
                                        $totalkeluar += $rincian->keluar;
                                    @endphp
                                @endforeach
                            </div>
                            <tr class="table-success  font-weight-bold">
                                <td colspan="2"><strong>Total</strong></td>
                                <td>Rp.{{ number_format($totalmasuk) }},-</td>
                                <td>
                                    Rp.{{ number_format($totalkeluar) }},-
                                </td>
                                <td>Rp.{{ number_format($totalmasuk - $totalkeluar) }},-</td>
                                <td colspan="3"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            {{-- komisi  --}}
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h4>Komisi</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover ">
                            <div class="thead">
                                <tr>
                                    <th>#</th>
                                    <th>Teknisi</th>
                                    <th>Komisi</th>
                                    <th>Saldo</th>
                                    <th>Sparepart</th>
                                    <th>Unit</th>
                                    <th>Pelanggan</th>
                                    <th>Keterangan</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </div>
                            <div class="tbody">
                                @php
                                    $totalkomisi = 0;
                                    $totalsp = 0;
                                @endphp
                                @foreach ($komisi as $index => $rincian)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $rincian->fullname }}</td>
                                        <td>
                                            <span style="color: rgb(133, 143, 0)">+
                                                {{ number_format($rincian->profit) }}</span>
                                        </td>
                                        <td>
                                            <span style="color: rgb(0, 143, 72)">+
                                                {{ number_format($rincian->saldo + $rincian->profit) }}</span>
                                        </td>

                                        <td>
                                            {{ number_format($rincian->harga_sp) }}
                                        </td>
                                        <td>
                                            {{ $rincian->type_unit }}
                                        </td>

                                        <td>
                                            {{ $rincian->nama_pelanggan }}
                                        </td>

                                        <td>{{ $rincian->keterangan }}</td>
                                        <td>
                                            @switch($rincian->status_services)
                                                @case('Diambil')
                                                    <span class="badge badge-success">Dibayar</span>
                                                @break

                                                @case('Selesai')
                                                    <span class="badge badge-info">Disimpan</span>
                                                @break

                                                @default
                                            @endswitch
                                        </td>
                                        <td>{{ $rincian->updated_at }}</td>
                                    </tr>
                                    @php
                                        $totalkomisi += $rincian->profit;
                                        $totalsp += $rincian->harga_sp;
                                    @endphp
                                @endforeach
                                <tr class="table-primary font-weight-bold">
                                    <td colspan="2"><strong>Total</strong></td>
                                    <td colspan="2">Rp.{{ number_format($totalkomisi) }},-</td>
                                    <!-- Column 3 total -->
                                    <td>Rp.{{ number_format($totalsp) }},-</td> <!-- Column 4 total -->
                                    <td colspan="5"></td> <!-- Empty cells for other columns -->
                                </tr>
                            </div>
                        </table>
                    </div>
                </div>

            </div>
            {{-- end komisi  --}}
            {{--  penarikan --}}
            <div class="card card-outline card-danger">
                <div class="card-header">
                    <h4>Penarikan</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover ">
                            <div class="thead">
                                <tr>
                                    <th>#</th>
                                    <th>Teknisi</th>
                                    <th>Jumlah</th>
                                    <th>Saldo</th>
                                    <th>Keterangan</th>
                                    <th>Tanggal</th>
                                </tr>
                            </div>
                            <div class="tbody">
                                @php
                                    $totalbon = 0;

                                @endphp
                                @foreach ($penarikan as $index => $rincian)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $rincian->name }}</td>
                                        <td>
                                            <span style="color: rgb(133, 143, 0)">+
                                                {{ number_format($rincian->jumlah_penarikan) }}</span>
                                        </td>
                                        <td>
                                            {{ number_format($rincian->dari_saldo - $rincian->jumlah_penarikan) }}
                                        </td>

                                        <td>{{ $rincian->catatan_penarikan }}</td>
                                        <td>{{ $rincian->updated_at }}</td>
                                    </tr>
                                    @php
                                        $totalbon += $rincian->jumlah_penarikan;

                                    @endphp
                                @endforeach
                                <tr class="table-danger font-weight-bold">
                                    <td colspan="2"><strong>Total</strong></td>
                                    <td>Rp.{{ number_format($totalbon) }},-</td>
                                    <td colspan="5"></td> <!-- Empty cells for other columns -->
                                </tr>
                            </div>
                        </table>
                    </div>
                </div>

            </div>
            {{-- end penarikan --}}
        </div>
        {{-- modal kategori --}}
        <div class="modal fade" id="mdkategori" tabindex="-1" role="dialog" aria-labelledby="recehModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="recehModalLabel">Kategori Baru</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('kategori_laci') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="form-group">

                                <input type="text" name="name_laci" id="name_laci" placeholder="Kategori"
                                    class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary">Kirim</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        {{-- modal kategori --}}
    </div>
</div>
<script type="text/javascript">
    $(function() {
        // const start = moment().subtract(29, 'days');
        // const end = moment();
        const start = moment($('#tgl_awal').val() || moment().startOf(
            'day')); // Mengambil nilai tanggal awal yang sudah di-submit
        const end = moment($('#tgl_akhir').val() ||
            moment()); // Mengambil nilai tanggal akhir yang sudah di-submit

        function cb(start, end) {
            $('#reportrange span').html(start.format('DD MMMM, YYYY') + ' - ' + end.format('DD MMMM, YYYY'));

            // Kirim nilai tanggal awal dan tanggal akhir ke rute 'laporan'
            const startDate = start.format('YYYY-MM-DD');
            const endDate = end.format('YYYY-MM-DD');

            $('#tgl_awal').val(startDate);
            $('#tgl_akhir').val(endDate);

        }

        $('#reportrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                    'month').endOf('month')]
            }
        }, cb);

        cb(start, end);
    });
</script>
@endsection
