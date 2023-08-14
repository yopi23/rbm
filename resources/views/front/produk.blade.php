@extends('front.layout.app')
@section('content-app')
  <!-- ======= Hero Section ======= -->
  <section id="hero" class="d-flex align-items-center">
    <div class="container text-center position-relative" data-aos="fade-in" data-aos-delay="200">
      <h1>Produk Kami</h1>
      <h2>Handphone,Laptop,Aksesoris dan berbagai Lainnya</h2>
      <form action="{{route('product')}}" method="GET">
        <div class="row">
          <div class="col-md-8">
            <input type="text" name="q" @if (isset($request->q) != null)
                value="{{$request->q}}"
            @endif placeholder="Search..." class="form-control">  
            <br>
          </div>
          <div class="col-md-3">
            <input type="text" name="ref" @if (isset($request) != null)
            value="{{$request->ref}}"
        @endif placeholder="Kode Invite ?" id="ref" class="form-control">
        <br>
          </div>
          <div class="col-md-1">
            <input type="submit" value="Cari" class="btn btn-success form-control">
          </div>
        </div>
      </form>
    </div>
  </section><!-- End Hero -->

  <main id="main">

    <!-- ======= Clients Section ======= -->
    <section id="clients" class="clients">
      <div class="container">

        <div class="row">

          <div class="col-lg-2 col-md-4 col-6 d-flex align-items-center" data-aos="zoom-in" data-aos-delay="100">
            <img src="{{asset('public/')}}/img/ip.png" class="img-fluid" alt="">
          </div>

          <div class="col-lg-2 col-md-4 col-6 d-flex align-items-center" data-aos="zoom-in" data-aos-delay="200">
            <img src="{{asset('public/')}}/img/oppo.png" class="img-fluid" alt="">
          </div>

          <div class="col-lg-2 col-md-4 col-6 d-flex align-items-center" data-aos="zoom-in" data-aos-delay="300">
            <img src="{{asset('public/')}}/img/samsung.png" class="img-fluid" alt="">
          </div>

          <div class="col-lg-2 col-md-4 col-6 d-flex align-items-center" data-aos="zoom-in" data-aos-delay="400">
            <img src="{{asset('public/')}}/img/vivo.png" class="img-fluid" alt="">
          </div>

          <div class="col-lg-2 col-md-4 col-6 d-flex align-items-center" data-aos="zoom-in" data-aos-delay="500">
            <img src="{{asset('public/')}}/img/xiaomi.png" class="img-fluid" alt="">
          </div>

          <div class="col-lg-2 col-md-4 col-6 d-flex align-items-center" data-aos="zoom-in" data-aos-delay="600">
            <img src="{{asset('public/')}}/img/huawei.png" class="img-fluid" alt="">
          </div>

        </div>

      </div>
    </section><!-- End Clients Section -->

    <!-- ======= About Section ======= -->
    <section id="content" class="content">
      <div class="container">
        <div class="row content">
          @if (isset($data) != null)
              @forelse ($data as $item)
                  <div class="col-md-3">
                    <div class="card card-outline">
                            <div class="text-center">
                              @if ($item->foto_barang != '-')
                              <img src="{{asset('public/uploads/'.$item->foto_barang)}}" width="100%" height="100%" class="card-img-top img-fluid img-thumbnail" id="view-img">
                              @else
                                <img src="{{asset('public/img/no_image.png')}}" width="100%" height="100%" class="card-img-top img-fluid img-thumbnail" id="view-img">
                              @endif
                            </div>
                        <div class="card-body">
                              <h5>{{$item->nama_barang}}</h5>
                              <p>{{$item->nama_kategori}}</p>
                              @if ($ismember)
                              <h5 class="">Rp.{{number_format($item->harga_jual_barang)}},-</h5>
                              @endif
                              <div class="d-flex justify-content-between">
                                <small class="border p-1">{{$item->stok_barang > 0 ? 'Tersedia' : 'Kosong'}}</small>
                                @if ($ismember)
                                <form action="{{route('add_produk_cart',$item->id_produk)}}" method="POST">
                                  @csrf
                                  @method('PUT')
                                  <input type="hidden" name="kode_invite" id="kode_invite" value="{{$request->ref}}">
                                  <button class="btn btn-success">Pesan</button>
                                </form>
                                @endif
                              </div>
                        </div>
                    </div>
                  </div>
              @empty
              <div class="alert alert-danger text-center">Produk Tidak Ditemukan</div>
              @endforelse
          @else
          <div class="alert alert-primary text-center">Tidak Ada Produk Yang Ditampilkan</div>
          @endif
        </div>
      </div>
    </section>
    <!-- End About Section -->

    
  </main>
@endsection
 