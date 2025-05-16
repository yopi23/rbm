<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\ScreenSize;
use App\Models\CameraPosition;
use App\Models\HpData;
use Illuminate\Http\Request;

class HpController extends Controller
{
    // Menampilkan data HP dikelompokkan berdasarkan posisi kamera
    public function index()
    {
        $groups = CameraPosition::with(['hpDatas' => function($query) {
            $query->with(['brand', 'screenSize']);
        }])->get()->groupBy('group');

        $page = 'Data HP (Group by Camera Position)';
        $content = view('admin.page.tg.index', compact('groups'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }
    public function cross()
    {
        // Get all brands, screen sizes, and camera positions for our table
        $brands = Brand::orderBy('name')->get();
        $screenSizes = ScreenSize::orderBy('size')->get();

        // Get camera positions and group them
        $cameraPositions = CameraPosition::orderBy('group')->orderBy('position')->get();
        $cameraGroups = $cameraPositions->groupBy('group');

        // Initialize our data matrix
        $matrix = [];

        // For each camera group
        foreach ($cameraGroups as $groupName => $positionsInGroup) {
            foreach ($positionsInGroup as $cameraPosition) {
                // For each screen size
                foreach ($screenSizes as $screenSize) {
                    $row = [
                        'camera_group' => $groupName,
                        'camera_position' => $cameraPosition->position,
                        'screen_size' => $screenSize->size
                    ];

                    // For each brand
                    foreach ($brands as $brand) {
                        // Get phone models for this combination of camera position, screen size and brand
                        $phones = HpData::where('camera_position_id', $cameraPosition->id)
                                    ->where('screen_size_id', $screenSize->id)
                                    ->where('brand_id', $brand->id)
                                    ->get();

                        // Add models to matrix cell or empty string if none
                        if ($phones->count() > 0) {
                            $modelsList = $phones->pluck('type')->implode(', ');
                            $row[$brand->name] = $modelsList;
                        } else {
                            $row[$brand->name] = '';
                        }
                    }

                    $matrix[] = $row;
                }
            }
        }

        $page = 'Data HP (Cross Table By Camera Position)';
        $content = view('admin.page.tg.cross_table', compact('brands', 'screenSizes', 'cameraGroups', 'matrix'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    // Form tambah data HP baru
    public function create()
    {
        $brands = Brand::all();
        $screenSizes = ScreenSize::all();
        $cameraPositions = CameraPosition::all();

        $page = 'Tambah Data HP Baru';
        $content = view('admin.page.tg.create', compact('brands', 'screenSizes', 'cameraPositions'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    // Menyimpan data HP baru
    public function store(Request $request)
    {
        $tipeList = $request->input('tipe_hp'); // array: ['a3s', 'a5s', 'a7s']
        $tipeArray = array_map('trim', explode(',', $tipeList)); // ['a3s', 'a5s', 'a7s']

        $ukuran = $request->input('screen_size_id'); // array: ['a3s' => '6.2"', 'a5s' => '6.5"', ...]
        $kamera = $request->input('camera_position_id');
        $brand  = $request->input('brand_id');

        foreach ($tipeArray as $tipe) {
            if ($tipe === '') continue;
            $data = [
                'type' => $tipe,
                'screen_size_id' => $ukuran,
                'camera_position_id' => $kamera,
                'brand_id' => $brand,
            ];

            // Simpan ke database, misal:
            HpData::create($data); // pastikan model HpModel diatur fillable-nya
        }

        return redirect()->back()->with('success', 'Data berhasil disimpan!');
    }

    // Form edit data HP
    public function edit($id)
    {
        $hp = HpData::with(['brand', 'screenSize', 'cameraPosition'])->findOrFail($id);
        $brands = Brand::all();
        $screenSizes = ScreenSize::all();
        $cameraPositions = CameraPosition::all();

        $page = 'Edit Data HP';
        $content = view('admin.page.tg.edit', compact('hp', 'brands', 'screenSizes', 'cameraPositions'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    // Update data HP
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'type' => 'required|string|max:255',
            'screen_size_id' => 'required|exists:screen_sizes,id',
            'camera_position_id' => 'required|exists:camera_positions,id'
        ]);

        $hp = HpData::findOrFail($id);
        $hp->update($validated);

        return redirect()->route('admin.tg.index')->with('success', 'Data HP berhasil diperbarui');
    }

    // Hapus data HP
    public function destroy($id)
    {
        $hp = HpData::findOrFail($id);
        $hp->delete();

        return redirect()->route('admin.tg.index')->with('success', 'Data HP berhasil dihapus');
    }
}
