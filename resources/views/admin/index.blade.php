@extends('admin.layout.app')
@section('content-app')
@section('dashboard', 'active')
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">{{ $page }}</h1>
                </div><!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">{{ $page }}</li>
                    </ol>
                </div><!-- /.col -->
            </div><!-- /.row -->
        </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    @if ($this_user->jabatan == '0')
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Small boxes (Stat box) -->
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-12">
                        <div class="info-box mb-3">
                            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-users"></i></span>

                            <div class="info-box-content">
                                <span class="info-box-text">Owner</span>
                                <span class="info-box-number">{{ number_format($data->count()) }}</span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                        <!-- /.info-box -->
                    </div>
                </div>
                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                @if (session('success'))
                    <div class="alert alert-primary">
                        {{ session('success') }}
                    </div>
                @endif
                <!-- /.row -->
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
</div>
@endsection
@else
<!-- Main content -->
<section class="content">
<div class="container-fluid">

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    @if (session('success'))
        <div class="alert alert-primary">
            {{ session('success') }}
        </div>
    @endif
    @if ($this_user->jabatan == '1' || $this_user->jabatan == '2')

        @if ($penarikan->isNotEmpty())
            @foreach ($penarikan as $data)
                <span class="alert alert-danger" style="display: block; margin-bottom:5px">
                    Nama: {{ $data->name }} - Mengajukan: Rp. {{ number_format($data->jumlah_penarikan) }}
                    <br>
                </span>
            @endforeach
        @endif

    @endif
    {{-- jalan pintas --}}
    <div class="my-2" id="shortcut">
        <div class="container-center">
            <center class="mb-4">
                <h5>Tambah Data</h5>
            </center>
            <div class="row">
                @if ($this_user->jabatan == '1')
                    <div class="col-md my-2">
                        <a class="info-box-icon bg-info elevation-1" href="{{ route('laci.form') }}">
                            <div class="clickable-element bg-success text-white">
                                <span class="info-box-text">Laci</span>
                                <span class="info-box-number">Rp.{{ number_format($totalReceh) }},-</span>
                            </div>
                        </a>
                    </div>
                @endif
                <div class="col-md my-2">
                    <a class="info-box-icon bg-danger elevation-1" href="#" data-toggle="modal"
                        data-target="#reallaci">
                        <div
                            class="clickable-element @if ($sumreal < $totalReceh) bg-danger text-white @else bg-success text-white @endif">
                            <span class="info-box-text">Uang sebenarnya

                            </span>
                            <span class="info-box-number">
                                Rp.{{ number_format($sumreal) }},-
                            </span>
                            @if ($sumreal < $totalReceh)
                                <strong class="bg-danger" style="padding: 5px; border-radius: 20px;">Kurang</strong>
                            @endif
                        </div>
                    </a>
                </div>
            </div>
            <div class="input-group my-2">
                <label class="input-group-text" for="id_kategorilaci">Jenis</label>
                <select name="id_kategorilaci" class="form-control" id="transactionType" required>
                    <option value="" disabled selected>--Pilih jenis transaksi--</option>
                    <option value="penjualan">Penjualan</option>
                    <option value="service">Service</option>
                    <option value="pengambilan">Pengambilan device</option>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>
            </div>

            <div class="listservice table-responsive">
                <div class="form-group">
                    <select name="teknisi" id="teknisi" class="form-control" required autofocus>
                        <option value="" disabled selected style="color: #a9a9a9;">---
                            Pilih Teknisi---</option>
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
                <table class="table table-hover" id="dataTable">
                    <thead>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Unit</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </thead>
                    <tbody>
                        @php
                            $nomor = 1;
                        @endphp
                        @forelse ($service as $item)
                            @if ($item->status_services == 'Antri')
                                <tr>
                                    <td>{{ $nomor++ }}</td>
                                    <td><b>{{ $item->nama_pelanggan }}</b><br>{{ $item->kode_service }}<br>{{ $item->no_telp }}
                                    </td>
                                    <td>{{ $item->type_unit }}</td>
                                    <td>{{ $item->keterangan }}</td>
                                    <td>
                                        <a href="{{ route('nota_service', $item->id) }}" target="_blank"
                                            class="btn btn-sm btn-success mt-2"><i class="fas fa-print"></i></a>
                                        <a href="{{ route('nota_tempel', $item->id) }}" target="_blank"
                                            class="btn btn-sm btn-warning mt-2"><i class="fas fa-print"></i></a>
                                        <form action="{{ route('proses_service', $item->id) }}"
                                            onsubmit="return confirm('Apakah Kamu yakin ingin memproses Service ini ?')"
                                            method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="status_services" id="status_services"
                                                value="Diproses">
                                            <input type="hidden" name="teknisi" id="teknisi-{{ $item->id }}"
                                                value="">
                                            <button type="submit" class="btn btn-sm btn-primary mt-2"
                                                onclick="setTeknisi('{{ $item->id }}')">Proses</button>
                                        </form>
                                        </form>
                                        <form action="{{ route('delete_service', $item->id) }}"
                                            onsubmit="return confirm('Apakah Kamu yakin ingin menghapus Service ini ?')"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger mt-2"><i
                                                    class="fas fa-trash"></i> </button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- form service --}}
            @include('admin.forms.dashboard.service')
            {{-- end form service --}}
            {{-- form penjualan --}}
            @include('admin.forms.dashboard.penjualan')
            {{-- end penjualan --}}
            {{-- form pengambilan --}}
            @include('admin.forms.dashboard.pengambilan')
            {{-- end pengambilan --}}
            {{-- form pemasukan --}}
            @include('admin.forms.dashboard.pemasukan')
            {{-- end pemasukan --}}
            {{-- form pengeluaran --}}
            @include('admin.forms.dashboard.pengeluaran')
            {{-- end pengeluaran --}}
        </div>
        {{-- uang sebenarnya --}}
        <div class="modal fade" id="reallaci" tabindex="-1" role="dialog" aria-labelledby="recehModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="recehModalLabel">Input uang real</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('laci.real') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <!-- Form input receh -->
                            <div class="form-group">
                                <label for="amount">Jumlah uang</label>
                                <input type="number" class="form-control" id="real" name="real"
                                    required>
                            </div>
                            <!-- Tambahkan field lainnya jika diperlukan -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                            <button type="submit" class="btn btn-primary">Kirim</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        {{-- uang sebenarnya --}}
    </div>
    {{-- jalan pintas --}}
