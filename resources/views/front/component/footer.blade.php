 <!-- ======= Footer ======= -->
 <footer id="footer">

    <div class="footer-top">
      <div class="container">
        <div class="row">

          <div class="col-lg-4 col-md-6 footer-contact">
            <h3>YOYOYCELL</h3>
            <p>
                YOYOYCELL adalah tempat perbaikan gadget terbaik dan lengkap, sebagian besar pengerjaan bisa di tunggu. sukucadang yang sudah siap dan tenaga ahli yang cukup kompeten. kabar baik bagi anda kami juga melayani grosir dan eceran.
            </p>
          </div>

          <div class="col-lg-4 col-md-6 footer-links">
            <h4>Menu</h4>
            <ul>
              <li><i class="bx bx-chevron-right "></i> <a href="#hero" class="scrollto">Beranda</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="#about" class="scrollto">Tentang</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="{{route('service')}}">Service</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="{{route('sparepart')}}">SparePart</a></li>
              <li><i class="bx bx-chevron-right"></i> <a href="{{route('product')}}">Produk</a></li>
            </ul>
          </div>


          <div class="col-lg-4 col-md-6 footer-newsletter">
            <h4>Layanan Pelanggan</h4>
            <div class="social-links text-md-right pt-3 pt-md-0">
              <a href="#" class="twitter"><i class="bx bxl-twitter"></i></a>
              <a href="#" class="facebook"><i class="bx bxl-facebook"></i></a>
              <a href="#" class="instagram"><i class="bx bxl-instagram"></i></a>
              <a href="#" class="google-plus"><i class="bx bxl-skype"></i></a>
              <a href="#" class="linkedin"><i class="bx bxl-linkedin"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="container d-md-flex py-4">

      <div class="me-md-auto text-center text-md-start">
        <div class="copyright">
          &copy; Copyright <strong><span>YOYOYCELL</span></strong>. All Rights Reserved
        </div>
        <div class="credits">
          <!-- All the links in the footer should remain intact. -->
          <!-- You can delete the links only if you purchased the pro version. -->
          <!-- Licensing information: https://bootstrapmade.com/license/ -->
          <!-- Purchase the pro version with working PHP/AJAX contact form: https://bootstrapmade.com/bethany-free-onepage-bootstrap-theme/ -->
          Developed by <a href="#">PE Engine</a>
        </div>
      </div>
      
    </div>
  </footer><!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="{{asset('public/front/')}}/assets/vendor/purecounter/purecounter_vanilla.js"></script>
  <script src="{{asset('public/front/')}}/assets/vendor/aos/aos.js"></script>
  <script src="{{asset('public/front/')}}/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="{{asset('public/front/')}}/assets/vendor/glightbox/js/glightbox.min.js"></script>
  <script src="{{asset('public/front/')}}/assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>
  <script src="{{asset('public/front/')}}/assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="{{asset('public/front/')}}/assets/vendor/php-email-form/validate.js"></script>

  <script src="{{asset('public/admin/')}}/plugins/jquery/jquery.min.js"></script>
  <!-- Template Main JS File -->
  <script src="{{asset('public/front/')}}/assets/js/main.js"></script>
  @yield('content-script')  

</body>

</html>