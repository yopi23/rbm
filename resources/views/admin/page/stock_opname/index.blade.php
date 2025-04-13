<!-- resources/views/admin/page/stock_opname/index.blade.php -->

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clipboard-check mr-1"></i>
                    Daftar Stock Opname
                </h3>
                <div class="card-tools">
                    <a href="{{ route('stock-opname.create') }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus mr-1"></i> Buat Stock Opname Baru
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="stockOpnameTable">
                        <thead>
                            <tr>
                                <th>Kode Periode</th>
                                <th>Nama Periode</th>
                                <th>Tanggal</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Progress</th>
                                <th>Dibuat Oleh</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($periods as $period)
                                <tr>
                                    <td>{{ $period->kode_periode }}</td>
                                    <td>{{ $period->nama_periode }}</td>
                                    <td>{{ date('d/m/Y', strtotime($period->tanggal_mulai)) }} -
                                        {{ date('d/m/Y', strtotime($period->tanggal_selesai)) }}</td>
                                    <td class="text-center">
                                        @if ($period->status == 'draft')
                                            <span class="badge badge-secondary">Draft</span>
                                        @elseif($period->status == 'in_progress')
                                            <span class="badge badge-warning">Sedang Berjalan</span>
                                        @elseif($period->status == 'completed')
                                            <span class="badge badge-success">Selesai</span>
                                        @elseif($period->status == 'cancelled')
                                            <span class="badge badge-danger">Dibatalkan</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $totalItems = $period->details()->count();
                                            $checkedItems = $period
                                                ->details()
                                                ->whereIn('status', ['checked', 'adjusted'])
                                                ->count();
                                            $progressPercentage =
                                                $totalItems > 0 ? round(($checkedItems / $totalItems) * 100) : 0;
                                        @endphp
                                        <div class="progress">
                                            <div class="progress-bar bg-primary" role="progressbar"
                                                style="width: {{ $progressPercentage }}%"
                                                aria-valuenow="{{ $progressPercentage }}" aria-valuemin="0"
                                                aria-valuemax="100">
                                                {{ $progressPercentage }}%
                                            </div>
                                        </div>
                                        <small>{{ $checkedItems }}/{{ $totalItems }} item</small>
                                    </td>
                                    <td>{{ $period->user ? $period->user->name : '-' }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('stock-opname.show', $period->id) }}"
                                                class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            @if (in_array($period->status, ['draft', 'in_progress']))
                                                <a href="{{ route('stock-opname.check-items', $period->id) }}"
                                                    class="btn btn-primary btn-sm">
                                                    <i class="fas fa-tasks"></i>
                                                </a>
                                            @endif

                                            @if ($period->status == 'completed')
                                                <a href="{{ route('stock-opname.report', $period->id) }}"
                                                    class="btn btn-success btn-sm">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>
                                                <a href="{{ route('stock-opname.export-excel', $period->id) }}"
                                                    class="btn btn-warning btn-sm">
                                                    <i class="fas fa-file-excel"></i>
                                                </a>
                                            @endif

                                            @if (in_array($period->status, ['draft']) && $period->details()->where('status', '!=', 'pending')->count() == 0)
                                                <button type="button" class="btn btn-danger btn-sm"
                                                    onclick="confirmDelete('{{ route('stock-opname.cancel-period', $period->id) }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">Belum ada data stock opname.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        $('#stockOpnameTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 10,
            "lengthMenu": [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Semua"]
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
            },
            "order": [
                [2, 'desc']
            ]
        });
    });

    function confirmDelete(url) {
        Swal.fire({
            title: 'Batalkan Stock Opname?',
            text: "Stock opname yang dibatalkan tidak dapat dikembalikan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Tidak'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>