</div><!-- /.container-fluid -->
</section>
<!-- /.content -->
</div>

@if ($isModalRequired)
<!-- Modal untuk input receh -->
<div class="modal fade" id="recehModal" tabindex="-1" role="dialog" aria-labelledby="recehModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recehModalLabel">Input Receh</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('laci.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <!-- Form input receh -->
                    <div class="form-group">
                        <label for="amount">Jumlah Receh</label>
                        <input type="number" class="form-control" id="receh" name="receh" required>
                    </div>
                    <!-- Tambahkan field lainnya jika diperlukan -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary">Kirim</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#recehModal').modal('show');
    });
</script>
@endif
@endsection



@section('content-script')
<script>
    var i = 0;
    var perkiraan_harga = 0;
    $('#add-dynamic-input').on('click', function() {
        ++i;
        $('.dynamic-input').append('<tr><td><select name="kode_sparepart[' + i + ']" id="kode_sparepart[' + i +
            ']"  class="form-control select-bootstrap kode_sparepart"><option value="">-- Pilih Sparepart --</option>'
            @forelse ($sparepart as $item)
                +'<option value="' + {{ $item->id }} + '" data-harga="' +
                    {{ $item->harga_jual + $item->harga_pasang }} + '" data-stok="' +
                    {{ $item->stok_sparepart }} + '">{{ $item->nama_sparepart }}</option>'
            @empty @endforelse +
            '</select></td><td><input type="text" name="harga_kode_sparepart[' + i +
            ']" id="harga_kode_sparepart[' + i + ']" class="form-control harga_part" readonly></td><td>' +
            '<input type="text" name="stok_kode_sparepart[' + i + ']" id="stok_kode_sparepart[' + i +
            ']" class="form-control stok_part" readonly></td><td><input type="number" value="1" name="qty_kode_sparepart[' +
            i + ']" id="qty_kode_sparepart[' + i +
            ']" class="form-control qty_part"></td><td><button type="button" class="btn btn-danger remove_dynamic" name="remove_dynamic" id="remove_dynamic"><i class="fas fa-trash"></i></button></td></tr>'
        );
        $('.select-bootstrap').select2({
            theme: 'bootstrap4'
        });
    });
    $(document).on('click', '.remove_dynamic', function() {
        $(this).parents('tr').remove()
    });
    $(document).on('change', '.kode_sparepart', function() {
        var harga = $(this).find(':selected').data('harga');
        var stok = $(this).find(':selected').data('stok');
        var qty = $(this).parents('tr').find('.qty_part').val();
        $(this).parents('tr').find('.harga_part').val(harga);
        $(this).parents('tr').find('.stok_part').val(stok);
    })
    $(document).on('change', '.kode_part', function() {
        var harga = $(this).find(':selected').data('harga');
        var stok = $(this).find(':selected').data('stok');

        // Format harga menjadi format uang
        var formattedHarga = formatCurrency(harga);

        // Update input harga dengan format uang
        $(this).closest('.table-responsive').find('.harga_spart').val(formattedHarga);
    })
    $(document).on('keyup change click', '.qty_part', function() {
        var qty = $(this).val();
        var harga = $(this).parents('tr').find('.kode_sparepart :selected').data('harga');
        var total_harga = harga * qty;
    })

    function formatCurrency(amount) {
        return 'Rp. ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g,
            "."); // Menggunakan titik sebagai pemisah ribuan
    }
