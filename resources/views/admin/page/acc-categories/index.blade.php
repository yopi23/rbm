<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">

        <a href="{{ route('accessory-categories.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Kategori
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Kategori</th>
                        <th>Jenis</th>
                        <th>Jumlah Produk</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->type }}</td>
                            <td>{{ $category->accessoryStocks->count() }}</td>
                            <td>
                                <a href="{{ route('accessory-categories.edit', $category->id) }}"
                                    class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('accessory-categories.destroy', $category->id) }}" method="POST"
                                    class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Hapus kategori ini?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $categories->links() }}
        </div>
    </div>
</div>
