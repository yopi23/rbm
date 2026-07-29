<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CameraPosition;
use App\Models\HpData;
use App\Models\ScreenSize;
use App\Models\Shift;
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

        // Ambil seluruh Kode Antigores yang ada beserta relasi Brand, Ukuran Layar, dan Posisi Kamera
        $existingCodes = HpData::select('code_tg', 'brand_id', 'screen_size_id', 'camera_position_id')
            ->whereNotNull('code_tg')
            ->where('code_tg', '!=', '')
            ->with(['brand', 'screenSize', 'cameraPosition'])
            ->get()
            ->unique('code_tg')
            ->values()
            ->map(function($item) {
                if (!$item->camera_position_id || !$item->cameraPosition) {
                    $validCam = HpData::where('code_tg', $item->code_tg)->whereNotNull('camera_position_id')->with('cameraPosition')->first();
                    if ($validCam) {
                        $item->camera_position_id = $validCam->camera_position_id;
                        $item->cameraPosition = $validCam->cameraPosition;
                    }
                }
                if (!$item->screen_size_id || !$item->screenSize) {
                    $validSize = HpData::where('code_tg', $item->code_tg)->whereNotNull('screen_size_id')->with('screenSize')->first();
                    if ($validSize) {
                        $item->screen_size_id = $validSize->screen_size_id;
                        $item->screenSize = $validSize->screenSize;
                    }
                }
                $models = HpData::where('code_tg', $item->code_tg)->pluck('type')->toArray();
                $item->models_str = implode(', ', $models);
                $brandName = $item->brand ? $item->brand->name : 'Umum';
                $modelsShort = implode(', ', array_slice($models, 0, 3));
                if (count($models) > 3) $modelsShort .= '...';
                $item->display_label = "{$brandName} {$modelsShort} (Kode: {$item->code_tg})";
                return $item;
            });

        $page = 'Tambah Data HP Baru';
        $content = view('admin.page.tg.create', compact('brands', 'screenSizes', 'cameraPositions', 'existingCodes'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    // Menyimpan data HP baru
    public function store(Request $request)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return redirect()->back()->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
        }

        $brandId = $request->input('brand_id');
        if ($brandId === 'NEW' || empty($brandId)) {
            $newBrandName = trim($request->input('new_brand') ?? 'Umum');
            $brandObj = Brand::firstOrCreate(['name' => $newBrandName]);
            $brandId = $brandObj->id;
        }

        $screenSizeId = $request->input('screen_size_id');
        if ($screenSizeId === 'NEW' || empty($screenSizeId)) {
            $newScreenSize = trim($request->input('new_screen_size') ?? '6.5 inch');
            $sizeObj = ScreenSize::firstOrCreate(['size' => $newScreenSize]);
            $screenSizeId = $sizeObj->id;
        }

        $cameraPosId = $request->input('camera_position_id');
        if ($cameraPosId === 'NEW' || empty($cameraPosId)) {
            $newCamPos = trim($request->input('new_camera_position') ?? 'Waterdrop');
            $newCamGroup = trim($request->input('new_camera_group') ?? 'A');
            $camObj = CameraPosition::firstOrCreate(
                ['position' => $newCamPos],
                ['group' => $newCamGroup]
            );
            $cameraPosId = $camObj->id;
        }

        $codeTg = $request->input('code_tg');
        if ($codeTg === 'NEW' || empty($codeTg)) {
            $codeTg = trim($request->input('new_code_tg') ?? '');
        }

        foreach ($tipeArray as $tipe) {
            if ($tipe === '') continue;
            HpData::firstOrCreate(
                [
                    'type' => $tipe,
                    'brand_id' => $brandId,
                ],
                [
                    'code_tg' => $codeTg,
                    'screen_size_id' => $screenSizeId,
                    'camera_position_id' => $cameraPosId,
                ]
            );
        }

        return redirect()->back()->with('success', 'Data HP berhasil disimpan!');
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
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return redirect()->route('admin.tg.index')->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
        }
        $validated = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'type' => 'required|string|max:255',
            'code_tg' => 'nullable|string|max:50',
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
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return redirect()->route('admin.tg.index')->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
        }
        $hp = HpData::findOrFail($id);
        $hp->delete();

        return redirect()->route('admin.tg.index')->with('success', 'Data HP berhasil dihapus');
    }
}
