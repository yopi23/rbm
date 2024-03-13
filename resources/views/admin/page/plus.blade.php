@section('page', $page)

@include('admin.component.header')

@include('admin.component.navbar')
@include('admin.component.sidebar')

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">@yield('page')</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active">@yield('page')</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-8">
                    <div class="row">
                        {{-- pembelian --}}
                        <div class="col-md-12">
                            <div class="card card-outline card-success">
                                <div class="card-header">
                                    <div class="card-title">
                                        Pembelian
                                    </div>
                                    <div class="card-tools">
                                        <button class="btn btn-danger my-2" onclick="hapusSemuaSparepart()">Hapus
                                            Semua</button>
                                        <a href="#" class="btn btn-success" data-toggle="modal"
                                            data-target="#modal_sparepart" name="tambah_sparepart" id="tambah_sparepart"
                                            onclick="resetFormSparepart()"><i class="fas fa-plus"></i></a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped border" id="tableSparepart">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>nama barang</th>
                                                    <th>kode</th>
                                                    <th>stock</th>
                                                    <th>Opsi</th>
                                                </tr>

                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- end pembelian --}}
                        {{-- restock --}}
                        <div class="col-md-12">
                            <div class="card card-outline card-primary">
                                <div class="card-header">
                                    <div class="card-title">
                                        Restock
                                    </div>
                                    <div class="card-tools">
                                        <button class="btn btn-danger my-2" onclick="hapusSemuaRSparepart()">Hapus
                                            Semua</button>
                                        <a href="#" class="btn btn-primary" data-toggle="modal"
                                            data-target="#modal_restock" name="restock" id="restock"
                                            onclick="resetFormSparepart()"><i class="fas fa-plus"></i></a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped border" id="listrestock">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>nama barang</th>
                                                    <th>kode</th>
                                                    <th>stock</th>
                                                    <th>Opsi</th>
                                                </tr>

                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- end restock --}}
                    </div>
                </div>
                <div class="col-md-4">
                    <form action="" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card card-outline card-success">
                                    <div class="card-body">
                                        <input type="hidden" name="total_penjualan" id="total_penjualan"
                                            class="form-control" value="">
                                        <div class="form-group">
                                            <label for="kategori">Kategori</label>
                                            <select name="kategori" id="kategori" class="form-control" required>
                                                <option value="" disabled selected style="color: #a9a9a9;">---
                                                    Pilih ---</option>
                                                @foreach ($kategori as $item)
                                                    <option value="{{ $item->id }}">{{ $item->nama_kategori }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">
                                                Pilih kategori.
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Supplier</label>
                                            <select name="supplier" id="supplier" class="form-control" required>
                                                <option value="" disabled selected style="color: #a9a9a9;">---
                                                    Pilih ---</option>
                                                @foreach ($supplier as $item)
                                                    <option value="{{ $item->id }}">{{ $item->nama_supplier }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback">
                                                Pilih supplier.
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <button type="submit" name="upload"
                                                class="form-control btn btn-success"><i
                                                    class="fas fa-cash-register"></i>
                                                Simpan</button>
                                        </div>
                                        <div class="form-group">
                                            <a href="{{ route('update.harga.ecer') }}" class="btn btn-primary">Perbarui
                                                Harga Ecer</a>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

            </div>

            <div class="modal fade" id="modal_sparepart">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modalTitle">Tambah Sparepart</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Formulir untuk menambah/edit data sparepart -->
                            <form id="formSparepart">
                                @csrf
                                <div class="form-group">
                                    <label>Nama Barang</label>
                                    <input type="text" name="nama_barang" id="nama_barang" class="form-control"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label>Kode</label>
                                    <input type="text" name="kode_barang" id="kode_barang" class="form-control"
                                        required>
                                </div>
                                <div class="form-group">
                                    <label>Stok</label>
                                    <input type="number" name="stok_barang" id="stok_barang" class="form-control"
                                        required>
                                </div>

                            </form>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                            <button type="button" class="btn btn-success" onclick="simpanSparepart()"
                                id="btnSimpanSparepart">Simpan</button>
                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

            <div class="modal fade" id="modalEditSparepart">
                <!-- ... (kode modal edit) ... -->
            </div>
            {{-- modal restock --}}
            <div class="modal fade" id="modal_restock">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modalTitle">Restock Sparepart</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <!-- Formulir untuk menambah/edit data sparepart -->
                            <form id="formRestockSparepart">
                                @csrf
                                <div class="form-group">
                                    <label>Cari di sini</label>
                                    <input type="text" name="caripart" id="caripart" class="form-control"
                                        oninput="cariSparepart()" autocomplete="off">

                                </div>
                                <div class="card">
                                    <div class="card-body"style="max-height: 300px; overflow-y: auto;">
                                        <table class="table table-striped " id="tablerestock">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nama Barang</th>
                                                    <th>Stock</th>
                                                    <th>Kode</th>
                                                    <th>Restock</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>

                        </div>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>

            {{-- modal restock --}}
        </div>
    </section>
    <!-- /.content -->
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

<script>
    $(document).ready(function() {
        tampilkanDataTabel();
    });


    function manageSpareparts() {
        const dataSparepartsKey = 'dataSpareparts';
        let dataSpareparts = JSON.parse(localStorage.getItem(dataSparepartsKey)) || [];

        function saveData() {
            localStorage.setItem(dataSparepartsKey, JSON.stringify(dataSpareparts));
        }

        function getAllSpareparts() {
            return dataSpareparts;
        }

        function getSparepart(index) {
            return dataSpareparts[index];
        }

        function addSparepart(sparepart) {
            dataSpareparts.push(sparepart);
            saveData();
        }

        function updateSparepart(index, updatedSparepart) {
            dataSpareparts[index] = updatedSparepart;
            saveData();
        }

        function deleteSparepart(index) {
            dataSpareparts.splice(index, 1);
            saveData();
        }

        function deleteSemuaSparepart() {
            // Menghapus semua data sparepart
            dataSpareparts = [];
            saveData();
        }

        return {
            getAllSpareparts,
            getSparepart,
            addSparepart,
            updateSparepart,
            deleteSparepart,
            deleteSemuaSparepart
        };
    }

    const sparepartManager = manageSpareparts();

    // Tambahkan fungsi resetFormSparepart
    function resetFormSparepart() {
        $("#formSparepart")[0].reset();
        $("#btnSimpanSparepart").removeAttr("data-index");
        $("#btnSimpanSparepart").text("Simpan").removeClass("btn-primary").addClass("btn-success");
        $("#modalTitle").text("Tambah Sparepart");


    }


    // Perbarui fungsi simpanSparepart
    function simpanSparepart() {
        const namaBarang = $("#nama_barang").val();
        const kodeBarang = $("#kode_barang").val();
        const stokBarang = $("#stok_barang").val();
        // Validasi form sebelum submit
        if (namaBarang === '' || kodeBarang === '' || stokBarang === '') {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Harap lengkapi semua kolom form.',
            });
            return;
        }
        const sparepart = {
            nama: namaBarang,
            kode: kodeBarang,
            stok: stokBarang
        };

        const index = $("#btnSimpanSparepart").attr("data-index");

        if (index === undefined) {
            // Jika tidak ada indeks, tambah sparepart
            sparepartManager.addSparepart(sparepart);

            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data ' + namaBarang + ' berhasil ditambahkan.',
                showConfirmButton: false,
                timer: 2000,
            });
        } else {
            // Jika ada indeks, update sparepart
            sparepartManager.updateSparepart(index, sparepart);

            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Berhasil!',
                text: 'Data ' + namaBarang + ' berhasil diubah.',
                showConfirmButton: false,
                timer: 2000,
                customClass: {
                    popup: 'sweet-alert-center'
                }
            });
        }

        tampilkanDataTabel();
        resetFormSparepart();
    }

    // Tambahkan event listener untuk menangani penutupan modal
    $("#modal_sparepart").on("hidden.bs.modal", function() {
        resetFormSparepart();
    });

    function tampilkanDataTabel() {
        $("#tableSparepart tbody").empty();

        const allSpareparts = sparepartManager.getAllSpareparts();

        for (let i = 0; i < allSpareparts.length; i++) {
            const newRow = `<tr>
        <td>${i + 1}</td>
        <td>${allSpareparts[i].nama}</td>
        <td>${allSpareparts[i].kode}</td>
        <td>${allSpareparts[i].stok}</td>
        <td>
            <button class="btn btn-success mb-2" onclick="editSparepart(${i})" data-index="${i}">Edit</button>
            <button class="btn btn-danger mb-2" onclick="hapusSparepart(${i})">Hapus</button>
        </td>
        </tr>`;

            $("#tableSparepart tbody").append(newRow);
        }
    }

    function editSparepart(index) {
        const sparepart = sparepartManager.getSparepart(index);

        // Mengisi nilai form dengan data dari sparepart yang akan di-edit
        $("#nama_barang").val(sparepart.nama);
        $("#kode_barang").val(sparepart.kode);
        $("#stok_barang").val(sparepart.stok);

        // Menyimpan indeks yang sedang diedit sebagai data tambahan pada tombol "Simpan"
        $("#btnSimpanSparepart").attr("data-index", index);

        // Mengubah judul modal
        $("#modalTitle").text("Edit Sparepart");

        // Mengubah teks dan warna tombol
        $("#btnSimpanSparepart").text("Update").removeClass("btn-success").addClass("btn-primary");

        // Menampilkan kembali modal edit
        $("#modal_sparepart").modal('show');
    }

    function simpanEditSparepart() {
        const index = $("#btnSimpanSparepart").attr("data-index");
        if (index === undefined) {
            // Tidak ada indeks yang disimpan, mungkin karena belum pernah mengklik tombol "Edit"
            return;
        }

        const namaBarang = $("#nama_barang").val();
        const kodeBarang = $("#kode_barang").val();
        const stokBarang = $("#stok_barang").val();

        // Update data pada array dataSpareparts
        const updatedSparepart = {
            nama: namaBarang,
            kode: kodeBarang,
            stok: stokBarang
        };

        sparepartManager.updateSparepart(index, updatedSparepart);

        // Tampilkan notifikasi bahwa data berhasil diubah
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: 'Berhasil!',
            text: 'Data ' + namaBarang + ' berhasil diubah.',
            showConfirmButton: false,
            timer: 2000,
            customClass: {
                popup: 'sweet-alert-center' // Menambahkan class CSS untuk styling
            }
        });

        tampilkanDataTabel();
        $("#modal_sparepart").modal('hide');
        $("#formSparepart")[0].reset();
    }


    function hapusSparepart(index) {
        const namaBarang = sparepartManager.getSparepart(index).nama;

        // Konfirmasi penghapusan
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: 'Data ' + namaBarang + ' akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
        }).then((result) => {
            if (result.isConfirmed) {
                sparepartManager.deleteSparepart(index);

                // Tampilkan notifikasi bahwa data berhasil dihapus
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Data ' + namaBarang + ' berhasil dihapus.',
                    showConfirmButton: false,
                    timer: 2000,
                });

                tampilkanDataTabel();
            }
        });
    }



    function hapusSemuaSparepart() {
        // Konfirmasi penghapusan semua data
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: 'Semua data sparepart akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus Semua!',
        }).then((result) => {
            if (result.isConfirmed) {
                // Memanggil fungsi di manager untuk menghapus semua sparepart
                sparepartManager.deleteSemuaSparepart();

                // Tampilkan notifikasi bahwa semua data berhasil dihapus
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Semua data sparepart berhasil dihapus.',
                    showConfirmButton: false,
                    timer: 2000,
                });

                // Perbarui tampilan tabel setelah penghapusan
                tampilkanDataTabel();
            }
        });
    }
