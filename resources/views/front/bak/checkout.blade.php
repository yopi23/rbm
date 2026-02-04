@extends('front.layout.app')
@section('content-app')
  <!-- ======= Hero Section ======= -->
  <section id="hero" class="d-flex align-items-center">
    <div class="container text-center position-relative" data-aos="fade-in" data-aos-delay="200">
      <h1>Checkout Pesanan</h1>
      <h2>Checkout Pesanan Kamu Disini</h2>
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
          @php
              $total = 0;
              $no = 1;
          @endphp
            <div class="col-md-8">
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
                              </tr>
                          @endforeach
                          @endif
                        </tbody>
                        <tfoot>
                          <tr class="text-center  font-weight-bold border border-4">
                            <td colspan="4">Total</td>
                            <td>Rp.{{number_format($total)}},-</td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>
             
            </div>
           
                <div class="col-md-4">
                    <div class="card card-outline card-success">
                        <div class="card-header">
                            <div class="card-title">
                                Data Pemesan
                            </div>
                        </div>
                        <form action="{{route('buat_pesanan')}}" method="POST">
                            @csrf
                            @method('POST')
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Nama</label>
                                    <input type="text" placeholder="Nama" name="nama_pemesan" id="nama_pemesan" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="">No Telp</label>
                                    <input type="number" name="no_telp" placeholder="No Telp" id="no_telp" class="form-control" required>
                                </div>
                                    <input type="hidden" value="-" placeholder="Email" name="email" id="email" class="form-control">
                                <div class="form-group">
                                    <label>Alamat</label>
                                    <textarea name="alamat" placeholder="Alamat" id="alamat" class="form-control" cols="30" rows="5" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Catatan Pesanan</label>
                                    <textarea name="catatan_pesanan" placeholder="Catatan Pesanan" id="catatan_pesanan" class="form-control" cols="30" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <br>
                                    <input type="submit" value="Submit" class="btn btn-success form-control">
                                </div>
                            </div>
                        </form>     
                    </div>  
                </div>
            
        </div>
      </div>
    </section>
    <!-- End About Section -->

    
  </main>
@endsection
 