@extends('admin.layout.app')

@section('content-app')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ $page ?? 'Buka Shift Baru' }}</h1>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Buka Shift Baru</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('shift.store') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="modal_awal">Modal Awal Kasir (Uang di Laci)</label>
                                    <input type="number" name="modal_awal" id="modal_awal" class="form-control" min="0" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Buka Shift</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
