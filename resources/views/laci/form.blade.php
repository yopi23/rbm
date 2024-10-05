@extends('admin.layout.app')
@section('content-app')
@section('Laci', 'active')

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <h1>{{ $page }}</h1> <!-- Gunakan variabel di sini -->
            <!-- Form untuk filter tanggal -->
            <form action="{{ route('laci.form') }}" method="GET">
                <div class="row">
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
                    <div class="col text-right">
                        <strong
                            style="font-family: 'Courier New', Courier, monospace;"class="pt-3 pb-3">@php echo date('l,d-M-Y') @endphp</strong>
                    </div>
                </div>
            </form>
            <!-- Small boxes (Stat box) -->
            {{-- @if ($this_user->jabatan == '1' || $this_user->jabatan == '2') --}}
            <div class="row">
                @foreach ($listLaci as $laci)
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="info-box mb-3">
                            <div class="info-box-content">
                                <span class="info-box-text">{{ $laci['name_laci'] }}</span>
                                <span class="info-box-number">Uang Masuk: Rp.
                                    {{ number_format($laci['total_uang_masuk']) }}</span>
                                <span class="info-box-number">Uang Keluar: Rp.
                                    {{ number_format($laci['total_uang_keluar']) }}</span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                        <!-- /.info-box -->
                    </div>
                @endforeach

            </div>
            {{-- @endif --}}

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

            <button class="btn btn-primary" data-toggle="modal" data-target="#mdkategori">Tambah Laci</button>
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
