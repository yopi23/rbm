<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Log Aktivitas Langganan</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Aksi</th>
                            <th>Deskripsi</th>
                            <th>Paket</th>
                            <th>Pelaku</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d M Y, H:i:s') }}</td>
                                <td>{{ $log->user->name ?? 'N/A' }}</td>
                                <td><span class="badge bg-info">{{ $log->action }}</span></td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->subscription->plan->name ?? 'N/A' }}</td>
                                <td>{{ $log->performer->name ?? 'Sistem' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Belum ada aktivitas langganan yang tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{-- Link untuk pagination --}}
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
