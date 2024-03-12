@include('template/head')
@include('template/nav')
<div class="container-fluid">
    <div class="row">
        @include('template/sidebar')
        <!-- Laporan  -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main active">
            <div class="card-tr" style="margin-top: 55px;">
                <div class="row">
                    <div class="col-2">
                        <img src="#" title="images">
                    </div>
                    <div class="col">
                        <span><b>
                                <h3>Input Data Service</h3>
                            </b></span>
                    </div>
                </div>
            </div>
            <div class="card-tr" style="margin-top: 5px;">
                <div class="card">
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <span class="input-group-text" id="basic-addon1"><i class="fa fa-user"
                                    aria-hidden="true"></i></span>
                            <input type="text" class="form-control" placeholder="Nama" aria-label="Nama"
                                aria-describedby="basic-addon1">
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text" id="basic-addon2">+62</span>
                            <input type="text" class="form-control" placeholder="Nomor Telpon"
                                aria-label="Nomor Telpon" aria-describedby="basic-addon2">
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text" id="basic-addon3">Type</span>
                            <input type="text" class="form-control" id="basic-url" placeholder="Type"
                                aria-label="Type" aria-describedby="basic-addon3">
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text">Keterangan</span>
                            <textarea class="form-control" aria-label="Keterangan"></textarea>
                        </div>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><i class="fa fa-unlock-alt" aria-hidden="true"></i></span>
                            <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)"
                                placeholder="Sandi" aria-label="sandi">
                            {{-- <span class="input-group-text">.00</span> --}}
                        </div>

                        <div class="input-group mb-3">
                            <input type="text" class="form-control" placeholder="Biaya" aria-label="Biaya">
                            <span class="input-group-text">@</span>
                            <input type="text" class="form-control" placeholder="DP" aria-label="DP">
                        </div>

                        <div class="input-group mb-3">
                            <input id="sparepartInput" type="text" class="form-control" placeholder="Sparepart"
                                aria-label="Sparepart">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#sparepartModal">Pilih</button>
                            </div>
                            <input id="hargaPasangInput" type="text" class="form-control" placeholder="Harga pasang"
                                aria-label="harga pasang">
                        </div>
                        <button class="btn btn-primary col-12 mt-4">Simpan</button>
                    </div>
                </div>
            </div>
            <!-- kolom -->
        </main>
        <!-- end laporan -->
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="sparepartModal" tabindex="-1" aria-labelledby="sparepartModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sparepartModalLabel">Pilih Sparepart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Isi modal dengan daftar sparepart yang bisa dipilih -->
                <select id="selectSparepart" class="form-control">
                    <option value="1">Sparepart 1</option>
                    <option value="2">Sparepart 2</option>
                    <option value="3">Sparepart 3</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="setSparepart()">Pilih Sparepart</button>
            </div>
        </div>
    </div>
</div>
<script>
    const spareparts = [{
            id: 1,
            nama: 'part1',
            harga: '1000'
        },
        {
            id: 2,
            nama: 'part2',
            harga: '2000'
        },
        {
            id: 3,
            nama: 'part3',
            harga: '3000'
        }
    ];
</script>

<script>
    function setSparepart() {
        // Mendapatkan nilai yang dipilih dari modal
        const selectedSparepartId = document.getElementById('selectSparepart').value;

        // Cari objek sparepart yang sesuai berdasarkan ID yang dipilih
        const selectedSparepart = spareparts.find(sparepart => sparepart.id.toString() === selectedSparepartId);

        if (selectedSparepart) {
            // Menyimpan nama dan harga ke input "Sparepart" dan "Harga Pasang"
            document.getElementById('sparepartInput').value = selectedSparepart.nama;
            document.getElementById('hargaPasangInput').value = selectedSparepart.harga;
        }

        // Menutup modal
        const modal = new bootstrap.Modal(document.getElementById('sparepartModal'));
        modal.hide();
    }
</script>

@include('template/foot')
