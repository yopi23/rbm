<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\customer_table;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of the customers.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page = 'Customer';
        // Ambil data customer untuk ditampilkan di tabel
        $customers = customer_table::orderBy('created_at', 'desc')->get();

        // Generate kode toko baru
        $lastCustomer = customer_table::orderBy('id', 'desc')->first();
        $kode_toko = 'CST-' . date('Ymd') . '-';

        if ($lastCustomer) {
            $lastNumber = intval(Str::substr($lastCustomer->kode_toko ?? 'CST-00000-000', -3));
            $kode_toko .= str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $kode_toko .= '001';
        }

        // Generate view dengan menggunakan blank_page layout
        $content = view('admin.page.customer.index', compact('customers', 'kode_toko'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Show the form for creating a new customer.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $page = 'Tambah Customer';

        // Generate kode toko baru
        $lastCustomer = customer_table::orderBy('id', 'desc')->first();
        $kode_toko = 'CST-' . date('Ymd') . '-';

        if ($lastCustomer) {
            $lastNumber = intval(Str::substr($lastCustomer->kode_toko ?? 'CST-00000-000', -3));
            $kode_toko .= str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $kode_toko .= '001';
        }

        $content = view('admin.page.customer.create', compact('kode_toko'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Store a newly created customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
{
    // Validasi data input
    $validator = Validator::make($request->all(), [
        'nama_pelanggan' => 'required|string|max:255',
        'nama_toko' => 'required|string|max:255',
        'alamat_toko' => 'required|string',
        'status_toko' => 'required|in:biasa,konter,glosir,super',
        'nomor_toko' => 'required|string|max:15',
        'kode_toko' => 'required|string|unique:customer_tables,kode_toko',
        // Remove kode_owner from validation rules
    ]);

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    // Get all data from request
    $customerData = $request->all();

    // Add kode_owner to the data
    $customerData['kode_owner'] = $this->getThisUser()->id_upline;

    // Simpan data customer baru
    customer_table::create($customerData);

    return redirect()->route('customer.index')
        ->with('success', 'Customer berhasil ditambahkan!');
}

    /**
     * Display the specified customer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $page = 'Detail Customer';
        $customer = customer_table::findOrFail($id);

        $content = view('admin.page.customer.show', compact('customer'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Show the form for editing the specified customer.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $page = 'Edit Customer';
        $customer = customer_table::findOrFail($id);

        $content = view('admin.page.customer.edit', compact('customer'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Update the specified customer in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $customer = customer_table::findOrFail($id);

        // Validasi data input
        $validator = Validator::make($request->all(), [
            'nama_pelanggan' => 'required|string|max:255',
            'nama_toko' => 'required|string|max:255',
            'alamat_toko' => 'required|string',
            'status_toko' => 'required|in:biasa,konter,glosir,super',
            'nomor_toko' => 'required|string|max:15',
            'kode_owner' => 'required|string|unique:customer_tables,kode_owner,'.$id,
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Update data customer
        $customer->update($request->all());

        return redirect()->route('customer.index')
            ->with('success', 'Customer berhasil diperbarui!');
    }

    /**
     * Remove the specified customer from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = customer_table::findOrFail($id);
        $customer->delete();

        return redirect()->route('customer.index')
            ->with('success', 'Customer berhasil dihapus!');
    }
}
