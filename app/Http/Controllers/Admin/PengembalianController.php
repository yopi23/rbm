<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengambilan;
use Illuminate\Http\Request;
use App\Models\Sevices as modelServices;
use App\Traits\KategoriLaciTrait;

class PengembalianController extends Controller
{
    use KategoriLaciTrait;
    //
    public function index()
    {
        $page = "Pengembalian";
        $listLaci = $this->getKategoriLaci();
        $data = Pengambilan::where([['user_input', '=', auth()->user()->id], ['kode_owner', '=', $this->getThisUser()->id_upline], ['status_pengambilan', '=', '0']])->get()->first();
        $count = Pengambilan::where([['kode_owner', '=', $this->getThisUser()->id_upline]])->get()->count();

        if (!$data) {
            $kode_pengambilan = 'PNG' . date('Ymd') . auth()->user()->id . $count;
            $create = Pengambilan::create([
                'kode_pengambilan' => $kode_pengambilan,
                'tgl_pengambilan' => date('Y-m-d'),
                'nama_pengambilan' => '',
                'total_bayar' => '0',
                'user_input' => auth()->user()->id,
                'status_pengambilan' => '0',
                'kode_owner' => $this->getThisUser()->id_upline,
            ]);
            if ($create) {
                $data = Pengambilan::where([['user_input', '=', auth()->user()->id], ['kode_owner', '=', $this->getThisUser()->id_upline], ['status_pengambilan', '=', '0']])->get()->first();
            }
        }
        $service = modelServices::where([['kode_pengambilan', '=', $data->id], ['status_services', '=', 'Selesai'], ['kode_owner', '=', $this->getThisUser()->id_upline]])->get();
        $done_service = modelServices::where([['status_services', '=', 'Selesai'], ['kode_owner', '=', $this->getThisUser()->id_upline]])->get();
        $content = view('admin.page.pengembalian', compact(['data', 'service', 'done_service', 'listLaci']));
        return view('admin.layout.blank_page', compact(['page', 'content']));
    }
    public function update(Request $request, $id)
    {
        $update = Pengambilan::findOrFail($id);
        $update->update([
            'nama_pengambilan' => $request->nama_pengambilan,
            'tgl_pengambilan' => $request->tgl_pengambilan,
            'total_bayar' => $request->total_bayar,
            'status_pengambilan' => '1',
        ]);
        // laci
        // Misalnya, ambil kategori dari request
        $kategoriId = $request->input('id_kategorilaci');
        $uangMasuk = $request->input('totalharga');
        $keterangan = 'Ngambil Unit oleh' . "-" . $request->input('nama_pengambilan');

        // Catat histori laci
        $this->recordLaciHistory($kategoriId, $uangMasuk, null, $keterangan);
        //end laci
        if ($update) {
            modelServices::where([['kode_pengambilan', '=', $id]])->update([
                'status_services' => 'Diambil'
            ]);
            return redirect()->back()->with(['success' => 'Pengambilan Berhasil']);
        }
        return redirect()->back()->with(['success' => 'Opss,Something Went Wrong']);
    }
    public function store_detail(Request $request, $id)
    {
        $update = modelServices::findOrFail($request->id_service);
        $update->update([
            'kode_pengambilan' => $id
        ]);
        // return redirect()->back();
        // Ambil data pengambilan terbaru setelah diambil
        $pengambilanServices = $this->getServices($id); // Misalnya, ambil kembali data setelah proses
        // Hitung jumlah layanan yang dikembalikan
        $jumlahData = count($pengambilanServices['pengambilanServices']);

        return response()->json([
            'message' => 'Service berhasil ditambahkan.',
            'jumlahData' => $jumlahData,
            'pengambilanServices' => $pengambilanServices['pengambilanServices'] // Kembalikan data
        ]);
    }
    public function destroy_detail(Request $request, $id)
    {
        $service = modelServices::findOrFail($request->id_service);

        // Update field kode_pengambilan
        $service->update([
            'kode_pengambilan' => ''
        ]);

        // Kembalikan respons JSON untuk AJAX
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus!'
        ]);
    }
    public function pengambilan_detail($id)
    {
        $pengambilanServices = modelServices::where('kode_pengambilan', $id)->get();

        $jumlahData = $pengambilanServices->count();
        $totalBiaya = $pengambilanServices->sum(function ($item) {
            return $item->total_biaya - $item->dp;
        });

        return response()->json([
            'jumlahData' => $jumlahData,
            'pengambilanServices' => $pengambilanServices,
            'totalBiaya' => $totalBiaya,
        ]);
    }
}
