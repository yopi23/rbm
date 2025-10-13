<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $page }}</h3>
    </div>
    <div class="card-body">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('price-settings.store') }}" method="POST">
            @csrf
            <p class="text-muted">
                Atur margin, garansi (dalam %), dan jasa default (dalam Rp). Kolom Jasa & Garansi boleh dikosongkan jika
                tidak ada standar untuk kategori tersebut.
            </p>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 25%;">Kategori</th>
                        <th>Margin Internal (%)</th>
                        <th>Jasa Default (Rp)</th>
                        <th>Garansi (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td><strong>{{ $category->nama_kategori }}</strong></td>
                            <td>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control"
                                        name="settings[{{ $category->id }}][internal_margin]"
                                        value="{{ $category->priceSetting->internal_margin ?? 0 }}">
                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                    <input type="number" class="form-control"
                                        name="settings[{{ $category->id }}][default_service_fee]"
                                        placeholder="Kosongkan jika tdk ada"
                                        value="{{ $category->priceSetting->default_service_fee ?? '' }}">
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control"
                                        name="settings[{{ $category->id }}][warranty_percentage]"
                                        placeholder="Kosongkan jika tdk ada"
                                        value="{{ $category->priceSetting->warranty_percentage ?? '' }}">
                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{-- Tambahkan juga pengaturan untuk harga Ecer & Grosir jika masih diperlukan --}}
            <hr>
            <h5 class="mt-4">Pengaturan Margin Ecer & Grosir</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 25%;">Kategori</th>
                        <th>Margin Ecer (%)</th>
                        <th>Margin Grosir (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categories as $category)
                        <tr>
                            <td><strong>{{ $category->nama_kategori }}</strong></td>
                            <td>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control"
                                        name="settings[{{ $category->id }}][retail_margin]"
                                        value="{{ $category->priceSetting->retail_margin ?? 0 }}">
                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control"
                                        name="settings[{{ $category->id }}][wholesale_margin]"
                                        value="{{ $category->priceSetting->wholesale_margin ?? 0 }}">
                                    <div class="input-group-append"><span class="input-group-text">%</span></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>


            <div class="card-footer text-right bg-white">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Semua Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
