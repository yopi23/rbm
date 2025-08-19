<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Paket Langganan</h3>
            <div class="card-tools">
                <a href="{{ route('administrator.tokens.plans.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Paket Baru
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 10px">#</th>
                        <th>Nama Paket</th>
                        <th>Harga</th>
                        <th>Durasi</th>
                        <th style="width: 150px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $key => $plan)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $plan->name }}</td>
                            <td>Rp {{ number_format($plan->price, 0, ',', '.') }}</td>
                            <td>{{ $plan->duration_in_months }} Bulan</td>
                            <td>
                                <a href="{{ route('administrator.tokens.plans.edit', $plan->id) }}"
                                    class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('administrator.tokens.plans.destroy', $plan->id) }}"
                                    method="POST" class="d-inline"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus paket ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Belum ada paket yang dibuat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
