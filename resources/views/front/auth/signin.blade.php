@extends('front.layout.app')
@section('content-app')
    <!-- ======= Hero Section ======= -->
    <section id="hero" class="d-flex align-items-center" style="width: 100%">
        <div class="container text-center position-relative" data-aos="fade-in" data-aos-delay="200">
            <h1>Sign In</h1>
            <h2>Punya Akun ? Silahkan Sign In Disini</h2>

        </div>
    </section><!-- End Hero -->

    <main id="main">
        <section>
            <div class="container">
                <div class="row">
                    <div class="col-md-12 align-items-center">
                        @if (session('error'))
                            <div class="alert alert-danger text-center">
                                {{ session('error') }}
                            </div>
                        @endif
                        @if (session('success'))
                            <div class="alert alert-primary text-center">
                                {{ session('success') }}
                            </div>
                        @endif
                        <div class="card py-1">
                            <div class="card-body text-center">
                                <h3 class="text-center">Masuk Disini</h3>
                                <p class="text-center">Jika Ada Kendala Login bisa langsung Hubungi Administrator</p>
                                <form action="{{ route('authenticate') }}" method="post">
                                    @method('POST')
                                    @csrf
                                    <div class="form-group text-start">
                                        <label>Email</label>
                                        <input type="email" name="email" id="email" class="form-control" autofocus
                                            autocomplete="off">
                                    </div>
                                    <br>
                                    <div class="form-group text-start">
                                        <label>Password</label>
                                        <input type="password" name="password" id="password" class="form-control"
                                            autocomplete="off">
                                    </div>
                                    <br>
                                    <div class="form-group">
                                        <input type="submit" value="Sign In" class="form-control btn btn-success">
                                    </div>

                                </form>
                                <br>
                                Tidak Punya Akun ? Daftar <a href="{{ route('register') }}">Disini</a>
                                <br>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
