<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Sparepart;
use Illuminate\Http\Request;

class CabangController extends Controller
{
    public function getTable($thead = '<th>No</th><th>Aksi</th>', $tbody = '') {
        $result = '<div class="table-responsive"><table class="table" id="dataTable">';
        $result .= '<thead>' . $thead . '</thead>';
        $result .= '<tbody>' . $tbody . '</tbody>';
        $result .= '</table></div>';
        return $result;
    }

    public function getHiddenItemForm($method = 'POST') {
        $result = '' . csrf_field() . '';
        $result .= '' . method_field($method) . '';
        return $result;
    }

    public function index()
    {
        $page = "Manajemen Cabang";
        $thead = '<th width="5%">No</th>
        <th>Nama Cabang</th>
        <th>Alamat</th>
        <th>Status</th>
        <th>Aksi</th>';

        $thisUser = $this->getThisUser();
        $kode_owner = ($thisUser->jabatan == '1') ? $thisUser->id_user : $thisUser->id_upline;

        $data = Cabang::where('kode_owner', $kode_owner)->get();
        $tbody = '';
        $no = 1;
        foreach ($data as $item) {
            $edit = route('cabang.edit', $item->id);
            $delete = route('cabang.destroy', $item->id);
            $status = $item->is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Tidak Aktif</span>';
            $tbody .= '<tr><td>' . $no++ . '</td>
                        <td>' . htmlspecialchars($item->nama_cabang) . '</td>
                        <td>' . htmlspecialchars($item->alamat_cabang ?? '-') . '</td>
                        <td>' . $status . '</td>
                        <th><form action="' . $delete . '" onsubmit="' . "return confirm('Apakah Anda yakin ?')" . '" method="POST">
                                ' . $this->getHiddenItemForm('DELETE') . '
                                <a href="' . $edit . '" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                    </th>';
        }
        $link_tambah = route('cabang.create');
        $data = $this->getTable($thead, $tbody);
        return view('admin.layout.card_layout', compact(['page', 'data', 'link_tambah']));
    }

