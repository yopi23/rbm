{{-- @extends('admin.layout.app') --}}
@section('content-app')
    {{-- @section('dashboard', 'active') --}}
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        {{-- <h1 class="m-0">{{ $page }}</h1> --}}
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            {{-- <li class="breadcrumb-item active">{{ $page }}</li> --}}
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->
        @if ($this_user->jabatan == '0')
            <!-- Main content -->

            <!-- /.content -->
    </div>
@endsection
@else
<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Small boxes (Stat box) -->

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif
        @if (session('success'))
            <div class="alert alert-primary">
                {{ session('success') }}
            </div>
        @endif
        <br>
        <h4>Data Services</h4>
        <hr>
        <div class="card card-success card-outline">
            <div class="card-body">
                <table class="table" id="dataTable">
                    <thead>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Pelanggan</th>
                        <th>Type unit</th>
                        <th>No Hp</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </thead>
                    <tbody>
                        {{-- @forelse ($service as $item)
                            @if ($item->status_services == 'Antri')
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $item->kode_service }}</td>
                                    <td>{{ $item->nama_pelanggan }}</td>
                                    <td>{{ $item->type_unit }}</td>
                                    <td>{{ $item->no_telp }}</td>
                                    <td>{{ $item->keterangan }}</td>
                                    <td>
                                        <a href="{{ route('nota_service', $item->id) }}" target="_blank"
                                            class="btn btn-sm btn-success mt-2"><i class="fas fa-print"></i></a>
                                        <a href="{{ route('nota_tempel', $item->id) }}" target="_blank"
                                            class="btn btn-sm btn-warning mt-2"><i class="fas fa-print"></i></a>
                                        <form action="{{ route('proses_service', $item->id) }}"
                                            onsubmit="return confirm('Apakah Kamu yakin ingin memproses Service ini ?')"
                                            method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status_services" id="status_services"
                                                value="Diproses">
                                            <button type="submit" class="btn btn-sm btn-primary mt-2">Proses</button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                        @endforelse --}}
                    </tbody>
                </table>
            </div>
        </div>
        <!-- /.row -->
        {{-- Modal --}}
        <div class="modal fade" id="modal_list_order">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Tambah List Order</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('create_list_order') }}" method="POST">
                        @csrf
                        @method('POST')
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Tanggal</label>
                                <input type="date" value="{{ date('Y-m-d') }}" name="tgl_order" id="tgl_order"
                                    class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" name="nama_order" id="nama_order" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Catatan</label>
                                <textarea name="catatan_order" id="catatan_order" class="form-control" cols="30" rows="10"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-success">Simpan</button>
                        </div>
                    </form>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>

    </div><!-- /.container-fluid -->
</section>
<!-- /.content -->
</div>
@endsection
@section('content-script')
{{-- <script>
    var i = 0;
    var perkiraan_harga = 0;
    $('#add-dynamic-input').on('click', function() {
        ++i;
        $('.dynamic-input').append('<tr><td><select name="kode_sparepart[' + i + ']" id="kode_sparepart[' + i +
            ']"  class="form-control select-bootstrap kode_sparepart"><option value="">-- Pilih Sparepart --</option>'
            @forelse ($sparepart as $item)
                +'<option value="' + {{ $item->id }} + '" data-harga="' +
                    {{ $item->harga_jual + $item->harga_pasang }} + '" data-stok="' +
                    {{ $item->stok_sparepart }} + '">{{ $item->nama_sparepart }}</option>'
            @empty @endforelse +
            '</select></td><td><input type="text" name="harga_kode_sparepart[' + i +
            ']" id="harga_kode_sparepart[' + i + ']" class="form-control harga_part" readonly></td><td>' +
            '<input type="text" name="stok_kode_sparepart[' + i + ']" id="stok_kode_sparepart[' + i +
            ']" class="form-control stok_part" readonly></td><td><input type="number" value="1" name="qty_kode_sparepart[' +
            i + ']" id="qty_kode_sparepart[' + i +
            ']" class="form-control qty_part"></td><td><button type="button" class="btn btn-danger remove_dynamic" name="remove_dynamic" id="remove_dynamic"><i class="fas fa-trash"></i></button></td></tr>'
        );
        $('.select-bootstrap').select2({
            theme: 'bootstrap4'
        });
    });
    $(document).on('click', '.remove_dynamic', function() {
        $(this).parents('tr').remove()
    });
    $(document).on('change', '.kode_sparepart', function() {
        var harga = $(this).find(':selected').data('harga');
        var stok = $(this).find(':selected').data('stok');
        var qty = $(this).parents('tr').find('.qty_part').val();
        $(this).parents('tr').find('.harga_part').val(harga);
        $(this).parents('tr').find('.stok_part').val(stok);
    })
    $(document).on('keyup change click', '.qty_part', function() {
        var qty = $(this).val();
        var harga = $(this).parents('tr').find('.kode_sparepart :selected').data('harga');
        var total_harga = harga * qty;
    })
</script> --}}
@endsection
@endif
