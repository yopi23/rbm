<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailCatatanService;
use App\Models\DetailPartLuarService;
use App\Models\DetailPartServices;
use App\Models\Garansi;
use App\Models\PresentaseUser;
use App\Models\ProfitPresentase;
use Illuminate\Http\Request;
use App\Models\Sevices as modelServices;
use App\Models\Sparepart;
use App\Models\User;
use App\Models\UserDetail;
use Milon\Barcode\Facades\DNS1DFacade;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDO;

class ServiceController extends Controller
{
    //
    public function getTable($thead = '<th>No</th><th>Aksi</th>', $tbody = '')
    {
        $result = '<div class="table-responsive"><table class="table" id="dataTable">';
        $result .= '<thead>' . $thead . '</thead>';
        $result .= '<tbody>' . $tbody . '</body>';
        $result .= '</table></div>';
        return $result;
    }
    public function getHiddenItemForm($method = 'POST')
    {
        $result = '' . csrf_field() . '';
        $result .= '' . method_field($method) . '';
        return $result;
    }
    public function view_all()
    {
        $page = "Data Service";
        $thead = '<th width="5%">No</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>No Telp</th>
                    <th>Unit</th>
                    <th>Keterangan</th>
                    <th>Harga</th>
                    <th>Sparepart</th>
                    <th>Status</th>
                    <th>Update at</th>
                    <th>Teknisi</th>
                    <th>Print</th>
                    ';
        $tbody = '';
        $no = 1;
        $year = date('Y'); // Mendapatkan tahun saat ini
        $data_service = modelServices::leftjoin('detail_part_services', 'sevices.id', '=', 'kode_services')
            ->leftjoin('detail_part_luar_services', 'sevices.id', '=', 'detail_part_luar_services.kode_services')
            ->leftjoin('users', 'sevices.id_teknisi', '=', 'users.id')
            ->where('kode_owner', '=', $this->getThisUser()->id_upline)
            ->whereYear('tgl_service', $year) // Menambahkan kondisi untuk memfilter data hanya untuk tahun tertentu
            ->orderBy('sevices.id', 'desc')
            ->latest()
            ->get([
                'sevices.id as id_service',
                'sevices.*',
                // 'users.*',
                'detail_part_services.detail_harga_part_service',
                'detail_part_services.qty_part as qty_part_toko',
                'detail_part_luar_services.qty_part as qty_part_luar',
                'detail_part_luar_services.created_at as dpl_created_at',
                'detail_part_luar_services.harga_part'
            ]);
        foreach ($data_service as $item) {
            $teknisi = $item->id_teknisi != null || $item->id_teknisi != '' ? User::where([['id', '=', $item->id_teknisi]])->get(['name'])->first()->name : '-';
            $qr = DNS1DFacade::getBarcodeHTML($item->kode_service, "C39", 1, 100);
            $tbody .= '<tr>
                        <td>' . $no++ . '</td>
                        <td>' . $item->tgl_service . '<br>' . $item->kode_service . '</td>
                        <td>' . $item->nama_pelanggan . '</td>
                        <td><a href="https://wa.me/62' . $item->no_telp . '?text=Assalamualaikum, pemberitahuan bahwa unit ' . $item->type_unit . ' sudah selesai diperbaiki. Teknisi: ' . $teknisi . ' (Terimakasih)" target="_blank">' . $item->no_telp . '</a></td>
                        <td>' . $item->type_unit . '</td>
                        <td>' . $item->keterangan . '</td>
                        <td>Rp.' . number_format($item->total_biaya) . ',-</td>
                        <td>Rp.' . number_format(($item->detail_harga_part_service * $item->qty_part_toko) + ($item->harga_part * $item->qty_part_luar))  . ',-</td>
                        <td>' . $item->status_services . '</td>
                        <td>' . $item->updated_at . '</td>
                        <td>
                        ' . $teknisi . '
                        </td>
                         <td>
                         <a href="' . route('nota_tempel_selesai', $item->id_service) . '" target="_blank" class="btn btn-sm btn-primary mt-2"><i class="fas fa-print"></i></a>
                        <button class="btn btn-secondary btn-sm my-2" data-toggle="modal" data-target="#editteknisi">Edit</button>
                         </td>
                        </tr>';
        }
        $data = $this->getTable($thead, $tbody);
        return view('admin.layout.card_layout', compact(['page', 'data']));
    }

