@extends('front.layout.app')
@section('content-app')
    
 

  <!-- ======= Hero Section ======= -->
  <section id="hero" class="d-flex align-items-center">
    <div class="container text-center position-relative" data-aos="fade-in" data-aos-delay="200">
      <h1>Cek Status Service ?</h1>
      <h2>Mau Cek Status Service Kamu ? Cek Disini Dengan Mengetikkan Kode Invoice</h2>
      <form action="{{route('service')}}" method="GET">
        <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <select name="type_search" id="type_search" class="form-control">
                  <option value="service" {{isset($request->type_search) != null && $request->type_search == 'service' ? 'selected' : ''}}>Cek Status Services</option>
                  <option value="garansi" {{isset($request->type_search) != null && $request->type_search == 'garansi' ? 'selected' : ''}}>Cek Garansi </option>
                </select>
              </div>
              <br>
            </div>
          <div class="col-md-7">
            <div class="form-group">
              <input type="text" name="q" value="{{isset($request->q) != null ? $request->q : ''}}" placeholder="Kode Service..." class="form-control">  
            </div>
            <br>
          </div>
          <div class="col-md-1">
            <div class="form-group">
              <input type="submit" value="Cek" class=" form-control btn btn-success">
            </div>
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

    <section>
      <div class="container">

        <div class="container">
          <div class="row content">
            @if (isset($data) != null && $request->type_search == 'service')
              <div class="col-md-4">
                <div class="card card-outline card-success">
                  <div class="card-body">
                    <div class="text-center border p-2">
                     <center> {!! $data['qr']; !!}</center>
                      <h3 class="text-uppercase"><b>{{$data['data']->kode_service}}</b></h3>
                    </div>
                    <br>
                    <div class="text-center border border-5">
                      <h2 class="text-uppercase">{{$data['data']->status_services}}</h2>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-8">
               
                  <div class="card card-outline card-success">
                    <div class="card-body">
                      <h5>Rincian</h5>
                      <hr>
                      <div class="row">
                        <div class="col-md-8">
                          <table border="0">
                            <tr>
                              <td>Nama</td>
                              <td>: {{$data['data']->nama_pelanggan}}</td>
                            </tr>
                            <tr>
                              <td>No Telp</td>
                              <td>: {{$data['data']->no_telp}}</td>
                            </tr>
                            <tr>
                              <td>Unit</td>
                              <td>: {{$data['data']->type_unit}}</td>
                            </tr>
                            <tr>
                              <td>Teknisi</td>
                              <td>: {{$data['teknisi']}}</td>
                            </tr>
                            <tr>
                              <td>Total Biaya</td>
                              <td>:<b>Rp.{{number_format($data['data']->total_biaya)}},-</b> </td>
                            </tr>
                            <tr>
                              <td>Keterangan</td>
                              <td>: {{$data['data']->keterangan}}</td>
                            </tr>
                          </table>
                        </div>
                        <div class="col-md-4 text-right">
                          <table border="0">
                            <tr>
                              <td><b>#{{$data['data']->kode_service}}</b></td>
                            </tr>
                            <tr>
                              <td>{{$data['data']->created_at}}</td>
                            </tr>
                          </table>
                        </div>
                      </div>
                      <br>
                      <h5>Sparepart Yang Digunakan</h5>
                      <hr>
                      <table class="table">
                        <thead>
                          <th>No</th>
                          <th>Nama Sparepart</th>
                          <th>Qty</th>
                        </thead>
                        <tbody>
                          @php
                              $no = 1;
                              $total_sparepart = 0;
                          @endphp
                          @foreach ($data['detail'] as $item)
                              <tr>
                                <td>{{$no++}}</td>
                                <td>{{$item->nama_sparepart}}</td>
                                <td>{{$item->qty_part}}</td>
                              </tr>
                          @endforeach
                          @foreach ($data['detail_luar'] as $item)
                              <tr>
                                <td>{{$no++}}</td>
                                <td>{{$item->nama_part}}</td>
                                <td>{{$item->qty_part}}</td>
                              </tr>
                          @endforeach
                        </tbody>
                      </table>
                      <hr>
                      <small><b>*Jika Service Belum selesai, Harga yang tertera bisa berubah sesuai kebutuhan service</b></small>
                    </div>
                  </div>
               
              </div>
            @endif
            @if (isset($data) != null && $request->type_search == 'garansi')
              <div class="col-md-4">
                <div class="card card-outline card-success">
                  <div class="card-body">
                    <div class="text-center border p-2">
                     <center> {!! $data['qr']; !!}</center>
                      <h3 class="text-uppercase"><b>{{$request->q}}</b></h3>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-8">
                <div class="card card-outline card-success">
                  <div class="card-body">
                    <h5>Garansi</h5>
                    <hr>
                    @foreach ($data['data'] as $item)
                        <div class="card card-outline card-success">
                          <div class="card-body">
                            <div class="card-title">
                              {{$item->nama_garansi}}
                            </div>
                          <hr>
                            <p>{{$item->catatan_garansi}}</p>
                          </div>
                          <div class="card-footer">
                            <small>*Berlaku Hingga {{$item->tgl_exp_garansi}}</small>
                          </div>
                        </div>
                        <br>
                    @endforeach
                  </div>
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </section><!-- End About Section -->
  </main>
@endsection
 