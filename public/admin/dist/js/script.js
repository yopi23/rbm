$(function () {
    //Initialize Select2 Elements
   $('#dataTable').DataTable({
     "paging": true,
     "lengthChange": true,
     "searching": true,
     "ordering": true,
     "info": true,
     "autoWidth": false,
     "responsive": false,
     scrollX: 200,
     scrollY: 300,
   });
   $("table[id^='TABLE']").DataTable( {
       scrollCollapse: true,
       searching: true,
       paging: true,
       autoWidth: false,
   });
    //Initialize Select2 Elements
    $('.select2').select2({
     tags: true,
     
    })

   //Initialize Select2 Elements
   $('.select-bootstrap').select2({
     theme: 'bootstrap4'
   })
   //
 });