</script>

{{-- transisi --}}
<script>
    // Fungsi untuk menampilkan konten Shortcut dan menyembunyikan Dashboard
    function showShortcut() {
        document.getElementById('shortcut').style.display = 'block';
        document.getElementById('main').style.display = 'none';
        setActiveButton('btn-shortcut');
    }

    // Fungsi untuk menampilkan konten Dashboard dan menyembunyikan Shortcut
    function showDashboard() {
        document.getElementById('main').style.display = 'block';
        document.getElementById('shortcut').style.display = 'none';
        setActiveButton('btn-dashboard');
    }
    // Fungsi untuk mengatur kelas aktif pada tombol
    function setActiveButton(activeId) {
        const labels = document.querySelectorAll('.btn-group label');
        labels.forEach(label => {
            if (label.htmlFor === activeId) {
                label.classList.remove('btn-outline-primary');
                label.classList.add('btn-primary');
            } else {
                label.classList.remove('btn-primary');
                label.classList.add('btn-outline-primary');
            }
        });
    }
    // Menampilkan konten Shortcut saat halaman diakses
    window.onload = showShortcut;

    // Menambahkan event listener pada tombol
    document.getElementById('btn-shortcut').addEventListener('click', showShortcut);
    document.getElementById('btn-dashboard').addEventListener('click', showDashboard);
</script>

