<!-- resources/views/admin/page/violations.blade.php -->

@section('violations', 'active')
@section('main', 'menu-is-opening menu-open')

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-danger">
            <div class="card-header">
                <div class="card-title">
                    Pelanggaran Karyawan
                </div>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                        data-target="#modalAddViolation">
                        <i class="fas fa-plus"></i> Tambah Pelanggaran
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="TABLES_1">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>Tipe Pelanggaran</th>
                                <th>Keterangan</th>
                                <th>Denda</th>
                                <th>Status</th>
                                <th>Dicatat Oleh</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($violations as $violation)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ \Carbon\Carbon::parse($violation->violation_date)->format('d M Y') }}</td>
                                    <td>{{ $violation->user->name }}</td>
                                    <td>
                                        @switch($violation->type)
                                            @case('telat')
                                                <span class="badge badge-warning">Terlambat</span>
                                            @break

                                            @case('alpha')
                                                <span class="badge badge-danger">Alpha</span>
                                            @break

                                            @case('kelalaian')
                                                <span class="badge badge-info">Kelalaian</span>
                                            @break

                                            @case('komplain')
                                                <span class="badge badge-secondary">Komplain</span>
                                            @break

                                            @default
                                                <span class="badge badge-dark">Lainnya</span>
                                        @endswitch
                                    </td>
                                    <td>{{ $violation->description }}</td>
                                    <td>
                                        @if ($violation->penalty_amount)
                                            Rp {{ number_format($violation->penalty_amount, 0, ',', '.') }}
                                        @endif
                                        @if ($violation->penalty_percentage)
                                            {{ $violation->penalty_percentage }}%
                                        @endif
                                    </td>
                                    <td>
                                        @switch($violation->status)
                                            @case('pending')
                                                <span class="badge badge-warning">Pending</span>
                                            @break

                                            @case('processed')
                                                <span class="badge badge-success">Diproses</span>
                                            @break

                                            @case('forgiven')
                                                <span class="badge badge-info">Dimaafkan</span>
                                            @break
                                        @endswitch
                                    </td>
                                    <td>{{ $violation->createdBy->name }}</td>
                                    <td>
                                        @if ($violation->status == 'pending')
                                            <button class="btn btn-success btn-xs"
                                                onclick="processViolation({{ $violation->id }}, 'processed')">
                                                <i class="fas fa-check"></i> Proses
                                            </button>
                                            <button class="btn btn-info btn-xs"
                                                onclick="processViolation({{ $violation->id }}, 'forgiven')">
                                                <i class="fas fa-ban"></i> Maafkan
                                            </button>
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

<!-- Modal Tambah Pelanggaran -->
<div class="modal fade" id="modalAddViolation">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Tambah Pelanggaran</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.violations.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Karyawan</label>
                        <select name="user_id" class="form-control" required>
                            <option value="">Pilih Karyawan</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id_user }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Pelanggaran</label>
                        <input type="date" name="violation_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Tipe Pelanggaran</label>
                        <select name="type" class="form-control" required>
                            <option value="">Pilih Tipe</option>
                            <option value="telat">Terlambat</option>
                            <option value="alpha">Alpha</option>
                            <option value="kelalaian">Kelalaian</option>
                            <option value="komplain">Komplain</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="description" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Denda (Nominal)</label>
                        <input type="number" name="penalty_amount" class="form-control"
                            placeholder="Kosongkan jika menggunakan persentase">
                    </div>
                    <div class="form-group">
                        <label>Denda (Persentase)</label>
                        <input type="number" name="penalty_percentage" class="form-control" min="0"
                            max="100" placeholder="Kosongkan jika menggunakan nominal">
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function processViolation(violationId, status) {
        if (confirm('Apakah Anda yakin?')) {
            $.ajax({
                url: "{{ route('admin.violations.update-status') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    violation_id: violationId,
                    status: status
                },
                success: function(response) {
                    window.location.reload();
                }
            });
        }
    }
</script>
