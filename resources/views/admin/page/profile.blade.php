<div class="row">
    <div class="col-md-4">
        <div class="card card-success card-outline">
            <div class="card-header text-center">
                <label>User Profile</label>
            </div>
            <div class="card-body">
                <div class="text-center">
                    @if (isset($this_user) && $this_user->foto_user != '-' && file_exists(public_path('uploads/' . $this_user->foto_user)))
                        <img class="profile-user-img img-fluid img-circle" src="{{ asset($this_user->foto_user) }}"
                            alt="Foto profil pengguna">
                    @else
                        <img class="profile-user-img img-fluid img-circle" src="{{ asset('img/user-default.png') }}"
                            alt="Foto profil pengguna">
                    @endif

                    <br><br>
                    @if ($this_user->jabatan != '0')
                        @if ($this_user->jabatan == '1')
                            <div class="text-uppercase">
                                <h4>{{ $this_user->kode_invite }}</h4>
                            </div>
                        @endif
                        @if ($this_user->jabatan != '1')
                            <small>saldo anda</small>
                            <h4>Rp.{{ number_format($this_user->saldo) }},-</h4>
                        @endif

                    @endif

                </div>
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Nama</b>
                        <div class="float-right">{{ $this_user->fullname }}</div>
                    </li>
                    <li class="list-group-item">
                        <b>Jabatan</b>
                        <div class="float-right"> @switch($this_user->jabatan)
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
                    </li>
                    <li class="list-group-item">
                        <b>Email</b>
                        <div class="float-right">{{ $this_user->email }}</div>
                    </li>
                    @if ($this_user->jabatan != '0')
                        @if ($this_user->jabatan == '1')
                            <li class="list-group-item">
                                <b>Kode Invite</b>
                                <div class="float-right">{{ $this_user->kode_invite }}</div>
                            </li>
                        @endif
                        @if ($this_user->jabatan != '1')
                            <li class="list-group-item">
                                <b>Komisi Bulan Ini</b>
                                <div class="float-right">Rp.{{ number_format($komisi) }},-</div>
                            </li>
                        @endif
                    @endif

                </ul>
                <form action="{{ route('signout') }}" method="post">
                    @csrf
                    <button type="submit" class="btn btn-danger form-control">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>
        @if ($this_user->jabatan == '0' || $this_user->jabatan == '1')
            <div class="card">
                <div class="card-header"><b>Karyawan</b></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Saldo</th>
                                    <th>Komisi</th>
                                    <th>Penarikan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($employees as $employee)
                                    <tr>
                                        <td>{{ $employee->fullname }}</td>
                                        <td>Rp.{{ number_format($employee->saldo) }},-</td>
                                        <td>Rp.{{ number_format($employee->total_komisi) }},-</td>
                                        <td>Rp.{{ number_format($employee->total_penarikan) }},-</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>
    <div class="col-md-8">
        <div class="card card-success card-outline">
            <div class="card-header">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#profile" data-toggle="tab">Profile</a></li>
                    @if ($this_user->jabatan != '0')
                        <li class="nav-item"><a class="nav-link" href="#gaji" data-toggle="tab">Penarikan Gaji</a>
                        </li>
                    @endif
                </ul>
            </div>
            <div class="card-body">

                <div class="tab-content">
                    <div class="tab-pane active" id="profile">
                        <div class="row">
                            <div class="col-md-12">
                                <form action="{{ route('update_profile', $this_user->id_user) }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-4 text-center">
                                            @if (isset($this_user) && $this_user->foto_user != '-')
                                                <img class="profile-user-img img-fluid"
                                                    src="{{ asset('uploads/' . $this_user->foto_user) }}"
                                                    alt="User profile picture" id="view-img">
                                            @else
                                                <img class="profile-user-img img-fluid"
                                                    src="{{ asset('/img/user-default.png') }}"
                                                    alt="User profile picture" id="view-img">
                                            @endif
                                        </div>
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label>Foto User</label>
                                                <input type="file" accept="image/png,image/jpeg" class="form-control"
                                                    name="foto_user" id="foto_user">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Nama</label>
                                        <input type="text" value="{{ $this_user->name }}" name="name"
                                            id="name" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Alamat</label>
                                        <textarea class="form-control" name="alamat_user" id="alamat_user" cols="30" rows="5">{{ $this_user->alamat_user }}</textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="text" value="{{ $this_user->email }}" name="email"
                                            id="email" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Password</label>
                                        <input type="password" name="password" id="password" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>No Telp</label>
                                        <input type="text" value="{{ $this_user->no_telp }}" name="no_telp"
                                            id="no_telp" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Role</label>
                                        <select name="jabatan" id="jabatan" class="form-control" disabled>
                                            <option value="0"
                                                {{ isset($this_user) && $this_user->jabatan == '0' ? 'selected' : '' }}>
                                                Administrator </option>
                                            <option
                                                value="1"{{ isset($this_user) && $this_user->jabatan == '1' ? 'selected' : '' }}>
                                                Owner </option>
                                            <option
                                                value="2"{{ isset($this_user) && $this_user->jabatan == '2' ? 'selected' : '' }}>
                                                Kasir </option>
                                            <option value="3"
                                                {{ isset($this_user) && $this_user->jabatan == '3' ? 'selected' : '' }}>
                                                Teknisi </option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Link Twitter</label>
                                        <input type="text" value="{{ $this_user->link_twitter }}"
                                            name="link_twitter" id="link_twitter" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Link Facebook</label>
                                        <input type="text" value="{{ $this_user->link_facebook }}"
                                            name="link_facebook" id="link_facebook" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Link Instagram</label>
                                        <input type="text" value="{{ $this_user->link_instagram }}"
                                            name="link_instagram" id="link_instagram" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>Link Linkedin</label>
                                        <input type="text" value="{{ $this_user->link_linkedin }}"
                                            name="link_linkedin" id="link_linkedin" class="form-control">
                                    </div>
                                    <button type="submit" class="btn btn-success form-control">Update</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @if ($this_user->jabatan != '0')
                        <div class="tab-pane" id="gaji">
                            {{-- @if ($this_user->jabatan == '1')
                                <div class="card"> --}}
                            {{-- <form action="{{ route('update_all_penarikan_statuses') }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-success">Perbarui Semua Status</button>
                                    </form> --}}
                            {{-- </div>
                            @endif --}}
                            <?php
                            if ($this_user->jabatan != '1') {
                                echo '<a href="#" class="btn btn-sm btn-success" data-toggle="modal" data-target="#modal_tarik_dana"><i class="fas fa-plus"></i> Tambah</a>';
                                echo '<hr>';
                            } elseif ($this_user->jabatan == '1') {
                                ?>
                            <form action="{{ route('update_all_penarikan_statuses') }}" method="POST">
                                @csrf
                                @method('PUT')
                                <button type="submit" class="btn btn-sm btn-success my-2">Selesaikan Semua
                                    Status</button>
                            </form>
                            <?php
                            }
                            ?>

                            <div class="table responsive">
                                <table class="table" id="TABLES_1">
                                    <thead>
                                        <th>#</th>
                                        <th>Tanggal</th>
                                        <th>Nama</th>
                                        <th>Jumlah</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </thead>
                                    @foreach ($penarikan as $item)
                                        <tr>
                                            <td>{{ $loop->index + 1 }}</td>
                                            <td>{{ $item->tgl_penarikan }}</td>
                                            <td>{{ $item->name }}</td>
                                            <td>Rp.{{ number_format($item->jumlah_penarikan) }},-</td>
                                            <td>
                                                @switch($item->status_penarikan)
                                                    @case(0)
                                                        <span class="badge badge-warning">Pending</span>
                                                    @break

                                                    @case(1)
                                                        <span class="badge badge-success">Berhasil</span>
                                                    @break

                                                    @case(2)
                                                        <span class="badge badge-danger">Gagal</span>
                                                    @break

                                                    @default
                                                @endswitch
                                            </td>
                                            <td>
                                                <form action="{{ route('delete_penarikan', $item->id_penarikan) }}"
                                                    onsubmit="return confirm('Apakah Kamu Yakin ?')" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <a href="#" class="btn btn-info btn-sm" data-toggle="modal"
                                                        data-target="#view_penarikan_{{ $item->id_penarikan }}"><i
                                                            class="fas fa-eye"></i></a>
                                                    @if ($this_user->jabatan == '1' && $item->status_penarikan == '0')
                                                        <a href="#" class="btn btn-warning btn-sm"
                                                            data-toggle="modal"
                                                            data-target="#edit_penarikan_{{ $item->id_penarikan }}"><i
                                                                class="fas fa-edit"></i></a>
                                                    @endif
                                                    @if ($item->status_penarikan == '0' && $this_user->jabatan == '1')
                                                        <button type="submit" class="btn btn-danger btn-sm"><i
                                                                class="fas fa-trash"></i></button>
                                                    @endif
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modal_tarik_dana">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Penarikan Gaji</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('store_penarikan') }}" method="POST">
                    @csrf
                    @method('POST')
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" value="{{ date('Y-m-d') }}" name="tgl_penarikan"
                                id="tgl_penarikan" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Jumlah Penarikan</label>
                            <input type="text" value="0" max={{ $this_user->saldo }} name="jumlah_penarikan"
                                id="jumlah_penarikan" class="form-control" required hidden>
                            <input type="text" max={{ $this_user->saldo }} name="inbon" id="inbon"
                                class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Catatan Penarikan</label>
                            <textarea name="catatan_penarikan" placeholder="Catatan" class="form-control" id="catatan_penarikan" cols="30"
                                rows="4"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-success">Simpan</button>
                    </div>
                </form>

            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
    @foreach ($penarikan as $item)
        <div class="modal fade" id="view_penarikan_{{ $item->id_penarikan }}">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Penarikan Gaji</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    @csrf
                    @method('POST')
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tanggal</label>
                            <input type="date" name="tgl_penarikan" id="tgl_penarikan"
                                value="{{ $item->tgl_penarikan }}" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" value="{{ $item->name }}" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Jumlah Penarikan</label>
                            <input type="text" name="jumlah_penarikan" id="jumlah_penarikan"
                                value="{{ number_format($item->jumlah_penarikan) }}" name="jumlah_penarikan"
                                id="jumlah_penarikan" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label>Catatan Penarikan</label>
                            <textarea name="catatan_penarikan" placeholder="Catatan" class="form-control" id="catatan_penarikan" cols="30"
                                rows="4" readonly>{{ $item->catatan_penarikan }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Status Penarikan</label>
                            <select name="status_penarikan" id="status_penarikan" class="form-control" disabled>
                                <option value="0" {{ $item->status_penarikan == '0' ? 'selected' : '' }}>Pending
                                </option>
                                <option value="1" {{ $item->status_penarikan == '1' ? 'selected' : '' }}>Sukses
                                </option>
                                <option value="2" {{ $item->status_penarikan == '2' ? 'selected' : '' }}>Batal
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                    </div>

                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        @if ($this_user->jabatan == '1')
            <div class="modal fade" id="edit_penarikan_{{ $item->id_penarikan }}">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Edit Penarikan Gaji</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{ route('update_penarikan', $item->id_penarikan) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body">
                                <div class="form-group">
                                    <label>Tanggal</label>
                                    <input type="datetime" name="tgl_penarikan" id="tgl_penarikan"
                                        value="{{ $item->tgl_penarikan }}" class="form-control" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Nama</label>
                                    <input type="text" value="{{ $item->name }}" class="form-control" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Jumlah Penarikan</label>
                                    <input type="number" name="jumlah_penarikan" id="jumlah_penarikan"
                                        value="{{ $item->jumlah_penarikan }}" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label>Catatan Penarikan</label>
                                    <textarea name="catatan_penarikan" placeholder="Catatan" class="form-control" id="catatan_penarikan" cols="30"
                                        rows="4">{{ $item->catatan_penarikan }}</textarea>
                                </div>
                                @if ($this_user->jabatan == '1')
                                    <div class="form-group">
                                        <label>Status Penarikan</label>
                                        <select name="status_penarikan" id="status_penarikan" class="form-control">
                                            <option value="0"
                                                {{ $item->status_penarikan == '0' ? 'selected' : '' }}>Pending</option>
                                            <option value="1"
                                                {{ $item->status_penarikan == '1' ? 'selected' : '' }}>Sukses</option>
                                            <option value="2"
                                                {{ $item->status_penarikan == '2' ? 'selected' : '' }}>Batal</option>
                                        </select>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer justify-content-between">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                                <button type="submit" class="btn btn-success">Simpan</button>
                            </div>
                        </form>
                    </div>
                    <!-- /.modal-content -->
                </div>
                <!-- /.modal-dialog -->
            </div>
        @endif
    @endforeach
    @section('content-script')
        <script>
            $('#foto_user').change(function(event) {
                $("#view-img").fadeIn("fast").attr('src', URL.createObjectURL(event.target.files[0]));
            });
        </script>
        <script>
            function formatRupiah(angka, prefix) {
                if (angka.startsWith("0") && !angka.includes(".")) {
                    angka = angka.replace(/^0+/, "");
                }
                const number_string = angka.toString().replace(/[^,\d]/g, "");
                const split = number_string.split(",");
                const sisa = split[0].length % 3;
                let rupiah = split[0].substr(0, sisa);
                const ribuan = split[0].substr(sisa).match(/\d{1,3}/g);

                if (ribuan) {
                    separator = sisa ? "." : "";
                    rupiah += separator + ribuan.join(".");
                }

                rupiah = split[1] != undefined ? rupiah + ',' + split[1] :
                    rupiah; // Tambahkan kondisi untuk menghilangkan angka 0 di depan jika tidak ada koma
                return prefix == undefined ? rupiah : (rupiah ? 'Rp. ' + rupiah : '');
            }


            function getNumericValue(rupiah) {
                const numericValue = rupiah.replace(/[^0-9]/g, "");
                return numericValue;
            }

            const incasbon = document.getElementById("inbon");
            const hiddenTBon = document.getElementById("jumlah_penarikan");

            incasbon.addEventListener("input", function(e) {
                const biaya = e.target.value;
                const rupiah = formatRupiah(biaya);
                const numericValue = getNumericValue(biaya);
                e.target.value = rupiah;
                hiddenTBon.value = numericValue;
            });
        </script>
    @endsection
