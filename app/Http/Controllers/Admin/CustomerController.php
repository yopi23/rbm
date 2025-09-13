<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\customer_table;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
        $kodeOwner = $this->getThisUser()->id_upline;

        // Ambil data customer
        $customers = customer_table::where('kode_owner', $kodeOwner)
                        ->orderBy('created_at', 'desc')
                        ->get();

        // Generate kode toko yang aman
        $kode_toko = $this->generateKodeToko($kodeOwner);

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
        $kodeOwner = $this->getThisUser()->id_upline;

        // Generate kode toko yang aman
        $kode_toko = $this->generateKodeToko($kodeOwner);

        $content = view('admin.page.customer.create', compact('kode_toko'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Generate unique kode toko dengan transaction untuk menghindari race condition
     */
    private function generateKodeToko($kodeOwner)
    {
        return DB::transaction(function () use ($kodeOwner) {
            // Cari kode terakhir untuk owner ini hari ini
            $lastCustomer = customer_table::where('kode_owner', $kodeOwner)
                                ->whereDate('created_at', today())
                                ->orderBy('id', 'desc')
                                ->lockForUpdate() // Lock row untuk menghindari race condition
                                ->first();

            $datePart = date('Ymd');
            $baseCode = 'CST-' .'0'. $kodeOwner . $datePart . '-';

            if ($lastCustomer && Str::startsWith($lastCustomer->kode_toko, $baseCode)) {
                // Extract number from existing code
                $lastNumber = (int) Str::substr($lastCustomer->kode_toko, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            return $baseCode . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
        });
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
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Gunakan transaction untuk memastikan konsistensi data
        DB::transaction(function () use ($request) {
            // Get all data from request
            $customerData = $request->all();

            // Add kode_owner to the data
            $kodeOwner = $this->getThisUser()->id_upline;
            $customerData['kode_owner'] = $kodeOwner;

            // Double check kode_toko uniqueness dalam transaction
            $existingCustomer = customer_table::where('kode_toko', $customerData['kode_toko'])
                                    ->lockForUpdate()
                                    ->first();

            if ($existingCustomer) {
                // Regenerate kode toko jika ternyata ada yang sama
                $customerData['kode_toko'] = $this->generateKodeToko($kodeOwner);
            }

            // Simpan data customer baru
            customer_table::create($customerData);
        });

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

        // Pastikan customer milik owner yang sesuai
        $kodeOwner = $this->getThisUser()->id_upline;
        if ($customer->kode_owner != $kodeOwner) {
            abort(403, 'Unauthorized action.');
        }

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

        // Pastikan customer milik owner yang sesuai
        $kodeOwner = $this->getThisUser()->id_upline;
        if ($customer->kode_owner != $kodeOwner) {
            abort(403, 'Unauthorized action.');
        }

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

        // Pastikan customer milik owner yang sesuai
        $kodeOwner = $this->getThisUser()->id_upline;
        if ($customer->kode_owner != $kodeOwner) {
            abort(403, 'Unauthorized action.');
        }

        // Validasi data input
        $validator = Validator::make($request->all(), [
            'nama_pelanggan' => 'required|string|max:255',
            'nama_toko' => 'required|string|max:255',
            'alamat_toko' => 'required|string',
            'status_toko' => 'required|in:biasa,konter,glosir,super',
            'nomor_toko' => 'required|string|max:15',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Update data customer (excluding kode_owner and kode_toko)
        $updateData = $request->except(['kode_owner', 'kode_toko']);
        $customer->update($updateData);

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

        // Pastikan customer milik owner yang sesuai
        $kodeOwner = $this->getThisUser()->id_upline;
        if ($customer->kode_owner != $kodeOwner) {
            abort(403, 'Unauthorized action.');
        }

        $customer->delete();

        return redirect()->route('customer.index')
            ->with('success', 'Customer berhasil dihapus!');
    }
}
