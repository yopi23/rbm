<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;

class AttributeValueController extends Controller
{
    /**
     * Menyimpan nilai atribut baru yang terhubung ke sebuah atribut.
     */
    public function store(Request $request, Attribute $attribute)
    {
        if ($attribute->kode_owner != $this->getThisUser()->id_upline) {
        abort(403, 'Akses ditolak.');
        }

        $request->validate([
            'value' => 'required|string|max:255'
        ]);

        $attribute->values()->create([
            'value' => $request->value,
            'kode_owner' => $this->getThisUser()->id_upline,
        ]);

        return back()->with('success', 'Nilai "' . $request->value . '" berhasil ditambahkan.');
    }

    /**
     * Menghapus sebuah nilai atribut.
     */
    public function destroy(AttributeValue $attributeValue)
    {
        $valueName = $attributeValue->value;
        $attributeValue->delete();

        return back()->with('success', 'Nilai "' . $valueName . '" berhasil dihapus.');
    }
}
