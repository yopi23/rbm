<!-- resources/views/admin/page/order/show.blade.php -->

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clipboard-list mr-1"></i>
                    Detail Pesanan #{{ $order->kode_order }}
                </h3>
                <div class="card-tools">
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
                                <span class="info-box-text">Informasi Pesanan</span>
                                <table class="mt-2">
                                    <tr>
                                        <td><strong>Kode Pesanan</strong></td>
                                        <td class="pl-3">: {{ $order->kode_order }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Tanggal</strong></td>
                                        <td class="pl-3">: {{ date('d/m/Y', strtotime($order->tanggal_order)) }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Item</strong></td>
                                        <td class="pl-3">: {{ $order->total_item }} item</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-truck"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Supplier</span>
                                <table class="mt-2">
                                    <tr>
                                        <td><strong>Nama Supplier</strong></td>
                                        <td class="pl-3">:
                                            {{ $order->supplier ? $order->supplier->nama_supplier : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Status Pesanan</strong></td>
                                        <td class="pl-3">:
                                            @if ($order->status_order == 'draft')
                                                <span class="badge badge-secondary">Draft</span>
                                            @elseif($order->status_order == 'menunggu_pengiriman')
                                                <span class="badge badge-warning">Menunggu Pengiriman</span>
                                            @elseif($order->status_order == 'selesai')
                                                <span class="badge badge-success">Selesai</span>
                                            @elseif($order->status_order == 'dibatalkan')
                                                <span class="badge badge-danger">Dibatalkan</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Catatan</strong></td>
                                        <td class="pl-3">: {{ $order->catatan ?? '-' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <h5 class="card-title">Daftar Item Pesanan</h5>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Nama Item</th>
                                        <th width="10%" class="text-center">Jumlah</th>
                                        <th width="15%" class="text-right">Harga Perkiraan</th>
                                        <th width="15%" class="text-right">Subtotal</th>
                                        <th>Catatan Item</th>
                                        <th width="15%" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($order->listOrders as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $item->nama_item }}</td>
                                            <td class="text-center">{{ $item->jumlah }}</td>
                                            <td class="text-right">
                                                @if ($item->harga_perkiraan)
                                                    Rp {{ number_format($item->harga_perkiraan, 0, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                @if ($item->harga_perkiraan)
                                                    Rp
                                                    {{ number_format($item->harga_perkiraan * $item->jumlah, 0, ',', '.') }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>{{ $item->catatan_item ?? '-' }}</td>
                                            <td class="text-center">
                                                @if ($item->status_item == 'pending')
                                                    <span class="badge badge-warning">Menunggu</span>
                                                @elseif($item->status_item == 'dikirim')
                                                    <span class="badge badge-info">Dikirim</span>
                                                @elseif($item->status_item == 'diterima')
                                                    <span class="badge badge-success">Diterima</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada item dalam pesanan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Total</th>
                                        <th class="text-center">{{ $order->listOrders->sum('jumlah') }}</th>
                                        <th></th>
                                        <th class="text-right">
                                            @php
                                                $totalHarga = 0;
                                                foreach ($order->listOrders as $item) {
                                                    if ($item->harga_perkiraan) {
                                                        $totalHarga += $item->harga_perkiraan * $item->jumlah;
                                                    }
                                                }
                                            @endphp
                                            @if ($totalHarga > 0)
                                                Rp {{ number_format($totalHarga, 0, ',', '.') }}
                                            @else
                                                -
                                            @endif
                                        </th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-md-6">
                        <a href="{{ route('order.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali
                        </a>

                        @if ($order->status_order == 'draft')
                            <a href="{{ route('order.edit', $order->id) }}" class="btn btn-primary ml-2">
                                <i class="fas fa-edit mr-1"></i> Edit Pesanan
                            </a>
                        @endif
                    </div>
                    <div class="col-md-6 text-right">
                        @if ($order->status_order == 'draft')
                            <a href="{{ route('order.finalize', $order->id) }}" class="btn btn-warning"
                                onclick="return confirm('Apakah Anda yakin ingin memfinalisasi pesanan ini?')">
                                <i class="fas fa-check-circle mr-1"></i> Finalisasi Pesanan
                            </a>
                        @endif

                        @if (in_array($order->status_order, ['menunggu_pengiriman', 'selesai']))
                            <a href="{{ route('order.convert-to-purchase', $order->id) }}" class="btn btn-success"
                                onclick="return confirm('Konversi pesanan ini menjadi pembelian?')">
                                <i class="fas fa-exchange-alt mr-1"></i> Konversi ke Pembelian
                            </a>
                        @endif

                        @if ($order->status_order != 'dibatalkan' && $order->status_order != 'selesai')
                            <a href="{{ route('order.cancel', $order->id) }}" class="btn btn-danger ml-2"
                                onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')">
                                <i class="fas fa-times mr-1"></i> Batalkan Pesanan
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        // DataTable initialization if needed
        $('.table').DataTable({
            "paging": false,
            "lengthChange": false,
            "searching": false,
            "ordering": false,
            "info": false,
            "autoWidth": false,
            "responsive": true
        });
    });
</script>