</script>

<script>
    $(document).ready(function() {
        $('#modal_restock').on('shown.bs.modal', function() {
            $('#caripart').focus();
        });
    });
    $(document).ready(function() {
        $("#caripart").on("input", function() {
            cariSparepart();
        });
    });

    function cariSparepart() {
        const cariPart = $("#caripart").val().toLowerCase();
        const sparepartData = <?php echo json_encode($sparepart); ?>;

        const hasilPencarian = sparepartData.filter(sparepart => {
            return sparepart.nama_sparepart.toLowerCase().includes(cariPart);
        });

        tampilkanDataTabelRestock(hasilPencarian);
    }

    function tampilkanDataTabelRestock(data) {
        $("#tablerestock tbody").empty();

        for (let i = 0; i < data.length; i++) {
            const newRow = `<tr>
                <td>${i + 1}</td>
                <td style="max-width:200px;">${data[i].nama_sparepart}</td>
                <td>${data[i].stok_sparepart}</td>
                <td style="max-width:150px;">
                    <input class="form-control" id="kode${i}" autocomplete="off" placeholder="Masukan kode" >
                </td>
                <td style="max-width:20px;">
                    <input class="form-control" id="jumlah${i}" autocomplete="off" placeholder="Jumlah">
                </td>
                <td>
                    <button class="btn btn-success"
                    nabar="${data[i].nama_sparepart}"
                    data-id="${data[i].id}" data-kode="${data[i].kode_harga}"
                    data-index="${i}" onclick="restockSparepart(event,this)">Tambahkan</button>
                </td>
            </tr>`;

            $("#tablerestock tbody").append(newRow);
        };
    }
