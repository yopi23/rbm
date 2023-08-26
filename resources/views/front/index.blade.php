@extends('front.layout.app')
@section('content-app')
    


  <!-- ======= Hero Section ======= -->
  <section id="hero" class="d-flex align-items-center">
    <div class="container text-center position-relative" data-aos="fade-in" data-aos-delay="200">
      <h1>SELAMAT DATANG DI YOYOYCELL</h1>
      <h2>Service Dan Grosir Sparpart,Handphone Dan lain lain</h2>
      {{-- <a href="#about" class="btn-get-started scrollto">Sign In</a> --}}
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
    <section id="about" class="about">
      <div class="container">

        <div class="row content">
          <div class="col-lg-6" data-aos="fade-right" data-aos-delay="100">
            <h2>KAMI MENERIMA SERVICE HP DAN LAPTOP</h2>
            <h3>Sparepart dan accessories lengkap</h3>
          </div>
          <div class="col-lg-6 pt-4 pt-lg-0" data-aos="fade-left" data-aos-delay="200">
            <p>
              YOYOYCELL adalah tempat perbaikan gadget terbaik dan lengkap, sebagian besar pengerjaan bisa di tunggu. sukucadang yang sudah siap dan tenaga ahli yang cukup kompeten. kabar baik bagi anda kami juga melayani grosir dan eceran.
              yoyoycell berdiri sejak tahun 2016 sampai saat ini. beberapa poin service yang kami sediakan diantaranya
            </p>
            <ul>
              <li><i class="ri-check-double-line"></i> Hp dan Laptop Mati total, Mentok logo, Ganti ic, Ganti touchscreen, Ganti lcd</li>
              <li><i class="ri-check-double-line"></i> Masalah pada jaringan, Lupa sandi (ketentuan berlaku) dll</li>
              <li><i class="ri-check-double-line"></i> Jual beli hp dan laptop</li>
            </ul>
            <p class="fst-italic">
              Utamakan bertanya! dan kami siap melayani anda.
            </p>
          </div>
        </div>

      </div>
    </section><!-- End About Section -->

    <!-- ======= Counts Section ======= -->
    <section id="counts" class="counts">
      <div class="container">

        <div class="row counters">

          <div class="col-lg-3 col-6 text-center">
            <span data-purecounter-start="0" data-purecounter-end="{{$service}}" data-purecounter-duration="1" class="purecounter"></span>
            <p>Service</p>
          </div>

          <div class="col-lg-3 col-6 text-center">
            <span data-purecounter-start="0" data-purecounter-end="{{$sparepart}}" data-purecounter-duration="1" class="purecounter"></span>
            <p>Sparepart</p>
          </div>

          <div class="col-lg-3 col-6 text-center">
            <span data-purecounter-start="0" data-purecounter-end="{{$datateam->count()}}" data-purecounter-duration="1" class="purecounter"></span>
            <p>Staff</p>
          </div>

          <div class="col-lg-3 col-6 text-center">
            <span data-purecounter-start="0" data-purecounter-end="{{$produk}}" data-purecounter-duration="1" class="purecounter"></span>
            <p>Produk</p>
          </div>

        </div>

      </div>
    </section><!-- End Counts Section -->

    <!-- ======= Why Us Section ======= -->
    <section id="why-us" class="why-us">
      <div class="container">

        <div class="row">
          <div class="col-lg-4 d-flex align-items-stretch" data-aos="fade-right">
            <div class="content">
              <h3>Kenapa harus YOYOYcell?</h3>
              <p>
                Kenapa Harus pilih YOYOYCELL Daripada Service Lain ?
              </p>
            </div>
          </div>
          <div class="col-lg-8 d-flex align-items-stretch">
            <div class="icon-boxes d-flex flex-column justify-content-center">
              <div class="row">
                <div class="col-xl-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="100">
                  <div class="icon-box mt-4 mt-xl-0">
                    <i class="bx bx-receipt"></i>
                    <h4>Bisa Ditunggu</h4>
                    <p>sebagian besar pengerjaan bisa di tunggu.</p>
                  </div>
                </div>
                <div class="col-xl-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="200">
                  <div class="icon-box mt-4 mt-xl-0">
                    <i class="bx bx-cube-alt"></i>
                    <h4>Tenaga Ahli dan Kompeten</h4>
                    <p>Ditangani oleh Teknisi Yang berpengalaman dan kompeten</p>
                  </div>
                </div>
                <div class="col-xl-4 d-flex align-items-stretch" data-aos="zoom-in" data-aos-delay="300">
                  <div class="icon-box mt-4 mt-xl-0">
                    <i class="bx bx-images"></i>
                    <h4>Terbaik</h4>
                    <p>Service,Suku Cadang dan Produk lainnya Diambil Yang Terbaik</p>
                  </div>
                </div>
              </div>
            </div><!-- End .content-->
          </div>
        </div>

      </div>
    </section><!-- End Why Us Section -->

    <!-- ======= Cta Section ======= -->
    <section id="cta" class="cta">
      <div class="container">

        <div class="text-center " data-aos="zoom-in">
          <h3>Mau Cek Status Service ?</h3>
          <p> Mau Cek status Servis kamu ? Coba Cari Disini</p>
          <a href="{{route('service')}}" class="btn-get-started scrollto">Disini</a>
        </div>

      </div>
    </section><!-- End Cta Section -->



    <!-- ======= Team Section ======= -->
    <section id="team" class="team">
      <div class="container">

        <div class="row">
          <div class="col-lg-4">
            <div class="section-title" data-aos="fade-right">
              <h2>Team</h2>
              <p>Tenaga Ahli Dan Kompeten Kami</p>
            </div>
          </div>
          <div class="col-lg-8">
            <div class="row">
              @foreach ($datateam as $item)
              <div class="col-lg-6">
                <br>
                <div class="member" data-aos="zoom-in" data-aos-delay="100">
                  <div class="pic">
                    @if ($item->foto_user != '-')
                    {{-- <img src="{{asset('public/')}}uploads/{{$item->foto_user}}" class="img-fluid" alt=""> --}}
                    <div style="background-image:url('{{asset('public')}}/uploads/{{$item->foto_user}}'); height:150px; background-size: cover; background-position: center;"  alt="" ></div>
                    @else
                        <img src="{{asset('public/')}}img/user-default.png" class="img-fluid" alt="">
                    @endif
                  </div>
                  <div class="member-info">
                    <h4>{{$item->name}}</h4>
                    @switch($item->jabatan)
                        @case(0)
                          <span>Administrator</span> 
                            @break
                        @case(1)
                        <span> Owner</span> 
                            @break
                        @case(2)
                        <span> Kasir</span>               
                            @break
                        @case(3)
                        <span> Teknisi</span> 
                            @break
                            
                    @endswitch
                    <div class="social">
                      <a href="{{$item->link_twitter}}"><i class="ri-twitter-fill"></i></a>
                      <a href="{{$item->link_facebook}}"><i class="ri-facebook-fill"></i></a>
                      <a href="{{$item->link_instagram}}"><i class="ri-instagram-fill"></i></a>
                      <a href="{{$item->link_linkedin}}"> <i class="ri-linkedin-box-fill"></i> </a>
                    </div>
                  </div>
                </div>
              </div>
              @endforeach
            </div>

          </div>
        </div>

      </div>
    </section><!-- End Team Section -->

    <!-- ======= Contact Section ======= -->
    <section id="contact" class="contact">
      <div class="container">
        <div class="row">
          <div class="col-lg-4" data-aos="fade-right">
            <div class="section-title">
              <h2>Kontak</h2>
              <p>Untuk info Detail Mengenai Servis dan Stok hp kita, bisa hubungi kita atau langsung saja datang ke toko kita.</p>
            </div>
          </div>

          <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
            <div class="info mt-4">
              <i class="bi bi-geo-alt"></i>
              <h4>Alamat</h4>
              <p>JL. Raya Sagaraten</p>
            </div>
            <div class="row">
              <div class="col-lg-12">
                <div class="info w-100 mt-4">
                  <i class="bi bi-phone"></i>
                  <h4>Call:</h4>
                  <p>+62 8560 3124 871</p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section><!-- End Contact Section -->

  </main><!-- End #main -->
@endsection
 