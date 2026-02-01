@extends('admin.layout.app')

@section('content-app')
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ $page ?? 'Riwayat Shift' }}</h1>
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
                <h3 class="card-title">Riwayat Shift</h3>
                <div class="card-tools">
                    <a href="{{ route('shift.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Buka Shift</a>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Kasir</th>
                            <th>Mulai</th>
                            <th>Selesai</th>
                            <th>Status</th>
                            <th>Modal Awal</th>
                            <th>Selisih</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shifts as $shift)
                        <tr>
                            <td>{{ $shift->id }}</td>
                            <td>{{ $shift->user->name }}</td>
                            <td>{{ $shift->start_time->format('d/m/Y H:i') }}</td>
                            <td>{{ $shift->end_time ? $shift->end_time->format('d/m/Y H:i') : '-' }}</td>
                            <td><span class="badge badge-{{ $shift->status == 'open' ? 'success' : 'secondary' }}">{{ strtoupper($shift->status) }}</span></td>
                            <td>Rp {{ number_format($shift->modal_awal, 0, ',', '.') }}</td>
                            <td>
                                @if($shift->status == 'closed')
                                <span class="{{ $shift->selisih < 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($shift->selisih, 0, ',', '.') }}</span>
                                @else
                                -
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('shift.show', $shift->id) }}" class="btn btn-info btn-xs"><i class="fas fa-eye"></i> Detail</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">Belum ada data shift.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                {{ $shifts->links() }}
            </div>
            </div>
        </div>
    </section>
</div>
@endsection
