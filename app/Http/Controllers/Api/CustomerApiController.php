<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\customer_table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerApiController extends Controller
{
    /**
     * Generate kode toko baru.
     *
     * @return string
     */
    private function getKodeToko()
    {
        $lastCustomer = customer_table::orderBy('id', 'desc')->first();
        $kode_toko = 'CST-' . date('Ymd') . '-';

        if ($lastCustomer) {
            $lastNumber = intval(Str::substr($lastCustomer->kode_toko ?? 'CST-00000-000', -3));
            $kode_toko .= str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $kode_toko .= '001';
        }

        return $kode_toko;
    }

    /**
     * Display a listing of all customers.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customers = customer_table::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar customer berhasil diambil',
            'data' => $customers,
            'kode_toko' => $this->getKodeToko()
        ]);
    }

    /**
     * Get customers filtered by status.
     *
     * @param  string  $status
     * @return \Illuminate\Http\Response
     */
    public function getByStatus($status)
    {
        // Validasi status
        if (!in_array($status, ['biasa', 'konter', 'glosir', 'super'])) {
            return response()->json([
                'success' => false,
                'message' => 'Status tidak valid'
            ], 400);
        }

        $customers = customer_table::where('status_toko', $status)
                                 ->orderBy('created_at', 'desc')
                                 ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar customer dengan status ' . $status . ' berhasil diambil',
            'data' => $customers
        ]);
    }

    /**
     * Store a newly created customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Jika kode_toko tidak disediakan, generate otomatis
        if (!$request->has('kode_toko') || empty($request->kode_toko)) {
            $request->merge(['kode_toko' => $this->getKodeToko()]);
        }

        // Tambahkan kode_owner dari user yang sedang login
        $request->merge(['kode_owner' => $this->getThisUser()->id_upline]);

        // Validasi data input
        $validator = Validator::make($request->all(), [
            'nama_pelanggan' => 'required|string|max:255',
            'nama_toko' => 'required|string|max:255',
            'alamat_toko' => 'required|string',
            'status_toko' => 'required|in:biasa,konter,glosir,super',
            'nomor_toko' => 'required|string|max:15',
            'kode_toko' => 'required|string|unique:customer_tables,kode_toko',
            // kode_owner sudah ditambahkan dari getThisUser
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simpan data customer baru
        $customer = customer_table::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil ditambahkan',
            'data' => $customer
        ], 201);
    }

    /**
     * Display the specified customer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $customer = customer_table::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail customer berhasil diambil',
            'data' => $customer
        ]);
    }

    /**
     * Update the specified customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $customer = customer_table::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        // Validasi data input (Menerima versi API dan versi DB)
        $validator = Validator::make($request->all(), [
            'nama_pelanggan' => 'sometimes|required|string|max:255',
            'nama_kontak' => 'sometimes|required|string|max:255',
            'nama_toko' => 'sometimes|required|string|max:255',
            'alamat_toko' => 'sometimes|required|string',
            'alamat' => 'sometimes|required|string',
            'status_toko' => 'sometimes|required|in:biasa,konter,glosir,super',
            'tipe_pelanggan' => 'sometimes|required|in:Retail,Grosir,biasa,konter,glosir,super',
            'nomor_toko' => 'sometimes|required|string|max:25',
            'nomor_telepon' => 'sometimes|required|string|max:25',
            'kode_toko' => 'sometimes|required|string|unique:customer_tables,kode_toko,'.$id,
            'kode_owner' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Map request data ke field database yang sebenarnya
        $updateData = $request->all();
        
        if ($request->has('nama_pelanggan')) $updateData['nama_kontak'] = $request->nama_pelanggan;
        if ($request->has('alamat_toko')) $updateData['alamat'] = $request->alamat_toko;
        if ($request->has('nomor_toko')) $updateData['nomor_telepon'] = $request->nomor_toko;
        
        if ($request->has('status_toko')) {
            $updateData['tipe_pelanggan'] = in_array($request->status_toko, ['glosir', 'super']) ? 'Grosir' : 'Retail';
        }

        // Hapus field API yang tidak ada di DB agar tidak error SQL
        unset($updateData['nama_pelanggan'], $updateData['alamat_toko'], $updateData['nomor_toko'], $updateData['status_toko']);

        // Update data customer
        $customer->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil diperbarui',
            'data' => $customer
        ]);
    }

    /**
     * Remove the specified customer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = customer_table::find($id);

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan'
            ], 404);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer berhasil dihapus'
        ]);
    }

    /**
     * Search for customers by name.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /**
 * Search for customers by name, shop name, code, or phone number.
 * Results are always filtered by the specified owner code.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function search(Request $request)
{
    $keyword = $request->keyword;
    $kode_owner = $request->kode_owner;

    if (empty($kode_owner)) {
        return response()->json([
            'success' => false,
            'message' => 'Kode owner harus diisi'
        ], 400);
    }

    $query = customer_table::where('kode_owner', $kode_owner);

    // Filter by keyword if provided
    if (!empty($keyword)) {
        $query->where(function($q) use ($keyword) {
            $q->where('nama_pelanggan', 'like', '%' . $keyword . '%')
              ->orWhere('nama_toko', 'like', '%' . $keyword . '%')
              ->orWhere('kode_toko', 'like', '%' . $keyword . '%')
              ->orWhere('nomor_toko', 'like', '%' . $keyword . '%');
        });
    }

    $customers = $query->orderBy('created_at', 'desc')->get();

    return response()->json([
        'success' => true,
        'message' => 'Pencarian customer berhasil',
        'data' => $customers,
        'total' => $customers->count()
    ]);
}

    /**
     * Get a new kode toko for new customer form.
     *
     * @return \Illuminate\Http\Response
     */
    public function getNewKodeToko()
    {
        return response()->json([
            'success' => true,
            'message' => 'Kode toko berhasil dibuat',
            'data' => [
                'kode_toko' => $this->getKodeToko()
            ]
        ]);
    }
}
