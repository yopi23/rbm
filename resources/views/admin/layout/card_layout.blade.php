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
                <h5 class="modal-title" id="staticBackdropLabel">Modal title</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" method="post">
                    @csrf
                    {{-- <input type="hidden" name="service_id" value="{{ $id }}"> --}}
                    <label for="debit_account">Akun yang Dikurangi:</label>
                    <select name="debit_account" id="debit_account" class="form-control">
                        <!-- Daftar akun yang tersedia untuk dipilih -->
                    </select>
                    <label for="credit_account">Akun yang Menerima:</label>
                    <select name="credit_account" id="credit_account" class="form-control">
                        <!-- Daftar akun yang tersedia untuk dipilih -->
                    </select>
                    <label for="percentage_debit_account">Persentase untuk Akun yang Dikurangi:</label>
                    <input type="number" name="percentage_debit_account" class="form-control">
                    <label for="percentage_credit_account">Persentase untuk Akun yang Menerima:</label>
                    <input type="number" name="percentage_credit_account" class="form-control">
                    <button type="submit" class="btn btn-primary">Pindahkan Komisi</button>
                </form>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Understood</button>
            </div>
        </div>
    </div>
</div>

@include('admin.component.footer')
