@section('page', $page)
@include('admin.component.header')
@include('admin.component.navbar')
@include('admin.component.sidebar')
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">@yield('page')</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">@yield('page')</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
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
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <div class="card-title">@yield('page')</div>
                            <div class="card-tools">
                                {!! isset($link_tambah)
                                    ? '<a href="' . $link_tambah . '" class="btn btn-success"><i class="fas fa-plus"></i> Tambah</a>'
                                    : '' !!}
                            </div>
                        </div>
                        <div class="card-body">
                            {!! $data !!}
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
</div>
<!-- Modal -->
<div class="modal fade" id="editteknisi" data-backdrop="static" data-keyboard="false" tabindex="-1"
    aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span id="devices"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('pindahKomisi') }}" method="post">
                @csrf
                <div class="modal-body">
                    {{-- <input type="hidden" name="service_id" value="{{ $id }}"> --}}
                    <label for="debit_account">Dari akun:</label>
                    <input class="form-control" id="nama_teknisi_lama" readonly>
                    <input class="form-control" name="teknisi_lama" id="teknisi_lama" hidden>
                    <input class="form-control" name="service_id" id="service_id" hidden>

                    <label for="credit_account">Ke akun:</label>
                    <select name="new_teknisi" id="new_teknisi" class="form-control">

                        @if (isset($user))
                            @foreach ($user as $users)
                                <option value="{{ $users->kode_user }}">{{ $users->fullname }}</option>
                            @endforeach
                        @endif
                    </select>
                    </select>



                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Pindahkan Komisi</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#editteknisi').on('show.bs.modal', function(event) {
            const button = $(event.relatedTarget) // Button that triggered the modal
            const id_teknisi = button.data('id_teknisi') // Extract info from data-* attributes
            const id_service = button.data('id_service')
            const teknisi = button.data('teknisi')
            const device = button.data('device')
            const modal = $(this)
            modal.find('.modal-body input#nama_teknisi_lama').val(teknisi)
            modal.find('.modal-body input#teknisi_lama').val(id_teknisi)
            modal.find('.modal-body input#service_id').val(id_service)
            modal.find('.modal-title span#devices').text(device)
        })
    })
</script>
@include('admin.component.footer')
