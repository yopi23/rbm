@extends('admin.layout.template')

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Manajemen Shift</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Daftar Shift</h3>
                            <div class="card-tools">
                                @if(!isset($activeShift) || !$activeShift)
                                    <form action="{{ route('shift.store') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Buka Shift Baru
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('shift.show', $activeShift->id) }}" class="btn btn-success">
                                        <i class="fas fa-eye"></i> Lihat Shift Aktif
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Kasir</th>
                                        <th>Waktu Buka</th>
                                        <th>Waktu Tutup</th>
                                        <th>Saldo Awal</th>
                                        <th>Saldo Akhir</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($shifts as $shift)
                                        <tr>
                                            <td>{{ $shift->id }}</td>
                                            <td>{{ $shift->user->name ?? 'Unknown' }}</td>
                                            <td>{{ $shift->start_time }}</td>
                                            <td>{{ $shift->end_time ?? '-' }}</td>
                                            <td>Rp {{ number_format($shift->saldo_awal, 0, ',', '.') }}</td>
                                            <td>{{ $shift->saldo_akhir ? 'Rp ' . number_format($shift->saldo_akhir, 0, ',', '.') : '-' }}</td>
                                            <td>
                                                @if($shift->status == 'open')
                                                    <span class="badge badge-success">Open</span>
                                                @else
                                                    <span class="badge badge-secondary">Closed</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('shift.show', $shift->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="mt-3">
                                {{ $shifts->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
