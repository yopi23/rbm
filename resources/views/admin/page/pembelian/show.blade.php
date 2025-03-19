{{-- File: resources/views/admin/page/pembelian/show.blade.php --}}
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Detail Pembelian</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row invoice-info">
                    <div class="col-sm-6 invoice-col">
                        <b>Kode Pembelian:</b> {{ $pembelian->kode_pembelian }}<br>
                        <b>Tanggal:</b> {{ date('d-m-Y', strtotime($pembelian->tanggal_pembelian)) }}<br>
                        <b>Supplier:</b> {{ $pembelian->supplier ?? '-' }}<br>
                    </div>
                    <div class="col-sm-6 invoice-col">
                        <b>Total Harga:</b> Rp {{ number_format($pembelian->total_harga, 0, ',', '.') }}<br>
                        <b>Keterangan:</b> {{ $pembelian->keterangan ?? '-' }}<br>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Item</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Kode Item</th>
                            <th>Nama Item</th>
                            <th>Jumlah</th>
                            <th>Harga Beli</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pembelian->detailPembelians as $index => $detail)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $detail->sparepart ? $detail->sparepart->kode : '-' }}</td>
                                <td>{{ $detail->nama_item }}</td>
                                <td>{{ $detail->jumlah }}</td>
                                <td>Rp {{ number_format($detail->harga_beli, 0, ',', '.') }}</td>
                                <td>Rp {{ number_format($detail->total, 0, ',', '.') }}</td>
                                <td>
                                    @if ($detail->is_new_item)
                                        <span class="badge bg-success">Item Baru</span>
                                    @else
                                        <span class="badge bg-primary">Restock</span>
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

<div class="row">
    <div class="col-12">
        <a href="{{ route('pembelian.index') }}" class="btn btn-secondary">Kembali</a>
        <a href="{{ route('pembelian.edit', $pembelian->id) }}" class="btn btn-primary float-right">Edit</a>
    </div>
</div>
