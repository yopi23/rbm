  <!-- /.content-wrapper -->
  <footer class="main-footer">
      <strong>Copyright &copy; 2022- 2023 <a href="#">PE Engine & Yopi (yoyoycell)</a>.</strong>
      All rights reserved.
      <div class="float-right d-none d-sm-inline-block">
          <b>Version</b> Beta
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
  <!-- ChartJS -->
  <script src="{{ asset('admin/plugins/chart.js/Chart.min.js') }}"></script>
  <!-- Sparkline -->
  <script src="{{ asset('admin/plugins/sparklines/sparkline.js') }}"></script>
  <!-- JQVMap -->
  <script src="{{ asset('admin/plugins/jqvmap/jquery.vmap.min.js') }}"></script>
  <script src="{{ asset('admin/plugins/jqvmap/maps/jquery.vmap.usa.js') }}"></script>
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
      function formatRupiah(angka, prefix) {
          var number_string = angka.toString().replace(/[^,\d]/g, "");
          var split = number_string.split(",");
          var sisa = split[0].length % 3;
          var rupiah = split[0].substr(0, sisa);
          var ribuan = split[0].substr(sisa).match(/\d{3}/g);

          if (ribuan) {
              separator = sisa ? "." : "";
              rupiah += separator + ribuan.join(".");
          }

          rupiah = split[1] != undefined ? rupiah + ',' + split[1] :
              rupiah; // Tambahkan kondisi untuk menghilangkan angka 0 di depan jika tidak ada koma
          return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
      }

      function getNumericValue(rupiah) {
          var numericValue = rupiah.replace(/[^0-9]/g, "");
          return numericValue;
      }

      var biayaServisInput = document.getElementById("in_biaya_servis");
      var hiddenInput = document.getElementById("biaya_servis");
      var dpInput = document.getElementById("in_dp");
      var dphidden = document.getElementById("dp");

      biayaServisInput.addEventListener("input", function(e) {
          var biaya = e.target.value;
          var rupiah = formatRupiah(biaya);
          var numericValue = getNumericValue(biaya);
          e.target.value = rupiah;
          hiddenInput.value = numericValue;
      });
      dpInput.addEventListener("input", function(e) {
          var biaya = e.target.value;
          var rupiah = formatRupiah(biaya);
          var numericValue = getNumericValue(biaya);
          e.target.value = rupiah;
          dphidden.value = numericValue;
      });
  </script>
  <script>
      $(function() {
          $("#table_data").DataTable({
              "dom": 'Bfrtip',
              "buttons": [
                  'pdf', 'print'
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
  </body>

  </html>