{{-- jenis transaksi --}}
<script>
    document.getElementById('transactionType').addEventListener('change', function() {
        const formServices = document.querySelectorAll('.formservice');
        const formSales = document.querySelectorAll('.formSales');
        const id_kategorilaciGrup = document.querySelectorAll('.kategorilaciGrup');
        const listservice = document.querySelectorAll('.listservice');
        const formTakeOut = document.querySelectorAll('.formTakeOut');
        const formPemasukan = document.querySelectorAll('.formpemasukan');
        const formPengeluaran = document.querySelectorAll('.formpengeluaran');
        // Menyembunyikan semua elemen dengan kelas d-none
        function hideAll(elements) {
            elements.forEach(el => el.classList.add('d-none'));
        }

        // Menampilkan elemen yang relevan
        function show(elements) {
            elements.forEach(el => el.classList.remove('d-none'));
        }
        // Mengatur tampilan berdasarkan jenis transaksi
        const allForms = [...formServices, ...formSales, ...listservice, ...formTakeOut, ...
            id_kategorilaciGrup, ...formPemasukan, ...formPengeluaran
        ]; // Gabungkan semua elemen

        if (this.value === 'service') {
            hideAll(allForms);
            show(formServices);
        } else if (this.value === 'penjualan') {
            hideAll(allForms);
            show(formSales);
            show(id_kategorilaciGrup);
        } else if (this.value === 'pengambilan') {
            hideAll(allForms);
            show(formTakeOut);
            const pengambilanId = $('#pengambilan-id').val();
            $.ajax({
                url: '/pengembalian/' + pengambilanId +
                    '/pengambilan_detail', // Sesuaikan dengan rute backend
                type: 'GET',
                success: function(response) {
                    $('#jmlitem').val(response
                        .jumlahData); // Perbarui jumlah item

                    let totalPengambilan = 0;
                    $.each(response.pengambilanServices, function(index, item) {
                        const totalItem = item.total_biaya - item.dp;
                        totalPengambilan += totalItem;
                    });

                    $('#gtotal-ambil').text('Rp. ' + new Intl.NumberFormat()
                        .format(totalPengambilan));
                    $('.totalharga').val(
                        totalPengambilan); // Perbarui total harga
                },
                error: function(xhr) {
                    alert('Terjadi kesalahan saat memperbarui data.');
                }
            });
        } else if (this.value === 'pemasukan') {
            hideAll(allForms);
            show(formPemasukan);
        } else if (this.value === 'pengeluaran') {
            hideAll(allForms);
            show(formPengeluaran);
        } else {
            hideAll(allForms);
        }
    });
</script>

{{-- teknisi --}}
<script>
    function setTeknisi(itemId) {
        const teknisiSelect = document.getElementById('teknisi');
        const hiddenInput = document.getElementById('teknisi-' + itemId);
        hiddenInput.value = teknisiSelect.value;
    }
