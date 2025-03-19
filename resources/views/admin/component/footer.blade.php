  <!-- /.content-wrapper -->
  <footer class="main-footer">
      <strong>Copyright &copy; 2022- 2025 <a href="#">Yopi (yoyoycell)</a>.</strong>
      All rights reserved.
      <div class="float-right d-none d-sm-inline-block">
          <b>Version</b> 3
      </div>
  </footer>
  </div>
  <!-- ./wrapper -->

  <!-- jQuery -->
  <script src="{{ asset('admin/plugins/jquery/jquery.min.js') }}"></script>
  <!-- jQuery UI 1.11.4 -->
  <script src="{{ asset('admin/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
  <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
  <script>
      $.widget.bridge('uibutton', $.ui.button)
  </script>
  <!-- Bootstrap 4 -->
  <script src="{{ asset('admin/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

  <!-- jQuery Knob Chart -->
  <script src="{{ asset('admin/plugins/jquery-knob/jquery.knob.min.js') }}"></script>
  <!-- daterangepicker -->
  <script src="{{ asset('admin/plugins/moment/moment.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/daterangepicker/daterangepicker.js') }}"></script>
  <!-- Tempusdominus Bootstrap 4 -->
  <script src="{{ asset('admin/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
  <!-- Summernote -->
  <script src="{{ asset('admin/plugins/summernote/summernote-bs4.min.js') }}"></script>
  <!-- overlayScrollbars -->
  <script src="{{ asset('admin/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js') }}"></script>

  <script src="{{ asset('admin/plugins/datatables/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/jszip/jszip.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/pdfmake/pdfmake.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/pdfmake/vfs_fonts.js') }}"></script>
  <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/select2/js/select2.full.min.js') }}"></script>
  <!-- SweetAlert2 -->
  <script src="{{ asset('admin/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
  <!-- AdminLTE App -->
  <script src="{{ asset('admin/dist/js/adminlte.js') }}"></script>
  <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
  <script src="{{ asset('admin/dist/js/pages/dashboard.js') }}"></script>
  <script src="{{ asset('admin/dist/js/script.js') }}"></script>
  @yield('content-script')

  <script>
      // Fungsi untuk format rupiah
      function formatRupiah(angka) {
          var number_string = angka.replace(/[^,\d]/g, '').toString(),
              split = number_string.split(','),
              sisa = split[0].length % 3,
              rupiah = split[0].substr(0, sisa),
              ribuan = split[0].substr(sisa).match(/\d{3}/gi);

          if (ribuan) {
              var separator = sisa ? '.' : '';
              rupiah += separator + ribuan.join('.');
          }

          rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
          return rupiah;
      }

      // Fungsi untuk mendapatkan nilai numerik
      function getNumericValue(rupiah) {
          return rupiah.replace(/\./g, '').replace(/,/g, '.');
      }

      // Menggunakan kelas untuk menerapkan event listener
      document.querySelectorAll('.biaya-input').forEach(function(input) {
          input.addEventListener("input", function(e) {
              var biaya = e.target.value;
              var rupiah = formatRupiah(biaya);
              var numericValue = getNumericValue(biaya);
              e.target.value = rupiah;

              // Menyimpan nilai numerik di hidden input yang sesuai
              var hiddenInput = document.getElementById("biaya_servis");
              hiddenInput.value = numericValue;
          });
      });

      document.querySelectorAll('.dp-input').forEach(function(input) {
          input.addEventListener("input", function(e) {
              var biaya = e.target.value;
              var rupiah = formatRupiah(biaya);
              var numericValue = getNumericValue(biaya);
              e.target.value = rupiah;

              // Menyimpan nilai numerik di hidden input yang sesuai
              var dphidden = document.getElementById("dp");
              dphidden.value = numericValue;
          });
      });
      //penjualan
      // Mengambil semua elemen dengan class .bayar dan .total_bayar
      var bayarHidden = document.querySelectorAll(".bayar");
      var totalBayarHidden = document.querySelectorAll(".total_bayar");
      document.querySelectorAll('.in_bayar').forEach(function(input) {
          input.addEventListener("input", function(e) {
              var inbayar = e.target.value;
              var rupiah = formatRupiah(inbayar);
              var numericValue = getNumericValue(inbayar);
              e.target.value = rupiah;

              // Menyimpan nilai numerik di hidden input yang sesuai
              bayarHidden.forEach(function(hidden, index) {
                  hidden.value = numericValue; // Menyimpan di setiap elemen .bayar
              });

              totalBayarHidden.forEach(function(hidden, index) {
                  hidden.value = numericValue; // Menyimpan di setiap elemen .total_bayar
              });
          });
      });
  </script>
  <script>
      $(function() {
          $("#table_data").DataTable({
              "dom": 'Bfrtip',
              "buttons": [
                  'pdf',
                  'print'
              ]
          });
          $("#service_data").DataTable({
              "dom": 'Bfrtip',
              "buttons": [{
                      extend: 'pdfHtml5',
                      orientation: 'landscape',
                      pageSize: 'LEGAL'
                  },

                  'print'
              ]
          });
          $('#table_data2').DataTable({
              "paging": true,
              "lengthChange": false,
              "searching": false,
              "ordering": true,
              "info": true,
              "autoWidth": false,
          });
      });
  </script>
  @stack('scripts')
  </body>

  </html>
