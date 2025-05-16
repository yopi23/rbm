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
                <div class="col-md-12">
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

                    <form
                        action="{{ isset($data) != null ? route('UpdateSparepart', $data->id) : route('StoreSparepart') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        @if (isset($data) != null)
                            @method('PUT')
                        @else
                            @method('POST')
                        @endif
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card card-outline card-success">
                                    <div class="card-header">
                                        <div class="card-title">Image</div>
                                    </div>
                                    <div class="card-body text-center">
                                        @if (isset($data) && $data->foto_sparepart != '-')
                                            <img src="{{ asset('public/uploads/' . $data->foto_sparepart) }}"
                                                width="100%" height="100%" class="img" id="view-img">
                                        @else
                                            <img src="{{ asset('public/img/no_image.png') }}" width="100%"
                                                height="100%" class="img" id="view-img">
                                        @endif
                                        <hr>
                                        <div class="form-group">
                                            <input type="file" accept="image/png, image/jpeg"
                                                class="form-control form-input" name="foto_sparepart"
                                                id="foto_sparepart">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card card-outline card-success">
                                    <div class="card-header">
                                        <div class="card-title">
                                            Data Sparepart
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label>Nama Sparepart</label>
                                            <input type="text"
                                                value="{{ isset($data) != null ? $data->nama_sparepart : '' }}"
                                                placeholder="Nama Sparepart" name="nama_sparepart" id="nama_sparepart"
                                                class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label>Kategori Sparepart</label>
                                            <select name="kode_kategori" id="kode_kategori" class="form-control">
                                                @forelse ($kategori as $item)
                                                    <option value="{{ $item->id }}"
                                                        {{ isset($data) != null && $data->kategori_barang == $item->id ? 'selected' : '' }}>
                                                        {{ $item->nama_kategori }}</option>
                                                @empty
                                                @endforelse
                                            </select>
                                        </div>
                                        <!-- Add this new field for subcategory -->
                                        <div class="form-group">
                                            <label for="kode_sub_kategori">Sub Kategori Sparepart</label>
                                            <select name="kode_sub_kategori" id="kode_sub_kategori"
                                                class="form-control @error('kode_sub_kategori') is-invalid @enderror">
                                                <option value="">Pilih Sub Kategori</option>
                                                @if (isset($sub_kategori) && count($sub_kategori) > 0)
                                                    @foreach ($sub_kategori as $item)
                                                        <option value="{{ $item->id }}"
                                                            @if (isset($data) && $data->kode_sub_kategori == $item->id) selected @endif>
                                                            {{ $item->nama_sub_kategori }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('kode_sub_kategori')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label>Deskripsi</label>
                                            <textarea name="desc_sparepart" id="desc_sparepart" placeholder="Deskripsi Sparepart" id="desc_barang" cols="30"
                                                rows="10" class="form-control">{{ isset($data) != null ? $data->desc_sparepart : '' }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Stok Sparepart</label>
                                            <input type="text" name="stok_sparepart"
                                                value="{{ isset($data) != null ? $data->stok_sparepart : '0' }}"
                                                placeholder="Stok Sparepart" id="stok_sparepart" class="form-control"
                                                {{ isset($data) != null ? 'readonly' : '' }}>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Harga Beli</label>
                                                    <input type="text" name="harga_beli"
                                                        value="{{ isset($data) != null ? $data->harga_beli : '0' }}"
                                                        placeholder="Harga Beli" id="harga_beli" class="form-control"
                                                        hidden>
                                                    <input type="text" name="in_harga_beli" placeholder="Harga Beli"
                                                        id="in_harga_beli" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Harga Jual</label>
                                                    <input type="text" name="harga_jual"
                                                        value="{{ isset($data) != null ? $data->harga_jual : '0' }}"
                                                        placeholder="Harga Jual" id="harga_jual" class="form-control"
                                                        hidden>
                                                    <input type="text" name="in_harga_jual"
                                                        placeholder="Harga Jual" id="in_harga_jual"
                                                        class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Harga Pemasangan</label>
                                                    <input type="text" name="harga_pasang"
                                                        value="{{ isset($data) != null ? $data->harga_pasang : '0' }}"
                                                        placeholder="Harga Pasang" id="harga_pasang"
                                                        class="form-control" hidden>
                                                    <input type="text" name="in_harga_pasang"
                                                        placeholder="Harga Pasang" id="in_harga_pasang"
                                                        class="form-control">
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-success">Simpan</button>
                                        <a href="{{ route('sparepart') }}" class="btn btn-danger">Kembali</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
</div>
@section('content-script')
    <script>
        $('#foto_sparepart').change(function(event) {
            $("#view-img").fadeIn("fast").attr('src', URL.createObjectURL(event.target.files[0]));
        });
    </script>

    <script>
        function formatRupiah(angka, prefix) {
            var number_string = angka.toString().replace(/[^,\d]/g, "");
            var split = number_string.split(",");
            var sisa = split[0].length % 3;
            var rupiah = split[0].substr(0, sisa);
            var ribuan = split[0].substr(sisa).match(/\d{3}/g);

            if (ribuan) {
                separator = sisa ? "." : "";
                rupiah += separator + ribuan.join(".");
            }

            rupiah = split[1] != undefined ? rupiah + ',' + split[1] :
                rupiah; // Tambahkan kondisi untuk menghilangkan angka 0 di depan jika tidak ada koma
            return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
        }

        function getNumericValue(rupiah) {
            var numericValue = rupiah.replace(/[^0-9]/g, "");
            return numericValue;
        }

        var HbInput = document.getElementById("in_harga_beli");
        var Hbhidden = document.getElementById("harga_beli");

        var HjInput = document.getElementById("in_harga_jual");
        var Hjhidden = document.getElementById("harga_jual");

        var HpInput = document.getElementById("in_harga_pasang");
        var Hphidden = document.getElementById("harga_pasang");


        HbInput.addEventListener("input", function(e) {
            var biaya = e.target.value;
            var rupiah = formatRupiah(biaya);
            var numericValue = getNumericValue(biaya);
            e.target.value = rupiah;
            Hbhidden.value = numericValue;
        });

        HjInput.addEventListener("input", function(e) {
            var biaya = e.target.value;
            var rupiah = formatRupiah(biaya);
            var numericValue = getNumericValue(biaya);
            e.target.value = rupiah;
            Hjhidden.value = numericValue;
        });

        HpInput.addEventListener("input", function(e) {
            var biaya = e.target.value;
            var rupiah = formatRupiah(biaya);
            var numericValue = getNumericValue(biaya);
            e.target.value = rupiah;
            Hphidden.value = numericValue;
        });
    </script>


    <!-- Add this script to your @push('scripts') section -->

        <script>
            $(document).ready(function() {
                // When category changes, load subcategories
                $('#kode_kategori').change(function() {
                    var kategoriId = $(this).val();
                    var subKategoriSelect = $('#kode_sub_kategori');

                    // Clear current options
                    subKategoriSelect.empty();
                    subKategoriSelect.append('<option value="">Pilih Sub Kategori</option>');

                    if (kategoriId) {
                        // Get subcategories via AJAX
                        $.ajax({
                            url: '{{ url('/admin/get-sub-kategori') }}/' + kategoriId,
                            type: 'GET',
                            dataType: 'json',
                            success: function(data) {
                                if (data.length > 0) {
                                    // Add options
                                    $.each(data, function(key, value) {
                                        subKategoriSelect.append('<option value="' + value
                                            .id + '">' + value.nama_sub_kategori +
                                            '</option>');
                                    });
                                }
                            }
                        });
                    }
                });

                // Trigger change on page load if editing
                @if (isset($data) && $data->kode_kategori)
                    $('#kode_kategori').trigger('change');
                @endif
            });
        </script>
    @endsection
    @include('admin.component.footer')
