{{-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script> --}}
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"
    integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous">
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"
    integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous">
</script>

<script src="{{ asset('DataTables/jQuery-3.7.0/jquery-3.7.0.js') }}"></script>
{{-- <script src="{{ asset('DataTables/datatables.min.js') }}"></script> --}}

{{-- <script src="https://code.jquery.com/jquery-3.7.0.js"></script> --}}
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script type="text/javascript">
    new DataTable('#example');
    new DataTable('#table');
</script>

<style>
    /* CSS untuk mengubah tampilan li items di sidebar */
    ul#sidebarList li {
        background-color: #ffffff;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 5px;
        margin-top: 15px;
    }
</style>



<script>
    // Fungsi untuk menampilkan atau menyembunyikan sidebar saat tombol diklik
    function toggleSidebar() {
        var sidebar = document.getElementById("sidebar");
        var main = document.querySelector(".main");
        var toggleButton = document.getElementById("toggleSidebar");

        if (sidebar.classList.contains("active")) {
            sidebar.classList.remove("active");
            main.classList.remove("active");
        } else {
            sidebar.classList.add("active");
            main.classList.add("active");
        }
    }

    // Menambahkan event listener ke tombol untuk mengontrol sidebar
    var toggleButton = document.getElementById("toggleSidebar");
    toggleButton.addEventListener("click", toggleSidebar);

    // Fungsi untuk menampilkan atau menyembunyikan daftar UL saat tombol "Tambah" diklik
    function toggleSidebarList() {
        var sidebarList = document.getElementById("sidebarList");
        var toggleButton = document.getElementById("toggleSidebarList");

        if (sidebarList.style.display === "none") {
            sidebarList.style.display = "block";
        } else {
            sidebarList.style.display = "none";
        }
    }

    // Menambahkan event listener ke tombol "Tambah" pada sidebar
    var toggleButtonList = document.getElementById("toggleSidebarList");
    toggleButtonList.addEventListener("click", toggleSidebarList);
</script>
<script>
    // Temukan semua elemen dengan kelas "nav-link"
    const navLinks = document.querySelectorAll('.nav-link');

    // Fungsi untuk menangani klik pada elemen dengan kelas "nav-link"
    function handleNavLinkClick(event) {
        // Ambil tautan href dari elemen <a>
        const href = event.target.querySelector('a').getAttribute('href');

        // Alihkan ke URL yang sesuai
        window.location.href = href;
    }

    // Tambahkan peristiwa klik ke semua elemen dengan kelas "nav-link"
    navLinks.forEach(function(navLink) {
        navLink.addEventListener('click', handleNavLinkClick);
    });
</script>

</body>

</html>
