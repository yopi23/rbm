@section('page', 'Todo List')

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

                        {{-- All Service --}}
                        <div class="col-md-12">
                            <div class="card card-outline card-success">
                                <div class="card-header">
                                    <div class="card-title">
                                        <strong> Service</strong>
                                    </div>
                                    <div class="card-tools">
                                        <button class="btn btn-danger my-2" onclick="hapusSemuaRSparepart()">Hapus
                                            Semua</button>
                                        <a href="#" class="btn btn-success" data-toggle="modal"
                                            data-target="#modal_job" name="restock" id="restock"><i
                                                class="fas fa-plus"></i></a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped border" id="listjob">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nama Pelanggan</th>
                                                    <th>Keterangan</th>
                                                    <th>Part</th>
                                                    <th>Biaya</th>
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
                        {{-- end All Service --}}
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
                                            <label for="kategori">Teknisi</label>
                                            <select name="teknisi" id="teknisi" class="form-control" required>
                                                <option value="" disabled selected style="color: #a9a9a9;">---
                                                    Pilih ---</option>
                                                @if (isset($user))
                                                    @foreach ($user as $users)
                                                        <option class="my2" value="{{ $users->kode_user }}">
                                                            {{ $users->fullname }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div class="invalid-feedback">
                                                Pilih Teknisi.
                                            </div>
                                        </div>


                                        <div class="form-group">

                                            <input type="hidden" name="status_services" id="status_services"
                                                value="Selesai">
                                            <button type="submit" name="selesaikan"
                                                class="form-control btn btn-success">
                                                <i class="fa fa-cogs"></i> Selesaikan
                                            </button>

                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>

            </div>

            {{-- modal Service --}}
            <div class="modal fade" id="modal_job">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title" id="modalTitle">Data Service</h4>
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
                                    <input type="text" name="carijob" id="carijob" class="form-control"
                                        oninput="cariService()" autocomplete="off">

                                </div>
                                <div class="card">
                                    <div class="card-body"style=" max-height: 300px; overflow-y: auto;">
                                        <table class="table table-striped " id="tablejob">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Type</th>
                                                    <th>Nama Pelanggan</th>
                                                    <th>Keterangan</th>
                                                    <th>Part</th>
                                                    <th>Biaya</th>
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

            {{-- modal Service --}}
        </div>
        {{-- list hari ini --}}
        <div class="container-fluid">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <div class="card-title">
                        <strong>Selesai <?php echo $today; ?></strong>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped border">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama</th>
                                    <th>Device</th>
                                    <th>Keterangan</th>
                                    <th>Biaya</th>
                                    <th>Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($data_selesai_hari_ini->isEmpty())
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data yang ditemukan.</td>
                                    </tr>
                                @else
                                    @foreach ($data_selesai_hari_ini as $index => $service)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $service->nama_pelanggan }}</td>
                                            <td>{{ $service->type_unit }}</td>
                                            <td>{{ $service->keterangan }}</td>
                                            <td>{{ number_format($service->total_biaya, 0, ',', '.') }}</td>
                                            <td><a href="{{ route('nota_tempel_selesai', $service->id) }}"
                                                    target="_blank" class="btn btn-sm btn-primary mt-2">
                                                    <i class="fas fa-print"></i>
                                                </a>

                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

</div>
</section>
<!-- /.content -->
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

