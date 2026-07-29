<style>
    .tg-index-card {
        background: #ffffff;
        border-radius: 16px;
        border: none;
        box-shadow: 0 10px 30px rgba(92, 75, 153, 0.08);
        overflow: hidden;
        margin-bottom: 24px;
    }
    .tg-index-header {
        background: linear-gradient(135deg, #5C4B99 0%, #7D6EC4 100%);
        color: #ffffff;
        padding: 16px 24px;
    }
    .tg-code-badge {
        background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
        color: #ffffff;
        font-weight: 700;
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3);
    }
    .tg-table th {
        background: #F8FAFC;
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px;
    }
    .tg-table td {
        padding: 14px 16px;
        vertical-align: middle;
    }
</style>

<div class="container-fluid py-3">
    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1" style="color: #1E293B;"><i class="bi bi-shield-check text-primary me-2"></i>Manajemen Data HP & Antigores</h3>
            <p class="text-muted small mb-0">Kelola master tipe HP, ukuran layar, posisi kamera, dan kelompok cetakan antigores (TG).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.tg.create') }}" class="btn btn-primary btn-lg rounded-3 shadow-sm px-4 fw-bold" style="background: linear-gradient(135deg, #5C4B99 0%, #7D6EC4 100%); border: none;">
                ➕ Tambah Data HP Baru
            </a>
            <a href="{{ route('admin.tg.cross-table') }}" class="btn btn-outline-secondary btn-lg rounded-3 px-3">
                📊 Tabel Silang (Cross-Table)
            </a>
        </div>
    </div>

    @foreach ($groups as $groupName => $positions)
        <div class="card tg-index-card">
            <div class="tg-index-header d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold"><i class="bi bi-camera me-2"></i>Grup Kamera: {{ $groupName ?? 'Ungrouped' }}</h5>
            </div>
            <div class="card-body p-4">
                @foreach ($positions as $position)
                    <div class="d-flex align-items-center gap-2 mb-3 mt-2">
                        <span class="badge bg-light text-dark border px-3 py-2 fs-6 fw-bold">
                            📷 Posisi Kamera: {{ $position->position }}
                        </span>
                        <span class="text-muted small">({{ $position->hpDatas->count() }} Tipe HP)</span>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table align-middle tg-table border rounded-3 overflow-hidden">
                            <thead>
                                <tr>
                                    <th style="width: 140px;">Kode TG</th>
                                    <th style="width: 160px;">Brand</th>
                                    <th>Tipe HP</th>
                                    <th style="width: 160px;">Ukuran Layar</th>
                                    <th style="width: 140px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($position->hpDatas as $hp)
                                    <tr>
                                        <td>
                                            @if($hp->code_tg)
                                                <span class="tg-code-badge">{{ $hp->code_tg }}</span>
                                            @else
                                                <span class="badge bg-secondary text-white">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark">{{ $hp->brand ? $hp->brand->name : 'Umum' }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-6 fw-semibold text-primary">{{ $hp->type }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-purple text-purple border px-2 py-1" style="background: #F3E8FF; color: #6B21A8; border-color: #E9D5FF !important;">
                                                📏 {{ $hp->screenSize ? $hp->screenSize->size : '-' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('admin.tg.edit', $hp->id) }}" class="btn btn-outline-warning" title="Edit">
                                                    ✏️ Edit
                                                </a>
                                                <form action="{{ route('admin.tg.destroy', $hp->id) }}" method="POST" style="display:inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Yakin ingin menghapus data HP ini?')" title="Hapus">
                                                        🗑️
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
