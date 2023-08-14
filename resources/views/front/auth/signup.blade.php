@extends('front.layout.app')
@section('content-app')
  <!-- ======= Hero Section ======= -->
  <section id="hero" class="d-flex align-items-center" style="width: 100%">
    <div class="container text-center position-relative" data-aos="fade-in" data-aos-delay="200">
      <h1>Sign Up</h1>
      <h2>Belum punya Akun ? Silahkan Daftar Disini</h2>
      
    </div>
  </section><!-- End Hero -->

  <main id="main">
    <section>
        <form action="{{route('sign_up')}}" method="post">
            @csrf
            @method('POST')
        <div class="container">
            <div class="row">
                <div class="col-md-12 align-items-center">
                    @if(session('error'))
                    <div class="alert alert-danger text-center">
                        {{ session('error') }}
                    </div>
                    @endif
                    @if(session('success'))
                    <div class="alert alert-primary text-center">
                        {{ session('success') }}
                    </div>
                    @endif
                        <div class="card py-1">
                            <div class="card-body text-center">
                                <h3 class="text-center">Register Disini</h3>
                                <p class="text-center">Isikan Data Diri Kamu Disini</p>
                                    <div class="form-group text-start">
                                        <label>Nama</label>
                                        <input type="text" placeholder="Nama" name="nama" id="nama" class="form-control" required autofocus>
                                    </div>
                                    <br>
                                    <div class="form-group text-start">
                                        <label>Alamat</label>
                                        <textarea name="alamat_user" placeholder="Alamat" id="alamat_user" cols="30" rows="3" class="form-control" required></textarea>
                                    </div>
                                    <br>
                                    <div class="form-group text-start">
                                        <label>No Telp</label>
                                        <input type="number" placeholder="No Telepon" name="no_telp" id="no_telp" class="form-control" required>
                                    </div>
                                    <br>
                                    <div class="form-group text-start">
                                        <label>Email</label>
                                        <input type="email" placeholder="Email" name="email" id="email" class="form-control" required>
                                    </div>
                                    <br>
                                    <div class="form-group text-start">
                                        <label>Password</label>
                                        <input type="password" placeholder="*********" name="password" id="password" class="form-control" required>
                                    </div>
                                    <br>
                                    <div>
                                        <h2>PUNYA KODE INVITE ?</h2>
                                        <p>Punya Kode Invite ? Masukkan di sini</p>
                                        <div id="message_kode_invite"></div>
                                        <div class="form-group">
                                            <input type="text" name="kode_invite" id="kode_invite" class="form-control" placeholder="Kode Invite">
                                        </div>
                                        
                                    </div>
                                    <br>
                                    <div class="form-group">
                                        <input type="submit" value="Daftar" class="form-control btn btn-success">
                                    </div>
                              
                                <br>
                                Sudah Punya Akun ? Sign In <a href="{{route('login')}}">Disini</a>
                                <br>
                            </div>
                        </div>
                </div>
                
            </div>
        </div>
    </form>
    </section>
  </main>
@section('content-script')
<script>
    $('#kode_invite').on('keyup',function(){
        val = $(this).val();
        $.ajax({
            type: 'GET',
            url : '{{route('search_kode_invite')}}',
            data: {
                'search': val,
            },
            success:function(data){
                $('#message_kode_invite').html(data)
            }
        });
    });
</script>
@endsection
@endsection
 