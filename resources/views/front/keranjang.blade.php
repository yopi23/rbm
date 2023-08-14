@extends('front.layout.app')
@section('content-app')
  <!-- ======= Hero Section ======= -->
  <section id="hero" class="d-flex align-items-center">
    <div class="container text-center position-relative" data-aos="fade-in" data-aos-delay="200">
      <h1>Keranjang Saya</h1>
      <h2>Pesanan Kamu Sementara Akan Masuk Kesini</h2>
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
          @if(session('error'))
          <div class="alert alert-danger">
              {{ session('error') }}
          </div>
          @endif
          @if(session('success'))
          <div class="alert alert-primary">
              {{ session('success') }}
          </div>
          @endif
          @if (session('cart_produk') || session('cart_sparepart'))
          @php
              $total = 0;
              $no = 1;
          @endphp
            <div class="col-md-12">
                  <div class="card card-outline card-success">
                    <div class="card-body">
                      <h5>Pesanan</h5>
                      <hr>
                      <table class="table table-bordered">
                        <thead>
                          <th width="5%">#</th>
                          <th>Produk</th>
                          <th width="20%">Harga</th>
                          <th width="10%">Qty</th>
                          <th width="15%">SubTotal</th>
                          <th>Aksi</th>
                        </thead>
                        <tbody>
                          @if (session('cart_produk'))
                          @foreach (session('cart_produk') as $id => $item)
                              @php
                                  $total += $item['price'] * $item['qty'];
                              @endphp
                              <tr>
                                <td>{{$no++}}</td>
                                <td>@if ($item['photo'] != '-')
                                    <img src="{{asset('public/uploads/'.$item['photo'])}}" width="50">
                                    @else
                                        <img src="{{asset('public/img/no_image.png')}}" width="50">
                                    @endif {{$item['name']}}</td>
                                <td class="text-center">Rp{{number_format($item['price'])}},-</td>
                                <td class="text-center">{{number_format($item['qty'])}}</td>
                                <td class="text-center">Rp{{number_format($item['price'] * $item['qty'])}},-</td>
                                <td class="text-center">
                                  <form action="{{route('delete_produk_cart',$id)}}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm remove-from-cart"><i class='bx bxs-trash'></i></button>
                                  </form>
                              </td>
                              </tr>
                          @endforeach
                          @endif
                          @if (session('cart_sparepart'))
                          @foreach (session('cart_sparepart') as $id => $item)
                              @php
                                  $total += $item['price'] * $item['qty'];
                              @endphp
                              <tr>
                                <td>{{$no++}}</td>
                                <td>@if ($item['photo'] != '-')
                                        <img src="{{asset('public/uploads/'.$item['photo'])}}" width="50">
                                    @else
                                        <img src="{{asset('public/img/no_image.png')}}" width="50">
                                    @endif {{$item['name']}}</td>
                                <td class="text-center">Rp{{number_format($item['price'])}},-</td>
                                <td class="text-center">{{number_format($item['qty'])}}</td>
                                <td class="text-center">Rp{{number_format($item['price'] * $item['qty'])}},-</td>
                                <td class="text-center">
                                  <form action="{{route('delete_sparepart_cart',$id)}}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm remove-from-cart"><i class='bx bxs-trash'></i></button>
                                  </form>
                              </td>
                              </tr>
                          @endforeach
                          @endif
                        </tbody>
                        <tfoot>
                          <tr class="text-center  font-weight-bold border border-4">
                            <td colspan="4">Total</td>
                            <td>Rp.{{number_format($total)}},-</td>
                            <td><a href="{{route('checkout')}}" class="btn btn-success ">
                                  <i class="bx bxs-basket"></i> Checkout
                                </a></td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>
             
            </div>
          @else
          <div class="alert alert-primary text-center">Keranjang Kosong</div>
          @endif
        </div>
      </div>
    </section>
    <!-- End About Section -->

    
  </main>
@endsection
 