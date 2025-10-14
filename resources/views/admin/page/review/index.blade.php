{{-- File: resources/views/admin/page/review/index.blade.php --}}

<div x-data="migrationManager()" x-init="init()">
    {{-- Header dengan Statistik --}}
    <div class="row mb-4">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 x-text="stats.unmigrated">{{ $stats['total_unmigrated'] }}</h3>
                    <p>Belum Dimigrasi</p>
                </div>
                <div class="icon"><i class="fas fa-database"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3 x-text="stats.migrated">{{ $stats['total_migrated'] }}</h3>
                    <p>Sudah Dimigrasi</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $stats['total_with_stock'] }}</h3>
                    <p>Ada Stok</p>
                </div>
                <div class="icon"><i class="fas fa-boxes"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3 x-text="progressPercentage + '%'">0%</h3>
                    <p>Progress</p>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Aksi Migrasi</h3>
        </div>
        <div class="card-body">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary" @click="openBulkAttributeModal()"
                    :disabled="selectedIds.length === 0 || isProcessing">
                    <i class="fas fa-tasks"></i>
                    Proses Atribut Terpilih (<span x-text="selectedIds.length">0</span>)
                </button>
                <button type="button" class="btn btn-info" @click="refreshStatus()">
                    <i class="fas fa-sync"></i> Refresh
                </button>
                <button type="button" class="btn btn-secondary" @click="selectAllOnCurrentPage()">
                    <i class="fas fa-check-square"></i> Pilih Halaman Ini
                </button>
            </div>
            <div x-show="isProcessingBulk" class="mt-3">
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                        :style="`width: ${processProgress}%`" x-text="processProgress + '%'">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Data --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Sparepart Lama</h3>
            {{-- ====================================================== --}}
            {{-- BAGIAN BARU: FORM PENCARIAN SERVER-SIDE --}}
            {{-- ====================================================== --}}
            <div class="card-tools">
                <form action="{{ url()->current() }}" method="GET">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="search" class="form-control float-right"
                            placeholder="Cari Nama Sparepart..." value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover" id="sparepartsDataTablereview">
                <thead>
                    <tr>
                        <th width="30">
                            <input type="checkbox" @change="toggleSelectAll($event)">
                        </th>
                        <th width="50">#</th>
                        <th>Nama Sparepart</th>
                        <th>Kategori</th>
                        <th width="80">Stok</th>
                        <th width="120">Harga Beli</th>
                        <th width="120">Harga Jual</th>
                        <th width="200">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unmigratedSpareparts as $index => $sp)
                        <tr :id="'row-' + {{ $sp->id }}"
                            :class="{ 'table-success': selectedIds.includes({{ $sp->id }}) }">
                            <td>
                                <input class="sparepart-checkbox" type="checkbox" value="{{ $sp->id }}"
                                    x-model="selectedIds">
                            </td>
                            <td>{{ $unmigratedSpareparts->firstItem() + $index }}</td>
                            <td>
                                <strong>{{ $sp->nama_sparepart }}</strong>
                                <br><small class="text-muted">ID: {{ $sp->id }}</small>
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $sp->kategori->nama_kategori ?? 'N/A' }}</span>
                            </td>
                            <td class="text-right">
                                @if ($sp->stok_sparepart > 0)
                                    <span class="badge badge-success">{{ $sp->stok_sparepart }}</span>
                                @else
                                    <span class="badge badge-secondary">0</span>
                                @endif
                            </td>
                            <td class="text-right">Rp {{ number_format($sp->harga_beli, 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($sp->harga_jual, 0, ',', '.') }}</td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-info" @click="previewMigration({{ $sp->id }})"
                                        title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" @click="migrateSingle({{ $sp->id }})"
                                        title="Migrasi Cepat (Tanpa Atribut)">
                                        <i class="fas fa-bolt"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary"
                                        @click="openAttributeModal({{ $sp->id }})" title="Migrasi + Atribut">
                                        <i class="fas fa-plus-circle"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">
                                @if (request('search'))
                                    <div class="alert alert-warning">Data '{{ request('search') }}' tidak ditemukan.
                                    </div>
                                @else
                                    <div class="alert alert-success"><i class="fas fa-check-circle"></i> Semua data
                                        sudah dimigrasi!</div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{-- Tampilkan link paginasi dari Laravel --}}
            <div class="mt-3 d-flex justify-content-end">
                {{ $unmigratedSpareparts->links() }}
            </div>
        </div>
    </div>

    {{-- ====================================================== --}}
    {{-- SEMUA MODAL DI BAWAH INI TIDAK ADA PERUBAHAN --}}
    {{-- ====================================================== --}}
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Migrasi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div x-show="isLoadingPreview" class="text-center py-5"><i
                            class="fas fa-spinner fa-spin fa-2x"></i></div>
                    <div x-show="!isLoadingPreview && previewData.sparepart">
                        <p><strong>Nama:</strong> <span x-text="previewData.sparepart?.nama"></span></p>
                        <p><strong>Kategori:</strong> <span x-text="previewData.sparepart?.kategori"></span></p>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Data Saat Ini (Lama)</h4>
                                <table class="table table-sm">
                                    <tr>
                                        <th>Stok</th>
                                        <td x-text="previewData.current_data?.stok"></td>
                                    </tr>
                                    <tr>
                                        <th>Harga Beli</th>
                                        <td x-text="formatCurrency(previewData.current_data?.harga_beli)"></td>
                                    </tr>
                                    <tr>
                                        <th>Harga Jual</th>
                                        <td x-text="formatCurrency(previewData.current_data?.harga_jual)"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h4>Akan Menjadi Varian</h4>
                                <table class="table table-sm table-success">
                                    <tr>
                                        <th>Stock</th>
                                        <td x-text="previewData.will_become?.stock"></td>
                                    </tr>
                                    <tr>
                                        <th>Purchase Price</th>
                                        <td x-text="formatCurrency(previewData.will_become?.purchase_price)"></td>
                                    </tr>
                                    <tr>
                                        <th>Wholesale Price</th>
                                        <td x-text="formatCurrency(previewData.will_become?.wholesale_price)"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary"
                        data-dismiss="modal">Tutup</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="attributeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Atribut Varian</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div x-show="isLoadingModal" class="text-center py-5"><i
                            class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                        <p class="mt-3">Memuat data...</p>
                    </div>
                    <div x-show="!isLoadingModal">
                        <div class="alert alert-info" x-show="modalData.sparepart"><strong
                                x-text="modalData.sparepart?.nama"></strong></div>
                        <div x-show="modalData.attributes && modalData.attributes.length > 0">
                            <template x-for="attr in modalData.attributes" :key="attr.id">
                                <div class="form-group">
                                    <label :for="'attr_' + attr.id" x-text="attr.name"></label>
                                    <select class="form-control" x-model="selectedAttributes[attr.id]">
                                        <option value="">-- Tidak Dipilih --</option>
                                        <template x-for="val in attr.values" :key="val.id">
                                            <option :value="val.id" x-text="val.value"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>
                        </div>
                        <div x-show="!modalData.attributes || modalData.attributes.length === 0"
                            class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> Tidak ada atribut untuk kategori ini. Migrasi akan
                            diproses tanpa atribut.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" @click="submitWithAttributes()"
                        :disabled="isLoadingModal || isProcessing">
                        <span x-show="isProcessing"><i class="fas fa-spinner fa-spin"></i> Memproses...</span>
                        <span x-show="!isProcessing"><i class="fas fa-check"></i> Migrasi</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkAttributeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Proses Atribut untuk <span x-text="selectedIds.length"></span> Item
                        Terpilih</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div x-show="isLoadingBulkModal" class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                        <p class="mt-3">Memuat data untuk item terpilih...</p>
                    </div>
                    <div x-show="!isLoadingBulkModal">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Nama Sparepart</th>
                                    <th>Atribut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in bulkItems" :key="item.sparepart.id">
                                    <tr>
                                        <td width="40%">
                                            <strong x-text="item.sparepart.nama"></strong><br>
                                            <small class="text-muted" x-text="item.sparepart.kategori_nama"></small>
                                        </td>
                                        <td>
                                            <div x-show="item.attributes.length > 0">
                                                <template x-for="attr in item.attributes" :key="attr.id">
                                                    <div class="form-group mb-2">
                                                        <label :for="'bulk_attr_' + item.sparepart.id + '_' + attr.id"
                                                            x-text="attr.name" class="small"></label>
                                                        <select class="form-control form-control-sm"
                                                            :id="'bulk_attr_' + item.sparepart.id + '_' + attr.id"
                                                            x-model="selectedBulkAttributes[item.sparepart.id][attr.id]">
                                                            <option value="">-- Tidak Dipilih --</option>
                                                            <template x-for="val in attr.values"
                                                                :key="val.id">
                                                                <option :value="val.id" x-text="val.value">
                                                                </option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                </template>
                                            </div>
                                            <div x-show="item.attributes.length === 0"><span
                                                    class="text-muted font-italic">Akan dimigrasi sebagai varian
                                                    default.</span></div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" @click="submitBulkWithAttributes()"
                        :disabled="isLoadingBulkModal || isProcessing">
                        <span x-show="isProcessing"><i class="fas fa-spinner fa-spin"></i> Memproses...</span>
                        <span x-show="!isProcessing"><i class="fas fa-check"></i> Proses Migrasi</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

