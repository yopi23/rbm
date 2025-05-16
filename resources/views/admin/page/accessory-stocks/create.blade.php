<!-- resources/views/accessory-stocks/create.blade.php -->
@extends('layouts.app')

@section('content')
    <div class="container">
        <h2>Tambah Stok Aksesoris Baru</h2>
        <form method="POST" action="{{ route('accessory-stocks.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Kode</label>
                        <input type="text" name="code" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Nama Aksesoris</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Jenis</label>
                        <select name="type" class="form-control" required>
                            <option value="tempered_glass">Tempered Glass</option>
                            <option value="case">Case</option>
                            <option value="charger">Charger</option>
                            <option value="cable">Cable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Ukuran Layar</label>
                        <select name="screen_size_id" class="form-control" required>
                            @foreach ($screenSizes as $size)
                                <option value="{{ $size->id }}">{{ $size->size }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Posisi Kamera</label>
                        <select name="camera_position_id" class="form-control" required>
                            @foreach ($cameraPositions as $position)
                                <option value="{{ $position->id }}">{{ $position->position }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="accessory_category_id" class="form-control" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if (auth()->user()->level === 'superadmin')
                        <div class="form-group">
                            <label>Upline</label>
                            <select name="upline_id" class="form-control" required>
                                @foreach ($uplines as $upline)
                                    <option value="{{ $upline->id }}">{{ $upline->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Harga Beli</label>
                        <input type="number" name="buy_price" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Harga Retail</label>
                        <input type="number" name="retail_price" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Harga Grosir</label>
                        <input type="number" name="wholesale_price" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>Harga Khusus</label>
                        <input type="number" name="special_price" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Stok Awal</label>
                        <input type="number" name="stock" class="form-control" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>Stok Minimum</label>
                        <input type="number" name="min_stock" class="form-control" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Simpan</button>
        </form>
    </div>
@endsection
