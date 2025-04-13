<!-- resources/views/admin/page/stock_opname/check_items.blade.php -->

<div class="row">
    <div class="col-md-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tasks mr-1"></i>
                    Pemeriksaan Stock Opname: {{ $period->nama_periode }}
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
                        <!-- Informasi Periode -->
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i class="fas fa-clipboard-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Periode Stock Opname</span>
                                <span class="info-box-number">{{ $period->kode_periode }}</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: {{ $period->progress }}%"></div>
                                </div>
                                <span class="progress-description">
                                    {{ $period->progress }}% Selesai
                                    ({{ $period->details()->whereIn('status', ['checked', 'adjusted'])->count() }} dari
                                    {{ $period->details()->count() }} item)
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <!-- Form Scan Barcode (jika ada) -->
                        <div class="card">
                            <div class="card-header bg-secondary">
                                <h3 class="card-title">Scan Barcode / Kode</h3>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('stock-opname.scan-item', $period->id) }}" method="POST"
                                    class="form-inline">
                                    @csrf
                                    <div class="input-group w-100">
                                        <input type="text" class="form-control" name="barcode" id="barcode"
                                            placeholder="Scan atau input kode sparepart" autofocus>
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search mr-1"></i> Cari
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Item yang terakhir di-scan (jika ada) -->
                @if (isset($lastScannedItem) && $lastScannedItem)
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h5><i class="icon fas fa-info"></i> Item Terakhir di-Scan</h5>
                                <p>
                                    <strong>{{ $lastScannedItem->sparepart->kode_sparepart }}</strong> -
                                    {{ $lastScannedItem->sparepart->nama_sparepart }}
                                    (Stok Tercatat: {{ $lastScannedItem->stock_tercatat }})
                                </p>
                                <a href="#item-{{ $lastScannedItem->id }}" class="btn btn-info btn-sm">
                                    <i class="fas fa-arrow-down mr-1"></i> Lihat Item
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5>Daftar Item yang Belum Diperiksa <span
                                class="badge badge-warning">{{ $pendingItems->total() }}</span></h5>

                        @foreach ($pendingItems as $index => $item)
                            <div class="card mb-3 {{ request('item') == $item->id ? 'border-primary' : '' }}"
                                id="item-{{ $item->id }}">
                                <div
                                    class="card-header {{ request('item') == $item->id ? 'bg-primary' : 'bg-light' }}">
                                    <h3 class="card-title">
                                        <strong>{{ $item->sparepart->kode_sparepart }}</strong> -
                                        {{ $item->sparepart->nama_sparepart }}
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Kategori:</strong>
                                                {{ $item->sparepart->kode_kategori }}</p>
                                            <p class="mb-1"><strong>Supplier:</strong>
                                                {{ $item->sparepart->kode_spl ?? 'Tidak ada' }}</p>
                                            <p class="mb-1"><strong>Harga Beli:</strong> Rp
                                                {{ number_format($item->sparepart->harga_beli, 0, ',', '.') }}</p>
                                            <p class="mb-1"><strong>Harga Jual:</strong> Rp
                                                {{ number_format($item->sparepart->harga_jual, 0, ',', '.') }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="alert alert-warning text-center">
                                                <h5 class="mb-0">Stok Tercatat</h5>
                                                <h2 class="mb-0">{{ $item->stock_tercatat }}</h2>
                                            </div>
                                            <form
                                                action="{{ route('stock-opname.save-item-check', [$period->id, $item->id]) }}"
                                                method="POST">
                                                @csrf
                                                <div class="form-group">
                                                    <label for="stock_aktual">Stok Aktual (Hasil Penghitungan) <span
                                                            class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" id="stock_aktual"
                                                        name="stock_aktual" min="0" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="catatan">Catatan (opsional)</label>
                                                    <textarea class="form-control" id="catatan" name="catatan" rows="2"></textarea>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-block">
                                                    <i class="fas fa-save mr-1"></i> Simpan Hasil Pemeriksaan
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-3">
                            {{ $pendingItems->links() }}
                        </div>

                        @if ($pendingItems->count() == 0)
                            <div class="alert alert-success">
                                <h5><i class="icon fas fa-check"></i> Semua Item Telah Diperiksa!</h5>
                                <p>
                                    Selamat! Semua item telah diperiksa. Anda dapat kembali ke halaman detail untuk
                                    menyelesaikan stock opname atau melakukan penyesuaian stok.
                                </p>
                                <a href="{{ route('stock-opname.show', $period->id) }}" class="btn btn-success">
                                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Detail
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('stock-opname.show', $period->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Detail
                </a>

                @if ($pendingItems->count() == 0 && $period->status == 'in_progress')
                    <a href="{{ route('stock-opname.complete-period', $period->id) }}"
                        class="btn btn-success float-right"
                        onclick="return confirm('Apakah Anda yakin ingin menyelesaikan stock opname ini?')">
                        <i class="fas fa-check-circle mr-1"></i> Selesaikan Stock Opname
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        // Focus pada field barcode saat halaman dimuat
        $('#barcode').focus();

        // Focus pada field stock_aktual dari item yang di-scan
        @if (request('item'))
            $('#item-{{ request('item') }} #stock_aktual').focus();
            // Scroll ke item yang di-scan
            $('html, body').animate({
                scrollTop: $('#item-{{ request('item') }}').offset().top - 100
            }, 500);
        @endif
    });
</script>
