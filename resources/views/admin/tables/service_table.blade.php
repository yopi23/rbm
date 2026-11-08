<div class="table-responsive">
    <table id="service_data" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Tanggal</th>
                <th>Nama</th>
                <th>No Telp</th>
                <th>Unit</th>
                <th>Keterangan</th>
                <th>Harga</th>
                <th>Sparepart</th>
                <th>Status</th>
                <th>Update at</th>
                <th>Teknisi</th>
                <th>Print</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data_service as $index => $item)
                @php
                    $teknisi = (!empty($item->id_teknisi) && !empty($item->nama_teknisi)) ? $item->nama_teknisi : '-';
                    $waText = 'Assalamualaikum, pemberitahuan bahwa unit ' . $item->type_unit . ' sudah selesai diperbaiki. Teknisi: ' . $teknisi . ' (Terimakasih)';
                    $cleanTelp = preg_replace('/^0/', '', preg_replace('/[^0-9]/', '', $item->no_telp ?? ''));
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->tgl_service }}<br>{{ $item->kode_service }}</td>
                    <td>{{ $item->nama_pelanggan }}</td>
                    <td>
                        <a href="https://wa.me/62{{ $cleanTelp }}?text={{ rawurlencode($waText) }}" target="_blank">{{ $item->no_telp }}</a>
                    </td>
                    <td>{{ $item->type_unit }}</td>
                    <td>{{ $item->keterangan }}</td>
                    <td>Rp.{{ number_format($item->total_biaya) }},-</td>
                    <td>Rp.{{ number_format(($item->detail_harga_part_service * $item->qty_part_toko) + ($item->harga_part * $item->qty_part_luar)) }},-</td>
                    <td>{{ $item->status_services }}</td>
                    <td>{{ $item->updated_at }}</td>
                    <td>{{ $teknisi }}</td>
                    <td>
                        <a href="{{ route('nota_tempel_selesai', $item->id_service) }}" target="_blank" class="btn btn-sm btn-primary mt-2"><i class="fas fa-print"></i></a>
                        <button class="btn btn-secondary btn-sm my-2" data-toggle="modal" data-target="#editteknisi" data-id_teknisi="{{ $item->id_teknisi }}" data-id_service="{{ $item->id_service }}" data-teknisi="{{ $teknisi }}" data-device="{{ $item->nama_pelanggan }} ({{ $item->type_unit }})">Edit</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