{{-- untuk melakukan pencarian service --}}
<script>
    $(document).ready(function() {
        $('#modal_job').on('shown.bs.modal', function() {
            $('#carijob').focus();
        });

        $("#carijob").on("input", function() {
            cariService();
        });
    });

    function cariService() {

        const cariJob = $("#carijob").val().toLowerCase();
        const serviceData = <?php echo json_encode($data_service); ?>;

        const hasilPencarian = serviceData.filter(service => {
            return service.nama_pelanggan.toLowerCase().includes(cariJob) ||
                service.type_unit.toLowerCase().includes(cariJob);

        });

        tampilkanDataTabelService(hasilPencarian);
    }

    function tampilkanDataTabelService(data) {
        $("#tablejob tbody").empty();

        for (let i = 0; i < data.length; i++) {
            // const totalPart = (parseFloat(data[i].detail_harga_part_service) || 0) *
            //     (parseFloat(data[i].qty_part_toko) || 0) +
            //     (parseFloat(data[i].harga_part) || 0) *
            //     (parseFloat(data[i].qty_part_luar) || 0);

            const formattedTotalPart = parseFloat(data[i].total_harga_part).toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR'
            });
            const formattedTotalBiaya = parseFloat(data[i].total_biaya).toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR'
            });

            const newRow = `<tr>
                <td>${i + 1}</td>
                <td>${data[i].type_unit}</td>
                <td>${data[i].nama_pelanggan}</td>
                <td>${data[i].keterangan}</td>
                <td>${formattedTotalPart}</td>
                <td>${formattedTotalBiaya}</td>
                <td>
                    <button class="btn btn-success"
                    napel="${data[i].nama_pelanggan}"
                    data-type="${data[i].type_unit}"
                    data-kode="${data[i].kode_service}"
                    data-id="${data[i].id_service}"
                    data-biaya="${data[i].total_biaya}"
                    data-ket="${data[i].keterangan}"
                    data-part="${data[i].total_harga_part}"
                    data-index="${i}" onclick="saveService(event,this)">
                    Tambahkan
                    </button>
                </td>
            </tr>`;

            $("#tablejob tbody").append(newRow);
        }
    }
    // pengelola data lokal
    function manageListService() {
        const savedServicesKey = 'savedServices';
        let savedServices = JSON.parse(localStorage.getItem(savedServicesKey)) || [];

        function saveDataList() {
            localStorage.setItem(savedServicesKey, JSON.stringify(savedServices));
        }

        function getAllServices() {
            return savedServices;
        }

        function addService(service) {
            // Periksa apakah service dengan ID yang sama sudah ada dalam data lokal
            const isDuplicate = savedServices.some(item => item.id_service === service.id_service);

            // Jika sudah ada, tampilkan pesan peringatan dengan SweetAlert dan hentikan penambahan data baru
            if (isDuplicate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops...',
                    text: 'Service dengan ID yang sama sudah ada. Harap pilih ID service yang berbeda.',
                });
                return;
            }
            savedServices.push(service);
            saveDataList();
        }

        function updateService(index, updatedService) {
            savedServices[index] = updatedService;
            saveDataList();
        }

        function deleteService(index) {
            savedServices.splice(index, 1);
            saveDataList();
        }

        function deleteAllServices() {
            // Menghapus semua data service
            savedServices = [];
            saveDataList();
        }

        return {
            getAllServices,
            addService,
            updateService,
            deleteService,
            deleteAllServices
        };
    }

    const serviceManager = manageListService();


    // end pengelola data local
    function saveService(event, button) {
        const idService = button.getAttribute('data-id');

        // Memeriksa apakah data dengan id service yang sama sudah ada dalam penyimpanan lokal
        const savedServices = JSON.parse(localStorage.getItem('savedServices')) || [];
        const existingServiceIndex = savedServices.findIndex(service => service.id_service === idService);

        if (existingServiceIndex !== -1) {
            savedServices[existingServiceIndex] = createServiceObject(button);
            localStorage.setItem('savedServices', JSON.stringify(savedServices));
            tampilkanDataSavedServices();
            Swal.fire('Data berhasil diganti', '', 'success');
        } else {
            // Jika data dengan ID yang sama tidak ditemukan, tambahkan data baru ke penyimpanan lokal
            const newService = createServiceObject(button);
            savedServices.push(newService);
            localStorage.setItem('savedServices', JSON.stringify(savedServices));
            tampilkanDataSavedServices();
            Swal.fire('Data berhasil disimpan', '', 'success');
        }

        // Menampilkan kembali data yang tersimpan setelah penyimpanan

    }

    function createServiceObject(button) {
        return {
            nama_pelanggan: button.getAttribute('napel'),
            type_unit: button.getAttribute('data-type'),
            id_service: button.getAttribute('data-id'),
            invo: button.getAttribute('data-kode'),
            total_biaya: button.getAttribute('data-biaya'),
            keterangan: button.getAttribute('data-ket'),
            total_part: button.getAttribute('data-part')
        };
    }


    function tampilkanDataSavedServices() {
        const savedServices = JSON.parse(localStorage.getItem('savedServices')) || [];

        $("#listjob tbody").empty();

        for (let i = 0; i < savedServices.length; i++) {

            const formattedTotalPart = parseFloat(savedServices[i].total_part).toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR'
            });
            const formattedTotalBiaya = parseFloat(savedServices[i].total_biaya).toLocaleString('id-ID', {
                style: 'currency',
                currency: 'IDR'
            });
            const newRow = `<tr>
            <td>${i + 1}</td>
            <td><strong >${savedServices[i].nama_pelanggan}</strong>
                <span class="my-2" style="background-color: #00A68C; padding: 5px; border-radius: 250px;color: #FFF;">${savedServices[i].type_unit}</span>
            </td>
            <td>${savedServices[i].keterangan}</td>
            <td>${formattedTotalPart}</td>
            <td>${formattedTotalBiaya}</td>
            <td>

                <button type="button" onclick="navigateToDetail('${savedServices[i].id_service}')" class="btn btn-info btn-sm my-2">Detail</button>
                <button class="btn btn-danger btn-sm my-2" onclick="hapusService(${i})">Hapus</button>
            </td>
        </tr>`;

            $("#listjob tbody").append(newRow);
        }
    }

    // Panggil fungsi untuk menampilkan data yang disimpan saat halaman dimuat
    $(document).ready(function() {
        tampilkanDataSavedServices();
    });

    // Fungsi untuk menghapus data dari penyimpanan lokal
    function hapusService(index) {
        let savedServices = JSON.parse(localStorage.getItem('savedServices')) || [];
        savedServices.splice(index, 1);
        localStorage.setItem('savedServices', JSON.stringify(savedServices));

        // Tampilkan kembali data yang tersisa setelah menghapus
        tampilkanDataSavedServices();
    }


    // detail
    function navigateToDetail(id_service) {
        const url = `/todolist/${id_service}/detail`;
        console.log(url); // Mencetak URL yang dihasilkan
        window.location.href = url;
    }
    // Tambahkan event listener pada tombol "Update"

    //menyimpan ke server
    $(document).ready(function() {
        // Tambahkan event submit pada formulir
        $("button[name='selesaikan']").click(function(e) {
            e.preventDefault(); // Mencegah formulir dikirim secara default
            simpanDataKeServer();
        });
    });

    function simpanDataKeServer() {
        const dataToSend = {
            // Sesuaikan data yang akan dikirim ke server
            _token: "{{ csrf_token() }}",
            id_teknisi: $("#teknisi").val(),
            service: serviceManager.getAllServices(),
        };

        // Kirim permintaan AJAX ke FFserver
        $.ajax({
            type: "POST",
            url: "/serviceUpdate", // Gantilah dengan URL endpoint sesuai dengan struktur server Anda
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
                serviceManager.deleteAllServices();
                // Reload halaman setelah 2 detik
                setTimeout(function() {
                    location.reload();
                }, 2000);

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


// end pencarian service



@include('admin.component.footer')
