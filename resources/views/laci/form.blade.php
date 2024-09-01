@extends('admin/layouts.app')

@section('content')
<div class="container">
    <h2>Isi Laci Hari Ini</h2>

    @if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
    @endif

    @if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    <form action="{{ route('laci.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="laci">Jumlah Laci:</label>
            <input type="number" name="laci" id="laci" class="form-control" required min="1" value="{{ old('laci') }}">
            @error('laci')
            <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label for="service">Service:</label>
            <input type="number" name="service" id="service" class="form-control" value="{{ old('service') }}">
        </div>

        <div class="form-group">
            <label for="penjualan">Penjualan:</label>
            <input type="number" name="penjualan" id="penjualan" class="form-control" value="{{ old('penjualan') }}">
        </div>

        <div class="form-group">
            <label for="pemasukan_lainnya">Pemasukan Lainnya:</label>
            <input type="number" name="pemasukan_lainnya" id="pemasukan_lainnya" class="form-control" value="{{ old('pemasukan_lainnya') }}">
        </div>

        <div class="form-group">
            <label for="pengeluaran">Pengeluaran:</label>
            <input type="number" name="pengeluaran" id="pengeluaran" class="form-control" value="{{ old('pengeluaran') }}">
        </div>

        <button type="submit" class="btn btn-primary mt-3">Isi Laci</button>
    </form>
</div>
@endsection
