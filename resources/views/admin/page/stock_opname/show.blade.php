<!-- resources/views/admin/page/stock_opname/show.blade.php -->

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clipboard-check mr-1"></i>
                    Detail Stock Opname: {{ $period->nama_periode }}
                </h3>
                <div class="card-tools">
                    @if (in_array($period->status, ['draft', 'in_progress']))
                        <a href="{{ route('stock-opname.start-process', $period->id) }}"
                            class="btn btn-primary btn-sm mr-1">
                            <i class="fas fa-play mr-1"></i> Mulai Proses
                        </a>
                    @endif

                    @if ($period->status == 'in_progress' && $pendingCount == 0)
                        <a href="{{ route('stock-opname.complete-period', $period->id) }}"
                            class="btn btn-success btn-sm mr-1"
                            onclick="return confirm('Apakah Anda yakin ingin menyelesaikan stock opname ini?')">
                            <i class="fas fa-check-circle mr-1"></i> Selesaikan
                        </a>
                    @endif

                    @if ($period->status == 'completed')
                        <a href="{{ route('stock-opname.report', $period->id) }}" class="btn btn-info btn-sm mr-1">
                            <i class="fas fa-file-alt mr-1"></i> Lihat Laporan
                        </a>
                        <a href="{{ route('stock-opname.export-excel', $period->id) }}"
                            class="btn btn-warning btn-sm mr-1">
                            <i class="fas fa-file-excel mr-1"></i> Export Excel
                        </a>
                    @endif

                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-info-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Informasi Periode</span>
                                <table class="mt-2">
                                    <tr>
                                        <td><strong>Kode Periode</strong></td>
                                        <td class="pl-3">: {{ $period->kode_periode }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal</strong></td>
                                        <td class="pl-3">: {{ date('d/m/Y', strtotime($period->tanggal_mulai)) }} -
                                            {{ date('d/m/Y', strtotime($period->tanggal_selesai)) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status</strong></td>
                                        <td class="pl-3">:
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
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-chart-pie"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Progres</span>
                                <div class="progress-group mt-2">
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-primary progress-bar-striped" role="progressbar"
                                            style="width: {{ $progressPercentage }}%"
                                            aria-valuenow="{{ $progressPercentage }}" aria-valuemin="0"
                                            aria-valuemax="100">
                                            {{ $progressPercentage }}%
                                        </div>
                                    </div>
                                    <small>{{ $checkedCount + $adjustedCount }}/{{ $totalItems }} item</small>
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-info">{{ $pendingCount }} item belum diperiksa</span>
                                    <span class="badge badge-primary">{{ $checkedCount }} item sudah diperiksa</span>
                                    <span class="badge badge-success">{{ $adjustedCount }} item sudah
                                        disesuaikan</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card card-outline card-warning">
                            <div class="card-header">
                                <h3 class="card-title">Catatan</h3>
                                @if (in_array($period->status, ['draft', 'in_progress']))
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-toggle="modal"
                                            data-target="#editNotesModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body">
                                {{ $period->catatan ?? 'Tidak ada catatan.' }}
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Statistik Selisih</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped">
                                    <tr>
                                        <td>Item dengan selisih</td>
                                        <td class="text-right">{{ $itemsWithSelisih }} item</td>
                                    </tr>
                                    <tr>
                                        <td>Total selisih positif (kelebihan)</td>
                                        <td class="text-right text-success">+{{ $positiveSelisih }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total selisih negatif (kekurangan)</td>
                                        <td class="text-right text-danger">{{ $negativeSelisih }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs mt-3" id="stockOpnameTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="pending-tab" data-toggle="tab" href="#pending" role="tab"
                            aria-controls="pending" aria-selected="true">
                            <i class="fas fa-clock mr-1"></i> Belum Diperiksa <span
                                class="badge badge-warning">{{ $pendingCount }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="checked-tab" data-toggle="tab" href="#checked" role="tab"
                            aria-controls="checked" aria-selected="false">
                            <i class="fas fa-check mr-1"></i> Sudah Diperiksa <span
                                class="badge badge-primary">{{ $checkedCount + $adjustedCount }}</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content" id="stockOpnameTabsContent">
                    <!-- Tab Belum Diperiksa -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel"
                        aria-labelledby="pending-tab">
                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered" id="pendingItemsTable">
                                <thead>
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">Kode</th>
                                        <th>Nama Sparepart</th>
                                        <th width="10%" class="text-center">Stok Tercatat</th>
                                        <th width="15%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pendingItems as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $item->sparepart->kode_sparepart }}</td>
                                            <td>{{ $item->sparepart->nama_sparepart }}</td>
                                            <td class="text-center">{{ $item->stock_tercatat }}</td>
                                            <td class="text-center">
                                                <a href="{{ route('stock-opname.check-items', $period->id) }}?item={{ $item->id }}"
                                                    class="btn btn-primary btn-sm">
                                                    <i class="fas fa-check-circle mr-1"></i> Periksa
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Tidak ada item yang belum
                                                diperiksa.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab Sudah Diperiksa -->
                    <div class="tab-pane fade" id="checked" role="tabpanel" aria-labelledby="checked-tab">
                        <div class="table-responsive mt-3">
                            <table class="table table-striped table-bordered" id="checkedItemsTable">
                                <thead>
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="15%">Kode</th>
                                        <th>Nama Sparepart</th>
                                        <th width="10%" class="text-center">Stok Tercatat</th>
                                        <th width="10%" class="text-center">Stok Aktual</th>
                                        <th width="10%" class="text-center">Selisih</th>
                                        <th width="10%" class="text-center">Status</th>
                                        <th width="15%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($checkedItems as $index => $item)
                                        <tr
                                            class="{{ $item->selisih > 0 ? 'table-success' : ($item->selisih < 0 ? 'table-danger' : '') }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $item->sparepart->kode_sparepart }}</td>
                                            <td>{{ $item->sparepart->nama_sparepart }}</td>
                                            <td class="text-center">{{ $item->stock_tercatat }}</td>
                                            <td class="text-center">{{ $item->stock_aktual }}</td>
                                            <td class="text-center">
                                                @if ($item->selisih > 0)
                                                    <span class="text-success">+{{ $item->selisih }}</span>
                                                @elseif($item->selisih < 0)
                                                    <span class="text-danger">{{ $item->selisih }}</span>
                                                @else
                                                    <span>0</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($item->status == 'checked')
                                                    <span class="badge badge-primary">Sudah Diperiksa</span>
                                                @elseif($item->status == 'adjusted')
                                                    <span class="badge badge-success">Sudah Disesuaikan</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($item->selisih != 0 && $item->status == 'checked' && in_array($period->status, ['in_progress', 'completed']))
                                                    <a href="{{ route('stock-opname.adjustment-form', [$period->id, $item->id]) }}"
                                                        class="btn btn-warning btn-sm">
                                                        <i class="fas fa-exchange-alt mr-1"></i> Sesuaikan
                                                    </a>
                                                @endif

                                                @if ($item->status == 'adjusted')
                                                    <a href="{{ route('stock-opname.adjustment-form', [$period->id, $item->id]) }}"
                                                        class="btn btn-info btn-sm">
                                                        <i class="fas fa-history mr-1"></i> Riwayat
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">Tidak ada item yang sudah
                                                diperiksa.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('stock-opname.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Catatan -->
<div class="modal fade" id="editNotesModal" tabindex="-1" aria-labelledby="editNotesModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('stock-opname.edit-notes', $period->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editNotesModalLabel">Edit Catatan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="catatan">Catatan</label>
                        <textarea class="form-control" id="catatan" name="catatan" rows="4">{{ $period->catatan }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(function() {
        $('#pendingItemsTable, #checkedItemsTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 10,
            "language": {
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Tidak ada data yang ditemukan",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "infoEmpty": "Tidak ada data yang tersedia",
                "infoFiltered": "(difilter dari _MAX_ total data)",
                "search": "Cari:",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": ">>",
                    "previous": "<<"
                }
            }
        });
    });
</script>