{{-- ====================================================== --}}
{{-- BAGIAN YANG DIUBAH: KONFIGURASI DATATABLES --}}
{{-- ====================================================== --}}
<script>
    $(function() {
        // Inisialisasi DataTables HANYA untuk styling (responsive),
        // semua fitur paginasi dan pencarian dinonaktifkan.
        $("#sparepartsDataTablereview").DataTable({
            "responsive": true,
            "autoWidth": false,
            "paging": false, // <-- MATIKAN paginasi DataTables
            "searching": false, // <-- MATIKAN pencarian DataTables
            "info": false, // <-- MATIKAN info "Showing 1 of X"
            "lengthChange": false // <-- MATIKAN dropdown jumlah data
        });
    });
</script>

{{-- SCRIPT ALPINE.JS DI BAWAH INI TIDAK ADA PERUBAHAN --}}
<script>
    function migrationManager() {
        return {
            // State
            selectedIds: [],
            isProcessing: false,
            isProcessingBulk: false,
            processProgress: 0,
            stats: {
                unmigrated: {{ $stats['total_unmigrated'] }},
                migrated: {{ $stats['total_migrated'] }},
            },

            // Modals State
            previewModal: null,
            attributeModal: null,
            bulkAttributeModal: null,
            isLoadingPreview: false,
            previewData: {},
            isLoadingModal: false,
            modalData: {},
            selectedAttributes: {},
            currentSparepartId: null,

            // State for bulk attribute modal
            isLoadingBulkModal: false,
            bulkItems: [],
            selectedBulkAttributes: {},

            get progressPercentage() {
                const total = this.stats.unmigrated + this.stats.migrated;
                return total > 0 ? ((this.stats.migrated / total) * 100).toFixed(2) : 0;
            },

            init() {
                this.previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
                this.attributeModal = new bootstrap.Modal(document.getElementById('attributeModal'));
                this.bulkAttributeModal = new bootstrap.Modal(document.getElementById('bulkAttributeModal'));
                setInterval(() => this.refreshStatus(), 30000);
            },

            // UI Helpers
            formatCurrency(num) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(num || 0);
            },
            toggleSelectAll(event) {
                if (event.target.checked) {
                    this.selectAllOnCurrentPage();
                } else {
                    this.selectedIds = [];
                }
            },

            selectAllOnCurrentPage() {
                const allCheckboxes = document.querySelectorAll('.sparepart-checkbox');
                let allIds = Array.from(allCheckboxes).map(cb => parseInt(cb.value));
                this.selectedIds = [...new Set([...this.selectedIds, ...allIds])];
            },

            // API Helper
            async handleApiResponse(response) {
                if (response.ok) return response.json();
                try {
                    const errorJson = await response.clone().json();
                    throw new Error(errorJson.message || 'Terjadi kesalahan dari server.');
                } catch (jsonError) {
                    const errorText = await response.text();
                    console.error("Server returned non-JSON response:", errorText);
                    throw new Error(`Terjadi kesalahan pada server (Status: ${response.status}). Cek console log.`);
                }
            },

            // Single Item Migration Logic
            async _doMigration(sparepartId, attributes = []) {
                this.isProcessing = true;
                try {
                    const response = await fetch(`/admin/review/migrate/${sparepartId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            attributes
                        })
                    });
                    const data = await this.handleApiResponse(response);
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    const row = document.getElementById(`row-${sparepartId}`);
                    if (row) {
                        row.style.transition = 'opacity 0.5s ease';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 500);
                    }
                    this.selectedIds = this.selectedIds.filter(id => id !== sparepartId);
                    this.refreshStatus();
                } catch (error) {
                    Swal.fire('Gagal', error.message, 'error');
                } finally {
                    this.isProcessing = false;
                }
            },

            async migrateSingle(sparepartId) {
                const result = await Swal.fire({
                    title: 'Migrasi Cepat?',
                    text: 'Item ini akan dimigrasi tanpa atribut.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Migrasi',
                    cancelButtonText: 'Batal'
                });
                if (result.isConfirmed) await this._doMigration(sparepartId, []);
            },

            async openAttributeModal(sparepartId) {
                this.currentSparepartId = sparepartId;
                this.selectedAttributes = {};
                this.modalData = {};
                this.isLoadingModal = true;
                this.attributeModal.show();
                try {
                    const response = await fetch(`/admin/review/data/${sparepartId}`);
                    this.modalData = await this.handleApiResponse(response);
                } catch (error) {
                    Swal.fire('Error', 'Gagal memuat data atribut: ' + error.message, 'error');
                    this.attributeModal.hide();
                } finally {
                    this.isLoadingModal = false;
                }
            },

            async submitWithAttributes() {
                const attributes = Object.values(this.selectedAttributes).filter(Boolean);
                await this._doMigration(this.currentSparepartId, attributes);
                this.attributeModal.hide();
            },

            // Bulk (Massal) Migration Logic
            async openBulkAttributeModal() {
                if (this.selectedIds.length === 0) return;
                this.isLoadingBulkModal = true;
                this.bulkItems = [];
                this.selectedBulkAttributes = {};
                this.bulkAttributeModal.show();

                try {
                    const promises = this.selectedIds.map(id => fetch(`/admin/review/data/${id}`).then(res => res
                        .json()));
                    const results = await Promise.all(promises);
                    this.bulkItems = results;
                    results.forEach(item => {
                        this.selectedBulkAttributes[item.sparepart.id] = {};
                        item.attributes.forEach(attr => {
                            this.selectedBulkAttributes[item.sparepart.id][attr.id] = '';
                        });
                    });
                } catch (error) {
                    Swal.fire('Error', 'Gagal memuat data: ' + error.message, 'error');
                    this.bulkAttributeModal.hide();
                } finally {
                    this.isLoadingBulkModal = false;
                }
            },

            async submitBulkWithAttributes() {
                this.isProcessing = true;
                const itemsPayload = Object.keys(this.selectedBulkAttributes).map(sparepartId => ({
                    id: parseInt(sparepartId),
                    attributes: Object.values(this.selectedBulkAttributes[sparepartId]).filter(Boolean)
                }));

                try {
                    const response = await fetch('/admin/review/migrate-bulk-attributes', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            items: itemsPayload
                        })
                    });
                    const data = await this.handleApiResponse(response);
                    this.bulkAttributeModal.hide();
                    await Swal.fire('Selesai!', data.message, 'success');
                    location.reload();
                } catch (error) {
                    Swal.fire('Error', 'Proses migrasi massal gagal: ' + error.message, 'error');
                } finally {
                    this.isProcessing = false;
                }
            },

            async migrateSelected() {
                if (this.selectedIds.length === 0) return;
                const result = await Swal.fire({
                    title: 'Konfirmasi Migrasi Cepat',
                    text: `Anda akan memigrasikan ${this.selectedIds.length} item sebagai varian default (tanpa atribut)?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Proses!',
                    cancelButtonText: 'Batal'
                });
                if (!result.isConfirmed) return;
                this.isProcessingBulk = true;
                this.processProgress = 50;
                try {
                    const response = await fetch('/admin/review/migrate-bulk', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            sparepart_ids: this.selectedIds
                        })
                    });
                    const data = await this.handleApiResponse(response);
                    this.processProgress = 100;
                    await Swal.fire('Selesai!', data.message, 'success');
                    location.reload();
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                } finally {
                    this.isProcessingBulk = false;
                    this.processProgress = 0;
                }
            },

            // Other Actions
            async migrateAll() {
                const result = await Swal.fire({
                    title: 'Migrasi Semua Data?',
                    html: 'Proses ini akan berjalan di background dan mungkin memakan waktu.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Mulai Proses',
                    cancelButtonText: 'Batal'
                });
                if (!result.isConfirmed) return;
                try {
                    const response = await fetch('/admin/review/migrate-all', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    const data = await this.handleApiResponse(response);
                    Swal.fire('Dimulai!', data.message, 'info');
                    this.refreshStatus();
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                }
            },

            async previewMigration(sparepartId) {
                this.isLoadingPreview = true;
                this.previewData = {};
                this.previewModal.show();
                try {
                    const response = await fetch(`/admin/review/preview/${sparepartId}`);
                    this.previewData = await this.handleApiResponse(response);
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                    this.previewModal.hide();
                } finally {
                    this.isLoadingPreview = false;
                }
            },

            async refreshStatus() {
                try {
                    const response = await fetch('/admin/review/check-status');
                    const data = await this.handleApiResponse(response);
                    this.stats.unmigrated = data.unmigrated;
                    this.stats.migrated = data.migrated;
                } catch (error) {
                    console.error('Gagal refresh status:', error);
                }
            }
        }
    }
</script>
