@section('penjualan', 'active')
@section('drop', 'active')
@section('main', 'menu-open')


<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-success">
            <div class="card-header">
                <div class="card-title">
                    Data Penjualan Hari ini
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="TABLES_3">
                        <thead>
                            <th>#</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>Catatan</th>
                            <th>Total Harga</th>
                            <th>Bayar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </thead>
                        <tbody>
                            @foreach ($view_penjualan as $item)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $item->kode_penjualan }}</td>
                                    <td>{{ $item->nama_customer }}</td>
                                    <td>{{ $item->catatan_customer }}</td>
                                    <td>Rp.{{ number_format($item->total_penjualan) }},-</td>
                                    <td>Rp.{{ number_format($item->total_bayar) }},-</td>
                                    <td>
                                        @switch($item->status_penjualan)
                                            @case(1)
                                                <span class="badge badge-success">Dibayar</span>
                                            @break

                                            @case(2)
                                                <span class="badge badge-info">Disimpan</span>
                                            @break

                                            @default
                                        @endswitch
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-primary btn-sm" data-toggle="modal"
                                            data-target="#modal_view_penjualan_{{ $item->id }}"><i
                                                class="fas fa-eye"></i></a>
                                        @if ($item->status_penjualan == '2')
                                            <a href="{{ route('edit_penjualan', $item->id) }}"
                                                class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
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
@foreach ($view_penjualan as $item)
    <div class="modal fade" id="modal_view_penjualan_{{ $item->id }}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">{{ $item->kode_penjualan }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-8">
                                    <table>
                                        <tr>
                                            <td>Kode Invoice </td>
                                            <td>: {{ $item->kode_penjualan }}</td>
                                        </tr>
                                        <tr>
                                            <td>Tanggal </td>
                                            <td>: {{ $item->created_at }}</td>
                                        </tr>
                                        <tr>
                                            <td>Nama Pelanggan </td>
                                            <td>: {{ $item->nama_customer }}</td>
                                        </tr>
                                        <tr>
                                            <td>Catatan </td>
                                            <td>: {{ $item->catatan_customer }}</td>
                                        </tr>
                                        <tr>
                                            <td>Status </td>
                                            <td>: @switch($item->status_penjualan)
                                                    @case(1)
                                                        <span class="badge badge-success">Selesai</span>
                                                    @break

                                                    @case(2)
                                                        <span class="badge badge-warning">Belum Selesai</span>
                                                    @break

                                                    @default
                                                @endswitch
                                            </td>

                                        </tr>

                                    </table>
                                </div>
                                <div class="col-md-4">
                                    <table>
                                        <tr>
                                            <td>Total </td>
                                            <td>: Rp.{{ number_format($item->total_penjualan) }},-</td>
                                        </tr>
                                        <tr>
                                            <td>Bayar </td>
                                            <td>: Rp.{{ number_format($item->total_bayar) }},-</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-12">
                            <label>Barang Yang Dipesan</label>
                            <div class="table-responsive">
                                <table class="table" id="TABLES_4">
                                    <thead>
                                        <th>#</th>
                                        <th>Barang</th>
                                        <th>Harga</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                        <th>Aksi</th>
                                    </thead>
                                    <tbody>
                                        @php
                                            $no = 1;
                                        @endphp
                                        @foreach ($view_barang as $b)
                                            @if ($b->kode_penjualan == $item->id)
                                                <tr>
                                                    <td>{{ $no++ }}</td>
                                                    <td>{{ $b->nama_barang }}</td>
                                                    <td>Rp.{{ number_format($b->harga_jual_barang) }},-</td>
                                                    <td>{{ number_format($b->qty_barang) }}</td>
                                                    <td>Rp.{{ number_format($b->harga_jual_barang * $b->qty_barang) }},-
                                                    </td>
                                                    <td> <!-- Tombol Refund untuk Barang -->
                                                        <button class="btn btn-warning btn-sm" data-toggle="modal"
                                                            data-target="#modal_refund_barang_{{ $b->id }}">
                                                            Refund
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <hr>
                        </div>
                        <div class="col-md-12">
                            <label>Sparepart Yang Dipesan</label>
                            <div class="table-responsive">
                                <table class="table" id="TABLES_4">
                                    <thead>
                                        <th>#</th>
                                        <th>Sparepart</th>
                                        <th>Harga</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                        <th>Aksi</th>
                                    </thead>
                                    <tbody>
                                        @php
                                            $no = 1;
                                        @endphp
                                        @foreach ($view_sparepart as $b)
                                            @if ($b->kode_penjualan == $item->id)
                                                <tr>
                                                    <td>{{ $no++ }}</td>
                                                    <td>{{ $b->nama_sparepart }}</td>
                                                    <td>Rp.{{ number_format($b->detail_harga_jual) }},-</td>
                                                    <td>{{ number_format($b->qty_sparepart) }}</td>
                                                    <td>Rp.{{ number_format($b->detail_harga_jual * $b->qty_sparepart) }},-
                                                    </td>
                                                    <td> <!-- Tombol Refund untuk Sparepart -->
                                                        @switch($b->status_rf)
                                                            @case(1)
                                                                <span class="badge badge-danger">Di Kembalikan</span>
                                                            @break

                                                            @case(0)
                                                                <button class="btn btn-primary btn-sm" data-toggle="modal"
                                                                    data-target="#modal_refund_sparepart_{{ $b->kode_penjualan }}_{{ $b->id }}">
                                                                    Refund
                                                                </button>
                                                            @break

                                                            @default
                                                        @endswitch
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
@endforeach

{{-- refund --}}
@foreach ($view_barang as $b)
    {{-- @if ($b->kode_penjualan == $item->id) --}}
    <!-- Modal Refund Barang -->
    <div class="modal fade" id="modal_refund_barang_{{ $b->id }}" tabindex="-1" role="dialog"
        aria-labelledby="refundModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="refundModalLabel">Refund Barang: {{ $b->nama_barang }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Form Refund Barang -->
                    {{-- <form action="{{ route('refund.store') }}" method="POST"> --}}
                    @csrf
                    <!-- Kirim ID Penjualan dan ID Barang -->
                    <input type="hidden" name="penjualan_id" value="{{ $item->id }}">
                    <input type="hidden" name="barang_id" value="{{ $b->id }}">

                    <div class="form-group">
                        <label for="refund_reason_{{ $b->id }}">Alasan Refund</label>
                        <textarea class="form-control" id="refund_reason_{{ $b->id }}" name="refund_reason" rows="3" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="refund_qty_{{ $b->id }}">Jumlah Barang yang Direfund</label>
                        <input type="number" class="form-control" id="refund_qty_{{ $b->id }}"
                            name="refund_qty" value="1" min="1" max="{{ $b->qty_barang }}" required>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary">Kirim Refund</button>
                    </div>
                    {{-- </form> --}}
                </div>
            </div>
        </div>
    </div>
    {{-- @endif --}}
@endforeach
@foreach ($view_sparepart as $s)
    <!-- Modal Refund Sparepart -->
    <div class="modal fade" id="modal_refund_sparepart_{{ $s->kode_penjualan }}_{{ $s->id }}" tabindex="-1"
        role="dialog" aria-labelledby="refundModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="refundModalLabel">Refund Sparepart: {{ $s->nama_sparepart }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Form Refund Sparepart -->
                    <form action="{{ route('refund.store') }}" method="POST">
                        @csrf
                        <!-- Kirim ID Penjualan dan ID Sparepart -->
                        <input type="hidden" name="penjualan_id" value="{{ $s->kode_penjualan }}">
                        <input type="hidden" name="kode_barang" value="{{ $s->id }}">
                        <input type="hidden" name="kode_supplier" value="{{ $s->kode_spl }}">

                        <div class="form-group">
                            <label for="catatan_retur_{{ $s->id }}">Alasan Refund</label>
                            <textarea class="form-control" id="catatan_retur_{{ $s->id }}" name="catatan_retur" rows="3" required></textarea>
                        </div>

                        <div class="form-group">
                            <label for="jumlah_retur_{{ $s->id }}">Jumlah Sparepart yang Direfund</label>
                            <input type="number" class="form-control" id="jumlah_retur_{{ $s->id }}"
                                name="jumlah_retur" value="1" min="1" max="{{ $s->qty_sparepart }}"
                                required>
                        </div>
                        <!-- Pilihan Retur -->
                        <div class="form-group">
                            <label>Jenis Retur</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jenis_retur"
                                    id="retur_ke_supplier" value="supplier" checked>
                                <label class="form-check-label" for="retur_ke_supplier">
                                    Retur ke Supplier
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jenis_retur" id="retur_ke_kita"
                                    value="kita">
                                <label class="form-check-label" for="retur_ke_kita">
                                    Retur ke Kita (Barang dalam Kondisi Baik)
                                </label>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary">Kirim Refund</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach
{{-- refund --}}