    public function detail_service(Request $request, $id)
    {
        $page = "Detail Services";
        $data = modelServices::findOrFail($id);
        $garansi = Garansi::where([['type_garansi', '=', 'service'], ['kode_garansi', '=', $data->kode_service]])->get();
        $catatan = DetailCatatanService::join('users', 'detail_catatan_services.kode_user', '=', 'users.id')->where([['detail_catatan_services.kode_services', '=', $id]])->get(['detail_catatan_services.id as id_catatan', 'detail_catatan_services.*', 'users.*']);
        $detail = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')->where([['detail_part_services.kode_services', '=', $id]])->get(['detail_part_services.id as id_detail_part', 'detail_part_services.*', 'spareparts.*']);
        $detail_luar = DetailPartLuarService::where([['kode_services', '=', $id]])->get();
        return view('admin.forms.proses_service', compact(['page', 'data', 'detail', 'detail_luar', 'catatan', 'garansi']));
    }
    public function update_detail_service(Request $request, $id)
    {
        $update = modelServices::findOrFail($id);
        $update->update([
            'tgl_service' => $request->tgl_service,
            'nama_pelanggan' => $request->nama_pelanggan,
            'dp' => $request->dp,
            'no_telp' => $request->no_telp,
            'type_unit' => $request->type_unit,
            'keterangan' => $request->keterangan,
            'total_biaya' => $request->total_biaya,
            'created_at' => Carbon::now(),
        ]);
        if ($update) {
            return redirect()->route('todolist')->with('success', 'Update Data Service Berhasil');
        }
    }
    public function update_service(Request $request, $id)
    {
        $update = modelServices::findOrFail($id);
        $update->update([
            'tgl_service' => $request->tgl_service,
            'nama_pelanggan' => $request->nama_pelanggan,
            'no_telp' => $request->no_telp,
            'type_unit' => $request->type_unit,
            'keterangan' => $request->keterangan,
            'dp' => $request->dp,
            'total_biaya' => $request->total_biaya,
            'created_at' => Carbon::now(),
        ]);
        if ($update) {
            return redirect()->route('all_service')->with('success', 'Update Data Service Berhasil');
        }
    }

