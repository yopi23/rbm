 <!-- ======= Header ======= -->
 <header id="header" class="fixed-top d-flex align-items-center">
    <div class="container">
      <div class="header-container d-flex align-items-center justify-content-between">
        <div class="logo">
          <h1 class="text-light"><a href="{{route('home')}}"><span>YOYOYCELL</span></a></h1>
          <!-- Uncomment below if you prefer to use an image logo -->
          <!-- <a href="index.html"><img src="{{asset('public/front/')}}/assets/img/logo.png" alt="" class="img-fluid"></a>-->
        </div>

        <nav id="navbar" class="navbar">
          <ul>
            <li><a class="nav-link scrollto active" href="{{route('home')}}">Beranda</a></li>
            <li><a class="nav-link scrollto" href="{{route('home')}}#about">Tentang</a></li>
            <li><a class="nav-link scrollto" href="{{route('service')}}">Service</a></li>
            <li><a class="nav-link scrollto" href="{{route('spareparts')}}">Sparepart & Acc</a></li>
            {{--<li><a class="nav-link scrollto " href="{{route('product')}}">Produk</a></li> --}}
            <li><a class="nav-link scrollto" href="{{route('home')}}#team">Team</a></li>
            <li><a class="nav-link scrollto" href="{{route('home')}}#contact">Kontak</a></li>
            <li><a class="nav-link" href="{{route('cart')}}">Keranjang</a></li>
            @if (Auth::check())
            <li><a class="getstarted scrollto" href="{{route('dashboard')}}">Dashboard</a></li>
            @else
            <li><a class="getstarted scrollto" href="{{route('login')}}">Sign In</a></li>
            @endif
            
          </ul>
          <i class="bi bi-list mobile-nav-toggle"></i>
        </nav><!-- .navbar -->

      </div><!-- End Header Container -->
    </div>
  </header><!-- End Header -->