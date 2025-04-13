<!-- resources/views/admin/page/stock_opname/report.blade.php -->

<div class="row">
    <div class="col-md-12">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-alt mr-1"></i>
                    Laporan Stock Opname: {{ $period->nama_periode }}
                </h3>
                <div class="card-tools">
                    <a href="{{ route('stock-opname.export-excel', $period->id) }}" class="btn btn-warning btn-sm mr-1">
                        <i class="fas fa-file-excel mr-1"></i> Export Excel
                    </a>
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <!-- Informasi Periode -->
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-clipboard-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Periode Stock Opname</span>
                                <span class="info-box-number">{{ $period->kode_periode }}</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 100%"></div>
                                </div>
                                <span class="progress-description">
                                    {{ date('d/m/Y', strtotime($period->tanggal_mulai)) }} -
                                    {{ date('d/m/Y', strtotime($period->tanggal_selesai)) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <!-- Statistik -->
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i class="fas fa-chart-pie"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Statistik</span>
                                <span class="info-box-number">{{ $totalItems }} Item</span>
                                <div class="progress">
                                    <div class="progress-bar"
                                        style="width: {{ ($itemsWithSelisih / $totalItems) * 100 }}%"></div>
                                </div>
                                <span class="progress-description">
                                    {{ $itemsWithSelisih }} item ({{ round(($itemsWithSelisih / $totalItems) * 100) }}%)
                                    memiliki selisih
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Laporan -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h3 class="card-title">Ringkasan Laporan</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-box bg-warning">
                                            <span class="info-box-icon"><i
                                                    class="fas fa-exclamation-triangle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Item dengan Selisih</span>
                                                <span class="info-box-number">{{ $itemsWithSelisih }}</span>
                                                <div class="progress">
                                                    <div class="progress-bar"
                                                        style="width: {{ ($itemsWithSelisih / $totalItems) * 100 }}%">
                                                    </div>
                                                </div>
                                                <span class="progress-description">
                                                    {{ round(($itemsWithSelisih / $totalItems) * 100) }}% dari total
                                                    item
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box bg-success">
                                            <span class="info-box-icon"><i class="fas fa-plus-circle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Selisih Positif</span>
                                                <span class="info-box-number">+{{ $positiveSelisih }}</span>
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: 100%"></div>
                                                </div>
                                                <span class="progress-description">
                                                    Stok fisik lebih banyak dari sistem
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-box bg-danger">
                                            <span class="info-box-icon"><i class="fas fa-minus-circle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Selisih Negatif</span>
                                                <span class="info-box-number">{{ $negativeSelisih }}</span>
                                                <div class="progress">
                                                    <div class="progress-bar" style="width: 100%"></div>
                                                </div>
                                                <span class="progress-description">
                                                    Stok fisik lebih sedikit dari sistem
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-success">
                                                <h3 class="card-title">Top 5 Selisih Positif</h3>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-striped mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Kode</th>
                                                            <th>Nama</th>
                                                            <th>Selisih</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($largestPositive as $item)
                                                            <tr>
                                                                <td>{{ $item->sparepart->kode_sparepart }}</td>
                                                                <td>{{ $item->sparepart->nama_sparepart }}</td>
                                                                <td class="text-success">+{{ $item->selisih }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="3" class="text-center">Tidak ada selisih
                                                                    positif</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-danger">
                                                <h3 class="card-title">Top 5 Selisih Negatif</h3>
                                            </div>
                                            <div class="card-body p-0">
                                                <table class="table table-striped mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Kode</th>
                                                            <th>Nama</th>
                                                            <th>Selisih</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($largestNegative as $item)
                                                            <tr>
                                                                <td>{{ $item->sparepart->kode_sparepart }}</td>
                                                                <td>{{ $item->sparepart->nama_sparepart }}</td>
                                                                <td class="text-danger">{{ $item->selisih }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="3" class="text-center">Tidak ada selisih
                                                                    negatif</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Hasil Stock Opname -->
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header bg-secondary">
                                <h3 class="card-title">Hasil Stock Opname</h3>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="resultTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all"
                                            role="tab">
                                            Semua Item <span class="badge badge-primary">{{ $totalItems }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="with-selisih-tab" data-toggle="tab"
                                            href="#with-selisih" role="tab">
                                            Dengan Selisih <span
                                                class="badge badge-warning">{{ $itemsWithSelisih }}</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="adjusted-tab" data-toggle="tab" href="#adjusted"
                                            role="tab">
                                            Disesuaikan <span class="badge badge-success">{{ $totalAdjusted }}</span>
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content mt-3" id="resultTabsContent">
                                    <!-- Tab Semua Item -->
                                    <div class="tab-pane fade show active" id="all" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered" id="allItemsTable">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">No</th>
                                                        <th width="15%">Kode</th>
                                                        <th>Nama Sparepart</th>
                                                        <th width="10%" class="text-center">Stok Tercatat</th>
                                                        <th width="10%" class="text-center">Stok Aktual</th>
                                                        <th width="10%" class="text-center">Selisih</th>
                                                        <th width="10%" class="text-center">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($period->details as $index => $item)
                                                        <tr
                                                            class="{{ $item->selisih > 0 ? 'table-success' : ($item->selisih < 0 ? 'table-danger' : '') }}">
                                                            <td>{{ $index + 1 }}</td>
                                                            <td>{{ $item->sparepart->kode_sparepart }}</td>
                                                            <td>{{ $item->sparepart->nama_sparepart }}</td>
                                                            <td class="text-center">{{ $item->stock_tercatat }}</td>
                                                            <td class="text-center">{{ $item->stock_aktual }}</td>
                                                            <td class="text-center">
                                                                @if ($item->selisih > 0)
                                                                    <span
                                                                        class="text-success">+{{ $item->selisih }}</span>
                                                                @elseif($item->selisih < 0)
                                                                    <span
                                                                        class="text-danger">{{ $item->selisih }}</span>
                                                                @else
                                                                    <span>0</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if ($item->status == 'checked')
                                                                    <span class="badge badge-primary">Sudah
                                                                        Diperiksa</span>
                                                                @elseif($item->status == 'adjusted')
                                                                    <span class="badge badge-success">Sudah
                                                                        Disesuaikan</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Tab Dengan Selisih -->
                                    <div class="tab-pane fade" id="with-selisih" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered" id="selisihItemsTable">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">No</th>
                                                        <th width="15%">Kode</th>
                                                        <th>Nama Sparepart</th>
                                                        <th width="10%" class="text-center">Stok Tercatat</th>
                                                        <th width="10%" class="text-center">Stok Aktual</th>
                                                        <th width="10%" class="text-center">Selisih</th>
                                                        <th width="10%" class="text-center">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $counter = 1; @endphp
                                                    @foreach ($period->details->where('selisih', '!=', 0)->where('selisih', '!=', null) as $item)
<tr class="{{ $item->selisih > 0 ? 'table-success' : 'table-danger' }}">
                                                            <td>{{ $counter++ }}</td>
                                                            <td>{{ $item->sparepart->kode_sparepart }}</td>
                                                            <td>{{ $item->sparepart->nama_sparepart }}</td>
                                                            <td class="text-center">{{ $item->stock_tercatat }}</td>
                                                            <td class="text-center">{{ $item->stock_aktual }}</td>
                                                            <td class="text-center">
                                                                @if ($item->selisih > 0)
<span class="text-success">+{{ $item->selisih }}</span>
@else
<span class="text-danger">{{ $item->selisih }}</span>
@endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if ($item->status == 'checked')
<span class="badge badge-primary">Sudah Diperiksa</span>
@elseif($item->status == 'adjusted')
<span class="badge badge-success">Sudah Disesuaikan</span>
@endif
                                                            </td>
                                                        </tr>
@endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Tab Disesuaikan -->
                                    <div class="tab-pane fade" id="adjusted" role="tabpanel">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered" id="adjustedItemsTable">
                                                <thead>
                                                    <tr>
                                                        <th width="5%">No</th>
                                                        <th width="15%">Kode</th>
                                                        <th>Nama Sparepart</th>
                                                        <th width="10%" class="text-center">Stok Tercatat</th>
                                                        <th width="10%" class="text-center">Stok Aktual</th>
                                                        <th width="10%" class="text-center">Selisih</th>
                                                        <th width="15%" class="text-center">Tanggal Penyesuaian</th>
                                                        <th>Alasan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php $counter = 1; @endphp
                                                    @foreach ($period->details->where('status', 'adjusted') as $item)
                                                        <tr
                                                            class="{{ $item->selisih > 0 ? 'table-success' : 'table-danger' }}">
                                                            <td>{{ $counter++ }}</td>
                                                            <td>{{ $item->sparepart->kode_sparepart }}</td>
                                                            <td>{{ $item->sparepart->nama_sparepart }}</td>
                                                            <td class="text-center">{{ $item->stock_tercatat }}</td>
                                                            <td class="text-center">{{ $item->stock_aktual }}</td>
                                                            <td class="text-center">
                                                                @if ($item->selisih > 0)
                                                                    <span
                                                                        class="text-success">+{{ $item->selisih }}</span>
                                                                @else
                                                                    <span
                                                                        class="text-danger">{{ $item->selisih }}</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                @if ($item->adjustments->count() > 0)
                                                                    {{ date('d/m/Y H:i', strtotime($item->adjustments->last()->created_at)) }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($item->adjustments->count() > 0)
                                                                    {{ $item->adjustments->last()->alasan_adjustment }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('stock-opname.show', $period->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Detail
                </a>
                <a href="{{ route('stock-opname.export-excel', $period->id) }}" class="btn btn-warning float-right">
                    <i class="fas fa-file-excel mr-1"></i> Export Excel
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        // DataTables initialization
        $('#allItemsTable, #selisihItemsTable, #adjustedItemsTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 25,
            "lengthMenu": [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Semua"]
            ],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
            }
        });
    });
</script>
