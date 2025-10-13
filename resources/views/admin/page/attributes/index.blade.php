
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Manajemen Atribut Varian</h3>
        <div class="card-tools">
            <a href="{{ route('attributes.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Tambah Atribut Baru
            </a>
        </div>
    </div>
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-check"></i> Sukses!</h5>
                {{ session('success') }}
            </div>
        @endif

        <table class="table table-bordered table-striped" id="attributesTable">
            <thead>
                <tr>
                    <th style="width: 10px">#</th>
                    <th>Nama Atribut</th>
                    <th>Kategori Terkait</th>
                    <th style="width: 120px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attributes as $attribute)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $attribute->name }}</td>
                        <td>
                            <span
                                class="badge badge-info">{{ $attribute->kategori->nama_kategori ?? 'Tidak ada kategori' }}</span>
                        </td>
                        <td>
                            <form action="{{ route('attributes.destroy', $attribute->id) }}" method="POST"
                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus atribut ini? Semua nilainya juga akan terhapus.');">
                                @csrf
                                @method('DELETE')
                                <a href="{{ route('attributes.edit', $attribute->id) }}"
                                    class="btn btn-warning btn-sm my-1">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="submit" class="btn btn-danger btn-sm my-1">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">Belum ada atribut yang dibuat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Tambahkan inisialisasi DataTable jika Anda menggunakannya --}}
<script>
    $(function() {
        $('#attributesTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
        });
    });
</script>
