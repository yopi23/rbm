<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Daftar Hutang Belum Lunas</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="hutang-table">
                    <thead>
                        <tr>
                            <th>Nota</th>
                            <th>Supplier</th>
                            <th>Total Hutang</th>
                            <th>Jatuh Tempo</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hutang as $item)
                            <tr>
                                <td>{{ $item->kode_nota }}</td>
                                <td>{{ $item->supplier->nama_supplier ?? 'N/A' }}</td>
                                <td>Rp {{ number_format($item->total_hutang, 0, ',', '.') }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->tgl_jatuh_tempo)->format('d/m/Y') }}</td>
                                <td>
                                    {{-- PERUBAHAN: Hapus onsubmit dan tambahkan class --}}
                                    <form action="{{ route('hutang.bayar', $item->id) }}" method="POST"
                                        class="form-pelunasan">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">Bayar Lunas</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada hutang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

{{-- SCRIPT BARU: Tambahkan script ini di akhir file --}}
<script>
    $(function() {
        const hutangTable = $('#hutang-table').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" // Terjemahan Bahasa Indonesia
            }
        });
        // Target semua form dengan class 'form-pelunasan'
        $('.form-pelunasan').on('submit', function(e) {
            // Hentikan aksi default form (yaitu submit langsung)
            e.preventDefault();
            const form = this; // Simpan referensi ke form yang di-submit

            Swal.fire({
                title: 'Konfirmasi Pelunasan',
                html: "Anda yakin ingin melunasi hutang ini?<br><strong>Transaksi pengeluaran akan dicatat di buku besar.</strong>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745', // Warna hijau, sesuai tombol
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Lakukan Pelunasan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                // Jika user mengklik tombol konfirmasi
                if (result.isConfirmed) {
                    // Lanjutkan proses submit form
                    form.submit();
                }
            });
        });
    });
</script>
