@section('page', $page)

@include('admin.component.header')
@include('admin.component.navbar')
@include('admin.component.sidebar')

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">@yield('page')</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Home</a></li>
              <li class="breadcrumb-item"><a href="{{route('cabang.index')}}">Cabang</a></li>
              <li class="breadcrumb-item active">@yield('page')</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
              @if(session('error'))
                  <div class="alert alert-danger">
                      {{ session('error') }}
                  </div>
              @endif
              @if(session('success'))
                  <div class="alert alert-primary">
                      {{ session('success') }}
                  </div>
              @endif
              
              <div class="card card-outline card-success">
                    <div class="card-header">
                        <div class="card-title">Form @yield('page')</div>
                    </div>
                    <form action="{{ route('cabang.transfer.process') }}" method="POST" id="transferForm">
                      @csrf
                      @method('POST')
                      <div class="card-body">
                          <div class="row">
                              <div class="col-md-6">
                                  <div class="form-group">
                                    <label for="from_cabang_id">Cabang Asal (Sumber Stok)</label>
                                    <select name="from_cabang_id" id="from_cabang_id" class="form-control" required>
                                      <option value="">-- Pilih Cabang Asal --</option>
                                      @foreach($cabangs as $cabang)
                                        <option value="{{ $cabang->id }}">{{ $cabang->nama_cabang }}</option>
                                      @endforeach
                                    </select>
                                  </div>
                              </div>
                              <div class="col-md-6">
                                  <div class="form-group">
                                    <label for="to_cabang_id">Cabang Tujuan (Penerima Stok)</label>
                                    <select name="to_cabang_id" id="to_cabang_id" class="form-control" required disabled>
                                      <option value="">-- Pilih Cabang Tujuan --</option>
                                      @foreach($cabangs as $cabang)
                                        <option value="{{ $cabang->id }}">{{ $cabang->nama_cabang }}</option>
                                      @endforeach
                                    </select>
                                  </div>
                              </div>
                          </div>

                          <div class="form-group mt-3">
                            <label for="sparepart_id">Pilih Barang / Sparepart</label>
                            <select name="sparepart_id" id="sparepart_id" class="form-control" required disabled>
                              <option value="">-- Pilih Cabang Asal Dulu --</option>
                            </select>
                            <small class="text-muted" id="stock_info" style="display: none;">Stok tersedia di cabang asal: <strong class="text-success" id="available_qty">0</strong> unit</small>
                          </div>

                          <div class="form-group mt-3">
                            <label for="qty">Jumlah (Quantity) yang Ditransfer</label>
                            <input type="number" name="qty" id="qty" placeholder="Jumlah kuantitas" class="form-control" min="1" required disabled>
                          </div>
                      </div>
                      <div class="card-footer">
                        <button type="submit" class="btn btn-success" id="submitBtn" disabled>Proses Transfer</button>
                        <a href="{{route('cabang.index')}}" class="btn btn-danger">Batal</a>
                      </div>
                    </form>
                </div>
            </div>
        </div>
      </div>
    </section>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $(document).ready(function() {
        const fromCabang = $('#from_cabang_id');
        const toCabang = $('#to_cabang_id');
        const sparepart = $('#sparepart_id');
        const qty = $('#qty');
        const submitBtn = $('#submitBtn');
        const stockInfo = $('#stock_info');
        const availableQty = $('#available_qty');

        let currentAvailableStock = 0;

        fromCabang.on('change', function() {
            const fromId = $(this).val();
            
            // Reset fields
            toCabang.prop('disabled', true).val('');
            sparepart.prop('disabled', true).html('<option value="">-- Loading... --</option>');
            qty.prop('disabled', true).val('');
            submitBtn.prop('disabled', true);
            stockInfo.hide();

            if (!fromId) {
                sparepart.html('<option value="">-- Pilih Cabang Asal Dulu --</option>');
                return;
            }

            // Enable and filter target branch options (exclude selected source branch)
            toCabang.prop('disabled', false);
            toCabang.find('option').show();
            toCabang.find(`option[value="${fromId}"]`).hide();

            // Fetch items belonging to source branch
            let url = "{{ route('cabang.get-items', ':id') }}";
            url = url.replace(':id', fromId);

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    sparepart.html('<option value="">-- Pilih Barang --</option>');
                    if (data.length === 0) {
                        sparepart.html('<option value="">-- Tidak ada barang bersisa stok di cabang ini --</option>');
                    } else {
                        sparepart.prop('disabled', false);
                        data.forEach(function(item) {
                            sparepart.append(`<option value="${item.id}" data-stok="${item.stok_sparepart}">[${item.kode_sparepart}] ${item.nama_sparepart} (Stok: ${item.stok_sparepart})</option>`);
                        });
                    }
                },
                error: function() {
                    sparepart.html('<option value="">-- Gagal memuat data barang --</option>');
                }
            });
        });

        sparepart.on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const stok = parseInt(selectedOption.data('stok')) || 0;

            if ($(this).val()) {
                currentAvailableStock = stok;
                availableQty.text(stok);
                stockInfo.show();
                qty.prop('disabled', false).attr('max', stok);
                
                if (toCabang.val()) {
                    submitBtn.prop('disabled', false);
                }
            } else {
                stockInfo.hide();
                qty.prop('disabled', true).val('');
                submitBtn.prop('disabled', true);
            }
        });

        toCabang.on('change', function() {
            if ($(this).val() && sparepart.val()) {
                submitBtn.prop('disabled', false);
            } else {
                submitBtn.prop('disabled', true);
            }
        });

        $('#transferForm').on('submit', function(e) {
            const transferQty = parseInt(qty.val()) || 0;
            if (transferQty > currentAvailableStock) {
                alert(`Gagal! Jumlah transfer (${transferQty}) melebihi stok yang tersedia (${currentAvailableStock}) di cabang asal.`);
                e.preventDefault();
                return false;
            }
            return true;
        });
    });
  </script>
@include('admin.component.footer')
