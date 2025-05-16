<!-- resources/views/admin/layout/form_layout.blade.php -->
@extends('admin.layout.app')

@section('title', isset($page) ? $page : 'Form')

@section('content-app')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="page-header">
                            <div class="page-title">
                                <h4>{{ isset($page) ? $page : 'Form' }}</h4>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="{{ route('sub_kategori_sparepart') }}">Sub
                                                Kategori</a>
                                        </li>
                                        <li class="breadcrumb-item active" aria-current="page">
                                            {{ isset($data) ? 'Edit' : 'Tambah' }}</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        @yield('form')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('public/admin/css/form.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('public/admin/js/form.js') }}"></script>
@endpush
