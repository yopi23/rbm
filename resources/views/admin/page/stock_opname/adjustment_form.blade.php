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
                                        <h6>Stok Aktual Fisik</h6>
                                        <h3>{{ $detail->stock_aktual }}</h3>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <h6>Selisih Opname</h6>
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
                        @if ($detail->selisih != 0 || $detail->status == 'adjusted')
                            {{-- Tampilkan form jika ada selisih ATAU jika status sudah adjusted (untuk riwayat) --}}
                            <div class="card">
                                <div class="card-header bg-primary">
                                    <h3 class="card-title">Form Penyesuaian Stok</h3>
                                </div>
                                <div class="card-body">
                                    <form
                                        action="{{ route('stock-opname.save-adjustment', [$period->id, $detail->id]) }}"
                                        method="POST">
                                        @csrf

                                        {{-- INFORMASI STOK SAAT INI DARI TABEL SPAREPART --}}
                                        <div class="form-group">
                                            <label for="current_stock">Stok Gudang Saat Ini (di DB)</label>
                                            <input type="text" id="current_stock" class="form-control"
                                                value="{{ $detail->sparepart->stok_sparepart }}" readonly>
                                            <small class="form-text text-muted">Stok saat ini di inventaris utama
                                                sebelum penyesuaian.</small>
                                        </div>

                                        {{-- INPUT WAJIB BARU: adjustment_qty --}}
                                        <div class="form-group">
                                            <label for="adjustment_qty">Kuantitas Penyesuaian (Delta) <span
                                                    class="text-danger">*</span></label>
                                            <input type="number" id="adjustment_qty" name="adjustment_qty"
                                                class="form-control @error('adjustment_qty') is-invalid @enderror"
                                                value="{{ old('adjustment_qty', $detail->selisih) }}" required>
                                            <small class="form-text text-muted">
                                                Masukkan nilai **positif** untuk menambah stok, atau nilai **negatif**
                                                untuk mengurangi stok. Nilai awal adalah selisih hasil opname, tapi bisa
                                                diubah.
                                            </small>
                                            @error('adjustment_qty')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- PREVIEW STOK BARU --}}
                                        <div class="mt-2 p-2 border rounded">
                                            Stok Baru (Prediksi): <strong id="new_stock_preview"
                                                class="text-primary">{{ $detail->sparepart->stok_sparepart + $detail->selisih }}</strong>
                                        </div>

                                        {{-- ALASAN PENYESUAIAN --}}
                                        <div class="form-group mt-3">
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

                                        @if ($detail->status != 'adjusted')
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="fas fa-save mr-1"></i> Simpan Penyesuaian
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-success btn-block" disabled>
                                                <i class="fas fa-check-circle mr-1"></i> Item Sudah Disesuaikan
                                            </button>
                                        @endif
                                    </form>
                                </div>
                            </div>
                        @endif

                        <div class="card {{ $detail->selisih == 0 && $detail->status == 'checked' ? 'mt-3' : '' }}">
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

<script>
    $(document).ready(function() {
        const currentStock = parseInt($('#current_stock').val());
        const adjustmentQtyInput = $('#adjustment_qty');
        const newStockPreview = $('#new_stock_preview');

        function updateNewStockPreview() {
            let adjustmentQty = parseInt(adjustmentQtyInput.val());

            // Handle NaN or empty input by treating it as 0
            if (isNaN(adjustmentQty) || adjustmentQtyInput.val() === '') {
                adjustmentQty = 0;
            }

            const newStock = currentStock + adjustmentQty;
            newStockPreview.text(newStock);
        }

        // Run on load
        updateNewStockPreview();

        // Run on input change
        adjustmentQtyInput.on('input', updateNewStockPreview);
    });
</script>
