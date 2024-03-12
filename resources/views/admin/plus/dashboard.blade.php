@include('admin.plus.template.head')
@include('admin.plus.template.nav')
<div class="container-fluid">
    <div class="row">
        @include('admin.plus.template.sidebar')
        <!-- Laporan  -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main active">
            <div class="card-tr" style="margin-top: 55px;">
                <div class="row">
                    <div class="col-2">
                        <img src="#" title="images">
                    </div>
                    <div class="col">
                        <span><b>
                                <h3>Laporan Keuangan</h3>
                            </b></span>
                    </div>
                </div>
            </div>
            <!-- kolom main -->
            <div class="card-tr" style="margin-top: 5px;">
                <div class="row">
                    <!-- satu -->
                    <div class="col-12 col-md-4">
                        <div class="card mb-2">
                            <div class="card-body">
                                <strong>Total Pembukuan</strong><br>
                                <span>Rp.1.000.000</span>
                            </div>
                        </div>
                        <div class="card mb-2">
                            <div class="card-body">
                                <strong>Uang Real</strong><br>
                                <span>Rp.1.000.000</span>
                            </div>
                        </div>
                        <label><span class="btn btn-success">PASS</span></label>
                    </div>
                    <!-- end satu -->
                    <!-- dua -->
                    <div class="col">
                        <div class="row">
                            <div class="col">
                                <div class="card mb-2">
                                    <div class="card-body">
                                        ini car
                                    </div>
                                </div>
                                <div class="card mb-2">
                                    <div class="card-body">
                                        ini card
                                    </div>
                                </div>
                                <div class="card mb-2">
                                    <div class="card-body">
                                        ini card
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card mb-2">
                                    <div class="card-body">
                                        ini card
                                    </div>
                                </div>
                                <div class="card mb-2">
                                    <div class="card-body">
                                        ini card
                                    </div>
                                </div>
                                <div class="card mb-2">
                                    <div class="card-body">
                                        ini card
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- end dua -->
                </div>
            </div>
            <!-- kolom -->
        </main>
        <!-- end laporan -->
    </div>
</div>
@include('admin.plus.template/foot')
