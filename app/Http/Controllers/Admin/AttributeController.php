<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Attribute;
use App\Models\KategoriSparepart;

class AttributeController extends Controller
{


    public function index()
    {
        $page = "Manajemen Atribut Varian";
        $attributes = Attribute::with('kategori')
            ->where('kode_owner', $this->getThisUser()->id_upline)
            ->latest()->get();

        $content = view('admin.page.attributes.index', compact('attributes'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function create()
    {
        $page = "Tambah Atribut Baru";
        $categories = KategoriSparepart::where('kode_owner', $this->getThisUser()->id_upline)
        ->orderBy('nama_kategori')->get();

        $content = view('admin.page.attributes.form', compact('categories'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kategori_sparepart_id' => 'required|exists:kategori_spareparts,id'
        ]);
        $validated['kode_owner'] = $this->getThisUser()->id_upline;
        $validated['is_required'] = $request->has('is_required');
        Attribute::create($validated);

        return redirect()->route('attributes.index')->with('success', 'Atribut berhasil dibuat.');
    }

    public function edit(Attribute $attribute)
    {
        $page = "Edit Atribut: " . $attribute->name;
        $categories = KategoriSparepart::orderBy('nama_kategori')->get();
        $attribute->load('values');
        $content = view('admin.page.attributes.form', compact('attribute', 'categories'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kategori_sparepart_id' => 'required|exists:kategori_spareparts,id'
        ]);
        $attribute->update($validated);
        return redirect()->route('attributes.index')->with('success', 'Atribut berhasil diperbarui.');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();
        return redirect()->route('attributes.index')->with('success', 'Atribut berhasil dihapus.');
    }
}
