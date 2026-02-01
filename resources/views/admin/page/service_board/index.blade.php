@section('page', $page)
@include('admin.component.header')
@include('admin.component.navbar')
@include('admin.component.sidebar')

<div class="content-wrapper">
    <!-- Content Header -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ $page }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">{{ $page }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="content">
        <div class="container-fluid">
            
            <!-- Toolbar -->
            <div class="row mb-3">
                <div class="col-md-12 text-right">
                    <!-- Button to create new service (adjust route as needed) -->
                    <a href="{{ route('service_board.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Terima Unit Baru
                    </a>
                </div>
            </div>

            <!-- Kanban Board -->
            <div class="row overflow-auto flex-nowrap pb-4" style="min-height: 500px;">
                @foreach($statuses as $key => $status)
                <div class="col-md-3 col-sm-6" style="min-width: 300px;">
                    <div class="card card-{{ $status['color'] }} card-outline h-100">
                        <div class="card-header">
                            <h3 class="card-title font-weight-bold">{{ $status['label'] }}</h3>
                            <div class="card-tools">
                                <span class="badge badge-{{ $status['color'] }}">{{ count($tickets[$key]) }}</span>
                            </div>
                        </div>
                        <div class="card-body p-2 kanban-column" 
                             style="background-color: #f4f6f9; min-height: 200px;"
                             data-status="{{ $key }}"
                             ondragover="allowDrop(event)"
                             ondrop="drop(event)">
                            
                            @foreach($tickets[$key] as $ticket)
                            <div class="card mb-2 draggable-ticket" 
                                 draggable="true" 
                                 data-id="{{ $ticket->id }}"
                                 ondragstart="drag(event)"
                                 style="cursor: grab;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="badge badge-secondary">{{ $ticket->kode_service }}</span>
                                        <small class="text-muted">{{ $ticket->created_at->format('d M H:i') }}</small>
                                    </div>
                                    <h5 class="font-weight-bold mb-1">{{ $ticket->nama_pelanggan }}</h5>
                                    <p class="text-sm mb-1 text-muted">{{ $ticket->type_unit }}</p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="text-xs text-primary font-weight-bold">Rp {{ number_format($ticket->total_biaya) }}</span>
                                        <button class="btn btn-xs btn-outline-info" onclick="viewTicket({{ $ticket->id }})">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                    </div>
                                </div>
                            </div>
                            @endforeach

                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
</div>

<!-- Floating Printer Button -->
<button id="connect-button" class="btn btn-info rounded-pill shadow-lg" 
        style="position: fixed; bottom: 20px; right: 20px; z-index: 1050; padding: 10px 20px;">
    <i class="fas fa-print mr-2"></i> <span id="btn-text">Connect Printer</span>
</button>

<!-- Detail Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Service</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="ticketModalBody">
                <div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x"></i></div>
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <div class="btn-group dropup">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-tag"></i> Label
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#" onclick="printTicket('label', '50x30')">Standard (50x30mm)</a>
                            <a class="dropdown-item" href="#" onclick="printTicket('label', '30x20')">Kecil (30x20mm)</a>
                            <a class="dropdown-item" href="#" onclick="printTicket('label', '33x15')">Kecil (33x15mm)</a>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" onclick="printTicket('ticket')"><i class="fas fa-receipt"></i> Nota</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="printTicket('warranty')"><i class="fas fa-shield-alt"></i> Garansi</button>
                    <button type="button" class="btn btn-outline-primary" onclick="editTicket()"><i class="fas fa-edit"></i> Edit</button>
                </div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editTicketModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Service</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editTicketForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nama Pelanggan</label>
                                <input type="text" name="nama_pelanggan" id="edit_nama_pelanggan" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>No. Telp</label>
                                <input type="text" name="no_telp" id="edit_no_telp" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Type Unit</label>
                                <input type="text" name="type_unit" id="edit_type_unit" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Kelengkapan</label>
                                <input type="text" name="kelengkapan" id="edit_kelengkapan" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Teknisi</label>
                                <select name="id_teknisi" id="edit_id_teknisi" class="form-control">
                                    <option value="">-- Pilih Teknisi --</option>
                                    @foreach($teknisi as $t)
                                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Uang Muka (DP)</label>
                                <input type="number" name="dp" id="edit_dp" class="form-control" value="0">
                            </div>
                            <div class="form-group">
                                <label>Tipe Kunci</label>
                                <select name="tipe_sandi" id="edit_tipe_sandi" class="form-control">
                                    <option value="None">Tidak Ada</option>
                                    <option value="PIN">PIN</option>
                                    <option value="Pola">Pola</option>
                                    <option value="Password">Password</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Kode / Sandi</label>
                                <input type="text" name="isi_sandi" id="edit_isi_sandi" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Keluhan / Keterangan</label>
                        <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/printer-thermal.js') }}"></script>
