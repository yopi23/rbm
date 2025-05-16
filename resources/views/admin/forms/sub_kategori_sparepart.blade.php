<!-- admin/forms/sub_kategori_sparepart.blade.php -->
@extends('admin.layout.form_layout')
@section('form')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    @php
                        $action = isset($data)
                            ? route('UpdateSubKategoriSparepart', $data->id)
                            : route('StoreSubKategoriSparepart');
                        $method = isset($data) ? 'PUT' : 'POST';
                    @endphp

                    <form action="{{ $action }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method($method)

                        <input type="hidden" name="kode_owner" value="{{ auth()->user()->id_upline }}">

                        <div class="form-group">
                            <label for="kategori_id">Kategori Sparepart</label>
                            <select name="kategori_id" id="kategori_id"
                                class="form-control @error('kategori_id') is-invalid @enderror" required>
                                <option value="">Pilih Kategori</option>
                                @foreach ($kategori as $item)
                                    <option value="{{ $item->id }}"
                                        @if (isset($data) && $data->kategori_id == $item->id) selected
                                    @elseif(isset($selected_kategori) && $selected_kategori->id == $item->id)
                                        selected @endif>
                                        {{ $item->nama_kategori }}</option>
                                @endforeach
                            </select>
                            @error('kategori_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="nama_sub_kategori">Nama Sub Kategori</label>
                            <input type="text" class="form-control @error('nama_sub_kategori') is-invalid @enderror"
                                id="nama_sub_kategori" name="nama_sub_kategori"
                                value="{{ isset($data) ? $data->nama_sub_kategori : old('nama_sub_kategori') }}" required>
                            @error('nama_sub_kategori')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="foto_sub_kategori">Foto Sub Kategori</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="foto_sub_kategori"
                                    name="foto_sub_kategori" accept="image/*">
                                <label class="custom-file-label" for="foto_sub_kategori">Pilih Foto</label>
                            </div>
                            @if (isset($data) && $data->foto_sub_kategori != '-')
                                <div class="mt-2">
                                    <img src="{{ asset('public/uploads/' . $data->foto_sub_kategori) }}" width="100"
                                        height="100" alt="Preview">
                                </div>
                            @endif
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Simpan</button>
                            <a href="{{ route('sub_kategori_sparepart') }}" class="btn btn-secondary">Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

    <script>
        // Preview image before upload
        $(document).ready(function() {
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').addClass("selected").html(fileName);

                // Image preview
                var reader = new FileReader();
                reader.onload = function(e) {
                    // Create image if it doesn't exist
                    if ($('#preview-image').length == 0) {
                        $('<div class="mt-2"><img id="preview-image" src="" width="100" height="100" alt="Preview"></div>')
                            .insertAfter('.custom-file');
                    }
                    $('#preview-image').attr('src', e.target.result);
                }
                reader.readAsDataURL(this.files[0]);
            });
        });
    </script>
