{{-- File: resources/views/admin/page/sparepart/index.blade.php --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">{{ $page }}</div>
        <div class="card-tools">
            <a href="{{ $link_tambah }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah Sparepart</a>
        </div>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <button class="btn btn-info" id="bulk-action-button" style="display: none;">
                <i class="fas fa-cogs"></i> Edit Item Terpilih (<span id="selected-count">0</span>)
            </button>
        </div>
        {!! $data !!}
    </div>
</div>

<div class="modal fade" id="bulk-edit-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Masal (<span id="modal-selected-count">0</span> item)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="bulk-edit-form">
                    {{-- 1. Tambahkan div .table-responsive untuk scroll horizontal --}}
                    <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-bordered table-sm" style="min-width: 1200px;"> {{-- Beri min-width --}}
                            <thead>
                                <tr>
                                    {{-- 2. Tambahkan header kolom baru --}}
                                    <th style="min-width: 200px;">Nama Sparepart</th>
                                    <th style="min-width: 150px;">Kategori</th>
                                    <th style="min-width: 150px;">Sub Kategori</th>
                                    <th style="width: 12%;">Harga Beli</th>
                                    <th style="width: 12%;">Harga Jual</th>
                                    <th style="width: 12%;">Harga Pasang</th>
                                    <th style="width: 12%;">Hrg. Khusus Toko</th>
                                    <th style="width: 12%;">Hrg. Khusus Satuan</th>
                                    <th style="width: 15%;">Diskon</th>
                                    <th style="width: 10%;">Stok</th>
                                </tr>
                            </thead>
                            <tbody id="bulk-edit-table-body">
                                {{-- Form akan dibuat oleh JavaScript di sini --}}
                            </tbody>
                        </table>
                    </div>
                </form>
                <div id="modal-loading-indicator" class="text-center p-5" style="display: none;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Mengambil data...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="apply-bulk-action">Simpan Semua Perubahan</button>
            </div>
        </div>
    </div>
</div>

{{-- @push('scripts') --}}
<script>
    $(document).ready(function() {
        // ... (Logika DataTable dan tombol aksi tetap sama) ...
        const table = $('#dataEditTable').DataTable();
        const bulkActionButton = $('#bulk-action-button');

        function updateBulkActionButton() {
            const selectedCount = $('.item-checkbox:checked').length;
            $('#selected-count').text(selectedCount);
            bulkActionButton.toggle(selectedCount > 0);
        }

        $('#select-all-checkbox').on('click', function() {
            table.rows({
                search: 'applied'
            }).nodes().to$().find('.item-checkbox').prop('checked', this.checked);
            updateBulkActionButton();
        });

        $('#dataEditTable tbody').on('click', '.item-checkbox', updateBulkActionButton);
        table.on('draw', updateBulkActionButton);

        // Logika saat tombol "Edit Item Terpilih" diklik
        bulkActionButton.on('click', function() {
            // ... (Logika untuk mengambil Ids dan menampilkan modal tetap sama) ...
            const selectedIds = $('.item-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            const modalTableBody = $('#bulk-edit-table-body');
            const loadingIndicator = $('#modal-loading-indicator');
            const modalFormTable = $('#bulk-edit-form table');

            $('#modal-selected-count').text(selectedIds.length);
            modalTableBody.html('');
            modalFormTable.hide();
            loadingIndicator.show();
            $('#bulk-edit-modal').modal('show');

            $.ajax({
                url: '{{ route('spareparts.get-details') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: selectedIds
                },
                success: function(items) {
                    items.forEach(item => {
                        const hargaKhusus = item.harga_khusus && item.harga_khusus
                            .length > 0 ? item.harga_khusus[0] : {};
                        const hargaToko = hargaKhusus.harga_toko || 0;
                        const hargaSatuan = hargaKhusus.harga_satuan || 0;
                        const diskonTipe = hargaKhusus.diskon_tipe || '';
                        const diskonNilai = hargaKhusus.diskon_nilai || 0;

                        const isPersenSelected = diskonTipe === 'persentase' ?
                            'selected' : '';
                        const isPotonganSelected = diskonTipe === 'potongan' ?
                            'selected' : '';

                        // 3. Tambahkan data kategori dan subkategori ke dalam baris tabel
                        const kategoriNama = item.kategori ? item.kategori
                            .nama_kategori : '-';
                        const subKategoriNama = item.sub_kategori ? item
                            .sub_kategori.nama_sub_kategori : '-';

                        const row = `
                        <tr>
                            <td>${item.nama_sparepart}</td>
                            <td>${kategoriNama}</td>
                            <td>${subKategoriNama}</td>
                            <td><input type="number" class="form-control form-control-sm" name="items[${item.id}][harga_beli]" value="${item.harga_beli}"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[${item.id}][harga_jual]" value="${item.harga_jual}"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[${item.id}][harga_pasang]" value="${item.harga_pasang}"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[${item.id}][harga_khusus_toko]" value="${hargaToko}"></td>
                            <td><input type="number" class="form-control form-control-sm" name="items[${item.id}][harga_khusus_satuan]" value="${hargaSatuan}"></td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <select class="form-control" name="items[${item.id}][diskon_tipe]">
                                        <option value="">-</option>
                                        <option value="persentase" ${isPersenSelected}>%</option>
                                        <option value="potongan" ${isPotonganSelected}>Rp</option>
                                    </select>
                                    <input type="number" class="form-control" name="items[${item.id}][diskon_nilai]" value="${diskonNilai}">
                                </div>
                            </td>
                            <td><input type="number" class="form-control form-control-sm" name="items[${item.id}][stok_sparepart]" value="${item.stok_sparepart}"></td>
                        </tr>
                    `;
                        modalTableBody.append(row);
                    });
                    loadingIndicator.hide();
                    modalFormTable.show();
                },
                error: () => Swal.fire('Error', 'Gagal mengambil data item.', 'error').then(
                () => $('#bulk-edit-modal').modal('hide'))
            });
        });

        // Logika untuk menyimpan perubahan (tidak ada perubahan di sini)
        $('#apply-bulk-action').on('click', function() {
            const formData = $('#bulk-edit-form').serialize();
            Swal.fire({
                title: 'Konfirmasi',
                text: 'Anda yakin ingin menyimpan semua perubahan ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Simpan!',
                cancelButtonText: 'Batal'
            }).then(result => {
                if (result.isConfirmed) {
                    $(this).prop('disabled', true).text('Menyimpan...');
                    $.ajax({
                        url: '{{ route('spareparts.bulk-update') }}',
                        type: 'POST',
                        data: formData + "&_token={{ csrf_token() }}",
                        success: res => Swal.fire('Sukses!', res.message, 'success')
                            .then(() => location.reload()),
                        error: xhr => Swal.fire('Error!', xhr.responseJSON.message ||
                            'Terjadi kesalahan.', 'error'),
                        complete: () => $(this).prop('disabled', false).text(
                            'Simpan Semua Perubahan')
                    });
                }
            });
        });
    });
</script>
{{-- @endpush --}}