</script>


<script>
    function manageListRSpareparts() {
        const dataListRSparepartsKey = 'dataListRSpareparts';
        let dataListRSpareparts = JSON.parse(localStorage.getItem(dataListRSparepartsKey)) || [];

        function saveDataList() {
            localStorage.setItem(dataListRSparepartsKey, JSON.stringify(dataListRSpareparts));
        }

        function getAllRSpareparts() {
            return dataListRSpareparts;
        }

        function addRSparepart(listsparepart) {
            // Periksa apakah nama barang sudah ada dalam data lokal
            const isDuplicate = dataListRSpareparts.some(item => item.nama_sparepart === listsparepart.nama_sparepart);

            // Jika sudah ada, tampilkan pesan peringatan dengan SweetAlert dan hentikan penambahan data baru
            if (isDuplicate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops...',
                    text: 'Nama barang sudah ada. Harap pilih nama barang yang berbeda.',
                });
                return;
            }
            dataListRSpareparts.push(listsparepart);
            saveDataList();
        }

        function updateRSparepart(index, updatedSparepart) {
            dataListRSpareparts[index] = updatedSparepart;
            saveDataList();
        }

        function deleteRSparepart(index) {
            dataListRSpareparts.splice(index, 1);
            saveDataList();
        }

        function deleteSemuaRSparepart() {
            // Menghapus semua data sparepart
            dataListRSpareparts = [];
            saveDataList
        }

        return {
            getAllRSpareparts,
            addRSparepart,
            updateRSparepart,
            deleteRSparepart,
            deleteSemuaRSparepart
        };
    }

    const listSparepartManager = manageListRSpareparts();

    function simpanDataTabelKedua(data) {
        listSparepartManager.addRSparepart({
            id: data.id,
            nama_sparepart: data.nama_sparepart,
            kode_harga: data.kode_harga,
            stok_sparepart: data.stok_sparepart,
            // Misalnya, kita anggap setiap restock menambah satu stok

        });
        Swal.fire({
            position: 'center',
            icon: 'success',
            title: 'Berhasil!',
            text: 'Data ' + data.nama_sparepart + ' berhasil ditambahkan.',
            showConfirmButton: false,
            timer: 2000,
        });
    }

    function tampilkanDataListRestock() {
        $("#listrestock tbody").empty();

        const dataTabelKedua = listSparepartManager.getAllRSpareparts();

        for (let i = 0; i < dataTabelKedua.length; i++) {
            // Mengatur panjang maksimal teks yang ditampilkan, misalnya 100 karakter
            const maxPanjangTeks = 100;

            // Memotong nama_sparepart jika melebihi panjang maksimal
            const trimmedNamaSparepart = dataTabelKedua[i].nama_sparepart.length > maxPanjangTeks ?
                dataTabelKedua[i].nama_sparepart.substring(0, maxPanjangTeks) + '...' :
                dataTabelKedua[i].nama_sparepart;

            const newRow = `<tr>
                <td>${i + 1}</td>
                <td title="${dataTabelKedua[i].nama_sparepart}" style="max-width: 150px;min-width: 150px;" >
                    ${trimmedNamaSparepart}
                </td>
                <td>${dataTabelKedua[i].kode_harga}</td>
                <td>${dataTabelKedua[i].stok_sparepart}</td>
                <td>
                    <button class="btn btn-danger" onclick="hapusDataListRestock(${i})">Hapus</button>
                </td>
            </tr>`;

            $("#listrestock tbody").append(newRow);
        }
    }

    function restockSparepart(event, buttonElement) {
        event.preventDefault();
        const index = buttonElement.getAttribute('data-index');
        const kodeInputValue = $("#kode" + index).val();
        const jumlahInputValue = $("#jumlah" + index).val();
        if (jumlahInputValue == '' | kodeInputValue == '') {
            Swal.fire({
                icon: 'warning',
                title: 'Oops...',
                text: 'Harap isi kode dan jumlah Restock',
            });
            return;
        }
        const dataRestock = {
            id: buttonElement.getAttribute('data-id'),
            nama_sparepart: buttonElement.getAttribute('nabar'),
            // kode_harga: buttonElement.getAttribute('data-kode'),
            kode_harga: kodeInputValue,
            stok_sparepart: jumlahInputValue,
        };

        simpanDataTabelKedua(dataRestock);
        tampilkanDataListRestock();
    }

    function hapusDataListRestock(index) {
        listSparepartManager.deleteRSparepart(index);
        tampilkanDataListRestock();
    }

    $(document).ready(function() {
        // Periksa apakah ada data di penyimpanan lokal
        const savedData = listSparepartManager.getAllRSpareparts();

        // Jika ada, tampilkan data di tabel restock
        if (savedData.length > 0) {
            tampilkanDataListRestock();
        }
    });

    // Fungsi untuk menghapus semua sparepart
    function hapusSemuaRSparepart() {
        // Konfirmasi penghapusan semua data
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: 'Semua data sparepart akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus Semua!',
        }).then((result) => {
            if (result.isConfirmed) {
                // Memanggil fungsi di manager untuk menghapus semua sparepart
                listSparepartManager.deleteSemuaRSparepart();

                // Tampilkan notifikasi bahwa semua data berhasil dihapus
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Semua data sparepart berhasil dihapus.',
                    showConfirmButton: false,
                    timer: 2000,
                });

                // Perbarui tampilan tabel setelah penghapusan
                tampilkanDataListRestock();
            }
        });
    }