    //CRUD Garansi Service
    public function store_garansi_service(Request $request)
    {
        $create = Garansi::create([
            'type_garansi' => 'service',
            'kode_garansi' => $request->kode_garansi,
            'nama_garansi' => $request->nama_garansi,
            'tgl_mulai_garansi' => $request->tgl_mulai_garansi,
            'tgl_exp_garansi' => $request->tgl_exp_garansi,
            'catatan_garansi' => $request->catatan_garansi != null ? $request->catatan_garansi : '-',
            'user_input' => auth()->user()->id,
            'kode_owner' => $this->getThisUser()->id_upline,
        ]);
        if ($create) {
            return redirect()->back()->with([
                'success' => 'Garansi Ditambahkan'
            ]);
        }
        return redirect()->back()->with([
            'error' => 'Oops,Something Went Wrong'
        ]);
    }
    public function update_garansi_service(Request $request, $id)
    {
        $data = Garansi::findOrFail($id);
        $data->update([
            'nama_garansi' => $request->nama_garansi,
            'tgl_mulai_garansi' => $request->tgl_mulai_garansi,
            'tgl_exp_garansi' => $request->tgl_exp_garansi,
            'catatan_garansi' => $request->catatan_garansi != null ? $request->catatan_garansi : '-',
            'user_input' => auth()->user()->id,
        ]);
        if ($data) {
            return redirect()->back()->with([
                'success' => 'Garansi diUpdate'
            ]);
        }
        return redirect()->back()->with([
            'error' => 'Oops,Something Went Wrong'
        ]);
    }
    public function delete_garansi_service(Request $request, $id)
    {
        $data = Garansi::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->back()->with([
                'success' => 'Garansi dihapus'
            ]);
        }
        return redirect()->back()->with([
            'error' => 'Oops,Something Went Wrong'
        ]);
    }


    //CRUD Catatan Service
    public function store_catatan_service(Request $request)
    {
        $create = DetailCatatanService::create([
            'tgl_catatan_service' => $request->tgl_catatan_service,
            'kode_services' => $request->kode_services,
            'kode_user' => auth()->user()->id,
            'catatan_service' => $request->catatan_service != null ? $request->catatan_service : '-',
        ]);
        if ($create) {
            return redirect()->back()->with([
                'success' => 'Catatan Berhasil Di Buat'
            ]);
        }
        return redirect()->back()->with([
            'error' => 'Oops, Something Went Wrong'
        ]);
    }
    public function delete_catatan_service(Request $request, $id)
    {
        $data = DetailCatatanService::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->back();
        }
        return redirect()->back()->with([
            'error' => 'Oops, Something Went Wrong'
        ]);
    }
    //CRUD Sparepart Toko
    public function store_sparepart_toko(Request $request)
    {
        $cek = DetailPartServices::where([['kode_services', '=', $request->kode_services], ['kode_sparepart', '=', $request->kode_sparepart]])->get()->first();
        if ($cek) {
            $update = DetailPartServices::findOrFail($cek->id);
            $qty_baru = $cek->qty_part + $request->qty_part;
            $update->update([
                'qty_part' => $qty_baru,
                'user_input' => auth()->user()->id,
            ]);
            if ($update) {
                $update_sparepart = Sparepart::findOrFail($update->kode_sparepart);
                $stok_awal = $update_sparepart->stok_sparepart + $cek->qty_part;
                $stok_baru = $stok_awal - $qty_baru;
                $update_sparepart->update([
                    'stok_sparepart' => $stok_baru,
                ]);
                return redirect()->back();
            }
        } else {
            $update_sparepart = Sparepart::findOrFail($request->kode_sparepart);
            $create = DetailPartServices::create([
                'kode_services' => $request->kode_services,
                'kode_sparepart' => $request->kode_sparepart,
                'detail_modal_part_service' => $update_sparepart->harga_beli,
                'detail_harga_part_service' => $update_sparepart->harga_jual,
                'qty_part' => $request->qty_part,
                'user_input' => auth()->user()->id,
            ]);
            if ($create) {

                $stok_baru = $update_sparepart->stok_sparepart - $request->qty_part;
                $update_sparepart->update([
                    'stok_sparepart' => $stok_baru,
                ]);
                return redirect()->back();
            }
        }
    }
    public function delete_sparepart_toko(Request $request, $id)
    {
        $data = DetailPartServices::findOrFail($id);
        if ($data) {
            $update_sparepart = Sparepart::findOrFail($data->kode_sparepart);
            $stok_baru = $update_sparepart->stok_sparepart + $data->qty_part;
            $update_sparepart->update([
                'stok_sparepart' => $stok_baru,
            ]);
        }
        $data->delete();
        if ($data) {
            return redirect()->back();
        }
    }
    //CRUD Sparepart Luar
    public function store_sparepart_luar(Request $request)
    {
        $create = DetailPartLuarService::create([
            'kode_services' => $request->kode_services,
            'nama_part' => $request->nama_part,
            'harga_part' => $request->harga_part,
            'qty_part' => $request->qty_part,
            'user_input' => auth()->user()->id,
        ]);
        if ($create) {
            return redirect()->back();
        }
    }
    public function update_sparepart_luar(Request $request, $id)
    {
        $update = DetailPartLuarService::findOrFail($id);
        $update->update([
            'nama_part' => $request->nama_part,
            'harga_part' => $request->harga_part,
            'qty_part' => $request->qty_part,
            'user_input' => auth()->user()->id,
        ]);
        if ($update) {
            return redirect()->back();
        }
    }
    public function delete_sparepart_luar(Request $request, $id)
    {
        $data = DetailPartLuarService::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->back();
        }
    }
    public function view_to_do()
    {
        $page = "To Do list";

        $antrian = modelServices::where([['status_services', '=', 'Antri'], ['kode_owner', '=', $this->getThisUser()->id_upline]])->get();

        $proses = modelServices::leftJoin(DB::raw('(SELECT kode_services, SUM(qty_part * harga_part) AS part_luar FROM detail_part_luar_services GROUP BY kode_services) AS part_luar_services'), 'sevices.id', '=', 'part_luar_services.kode_services')
            ->leftJoin(DB::raw('(SELECT kode_services, SUM(qty_part * detail_harga_part_service) AS part_toko FROM detail_part_services GROUP BY kode_services) AS part_toko'), 'sevices.id', '=', 'part_toko.kode_services')
            ->leftjoin('users', 'sevices.id_teknisi', '=', 'users.id')
            ->select([
                'sevices.id as id_service',
                'sevices.kode_service',
                'sevices.tgl_service',
                'sevices.nama_pelanggan',
                'sevices.no_telp',
                'sevices.type_unit',
                'sevices.keterangan',
                'sevices.total_biaya',
                'sevices.dp',
                'sevices.id_teknisi',
                'sevices.kode_pengambilan',
                'sevices.status_services',
                'sevices.kode_owner',
                DB::raw('COALESCE(SUM(part_luar_services.part_luar), 0) + COALESCE(SUM(part_toko.part_toko), 0) AS total_harga_part'),
                'users.id as user_id', // Adjust this line according to your needs
                'users.name'
            ])
            ->where([['id_teknisi', '=', auth()->user()->id], ['status_services', '=', 'Diproses']])
            ->groupBy(
                'sevices.id',
                'sevices.kode_service',
                'sevices.tgl_service',
                'sevices.nama_pelanggan',
                'sevices.no_telp',
                'sevices.type_unit',
                'sevices.keterangan',
                'sevices.total_biaya',
                'sevices.dp',
                'sevices.id_teknisi',
                'sevices.kode_pengambilan',
                'sevices.status_services',
                'sevices.kode_owner',
                'users.id',
                'users.name',
            )
            ->get();

        // $proses = modelServices::leftjoin('detail_part_services', 'sevices.id', '=', 'kode_services')
        //     ->leftjoin('detail_part_luar_services', 'sevices.id', '=', 'detail_part_luar_services.kode_services')
        //     ->leftjoin('users', 'sevices.id_teknisi', '=', 'users.id')
        //     ->where([['id_teknisi', '=', auth()->user()->id], ['status_services', '=', 'Diproses']])
        //     ->orderByDesc('id_service')
        //     ->get([
        //         'sevices.id as id_service',
        //         'sevices.*',
        //         'users.*',
        //         'detail_part_services.*',
        //         'detail_part_services.qty_part as qty_part_toko',
        //         'detail_part_services.detail_harga_part_service as hpart_toko',
        //         'detail_part_luar_services.qty_part as qty_part_luar',
        //         'detail_part_luar_services.*',
        //     ]);





        $selesai = modelServices::join('users', 'sevices.id_teknisi', '=', 'users.id')->where([['id_teknisi', '=', auth()->user()->id], ['status_services', '=', 'Selesai']])->get(['sevices.id as id_service', 'sevices.*', 'users.*']);
        $batal = modelServices::join('users', 'sevices.id_teknisi', '=', 'users.id')->where([['id_teknisi', '=', auth()->user()->id], ['status_services', '=', 'Cancel']])->get(['sevices.id as id_service', 'sevices.*', 'users.*']);
        $content = view('admin.page.todolist', compact(['antrian', 'proses', 'selesai', 'batal']));
        return view('admin.layout.blank_page', compact(['page', 'content']));
    }
    public function proses_service(Request $request, $id)
    {
        $update = modelServices::findOrFail($id);
        $update->update([
            'status_services' => $request->status_services,
            'id_teknisi' => auth()->user()->id
        ]);
        if ($update) {
            if ($request->status_services == 'Cancel') {
                $data = DetailPartServices::where([['kode_services', '=', $id]])->get();
                foreach ($data as $i) {
                    $update_sparepart = Sparepart::findOrFail($i->kode_sparepart);
                    $newstok = $update_sparepart->stok_sparepart + $i['qty_part'];
                    $update_sparepart->update([
                        'stok_sparepart' => $newstok
                    ]);
                }
            }
            if ($request->status_services == 'Selesai') {
                if ($this->getThisUser()->jabatan != '1') {
                    $part_toko_service = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')->where([['kode_services', '=', $id]])->get(['detail_part_services.id as id_detail_part', 'detail_part_services.*', 'spareparts.*']);
                    $part_luar_toko_service = DetailPartLuarService::where([['kode_services', '=', $id]])->get();
                    $presentase = PresentaseUser::where([['kode_user', '=', $this->getThisUser()->kode_user]])->get()->first();
                    if ($presentase) {
                        $total_part = 0;
                        foreach ($part_toko_service as $a) {
                            $total_part += $a->harga_jual * $a->qty_part;
                        }
                        foreach ($part_luar_toko_service as $b) {
                            $total_part += $b->harga_part * $b->qty_part;
                        }
                        $profit = $update->total_biaya - $total_part;
                        $fix_profit =  $profit * $presentase->presentase / 100;
                        $komisi = ProfitPresentase::create([
                            'tgl_profit' => date('Y-m-d'),
                            'kode_service' => $id,
                            'kode_presentase' => $presentase->id,
                            'kode_user' => $this->getThisUser()->kode_user,
                            'profit' => $fix_profit,
                        ]);
                        if ($komisi) {
                            $pegawais = UserDetail::where([['kode_user', '=', $this->getThisUser()->kode_user]])->get()->first();
                            $new_saldo = $pegawais->saldo + $fix_profit;
                            $pegawais->update([
                                'saldo' => $new_saldo
                            ]);
                        }
                    }
                }
            }
            return redirect()->route('todolist');
        }
    }
    public function oper_service(Request $request, $id)
    {
        $update = modelServices::findOrFail($id);
        $update->update([
            'status_services' => $request->status_services,
            'id_teknisi' => '',
        ]);
        if ($update) {
            return redirect()->back();
        }
    }
}
