@if (isset($data) && count($data) > 0)
    @foreach ($data as $index => $item)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->nama_sparepart }}</td>
            <td>Rp.{{ number_format($item->harga_jual) }}</td>
            <td>Rp.{{ number_format($item->harga_jual + $item->harga_pasang) }}</td>
            <td>{{ $item->stok_sparepart }}</td>
            <td>
                @if ($item->stok_sparepart > 0)
                    <form action="{{ route('store_sparepart_toko') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <input type="number" name="qty_part" class="form-control" min="1" max="{{ $item->stok_sparepart }}" value="1">
                            </div>
                            <div class="col-md-6">
                                <input type="hidden" name="kode_services" value="{{ $kode_service }}">
                                <input type="hidden" name="kode_sparepart" value="{{ $item->id }}">
                                <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </form>
                @else
                    <span class="badge badge-danger">Stok Kosong</span>
                @endif
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td class="text-center text-muted" colspan="100%">Data Tidak Ditemukan</td>
    </tr>
@endif