</script>
{{-- end teknisi --}}
{{-- pencarian part --}}
<script>
    $(document).ready(function() {
        $('#modal_sp').on('shown.bs.modal', function() {
            // Reset semua tombol Plus
            $('#searchResults tbody button').each(function() {
                $(this).removeClass('btn-secondary disabled').addClass('btn-success');
                $(this).prop('disabled', false);
            });
            $('#caripart').focus();
        });

    });
    $(document).ready(function() {
        // Event input untuk mencari sparepart
        $("#caripart").on("input", function() {
            cariSparepart();
        });
    });

    function sanitizeInput(input) {
        return $('<div>').text(input).html(); // Menyandikan input untuk menghindari XSS
    }

    function cariSparepart() {
        const cariPart = sanitizeInput($("#caripart").val().toLowerCase());

        // Cek apakah input kosong
        if (cariPart === '') {
            tampilkanDataTabelSP([]); // Kosongkan tabel jika tidak ada input
            return; // Keluar dari fungsi
        }
        const sparepartData = <?php echo json_encode($sparepart); ?>;
        // console.info(sparepartData);

        const hasilPencarian = sparepartData.filter(sparepart => {
            return sparepart.nama_sparepart.toLowerCase().includes(cariPart);
        });
        tampilkanDataTabelSP(hasilPencarian);
    }

    function sanitizeOutput(output) {
        return $('<div>').text(output).html(); // Menyandikan output untuk menghindari XSS
    }

    function tampilkanDataTabelSP(data) {
        $("#searchResults tbody").empty(); // Kosongkan tabel sebelum menampilkan hasil pencarian

        const selectedCustomer = $('#kat_customer').val(); // Ambil jenis pelanggan yang dipilih

        data.forEach((item, index) => {
            let hargaFinal = parseFloat(item.harga_ecer); // Ambil harga asli

            // Logika penyesuaian harga berdasarkan jenis pelanggan
            if (selectedCustomer === 'ecer') {
                if (hargaFinal < 15000) {
                    hargaFinal += parseFloat(item.harga_beli); // Tambah harga modal
                } else if (hargaFinal >= 15000 && hargaFinal <= 200000) {
                    hargaFinal += 10000; // Tambah 10.000
                } else if (hargaFinal > 200000) {
                    hargaFinal += 20000; // Tambah 20.000
                }
            } else if (selectedCustomer === 'glosir') {
                if (hargaFinal < 15000 && hargaFinal >= 5000) {
                    hargaFinal += -1000; // kurangi
                } else if (hargaFinal >= 50000 && hargaFinal < 200000) {
                    hargaFinal += -5000; // Tambah 10.000
                }
            } else if (selectedCustomer === 'jumbo') {
                if (hargaFinal < 15000 && hargaFinal >= 5000) {
                    hargaFinal += -2000; // kurangi
                } else if (hargaFinal >= 50000 && hargaFinal < 200000) {
                    hargaFinal += -10000; // Tambah 10.000
                }
            }
            const stock = item.stok_sparepart;
            const stockDisplay = stock > 0 ? sanitizeOutput(stock) :
                '<span style="color: red;">Kosong</span>';
            const buttonDisabled = stock > 0 ? '' : 'disabled'; // Menonaktifkan tombol jika stok kosong
            const buttonClass = stock > 0 ? 'btn-success' : 'btn-secondary'; // Mengubah kelas tombol

            const newRow = `<tr>
                <td>${i + 1}</td>
                <td style="max-width:200px;">${sanitizeOutput(item.nama_sparepart)}</td>
                <td style="max-width:100px; ">
                ${stockDisplay}
                </td>
                <td style="max-width:150px;">
                    ${sanitizeOutput(formatCurrency(hargaFinal))}
                </td>
                <td style="max-width:50px;">
                    <input class="form-control" id="qty${index}" autocomplete="off" placeholder="Jumlah" oninput="validateQty(this, ${stock})">
                </td>
                <td>
                    <button class="btn ${buttonClass} mb-2"
                    data-id="${sanitizeOutput(item.id)}"
                    data-nama="${sanitizeOutput(item.nama_sparepart)}"
                    data-harga="${sanitizeOutput(hargaFinal)}"
                    data-qty="${sanitizeOutput(item.qty)}"
                    ${buttonDisabled}
                    onclick="jualSparepart(event, this)">
                    <i class="fa fa-plus"></i>
                </button>

                </td>
            </tr>`;

            $("#searchResults tbody").append(newRow);
        });


    };

    // Fungsi untuk validasi input jumlah
    function validateQty(input, stock) {
        const qty = parseInt(input.value);
        if (stock <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Stok Kosong',
                text: 'Tidak dapat melakukan pembelian, stok tidak tersedia.',
                confirmButtonText: 'Ok'
            });
            input.value = ''; // Reset input
            return; // Keluar dari fungsi
        }
        if (qty > stock) {
            Swal.fire({
                icon: 'warning',
                title: 'Jumlah tidak valid',
                text: `Jumlah tidak boleh melebihi stok (${stock})`,
                confirmButtonText: 'Ok'
            });
            input.value = stock; // Reset ke stok maksimum
        }
    }
