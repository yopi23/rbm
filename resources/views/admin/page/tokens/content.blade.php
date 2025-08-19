<div class="container-fluid">
    {{-- Form Generate Token --}}
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">Generate Token Langganan Baru</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('administrator.tokens.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <select name="plan_id" class="form-select" required>
                            <option value="">-- Pilih Paket --</option>
                            @foreach ($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }} (Rp
                                    {{ number_format($plan->price) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Generate Token</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabel Riwayat Token --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Riwayat Token yang Dibuat</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Token</th>
                            <th>Paket</th>
                            <th>Status</th>
                            <th>Digunakan Oleh</th>
                            <th>Tanggal Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tokens as $token)
                            <tr>
                                <td><code>{{ $token->token }}</code></td>
                                <td>{{ $token->plan->name }}</td>
                                <td>
                                    @if ($token->is_used)
                                        <span class="badge bg-success">Sudah Digunakan</span>
                                    @else
                                        <span class="badge bg-warning">Belum Digunakan</span>
                                    @endif
                                </td>
                                <td>{{ $token->usedBy->name ?? '-' }}</td>
                                <td>{{ $token->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Belum ada token yang dibuat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $tokens->links() }}
            </div>
        </div>
    </div>
</div>
