<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-success navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="{{route('home')}}" class=" btn btn-success"><i class="fas fa-store"></i> My Shop</a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

      
      <!-- Notifications Dropdown Menu -->
      
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="fas fa-user"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-header"><div class="text-center">
            @if (isset($this_user) && $this_user->foto_user != '-')
            <img class="profile-user-img img-fluid img-circle"
                 src="{{asset('/uploads/{{$this_user->foto_user}}')}}"
                 alt="User profile picture">
            @else
            <img class="profile-user-img img-fluid img-circle"
                 src="{{asset('/img/user-default.png')}}"
                 alt="User profile picture">
            @endif
          </div>
          <h3 class="profile-username text-center">{{auth()->user()->name}}</h3>
          <p class="text-muted text-center">@switch($this_user->jabatan)
              @case(0)
                  Administrator
                  @break
              @case(1)
                  Owner
                  @break
              @case(2)
                  Kasir                  
                  @break
              @case(3)
                  Teknisi
                  @break
                  
          @endswitch</p>
        </span>
          <div class="dropdown-divider"></div>
          <a href="{{route('profile')}}" class="dropdown-item">
            <i class="fas fa-user mr-2"></i> Profile
          </a>
          <div class="dropdown-divider"></div>
          <form action="{{route('signout')}}" method="post">
            @csrf
            <button type="submit" class="dropdown-item text-danger">
              <i class="fas fa-sign-out-alt"></i> Logout
          </button>
          </form>
        </div>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->