</script>

<script>
    $(document).ready(function() {
        // Tambahkan event submit pada formulir
        $("form").submit(function(e) {
            e.preventDefault(); // Mencegah formulir dikirim secara default
            simpanDataKeServer();
        });
    });

    function simpanDataKeServer() {
        const dataToSend = {
            // Sesuaikan data yang akan dikirim ke server
            _token: "{{ csrf_token() }}",
            kode_kategori: $("#kategori").val(),
            kode_supplier: $("#supplier").val(),
            spareparts: sparepartManager.getAllSpareparts(),
            restocks: listSparepartManager.getAllRSpareparts(),
        };

        // Kirim permintaan AJAX ke server
        $.ajax({
            type: "POST",
            url: "/plusUpdate", // Gantilah dengan URL endpoint sesuai dengan struktur server Anda
            data: JSON.stringify(dataToSend),
            contentType: "application/json; charset=utf-8",

            success: function(response) {
                // Tambahkan logika atau respons yang sesuai dengan kebutuhan Anda
                console.log(response);
                Swal.fire({
                    position: 'center',
                    icon: 'success',
                    title: 'Berhasil!',
                    text: 'Data berhasil disimpan ke server.',
                    showConfirmButton: false,
                    timer: 2000,
                });
                // Lakukan tindakan lain setelah data disimpan ke server
            },
            error: function(error) {
                // Tambahkan logika atau respons yang sesuai dengan kebutuhan Anda
                console.error(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Terjadi kesalahan saat menyimpan data ke server.',
                });
            }
        });
    }
</script>




@include('admin.component.footer')
