<section class="content">
    <div class="container-fluid">
        <div class="row">
            {{-- Bagian Form Tambah Beban --}}
            <div class="col-md-5">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Tambah Beban Tetap Baru</h3>
                    </div>
                    <form action="{{ route('beban.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>Nama Beban</label>
                                <input type="text" name="nama_beban" class="form-control" required
                                    placeholder="Contoh: Sewa Ruko">
                            </div>

                            {{-- Input Baru: Periode --}}
                            <div class="form-group">
                                <label>Periode Pembayaran</label>
                                <select name="periode" class="form-control" required>
                                    <option value="bulanan">Bulanan</option>
                                    <option value="tahunan">Tahunan</option>
                                </select>
                            </div>

                            {{-- Input Diubah: Nominal Jatah --}}
                            <div class="form-group">
                                <label>Nominal Jatah (Rp)</label>
                                <input type="number" name="nominal" class="form-control" required
                                    placeholder="Masukkan nominal sesuai periode">
                            </div>

                            <div class="form-group">
                                <label>Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Simpan Beban</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Bagian Daftar Beban --}}
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Daftar Beban Tetap - <strong>{{ $namaBulan }}</strong></h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Beban</th>
                                    <th class="text-center">Nominal Jatah</th>
                                    <th class="text-center">Terpakai Periode Ini</th>
                                    <th class="text-center">Sisa Jatah</th>
                                    <th style="width: 150px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($beban as $item)
                                    <tr>
                                        <td>
                                            {{ $item->nama_beban }}
                                            {{-- Badge untuk menandakan periode --}}
                                            <span
                                                class="badge
                                                @if ($item->periode == 'tahunan') badge-danger
                                                @elseif($item->periode == 'bulanan') badge-info
                                                @else badge-secondary @endif ml-2">
                                                {{ ucfirst($item->periode) }}
                                            </span>
                                            @php
                                                $persentase =
                                                    $item->nominal > 0
                                                        ? ($item->terpakai_periode_ini / $item->nominal) * 100
                                                        : 0;
                                                $progressColor =
                                                    $persentase > 90
                                                        ? 'bg-danger'
                                                        : ($persentase > 70
                                                            ? 'bg-warning'
                                                            : 'bg-success');
                                            @endphp
                                            <div class="progress progress-xs mt-1">
                                                <div class="progress-bar {{ $progressColor }}"
                                                    style="width: {{ $persentase }}%"></div>
                                            </div>
                                        </td>
                                        <td class="text-right">{{ number_format($item->nominal) }}</td>
                                        <td class="text-right">{{ number_format($item->terpakai_periode_ini) }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($item->sisa_jatah) }}
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('create_pengeluaran_opex', ['beban_id' => $item->id]) }}"
                                                    class="btn btn-sm btn-success" title="Bayar/Catat Pengeluaran"><i
                                                        class="fas fa-money-bill-wave"></i></a>
                                                <button class="btn btn-sm btn-info" data-toggle="modal"
                                                    data-target="#detailModal-{{ $item->id }}"
                                                    title="Lihat Rincian"><i class="fas fa-list-ul"></i></button>
                                                <button class="btn btn-sm btn-warning" data-toggle="modal"
                                                    data-target="#editModal-{{ $item->id }}" title="Edit Beban"><i
                                                        class="fas fa-edit"></i></button>
                                                <form action="{{ route('beban.destroy', $item->id) }}" method="POST"
                                                    onsubmit="return confirm('Yakin ingin menghapus beban ini?');"
                                                    class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        title="Hapus Beban"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Belum ada beban tetap yang didaftarkan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="4" class="text-right">Total Beban (Ekuivalen Bulanan)</th>
                                    <th class="text-right font-weight-bold">{{ number_format($totalJatahBulanan) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- MODAL UNTUK EDIT & RINCIAN --}}
@foreach ($beban as $item)
    {{-- Modal untuk Edit Beban --}}
    <div class="modal fade" id="editModal-{{ $item->id }}">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Beban Tetap</h4><button type="button" class="close"
                        data-dismiss="modal">&times;</button>
                </div>
                <form action="{{ route('beban.update', $item->id) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <div class="form-group"><label>Nama Beban</label><input type="text" name="nama_beban"
                                class="form-control" required value="{{ $item->nama_beban }}"></div>

                        {{-- Edit Periode --}}
                        <div class="form-group">
                            <label>Periode Pembayaran</label>
                            <select name="periode" class="form-control" required>
                                <option value="bulanan" {{ $item->periode == 'bulanan' ? 'selected' : '' }}>Bulanan
                                </option>
                                <option value="tahunan" {{ $item->periode == 'tahunan' ? 'selected' : '' }}>Tahunan
                                </option>
                            </select>
                        </div>

                        {{-- Edit Nominal --}}
                        <div class="form-group"><label>Nominal Jatah (Rp)</label><input type="number" name="nominal"
                                class="form-control" required value="{{ $item->nominal }}"></div>

                        <div class="form-group"><label>Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="2">{{ $item->keterangan }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-default"
                            data-dismiss="modal">Batal</button><button type="submit"
                            class="btn btn-primary">Update</button></div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal untuk Rincian Transaksi --}}
    <div class="modal fade" id="detailModal-{{ $item->id }}">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Rincian Pengeluaran: {{ $item->nama_beban }}</h4><button type="button"
                        class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Daftar semua pengeluaran untuk <strong>{{ $item->nama_beban }}</strong> pada periode
                        {{ $item->periode == 'tahunan' ? 'tahun ini' : 'bulan ini' }}.</p>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Tanggal</th>
                                <th>Deskripsi Pengeluaran</th>
                                <th class="text-right">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($item->pengeluaranOperasional as $pengeluaran)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($pengeluaran->tgl_pengeluaran)->format('d M Y') }}
                                    </td>
                                    <td>{{ $pengeluaran->nama_pengeluaran }}</td>
                                    <td class="text-right">{{ number_format($pengeluaran->jml_pengeluaran) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">Belum ada pengeluaran untuk beban ini pada
                                        periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="font-weight-bold">
                            <tr>
                                <td colspan="2" class="text-right">Total Terpakai:</td>
                                <td class="text-right">{{ number_format($item->terpakai_periode_ini) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-default"
                        data-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>
@endforeach
