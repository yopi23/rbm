 <!-- Main Sidebar Container -->
 <aside class="main-sidebar sidebar-dark-success elevation-4">
     <!-- Brand Logo -->
     <a href="{{ route('dashboard') }}" class="brand-link">
         <img src="{{ asset('/img/yoygreen.png') }}" alt="AdminLTE Logo" class="brand-image img-circle" style="opacity: 1">
         <span class="brand-text font-weight-bolder">YOYOYCELL</span>
     </a>

     <!-- Sidebar -->
     <div class="sidebar">
         <!-- Sidebar user panel (optional) -->
         <div class="user-panel mt-3 pb-3 mb-3 d-flex">
             <div class="image">
                 @if (isset($this_user) && $this_user->foto_user != '-')
                     {{-- <img src="{{asset('')}}/uploads/{{$this_user->foto_user}}" class="img-circle" alt="User Image"> --}}
                     <div style="background-image:url('{{ asset('uploads') }}/{{ $this_user->foto_user }}'); height:35px; width:35px; background-size: cover; background-position: center;"
                         alt=""></div>
                 @else
                     <img src="{{ asset('/img/user-default.png') }}" class="img-circle" alt="User Image" style="w">
                 @endif

             </div>
             <div class="info">
                 <a href="{{ route('profile') }}" class="d-block">{{ auth()->user()->name }}</a>
                 @switch($this_user->jabatan)
                     @case(0)
                         <span class="badge badge-success">Administrator</span>
                     @break

                     @case(1)
                         <span class="badge badge-success"> Owner</span>
                     @break

                     @case(2)
                         <span class="badge badge-success"> Kasir</span>
                     @break

                     @case(3)
                         <span class="badge badge-success"> Teknisi</span>
                     @break
                 @endswitch
             </div>
         </div>

         <!-- SidebarSearch Form -->
         <div class="form-inline">
             <div class="input-group" data-widget="sidebar-search">
                 <input class="form-control form-control-sidebar" type="search" placeholder="Search"
                     aria-label="Search">
                 <div class="input-group-append">
                     <button class="btn btn-sidebar">
                         <i class="fas fa-search fa-fw"></i>
                     </button>
                 </div>
             </div>
         </div>

         <!-- Sidebar Menu -->
         <nav class="mt-2">
             <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                 data-accordion="false">
                 <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
                 @if ($this_user->jabatan != '1' || $this_user->jabatan != '2')
                     <li class="nav-item ">
                         <a href="{{ route('dashboard') }}" class="nav-link  @yield('dashboard')">
                             <i class="nav-icon fas fa-tachometer-alt"></i>
                             <p>
                                 Dashboard
                             </p>
                         </a>
                     </li>
                 @endif
                 @if ($this_user->jabatan == '0')
                     <li class="nav-header">DATA MASTER</li>
                     <li class="nav-item">
                         <a href="{{ route('owner.index') }}" class="nav-link">
                             <i class="nav-icon fas fa-users"></i>
                             <p>
                                 Owner
                             </p>
                         </a>
                     </li>
                     <li class="nav-item">
                         <a href="{{ route('laporan_owner') }}" class="nav-link">
                             <i class="nav-icon fas fa-copy"></i>
                             <p>
                                 Laporan Owner
                             </p>
                         </a>
                     </li>
                 @endif
                 @if ($this_user->jabatan != '1' || $this_user->jabatan != '2')
                     <li class="nav-header">DATA MASTER</li>
                     @if ($this_user->jabatan == '1')
                         {{-- Tambahkan menu berikut di sidebar menu dalam file sidebar.blade.php --}}

                         <!-- Menu Keuangan -->
                         <li class="nav-item {{ request()->is('financial*') ? 'menu-open' : '' }}">
                             <a href="#" class="nav-link {{ request()->is('financial*') ? 'active' : '' }}">
                                 <i class="nav-icon fas fa-money-bill-wave"></i>
                                 <p>
                                     Keuangan
                                     <i class="right fas fa-angle-left"></i>
                                 </p>
                             </a>
                             <ul class="nav nav-treeview">
                                 <li class="nav-item">
                                     <a href="{{ route('financial.index') }}"
                                         class="nav-link {{ request()->is('financial') ? 'active' : '' }}">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Dashboard</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('financial.transactions') }}"
                                         class="nav-link {{ request()->is('financial/transactions') ? 'active' : '' }}">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Transaksi</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('financial.create') }}"
                                         class="nav-link {{ request()->is('financial/create') ? 'active' : '' }}">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Tambah Transaksi</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('financial.categories') }}"
                                         class="nav-link {{ request()->is('financial/categories') ? 'active' : '' }}">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Kategori</p>
                                     </a>
                                 </li>

                                 <li class="nav-item">
                                     <a href="{{ route('financial.reports') }}"
                                         class="nav-link {{ request()->is('financial/reports') ? 'active' : '' }}">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Laporan</p>
                                     </a>
                                 </li>
                             </ul>
                         </li>
                         <!-- Menu Inventory Management -->
                         <li
                             class="nav-item {{ in_array(request()->route()->getName(), ['admin.inventory.home', 'admin.inventory.restock-report', 'admin.inventory.bestsellers']) ? 'menu-open' : '' }}">
                             <a href="#"
                                 class="nav-link {{ in_array(request()->route()->getName(), ['admin.inventory.home', 'admin.inventory.restock-report', 'admin.inventory.bestsellers']) ? 'active' : '' }}">
                                 <i class="nav-icon fas fa-box"></i>
                                 <p>
                                     Manajemen Stok
                                     <i class="right fas fa-angle-left"></i>
                                 </p>
                             </a>
                             <ul class="nav nav-treeview">
                                 <li class="nav-item">
                                     <a href="{{ route('admin.inventory.home') }}"
                                         class="nav-link {{ request()->route()->getName() == 'admin.inventory.home' ? 'active' : '' }}">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Statistik</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('admin.inventory.restock-report') }}"
                                         class="nav-link {{ request()->route()->getName() == 'admin.inventory.restock-report' ? 'active' : '' }}">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Laporan Restock</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('admin.inventory.bestsellers') }}"
                                         class="nav-link {{ request()->route()->getName() == 'admin.inventory.bestsellers' ? 'active' : '' }}">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Produk Terlaris</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('order.index') }}"
                                         class="nav-link {{ request()->route()->getName() == 'admin.inventory.bestsellers' ? 'active' : '' }}">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>List Order</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('stock-opname.index') }}"
                                         class="nav-link {{ request()->route()->getName() == 'admin.inventory.bestsellers' ? 'active' : '' }}">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Stock Opname</p>
                                     </a>
                                 </li>
                             </ul>
                         </li>
                         <li class="nav-item">
                             <a href="{{ route('admin.tg.index') }}" class="nav-link">
                                 <i class="nav-icon fas fa-box"></i>
                                 <p>
                                     Daftar TG
                                 </p>
                             </a>
                         </li>
                     @endif
                     <li class="nav-item ">
                         <a href="#" class="nav-link ">
                             <i class="nav-icon fas fa-box"></i>
                             <p>
                                 Barang
                                 <i class="fas fa-angle-left right"></i>
                             </p>
                         </a>
                         <ul class="nav nav-treeview">
                             @if ($this_user->jabatan == '1')
                                 <li class="nav-item ">
                                     <a href="{{ route('produk') }}" class="nav-link ">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Data Barang</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('kategori_produk') }}" class="nav-link ">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Kategori</p>
                                     </a>
                                 </li>
                             @endif

                             <li class="nav-item">
                                 <a href="{{ route('stok_produk') }}" class="nav-link">
                                     <i class="far fa-circle nav-icon"></i>
                                     <p>Stok</p>
                                 </a>
                             </li>
                         </ul>
                     </li>
                     <li class="nav-item">
                         <a href="#" class="nav-link">
                             <i class="nav-icon 	fas fa-box-open"></i>
                             <p>
                                 Acc Dan Sparepart
                                 <i class="right fas fa-angle-left"></i>
                             </p>
                         </a>
                         <ul class="nav nav-treeview">
                             @if ($this_user->jabatan == '1')
                                 <li class="nav-item">
                                     <a href="{{ route('sparepart') }}" class="nav-link">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Sparepart</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('kategori_sparepart') }}" class="nav-link">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Kategori</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('sub_kategori_sparepart') }}"
                                         class="nav-link {{ Request::routeIs('sub_kategori_sparepart*') ? 'active' : '' }}">
                                         <i class="nav-icon fas fa-tags"></i>
                                         <p>Sub Kategori Sparepart</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('orders.view') }}" class="nav-link">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>List Order</p>
                                     </a>
                                 </li>
                             @endif

                             <li class="nav-item">
                                 <a href="{{ route('stok_sparepart') }}" class="nav-link">
                                     <i class="far fa-circle nav-icon"></i>
                                     <p>Stok</p>
                                 </a>
                             </li>
                             <li class="nav-item">
                                 <a href="{{ route('opname_sparepart') }}" class="nav-link">
                                     <i class="far fa-circle nav-icon"></i>
                                     <p>Stok Opname</p>
                                 </a>
                             </li>
                         </ul>
                     </li>
                     @if ($this_user->jabatan == '1')
                         <li class="nav-item">
                             <a href="{{ route('supplier.index') }}" class="nav-link @yield('supplier.index')">
                                 <i class="nav-icon fas fa-users"></i>
                                 <p>
                                     Supplier
                                 </p>
                             </a>
                         </li>
                         <li class="nav-item">
                             <a href="{{ route('customer.index') }}" class="nav-link @yield('customer.index')">
                                 <i class="nav-icon fas fa-users"></i>
                                 <p>
                                     Customer
                                 </p>
                             </a>
                         </li>

                         <li class="nav-header">TRANSAKSI</li>
                     @endif
                     <li class="nav-item @yield('maintodo')">
                         <a href="#" class="nav-link @yield('droptodo')">
                             <i class="nav-icon fas fa-cogs"></i>
                             <p>
                                 Repair
                                 <i class="fas fa-angle-left right"></i>
                             </p>
                         </a>
                         <ul class="nav nav-treeview">
                             <li class="nav-item">
                                 <a href="{{ route('all_service') }}" class="nav-link">
                                     <i class="far fa-circle nav-icon"></i>
                                     <p>Semua Service</p>
                                 </a>
                             </li>
                             <li class="nav-item">
                                 <a href="{{ route('todolist') }}" class="nav-link @yield('todolist')">
                                     <i class="far fa-circle nav-icon"></i>
                                     <p>Todo List</p>
                                 </a>
                             </li>
                         </ul>
                     </li>
                     @if ($this_user->jabatan == '1')
                         <li class="nav-item @yield('main')">
                             <a href="#" class="nav-link @yield('drop')">
                                 <i class="nav-icon fas fas fa-cash-register"></i>
                                 <p>
                                     Transaksi
                                     <i class="fas fa-angle-left right"></i>
                                 </p>
                             </a>
                             <ul class="nav nav-treeview">
                                 <li class="nav-item">
                                     <a href="{{ route('penjualan') }}" class="nav-link @yield('penjualan')">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Penjualan</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('pembelian.index') }}" class="nav-link @yield('Pembelian')">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Pembelian</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('pesanan') }}" class="nav-link ">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Pesanan</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('pengembalian') }}" class="nav-link @yield('pengambilan')">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Pengembalian</p>
                                     </a>
                                 </li>
                             </ul>
                         </li>
                     @endif
                     <li class="nav-item">
                         <a href="#" class="nav-link">
                             <i class="nav-icon fas fa-cart-arrow-down"></i>
                             <p>
                                 Pengeluaran
                                 <i class="fas fa-angle-left right"></i>
                             </p>
                         </a>
                         <ul class="nav nav-treeview">
                             <li class="nav-item">
                                 <a href="{{ route('pengeluaran_toko') }}" class="nav-link">
                                     <i class="far fa-circle nav-icon"></i>
                                     <p>Toko</p>
                                 </a>
                             </li>
                             @if ($this_user->jabatan == '1')
                                 <li class="nav-item">
                                     <a href="{{ route('pengeluaran_operasional') }}" class="nav-link">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Operasional Opex</p>
                                     </a>
                                 </li>
                             @endif
                         </ul>
                     </li>
                     @if ($this_user->jabatan == '1')
                         <li class="nav-header">Lainnya</li>
                         <li class="nav-item">
                             <a href="{{ route('laporan') }}" class="nav-link @yield('laporan')">
                                 <i class="nav-icon fas fa-copy"></i>
                                 <p>
                                     Laporan
                                 </p>
                             </a>
                         </li>
                         <li class="nav-item @yield('main')">
                             <a href="#" class="nav-link @yield('persentase')">
                                 <i class="nav-icon fas fa-cog"></i>
                                 <p>
                                     Settings
                                     <i class="fas fa-angle-left right"></i>
                                 </p>
                             </a>
                             <ul class="nav nav-treeview">
                                 <li class="nav-item">
                                     <a href="{{ route('presentase') }}" class="nav-link @yield('persentase')">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Persentase</p>
                                     </a>
                                 </li>
                             </ul>
                         </li>
                         <li class="nav-item has-treeview @yield('employee_management')">
                             <a href="#" class="nav-link @yield('employee_management')">
                                 <i class="nav-icon fas fa-users-cog"></i>
                                 <p>
                                     Manajemen Karyawan
                                     <i class="right fas fa-angle-left"></i>
                                 </p>
                             </a>
                             <ul class="nav nav-treeview">
                                 <li class="nav-item">
                                     <a href="{{ route('admin.attendance.index') }}"
                                         class="nav-link @yield('attendance')">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Absensi</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('admin.salary.index') }}" class="nav-link @yield('salary_settings')">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Pengaturan Gaji</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('admin.violations.index') }}"
                                         class="nav-link @yield('violations')">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Pelanggaran</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('admin.employee.monthly-report') }}"
                                         class="nav-link @yield('monthly_report')">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Laporan Bulanan</p>
                                     </a>
                                 </li>
                                 <li class="nav-item">
                                     <a href="{{ route('admin.schedule.index') }}"
                                         class="nav-link @yield('schedule')">
                                         <i class="far fa-circle nav-icon"></i>
                                         <p>Jadwal Kerja</p>
                                     </a>
                                 </li>
                             </ul>
                         </li>
                         <li class="nav-item">
                             <a href="{{ route('users.index') }}" class="nav-link">
                                 <i class="nav-icon fas fa-users"></i>
                                 <p>
                                     Pengguna
                                 </p>
                             </a>
                         </li>
                     @endif
                 @endif

             </ul>
         </nav>
         <!-- /.sidebar-menu -->
     </div>
     <!-- /.sidebar -->
 </aside>