    public function create()
    {
        $page = "Tambah Cabang";
        return view('admin.forms.cabang', compact(['page']));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'nama_cabang' => ['required', 'string', 'max:255'],
            'alamat_cabang' => ['nullable', 'string'],
        ]);

        $thisUser = $this->getThisUser();
        $kode_owner = ($thisUser->jabatan == '1') ? $thisUser->id_user : $thisUser->id_upline;

        Cabang::create([
            'kode_owner' => $kode_owner,
            'nama_cabang' => $request->nama_cabang,
            'alamat_cabang' => $request->alamat_cabang,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->route('cabang.index')->with('success', 'Cabang Berhasil Ditambahkan');
    }

    public function edit($id)
    {
        $page = "Edit Cabang";
        $thisUser = $this->getThisUser();
        $kode_owner = ($thisUser->jabatan == '1') ? $thisUser->id_user : $thisUser->id_upline;

        $data = Cabang::where('id', $id)->where('kode_owner', $kode_owner)->firstOrFail();
        return view('admin.forms.cabang', compact(['page', 'data']));
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'nama_cabang' => ['required', 'string', 'max:255'],
            'alamat_cabang' => ['nullable', 'string'],
            'is_active' => ['required'],
        ]);

        $thisUser = $this->getThisUser();
        $kode_owner = ($thisUser->jabatan == '1') ? $thisUser->id_user : $thisUser->id_upline;

        $cabang = Cabang::where('id', $id)->where('kode_owner', $kode_owner)->firstOrFail();
        $cabang->update([
            'nama_cabang' => $request->nama_cabang,
            'alamat_cabang' => $request->alamat_cabang,
            'is_active' => $request->is_active,
        ]);

        return redirect()->route('cabang.index')->with('success', 'Cabang Berhasil Diubah');
    }

    public function destroy($id)
    {
        $thisUser = $this->getThisUser();
        $kode_owner = ($thisUser->jabatan == '1') ? $thisUser->id_user : $thisUser->id_upline;

        $cabang = Cabang::where('id', $id)->where('kode_owner', $kode_owner)->firstOrFail();
        $cabang->update(['is_active' => false]);

        return redirect()->route('cabang.index')->with('success', 'Cabang Berhasil Dinonaktifkan');
    }

    public function transferForm()
    {
        $page = "Transfer Stok Antar Cabang";
        $thisUser = $this->getThisUser();
        $kode_owner = ($thisUser->jabatan == '1') ? $thisUser->id_user : $thisUser->id_upline;

        $cabangs = Cabang::where('kode_owner', $kode_owner)->where('is_active', true)->get();
        return view('admin.forms.cabang_transfer', compact(['page', 'cabangs']));
    }

    public function getItemsByCabang($cabang_id)
    {
        $items = Sparepart::withoutGlobalScope(\App\Scopes\CabangScope::class)
            ->where('cabang_id', $cabang_id)
            ->where('stok_sparepart', '>', 0)
            ->where('is_active', true)
            ->get(['id', 'kode_sparepart', 'nama_sparepart', 'stok_sparepart']);

        return response()->json($items);
    }

    public function processTransfer(Request $request)
    {
        $request->validate([
            'from_cabang_id' => 'required|integer|exists:cabangs,id',
            'to_cabang_id' => 'required|integer|exists:cabangs,id|different:from_cabang_id',
            'sparepart_id' => 'required|integer|exists:spareparts,id',
            'qty' => 'required|integer|min:1'
        ]);

        $thisUser = $this->getThisUser();
        $userId = $thisUser->id_user;

        $fromCabangId = $request->from_cabang_id;
        $toCabangId = $request->to_cabang_id;
        $sparepartId = $request->sparepart_id;
        $qty = $request->qty;

        $sourceItem = Sparepart::withoutGlobalScope(\App\Scopes\CabangScope::class)
            ->where('id', $sparepartId)
            ->where('cabang_id', $fromCabangId)
            ->first();

        if (!$sourceItem) {
            return redirect()->back()->with('error', 'Barang tidak ditemukan di cabang asal.');
        }

        if ($sourceItem->stok_sparepart < $qty) {
            return redirect()->back()->with('error', 'Stok di cabang asal tidak mencukupi (Stok saat ini: ' . $sourceItem->stok_sparepart . ').');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $sourceItem->logStockChange(
                -$qty, 
                'transfer_out', 
                $toCabangId, 
                'Transfer stok ke Cabang ID: ' . $toCabangId, 
                $userId
            );

            $targetItem = Sparepart::withoutGlobalScope(\App\Scopes\CabangScope::class)
                ->where('kode_sparepart', $sourceItem->kode_sparepart)
                ->where('cabang_id', $toCabangId)
                ->first();

            if (!$targetItem) {
                $targetItem = Sparepart::create([
                    'kode_sparepart' => $sourceItem->kode_sparepart,
                    'nama_sparepart' => $sourceItem->nama_sparepart,
                    'desc_sparepart' => $sourceItem->desc_sparepart,
                    'foto_sparepart' => $sourceItem->foto_sparepart,
                    'kode_kategori' => $sourceItem->kode_kategori,
                    'kode_sub_kategori' => $sourceItem->kode_sub_kategori,
                    'stok_sparepart' => 0,
                    'harga_beli' => $sourceItem->harga_beli,
                    'harga_jual' => $sourceItem->harga_jual,
                    'harga_ecer' => $sourceItem->harga_ecer,
                    'harga_pasang' => $sourceItem->harga_pasang,
                    'kode_owner' => $sourceItem->kode_owner,
                    'cabang_id' => $toCabangId,
                    'kode_spl' => $sourceItem->kode_spl,
                    'is_active' => $sourceItem->is_active,
                    'is_visible_on_web' => $sourceItem->is_visible_on_web,
                ]);

                $sourceVariants = \App\Models\ProductVariant::where('sparepart_id', $sourceItem->id)->get();
                foreach ($sourceVariants as $var) {
                    \App\Models\ProductVariant::create([
                        'sparepart_id' => $targetItem->id,
                        'sku' => $var->sku,
                        'purchase_price' => $var->purchase_price,
                        'wholesale_price' => $var->wholesale_price,
                        'retail_price' => $var->retail_price,
                        'internal_price' => $var->internal_price,
                        'stock' => 0,
                    ]);
                }
            }

            $targetItem->logStockChange(
                $qty, 
                'transfer_in', 
                $fromCabangId, 
                'Transfer stok masuk dari Cabang ID: ' . $fromCabangId, 
                $userId
            );

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('cabang.index')->with('success', 'Transfer stok ' . $qty . ' ' . $sourceItem->nama_sparepart . ' berhasil diproses.');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mentransfer stok: ' . $e->getMessage());
        }
    }
}
