<!-- resources/views/admin/page/stock_opname/adjustment_form.blade.php -->

<div class="row">
    <div class="col-md-12">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exchange-alt mr-1"></i>
                    Penyesuaian Stok: {{ $detail->sparepart->nama_sparepart }}
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
                        <!-- Informasi Item -->
                        <div class="card">
                            <div class="card-header bg-info">
                                <h3 class="card-title">Informasi Item</h3>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Kode</th>
                                        <td>{{ $detail->sparepart->kode_sparepart }}</td>
                                    </tr>
                                    <tr>
                                        <th>Nama</th>
                                        <td>{{ $detail->sparepart->nama_sparepart }}</td>
                                    </tr>
                                    <tr>
                                        <th>Kategori</th>
                                        <td>{{ $detail->sparepart->kode_kategori }}</td>
                                    </tr>
                                    <tr>
                                        <th>Supplier</th>
                                        <td>{{ $detail->sparepart->kode_spl ?? 'Tidak ada' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Harga Beli</th>
                                        <td>Rp {{ number_format($detail->sparepart->harga_beli, 0, ',', '.') }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Informasi Stock Opname -->
                        <div class="card mt-3">
                            <div class="card-header bg-warning">
                                <h3 class="card-title">Hasil Pemeriksaan</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <h6>Stok Tercatat</h6>
                                        <h3>{{ $detail->stock_tercatat }}</h3>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <h6>Stok Aktual</h6>
                                        <h3>{{ $detail->stock_aktual }}</h3>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <h6>Selisih</h6>
                                        <h3
                                            class="{{ $detail->selisih > 0 ? 'text-success' : ($detail->selisih < 0 ? 'text-danger' : '') }}">
                                            @if ($detail->selisih > 0)
                                                +{{ $detail->selisih }}
                                            @else
                                                {{ $detail->selisih }}
                                            @endif
                                        </h3>
                                    </div>
                                </div>

                                @if ($detail->catatan)
                                    <div class="alert alert-info mt-3 mb-0">
                                        <strong>Catatan Pemeriksaan:</strong> {{ $detail->catatan }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <!-- Form Penyesuaian -->
                        @if ($detail->selisih != 0 && $detail->status != 'adjusted')
                            <div class="card">
                                <div class="card-header bg-primary">
                                    <h3 class="card-title">Form Penyesuaian Stok</h3>
                                </div>
                                <div class="card-body">
                                    <form
                                        action="{{ route('stock-opname.save-adjustment', [$period->id, $detail->id]) }}"
                                        method="POST">
                                        @csrf

                                        <div
                                            class="alert {{ $detail->selisih > 0 ? 'alert-success' : 'alert-danger' }}">
                                            <h5>Konfirmasi Penyesuaian</h5>
                                            <p>
                                                Anda akan melakukan penyesuaian stok sebanyak
                                                <strong>{{ $detail->selisih > 0 ? '+' : '' }}{{ $detail->selisih }}</strong>
                                                untuk item <strong>{{ $detail->sparepart->nama_sparepart }}</strong>.
                                            </p>
                                            <p>
                                                Stok akan berubah dari
                                                <strong>{{ $detail->sparepart->stok_sparepart }}</strong>
                                                menjadi
                                                <strong>{{ $detail->sparepart->stok_sparepart + $detail->selisih }}</strong>.
                                            </p>
                                        </div>

                                        <div class="form-group">
                                            <label for="alasan_adjustment">Alasan Penyesuaian <span
                                                    class="text-danger">*</span></label>
                                            <textarea class="form-control @error('alasan_adjustment') is-invalid @enderror" id="alasan_adjustment"
                                                name="alasan_adjustment" rows="3" required></textarea>
                                            <small class="form-text text-muted">
                                                Berikan alasan yang jelas untuk penyesuaian stok ini.
                                            </small>
                                            @error('alasan_adjustment')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-save mr-1"></i> Simpan Penyesuaian
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @elseif($detail->status == 'adjusted')
                            <div class="alert alert-success">
                                <h5><i class="icon fas fa-check"></i> Stok Sudah Disesuaikan</h5>
                                <p>
                                    Stok untuk item ini sudah disesuaikan. Lihat riwayat penyesuaian di bawah.
                                </p>
                            </div>
                        @endif

                        <!-- Riwayat Penyesuaian -->
                        <div class="card {{ $detail->status == 'adjusted' ? '' : 'mt-3' }}">
                            <div class="card-header bg-secondary">
                                <h3 class="card-title">Riwayat Penyesuaian</h3>
                            </div>
                            <div class="card-body p-0">
                                @if (count($adjustmentHistory) > 0)
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Stok Sebelum</th>
                                                <th>Stok Sesudah</th>
                                                <th>Perubahan</th>
                                                <th>Alasan</th>
                                                <th>Oleh</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($adjustmentHistory as $history)
                                                <tr>
                                                    <td>{{ date('d/m/Y H:i', strtotime($history->created_at)) }}</td>
                                                    <td>{{ $history->stock_before }}</td>
                                                    <td>{{ $history->stock_after }}</td>
                                                    <td
                                                        class="{{ $history->adjustment_qty > 0 ? 'text-success' : ($history->adjustment_qty < 0 ? 'text-danger' : '') }}">
                                                        {{ $history->adjustment_qty > 0 ? '+' : '' }}{{ $history->adjustment_qty }}
                                                    </td>
                                                    <td>{{ $history->alasan_adjustment }}</td>
                                                    <td>{{ $history->user ? $history->user->name : '-' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <div class="card-body">
                                        <p class="text-center mb-0">Belum ada riwayat penyesuaian.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('stock-opname.show', $period->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Detail
                </a>
            </div>
        </div>
    </div>
</div>