</script>
{{-- pencarian --}}
{{-- simpan ke lokal --}}
<script>
    function jualSparepart(event, button) {
        event.preventDefault(); // Mencegah perilaku default tombol
        // Cegah tombol diklik dua kali
        if ($(button).hasClass('disabled')) {
            return; // Jika sudah disabled, hentikan eksekusi
        }
        // Ambil data dari tombol
        const id = $(button).data('id');
        const nama = $(button).data('nama');
        const harga = $(button).data('harga');
        const kodetrxid = $('#kodetrxid').val();
        // Mengambil nilai qty dari input yang relevan
        const qtyInputId = $(button).closest('tr').find('input[id^="qty"]').attr(
            'id'); // Mencari input qty di baris yang sama
        const qty = $("#" + qtyInputId).val(); // Mengambil nilai dari input qty

        // Validasi qty
        if (!qty || qty <= 0) {
            alert('Jumlah tidak valid!');
            return;
        }
        // Kirim data ke server menggunakan AJAX
        $.ajax({
            url: '{{ route('create_detail_sparepart_penjualan') }}', // Sesuaikan dengan route Anda
            type: 'POST',
            data: {
                kode_penjualan: kodetrxid, // Ambil kode transaksi
                kode_sparepart: id,
                qty_sparepart: qty,
                custom_harga: harga,
                _token: '{{ csrf_token() }}' // Kirim token CSRF
            },
            success: function(response) {
                // Tangani respon dari server (misalnya, update tampilan)
                Swal.fire({
                    icon: 'success',
                    title: 'Data ditambahkan',
                    text: `${nama} (${qty})`,
                    showConfirmButton: false,
                    timer: 2500
                });
                // Muat ulang data atau tampilkan pesan sesuai kebutuhan

            },
            error: function(xhr) {
                // Tangani error
                Swal.fire({
                    icon: 'danger',
                    title: 'Gagal ditambahkan',
                    text: "Terjadi kesalahan: " + xhr.responseText,
                    showConfirmButton: false,
                    timer: 2500
                });

            }
        });

        // Ubah tombol jadi tidak bisa diklik
        $(button).removeClass('btn-success').addClass('btn-secondary disabled');
        $(button).prop('disabled', true);
    }

    function updateDetails() {
        // Fungsi untuk memuat data dari localStorage
        const kodePenjualan = $('#kodetrxid').val(); // Ambil kode penjualan dari PHP
        const sparepartList = $('#sparepartList');
        sparepartList.empty(); // Kosongkan tabel sebelum mengisi

        $.ajax({
            url: `/penjualan/detail/${kodePenjualan}`, // Sesuaikan dengan endpoint di server
            method: 'GET',
            success: function(data) {
                data.detailsparepart.forEach((item, index) => {
                    const newRow = `<tr>
                    <td>${index + 1}</td>
                    <td>${sanitizeOutput(item.nama_sparepart)}</td>
                    <td>${formatCurrency(item.detail_harga_jual)}</td>
                    <td>${sanitizeOutput(item.qty_sparepart)}</td>
                    <td>
                        <button class="btn btn-danger" data-nama="${item.nama_sparepart}" data-qty="${item.qty_sparepart}" onclick="removeItem(this,${item.id_detail})">Hapus</button>
                    </td>
                </tr>`;
                    sparepartList.append(newRow);
                });
            },

        });
    }
    // detail data
    $(document).ready(function() {
        // Panggil fungsi untuk mengisi data saat modal dibuka
        $('#detail_sp').on('shown.bs.modal', function() {
            updateDetails()
        });


    });

    // Fungsi untuk menghapus item dari
    function removeItem(button, id) {
        const namaSparepart = button.getAttribute('data-nama');
        const qty = button.getAttribute('data-qty');
        Swal.fire({
            title: 'Apakah Kamu Yakin?',
            text: `Menghapus ${namaSparepart} (qty: ${qty})`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Tidak, batalkan'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `dashboard/${id}/delete_detail_sparepart`, // Sesuaikan dengan endpoint di server
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}', // Tambahkan token CSRF
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Detail sparepart berhasil dihapus!',
                        });
                        updateDetails();
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan saat menghapus detail sparepart.',
                        });
                    }
                });
            }
        });

    };
</script>
{{-- untuk update otomatis --}}
<script>
    // Fungsi untuk mengambil dan memperbarui data
    function updateTotals() {
        const kodePenjualan = $('#kodetrxid').val(); // Ambil ID penjualan dari PHP
        $.ajax({
            url: `/penjualan/detail/${kodePenjualan}`,
            method: 'GET',
            success: function(data) {
                $('#item').val(data.totalitem); // Update jumlah item
                $('#gtotal-result').text('Rp. ' + new Intl.NumberFormat().format(data
                    .total_part_penjualan)); // Update grand total
                $('#total_penjualan').val(data.total_part_penjualan); // Update input hidden

            }
        });
    }
    $(document).ready(function() {
        // Panggil fungsi updateTotals saat halaman dimuat
        updateTotals();

        // Misalnya, jika ada event tertentu, panggil updateTotals
        $('#modal_sp').on('hidden.bs.modal', function() {
            updateTotals();
        });
        $('#detail_sp').on('hidden.bs.modal', function() {
            updateTotals();
        });
    });
</script>
@endsection
@endif