<script>
    // --- Kanban Logic ---
    function allowDrop(ev) {
        ev.preventDefault();
        ev.currentTarget.classList.add('bg-light-blue'); // Highlight
    }

    function drag(ev) {
        ev.dataTransfer.setData("text/plain", ev.target.dataset.id);
        ev.target.style.opacity = '0.5';
    }

    function drop(ev) {
        ev.preventDefault();
        var column = ev.currentTarget;
        column.classList.remove('bg-light-blue');
        
        var ticketId = ev.dataTransfer.getData("text/plain");
        var newStatus = column.dataset.status;
        
        // Find the element
        var element = document.querySelector(`.draggable-ticket[data-id='${ticketId}']`);
        if (element) {
            element.style.opacity = '1';
            column.appendChild(element); // Move visually
            
            // Update status via AJAX
            updateStatus(ticketId, newStatus);
        }
    }

    // Reset opacity if drag ends without drop
    document.addEventListener("dragend", function(event) {
        if(event.target.classList.contains("draggable-ticket")) {
            event.target.style.opacity = "1";
        }
    });

    function updateStatus(id, status) {
        $.ajax({
            url: '{{ route("service_board.update_status") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: id,
                status: status
            },
            success: function(res) {
                if(res.success) {
                    toastr.success('Status updated successfully');
                } else {
                    toastr.error('Failed to update status');
                }
            },
            error: function() {
                toastr.error('Error updating status');
            }
        });
    }

    // --- Ticket Detail & Printing ---
    var currentTicketId = null;
    var currentTicketData = null; // Store for edit

    function viewTicket(id) {
        currentTicketId = id;
        $('#ticketModal').modal('show');
        $('#ticketModalBody').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-3x"></i></div>');
        
        $.ajax({
            url: '{{ url("admin/service-board/details") }}/' + id,
            method: 'GET',
            success: function(res) {
                if(res.success) {
                    currentTicketData = res.data; // Store
                    renderTicketDetails(res.data);
                }
            }
        });
    }
    
    // --- Edit Logic ---
    function editTicket() {
        if(!currentTicketData) return;
        
        var d = currentTicketData;
        
        // Parse data_unit for kelengkapan
        var kelengkapan = '-';
        try {
            var dataUnit = JSON.parse(d.data_unit);
            kelengkapan = dataUnit.kelengkapan || '-';
        } catch(e) {}
        
        $('#edit_id').val(d.id);
        $('#edit_nama_pelanggan').val(d.nama_pelanggan);
        $('#edit_no_telp').val(d.no_telp);
        $('#edit_type_unit').val(d.type_unit);
        $('#edit_kelengkapan').val(kelengkapan);
        $('#edit_keterangan').val(d.keterangan);
        $('#edit_tipe_sandi').val(d.tipe_sandi || 'None');
        $('#edit_isi_sandi').val(d.isi_sandi);
        $('#edit_dp').val(d.dp);
        $('#edit_id_teknisi').val(d.id_teknisi); // Might need .trigger('change') if select2

        $('#ticketModal').modal('hide');
        $('#editTicketModal').modal('show');
    }
    
    $('#editTicketForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        formData += '&_token={{ csrf_token() }}';
        
        $.ajax({
            url: '{{ route("service_board.update_details") }}',
            method: 'POST',
            data: formData,
            success: function(res) {
                if(res.success) {
                    toastr.success(res.message);
                    $('#editTicketModal').modal('hide');
                    viewTicket(currentTicketId); // Reopen view modal with updated data
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(err) {
                toastr.error('Failed to update: ' + (err.responseJSON ? err.responseJSON.message : 'Error'));
            }
        });
    });

    // --- WA Notification ---
    function sendWhatsAppNotification() {
        if(!currentTicketId) return;
        if(!confirm('Kirim notifikasi WhatsApp ke pelanggan sekarang?')) return;
        
        var btn = $('#btnSendWa');
        var originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Sending...');
        btn.prop('disabled', true);
        
        $.ajax({
            url: '{{ url("admin/service-board/send-whatsapp") }}/' + currentTicketId,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if(res.success) {
                    toastr.success(res.message);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(err) {
                toastr.error('Failed to send: ' + (err.responseJSON ? err.responseJSON.message : 'Error'));
            },
            complete: function() {
                btn.html(originalText);
                btn.prop('disabled', false);
            }
        });
    }

    function renderTicketDetails(data) {
        var kelengkapan = '-';
        try {
            var dataUnit = JSON.parse(data.data_unit);
            kelengkapan = dataUnit.kelengkapan || '-';
        } catch(e) {}

        var partTokoHtml = '';
        if(data.part_toko && data.part_toko.length > 0) {
            data.part_toko.forEach(function(item) {
                var namaPart = item.sparepart ? item.sparepart.nama_sparepart : 'Unknown';
                partTokoHtml += `
                    <tr>
                        <td>${namaPart}</td>
                        <td>${item.qty_part}</td>
                        <td>Rp ${new Intl.NumberFormat('id-ID').format(item.detail_harga_part_service)}</td>
                        <td>
                            <button class="btn btn-xs btn-danger" onclick="deletePartToko(${item.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        } else {
            partTokoHtml = '<tr><td colspan="4" class="text-center text-muted">Belum ada sparepart toko</td></tr>';
        }

        var partLuarHtml = '';
        if(data.part_luar && data.part_luar.length > 0) {
            data.part_luar.forEach(function(item) {
                partLuarHtml += `
                    <tr>
                        <td>${item.nama_part}</td>
                        <td>${item.qty_part}</td>
                        <td>Rp ${new Intl.NumberFormat('id-ID').format(item.harga_part)}</td>
                        <td>
                            <button class="btn btn-xs btn-danger" onclick="deletePartLuar(${item.id})"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            });
        } else {
            partLuarHtml = '<tr><td colspan="4" class="text-center text-muted">Belum ada sparepart luar/jasa</td></tr>';
        }

        // WhatsApp Link Construction
        var waMessage = `Halo ${data.nama_pelanggan}, update status service ${data.type_unit} (Ref: ${data.kode_service}): ${data.status_services}. Total Biaya saat ini: Rp ${new Intl.NumberFormat('id-ID').format(data.total_biaya)}. Terima kasih.`;
        var waUrl = `https://wa.me/${data.no_telp}?text=${encodeURIComponent(waMessage)}`;

        var priorityBadge = '';
        if(data.priority === 'urgent') priorityBadge = '<span class="badge badge-danger">Urgent</span>';
        else if(data.priority === 'prioritas') priorityBadge = '<span class="badge badge-warning">Prioritas</span>';
        else priorityBadge = '<span class="badge badge-secondary">Normal</span>';

        var html = `
            <div class="row">
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2">Info Pelanggan & Unit</h5>
                    <dl>
                        <dt>Kode Service</dt><dd>${data.kode_service}</dd>
                        <dt>Pelanggan</dt><dd>${data.nama_pelanggan} (${data.no_telp}) 
                            <a href="${waUrl}" target="_blank" class="btn btn-xs btn-success ml-2"><i class="fab fa-whatsapp"></i> Chat</a>
                            <button id="btnSendWa" class="btn btn-xs btn-outline-success ml-1" onclick="sendWhatsAppNotification()"><i class="fas fa-paper-plane"></i> Notify</button>
                        </dd>
                        <dt>Unit</dt><dd>${data.type_unit}</dd>
                        <dt>Prioritas</dt><dd>${priorityBadge}</dd>
                        <dt>Kelengkapan</dt><dd>${kelengkapan}</dd>
                        <dt>Keluhan</dt><dd>${data.keterangan || '-'}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h5 class="border-bottom pb-2">Status & Biaya</h5>
                    <dl>
                        <dt>Status</dt><dd><span class="badge badge-info">${data.status_services}</span></dd>
                        <dt>Teknisi</dt><dd>${data.teknisi ? data.teknisi.name : '-'}</dd>
                        <dt>Total Biaya</dt><dd class="h4 text-primary">Rp ${new Intl.NumberFormat('id-ID').format(data.total_biaya)}</dd>
                        <dt>Keamanan</dt>
                        <dd>
                            Type: ${data.tipe_sandi || '-'} <br>
                            Code: <strong>${data.isi_sandi || '-'}</strong>
                        </dd>
                    </dl>
                </div>
            </div>
            
            <hr>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="font-weight-bold">Sparepart Toko</h6>
                        <button class="btn btn-xs btn-primary" onclick="toggleAddPartToko()"><i class="fas fa-plus"></i> Tambah</button>
                    </div>
                    
                    <div id="addPartTokoForm" class="card card-body p-2 mb-2 bg-light" style="display:none;">
                        <div class="form-group mb-2">
                            <select id="selectSparepartToko" class="form-control form-control-sm" style="width:100%"></select>
                        </div>
                        <div class="form-group mb-2">
                            <input type="number" id="qtySparepartToko" class="form-control form-control-sm" placeholder="Qty" value="1" min="1">
                        </div>
                        <button class="btn btn-sm btn-success btn-block" onclick="savePartToko()">Simpan Part Toko</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead class="thead-light"><tr><th>Part</th><th>Qty</th><th>Harga</th><th>Act</th></tr></thead>
                            <tbody>${partTokoHtml}</tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="font-weight-bold">Part Luar / Jasa Lain</h6>
                        <button class="btn btn-xs btn-primary" onclick="toggleAddPartLuar()"><i class="fas fa-plus"></i> Tambah</button>
                    </div>

                    <div id="addPartLuarForm" class="card card-body p-2 mb-2 bg-light" style="display:none;">
                        <div class="form-group mb-2">
                            <input type="text" id="namaPartLuar" class="form-control form-control-sm" placeholder="Nama Part/Jasa">
                        </div>
                        <div class="form-group mb-2">
                            <input type="number" id="hargaPartLuar" class="form-control form-control-sm" placeholder="Harga Jual">
                        </div>
                        <div class="form-group mb-2">
                            <input type="number" id="qtyPartLuar" class="form-control form-control-sm" placeholder="Qty" value="1" min="1">
                        </div>
                        <button class="btn btn-sm btn-success btn-block" onclick="savePartLuar()">Simpan Part Luar</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead class="thead-light"><tr><th>Item</th><th>Qty</th><th>Harga</th><th>Act</th></tr></thead>
                            <tbody>${partLuarHtml}</tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        $('#ticketModalBody').html(html);
        
        // Init Select2 for Sparepart Toko
        if($.fn.select2) {
            $('#selectSparepartToko').select2({
                placeholder: 'Cari Sparepart...',
                dropdownParent: $('#ticketModal'), // Important for modal
                ajax: {
                    url: '{{ route("service_board.search_sparepart") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                }
            });
        }
    }

    function toggleAddPartToko() { $('#addPartTokoForm').slideToggle(); }
    function toggleAddPartLuar() { $('#addPartLuarForm').slideToggle(); }

    function savePartToko() {
        var partId = $('#selectSparepartToko').val();
        var qty = $('#qtySparepartToko').val();
        
        if(!partId) { alert('Pilih sparepart dulu'); return; }
        if(qty < 1) { alert('Qty minimal 1'); return; }
        
        $.ajax({
            url: '{{ route("service_board.add_sparepart_toko") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                kode_services: currentTicketId,
                kode_sparepart: partId,
                qty_part: qty
            },
            success: function(res) {
                if(res.success) {
                    toastr.success(res.message);
                    viewTicket(currentTicketId); // Reload modal
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(err) {
                toastr.error('Error adding part: ' + (err.responseJSON ? err.responseJSON.message : 'Server Error'));
            }
        });
    }

    function deletePartToko(id) {
        if(!confirm('Hapus part ini?')) return;
        $.ajax({
            url: '{{ url("admin/service-board/delete-sparepart-toko") }}/' + id,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if(res.success) {
                    toastr.success(res.message);
                    viewTicket(currentTicketId);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(err) {
                 toastr.error('Error deleting part');
            }
        });
    }

    function savePartLuar() {
        var nama = $('#namaPartLuar').val();
        var harga = $('#hargaPartLuar').val();
        var qty = $('#qtyPartLuar').val();

        if(!nama || !harga) { alert('Lengkapi data part luar'); return; }
        
        $.ajax({
            url: '{{ route("service_board.add_sparepart_luar") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                kode_services: currentTicketId,
                nama_part: nama,
                harga_part: harga,
                qty_part: qty
            },
            success: function(res) {
                if(res.success) {
                    toastr.success(res.message);
                    viewTicket(currentTicketId);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(err) {
                toastr.error('Error adding external part');
            }
        });
    }

    function deletePartLuar(id) {
        if(!confirm('Hapus part luar ini?')) return;
        $.ajax({
            url: '{{ url("admin/service-board/delete-sparepart-luar") }}/' + id,
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if(res.success) {
                    toastr.success(res.message);
                    viewTicket(currentTicketId);
                } else {
                    toastr.error(res.message);
                }
            }
        });
    }

    function printTicket(type, size) {
        if(!currentTicketId) return;
        
        // ensurePrinterConnection handled by printer-thermal.js functions usually, 
        // but we can check global state too.
        
        $.ajax({
            url: '{{ url("admin/service-board/print-data") }}/' + currentTicketId + '/' + type,
            method: 'GET',
            success: async function(res) {
                if(res.success && res.print_data) {
                    if (type === 'label') {
                        // Default to 50x30 if not specified
                        size = size || '50x30';
                        res.print_data.label_size = size;
                        res.print_data.use_gap_mode = true; // Force TSPL/CPCL

                        if (size === '30x20') {
                             res.print_data.label_width_mm = 30;
                             res.print_data.label_height_mm = 20;
                        } else if (size === '33x15') {
                             res.print_data.label_width_mm = 33;
                             res.print_data.label_height_mm = 15;
                        } else {
                             // Standard 50x30
                             res.print_data.label_width_mm = 50;
                             res.print_data.label_height_mm = 30;
                             res.print_data.sticker_height_mm = 30;
                        }
                    }
                    await processPrint(type, res.print_data, size);
                }
            }
        });
    }

    async function processPrint(type, data, size) {
        try {
            if (type === 'ticket') {
                await printServiceTicket(data);
            } else if (type === 'label') {
                if (size === '30x20' || size === '33x15') {
                    await printServiceLabel30x20(data);
                } else {
                    await printServiceSticker(data);
                }
            } else if (type === 'warranty') {
                await printServiceWarranty(data);
            } else {
                alert('Tipe print tidak dikenal');
            }
        } catch (e) {
            console.error(e);
            alert('Printing failed: ' + e.message);
        }
    }
</script>
@endpush
@include('admin.component.footer')
