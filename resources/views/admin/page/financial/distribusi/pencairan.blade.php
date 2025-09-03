<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Total Saldo Alokasi Tersedia</h3>
                    </div>
                    <div class="card-body">
                        @php
                            $roles = [
                                'owner' => 'Owner',
                                'investor' => 'Investor',
                                'karyawan_bonus' => 'Bonus Karyawan',
                                'kas_aset' => 'Kas Aset',
                            ];
                        @endphp
                        <ul class="list-group">
                            @foreach ($roles as $key => $label)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $label }}
                                    <span class="badge badge-primary badge-pill">Rp
                                        {{ number_format($saldoTersedia[$key] ?? 0) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">Form Pencairan Dana</h3>
                    </div>
                    <form action="{{ route('distribusi.prosesPencairan') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>Cairkan Dana Dari Pos</label>
                                <select name="role" class="form-control" required>
                                    <option value="">-- Pilih Pos --</option>
                                    @foreach ($roles as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Jumlah Penarikan (Rp)</label>
                                <input type="number" name="jumlah" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success">Proses Pencairan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
