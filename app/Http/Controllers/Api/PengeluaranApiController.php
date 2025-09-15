<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\BebanOperasional;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Traits\KategoriLaciTrait;
use Illuminate\Support\Facades\Log;
use App\Traits\ManajemenKasTrait;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengeluaranApiController extends Controller
{
    use KategoriLaciTrait;
    use ManajemenKasTrait;

    /**
     * Get all pengeluaran toko
     */
    public function getPengeluaranToko(Request $request): JsonResponse
    {
        try {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $query = PengeluaranToko::where('kode_owner', $this->getThisUser()->id_upline)
                              ->whereBetween('tanggal_pengeluaran', [$startDate, $endDate]);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nama_pengeluaran', 'LIKE', "%{$search}%")
                      ->orWhere('catatan_pengeluaran', 'LIKE', "%{$search}%");
                });
            }

            $data = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Data pengeluaran toko berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengeluaran toko',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // public function getKategoriPengeluaran(): JsonResponse
    // {
    //     try {
    //         // Ambil kategori dari master data Beban Tetap
    //         $kategoriBeban = BebanOperasional::where('kode_owner', $this->getThisUser()->id_upline)
    //                                         ->pluck('nama_beban');

    //         // Tambahkan kategori standar (tanpa 'Lainnya' dulu)
    //         $kategoriStandar = collect(['Penggajian']);

    //         // Gabungkan dan pastikan unik
    //         $semuaKategori = $kategoriStandar->merge($kategoriBeban)->unique()->values();

    //         // Pastikan 'Lainnya' selalu ada di paling bawah
    //         $semuaKategori = $semuaKategori->reject(function ($item) {
    //             return strtolower($item) === 'lainnya';
    //         })->values();
    //         $semuaKategori->push('Lainnya');

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Kategori berhasil diambil',
    //             'data' => $semuaKategori
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal mengambil kategori',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getBebanOperasionalList(): JsonResponse
    {
        try {
            $ownerId = $this->getThisUser()->id_upline;

            // Ambil semua beban, eager load semua pengeluaran dari awal tahun ini untuk efisiensi
            $awalTahunIni = Carbon::now()->startOfYear();
            $daftarBeban = BebanOperasional::where('kode_owner', $ownerId)
                ->with(['pengeluaranOperasional' => function ($query) use ($awalTahunIni) {
                    $query->where('tgl_pengeluaran', '>=', $awalTahunIni);
                }])
                ->orderBy('nama_beban', 'asc')
                ->get();

            // Siapkan variabel untuk tanggal bulan ini
            $awalBulanIni = Carbon::now()->startOfMonth();
            $akhirBulanIni = Carbon::now()->endOfMonth();

            $result = $daftarBeban->map(function ($item) use ($awalBulanIni, $akhirBulanIni) {

                // Tentukan pengeluaran yang relevan berdasarkan periode
                if ($item->periode == 'tahunan') {
                    // Untuk tahunan, semua pengeluaran yang di-load dari awal tahun relevan
                    $pengeluaranPeriodeIni = $item->pengeluaranOperasional;
                } else { // Default ke 'bulanan'
                    // Untuk bulanan, filter lagi dari data yang sudah di-load untuk bulan ini saja
                    $pengeluaranPeriodeIni = $item->pengeluaranOperasional->whereBetween('tgl_pengeluaran', [$awalBulanIni, $akhirBulanIni]);
                }

                // Hitung properti
                $terpakai = $pengeluaranPeriodeIni->sum('jml_pengeluaran');
                // Gunakan kolom 'nominal' yang baru
                $sisa_jatah = (float) $item->nominal - $terpakai;

                return [
                    'id' => $item->id,
                    'nama_beban' => $item->nama_beban,
                    'periode' => $item->periode, // Kirim juga info periode
                    // Gunakan kolom 'nominal' dan pastikan tipenya float
                    'jumlah_bulanan' => (float) $item->nominal, // Dibiarkan agar Flutter tidak perlu diubah
                    'sisa_jatah' => $sisa_jatah > 0 ? $sisa_jatah : 0,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Daftar beban operasional berhasil diambil',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 'message' => 'Gagal mengambil data', 'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single pengeluaran toko by ID
     */
    public function showPengeluaranToko($id): JsonResponse
    {
        try {
            $data = PengeluaranToko::where('kode_owner', $this->getThisUser()->id_upline)
                                  ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data pengeluaran toko berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengeluaran toko tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store new pengeluaran toko
     */
    public function storePengeluaranToko(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tanggal_pengeluaran' => 'required|string',
                'nama_pengeluaran' => 'required|string|max:255',
                'jumlah_pengeluaran' => 'required|string',
                'catatan_pengeluaran' => 'required|string',
                'id_kategorilaci' => 'nullable|integer'
            ]);

            $pengeluaran = PengeluaranToko::create([
                'tanggal_pengeluaran' => $request->tanggal_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
                'catatan_pengeluaran' => $request->catatan_pengeluaran,
                'kode_owner' => $this->getThisUser()->id_upline
            ]);
            $this->catatKas(
                    $pengeluaran, 0, $pengeluaran->jumlah_pengeluaran,
                    'Pengeluaran Toko (API): ' . $pengeluaran->nama_pengeluaran,
                    $pengeluaran->tanggal_pengeluaran
                );

            // Handle laci history if kategori laci is provided
            if ($request->id_kategorilaci) {
                $kategoriId = $request->id_kategorilaci;
                $uangKeluar = (int) str_replace(',', '', $request->jumlah_pengeluaran);
                $keterangan = $request->nama_pengeluaran . " - " . $request->catatan_pengeluaran;

                $this->recordLaciHistory(
                    $kategoriId,
                    null, // uang masuk
                    $uangKeluar, // uang keluar
                    $keterangan,
                    'pengeluaran_toko',
                    $pengeluaran->id,
                    'TKO-' . $pengeluaran->id
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran toko berhasil ditambahkan',
                'data' => $pengeluaran
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pengeluaran toko',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pengeluaran toko
     */
    public function updatePengeluaranToko(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'tanggal_pengeluaran' => 'required|string',
                'nama_pengeluaran' => 'required|string|max:255',
                'jumlah_pengeluaran' => 'required|string',
                'catatan_pengeluaran' => 'required|string',
                'id_kategorilaci' => 'nullable|integer'
            ]);

            $pengeluaran = PengeluaranToko::where('kode_owner', $this->getThisUser()->id_upline)
                                        ->findOrFail($id);

            // Get old data for history comparison
            $oldAmount = $pengeluaran->jumlah_pengeluaran;
            $oldName = $pengeluaran->nama_pengeluaran;
            $oldCatatan = $pengeluaran->catatan_pengeluaran;

            $pengeluaran->update([
                'tanggal_pengeluaran' => $request->tanggal_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
                'catatan_pengeluaran' => $request->catatan_pengeluaran,
            ]);

            // Handle laci history if kategori laci is provided
            if ($request->id_kategorilaci) {
                $kategoriId = $request->id_kategorilaci;
                $newAmount = (int) str_replace(',', '', $request->jumlah_pengeluaran);
                $oldAmountClean = (int) str_replace(',', '', $oldAmount);

                // If amount changed, record the adjustment
                if ($newAmount != $oldAmountClean) {
                    $keterangan = "Update Pengeluaran Toko: " . $request->nama_pengeluaran . " - " . $request->catatan_pengeluaran . " (Penyesuaian dari " . number_format($oldAmountClean) . " ke " . number_format($newAmount) . ")";

                    if ($newAmount > $oldAmountClean) {
                        // Additional expense
                        $this->recordLaciHistory(
                            $kategoriId,
                            null, // uang masuk
                            $newAmount - $oldAmountClean, // additional amount out
                            $keterangan,
                            'pengeluaran_toko_update',
                            $pengeluaran->id,
                            'TKO-UPD-' . $pengeluaran->id
                        );
                    } else {
                        // Reduction in expense (money back to laci)
                        $this->recordLaciHistory(
                            $kategoriId,
                            $oldAmountClean - $newAmount, // money back in
                            null, // uang keluar
                            $keterangan,
                            'pengeluaran_toko_update',
                            $pengeluaran->id,
                            'TKO-UPD-' . $pengeluaran->id
                        );
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran toko berhasil diupdate',
                'data' => $pengeluaran
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate pengeluaran toko',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete pengeluaran toko
     */
    public function deletePengeluaranToko($id): JsonResponse
    {
        try {
            $pengeluaran = PengeluaranToko::where('kode_owner', $this->getThisUser()->id_upline)
                                        ->findOrFail($id);

            // Before deleting, check if we need to reverse laci transaction
            // This would require additional logic to find and reverse the laci entry
            // You might want to add a soft delete or mark as cancelled instead

            $pengeluaran->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran toko berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengeluaran toko',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pengeluaran operasional
     */
    public function getPengeluaranOperasional(Request $request): JsonResponse
    {
        try {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $kategori = $request->get('kategori');
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $query = PengeluaranOperasional::with(['pegawai' => function($q) {
            $q->select('id', 'name');
        }])->where('kode_owner', $this->getThisUser()->id_upline)
          ->whereBetween('tgl_pengeluaran', [$startDate, $endDate]);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nama_pengeluaran', 'LIKE', "%{$search}%")
                      ->orWhere('desc_pengeluaran', 'LIKE', "%{$search}%");
                });
            }

            if ($kategori) {
                $query->where('kategori', $kategori);
            }

            $data = $query->latest('tgl_pengeluaran')->paginate($perPage);

            // Transform data to include employee name for payroll category
            $data->getCollection()->transform(function ($item) {
                if ($item->kategori === 'Penggajian' && $item->pegawai) {
                    $item->kategori_display = $item->kategori . ' (' . $item->pegawai->name . ')';
                } else {
                    $item->kategori_display = $item->kategori;
                }
                return $item;
            });

            return response()->json([
                'success' => true,
                'message' => 'Data pengeluaran operasional berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengeluaran operasional',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single pengeluaran operasional by ID
     */
    public function showPengeluaranOperasional($id): JsonResponse
    {
        try {
            $data = PengeluaranOperasional::with(['pegawai' => function($q) {
                $q->select('id', 'name');
            }])->where('kode_owner', $this->getThisUser()->id_upline)
              ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data pengeluaran operasional berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengeluaran operasional tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store new pengeluaran operasional
     */
    public function storePengeluaranOperasional(Request $request): JsonResponse
    {
        // LOG 1: Mencatat semua data yang masuk dari Flutter
        Log::info('API Store Pengeluaran Opex Dimulai', ['request_data' => $request->all()]);

        try {
            $validatedData = $request->validate([
                'tgl_pengeluaran' => 'required|date',
                'nama_pengeluaran' => 'required|string|max:255',
                'beban_operasional_id' => 'nullable|integer|exists:beban_operasional,id',
                'kode_pegawai' => 'nullable|integer|exists:users,id',
                'jml_pengeluaran' => 'required|numeric|min:1',
                'desc_pengeluaran' => 'nullable|string',
                'id_kategorilaci' => 'nullable|integer'
            ]);

            // Validasi Sisa Jatah (sudah benar)
            if (isset($validatedData['beban_operasional_id'])) {
                $beban = BebanOperasional::findOrFail($validatedData['beban_operasional_id']);
                $pengeluaranBaru = (float) $validatedData['jml_pengeluaran'];

                if ($beban->periode == 'tahunan') {
                    $awalPeriode = Carbon::parse($validatedData['tgl_pengeluaran'])->startOfYear();
                    $akhirPeriode = Carbon::parse($validatedData['tgl_pengeluaran'])->endOfYear();
                } else {
                    $awalPeriode = Carbon::parse($validatedData['tgl_pengeluaran'])->startOfMonth();
                    $akhirPeriode = Carbon::parse($validatedData['tgl_pengeluaran'])->endOfMonth();
                }

                $sudahTerpakai = PengeluaranOperasional::where('beban_operasional_id', $beban->id)
                    ->whereBetween('tgl_pengeluaran', [$awalPeriode, $akhirPeriode])
                    ->sum('jml_pengeluaran');

                $sisaJatah = (float) $beban->nominal - $sudahTerpakai;

                if ($pengeluaranBaru > $sisaJatah) {
                    $pesanError = "Jumlah melebihi sisa jatah untuk '" . $beban->nama_beban . "'. Sisa jatah: " . number_format($sisaJatah);
                    return response()->json(['success' => false, 'message' => $pesanError], 422);
                }
            }

            DB::beginTransaction();

            $validatedData['kategori'] = 'Lainnya';
            if (isset($validatedData['beban_operasional_id'])) {
                $validatedData['kategori'] = BebanOperasional::find($validatedData['beban_operasional_id'])->nama_beban;
            }

            $validatedData['kode_owner'] = $this->getThisUser()->id_upline;

            $validatedData['kode_pegawai'] = $this->getThisUser()->kode_user;

            // LOG 2: Mencatat data final sebelum disimpan ke database
            Log::info('Data Siap Disimpan ke Database', ['data_to_create' => $validatedData]);

            $pengeluaran = PengeluaranOperasional::create($validatedData);

            $this->catatKas(
                $pengeluaran, 0, $pengeluaran->jml_pengeluaran,
                'Pengeluaran Opex (API): ' . $pengeluaran->nama_pengeluaran,
                $pengeluaran->tgl_pengeluaran
            );

            if (isset($validatedData['id_kategorilaci'])) {
                $keterangan = $validatedData['kategori'] . " - " . $validatedData['nama_pengeluaran'];
                $this->recordLaciHistory(
                    $validatedData['id_kategorilaci'], null, $validatedData['jml_pengeluaran'], $keterangan,
                    'pengeluaran_operasional', $pengeluaran->id, 'OPX-' . $pengeluaran->id
                );
            }

            DB::commit();
            Log::info('Pengeluaran Opex Berhasil Disimpan', ['pengeluaran_id' => $pengeluaran->id]);

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran operasional berhasil ditambahkan',
                'data' => $pengeluaran->load('pegawai')
            ], 201);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            // LOG 3: Mencatat detail error jika terjadi kegagalan
            Log::error('Gagal Simpan Pengeluaran Opex API: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan pengeluaran operasional', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update pengeluaran operasional
     */
    public function updatePengeluaranOperasional(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'tgl_pengeluaran' => 'required|date',
                'nama_pengeluaran' => 'required|string|max:255',
                'beban_operasional_id' => 'nullable|integer|exists:beban_operasional,id',
                'kode_pegawai' => 'nullable|integer|exists:users,id',
                'jml_pengeluaran' => 'required|numeric|min:1',
                'desc_pengeluaran' => 'nullable|string',
                'id_kategorilaci' => 'nullable|integer'
            ]);

            $pengeluaran = PengeluaranOperasional::where('kode_owner', $this->getThisUser()->id_upline)
                                                ->findOrFail($id);

            // Simpan jumlah lama untuk perbandingan di history laci nanti
            $oldAmount = $pengeluaran->jml_pengeluaran;

            // Validasi Sisa Jatah saat Update
            if (isset($validatedData['beban_operasional_id'])) {
                $beban = BebanOperasional::findOrFail($validatedData['beban_operasional_id']);
                $pengeluaranBaru = (float) $validatedData['jml_pengeluaran'];

                if ($beban->periode == 'tahunan') {
                    $awalPeriode = Carbon::parse($validatedData['tgl_pengeluaran'])->startOfYear();
                    $akhirPeriode = Carbon::parse($validatedData['tgl_pengeluaran'])->endOfYear();
                } else {
                    $awalPeriode = Carbon::parse($validatedData['tgl_pengeluaran'])->startOfMonth();
                    $akhirPeriode = Carbon::parse($validatedData['tgl_pengeluaran'])->endOfMonth();
                }

                $sudahTerpakai = PengeluaranOperasional::where('beban_operasional_id', $beban->id)
                    ->where('id', '!=', $id) // <-- Jangan hitung data yang sedang diedit
                    ->whereBetween('tgl_pengeluaran', [$awalPeriode, $akhirPeriode])
                    ->sum('jml_pengeluaran');

                $sisaJatah = (float) $beban->nominal - $sudahTerpakai;

                if ($pengeluaranBaru > $sisaJatah) {
                    $pesanError = "Jumlah melebihi sisa jatah untuk '" . $beban->nama_beban . "'. Sisa jatah: " . number_format($sisaJatah);
                    return response()->json(['success' => false, 'message' => $pesanError], 422);
                }
            }

            $validatedData['kategori'] = 'Lainnya';
            if (isset($validatedData['beban_operasional_id'])) {
                $validatedData['kategori'] = BebanOperasional::find($validatedData['beban_operasional_id'])->nama_beban;
            }

            if (strtolower($validatedData['kategori']) != 'penggajian') {
                $validatedData['kode_pegawai'] = null;
            }

            $pengeluaran->update($validatedData);

            // Logika untuk penyesuaian history laci saat update (sudah benar)
            if (isset($validatedData['id_kategorilaci'])) {
                $newAmount = (float) $validatedData['jml_pengeluaran'];
                if ($newAmount != $oldAmount) {
                    $keterangan = "Update Pengeluaran: " . $validatedData['nama_pengeluaran'] . " (dari " . number_format($oldAmount) . " ke " . number_format($newAmount) . ")";
                    if ($newAmount > $oldAmount) {
                        $this->recordLaciHistory($validatedData['id_kategorilaci'], null, $newAmount - $oldAmount, $keterangan, 'pengeluaran_operasional_update', $pengeluaran->id, 'OPX-UPD-' . $pengeluaran->id);
                    } else {
                        $this->recordLaciHistory($validatedData['id_kategorilaci'], $oldAmount - $newAmount, null, $keterangan, 'pengeluaran_operasional_update', $pengeluaran->id, 'OPX-UPD-' . $pengeluaran->id);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran operasional berhasil diupdate',
                'data' => $pengeluaran->load('pegawai')
            ]);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Gagal Update Pengeluaran Opex API: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal mengupdate pengeluaran operasional', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete pengeluaran operasional
     */
    public function deletePengeluaranOperasional($id): JsonResponse
    {
        try {
            $pengeluaran = PengeluaranOperasional::where('kode_owner', $this->getThisUser()->id_upline)
                                                ->findOrFail($id);

            // Before deleting, check if we need to reverse laci transaction
            // This would require additional logic to find and reverse the laci entry
            // You might want to add a soft delete or mark as cancelled instead

            $pengeluaran->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran operasional berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengeluaran operasional',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employees for dropdown
     */
    public function getEmployees(): JsonResponse
    {
        try {
            $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
                           ->where([
                               ['user_details.jabatan', '!=', '0'],
                               ['user_details.jabatan', '!=', '1'],
                               ['user_details.status_user', '=', '1'],
                               ['user_details.id_upline', '=', $this->getThisUser()->id_upline]
                           ])
                           ->select('users.id', 'users.name')
                           ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data karyawan berhasil diambil',
                'data' => $employees
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data karyawan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary statistics
     */
    public function getSummary(Request $request): JsonResponse
    {
        try {
            Log::info('Memulai proses getSummary', ['request' => $request->all()]);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $ownerId = $this->getThisUser()->id_upline;

            Log::debug('Parameter dan user data', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'owner_id' => $ownerId,
                'user' => $this->getThisUser()->toArray()
            ]);

            // Validasi format tanggal
        if (!strtotime($startDate) || !strtotime($endDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Format tanggal tidak valid'
            ], 400);
        }

        // Pastikan start date tidak lebih besar dari end date
        if ($startDate > $endDate) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir'
            ], 400);
        }

            // Query untuk Pengeluaran Toko
            $tokoQuery = PengeluaranToko::where('kode_owner', $ownerId)
            ->whereBetween('tanggal_pengeluaran', [$startDate, $endDate]);

            Log::debug('Query Pengeluaran Toko', [
                'sql' => $tokoQuery->toSql(),
                'bindings' => $tokoQuery->getBindings()
            ]);

            // Query untuk Pengeluaran Operasional
            $opexQuery = PengeluaranOperasional::where('kode_owner', $ownerId)
            ->whereBetween('tgl_pengeluaran', [$startDate, $endDate]);

            Log::debug('Query Pengeluaran Operasional', [
                'sql' => $opexQuery->toSql(),
                'bindings' => $opexQuery->getBindings()
            ]);

            $totalToko = $tokoQuery->sum('jumlah_pengeluaran') ?? 0;
            $totalOpex = $opexQuery->sum('jml_pengeluaran') ?? 0;
            $totalPengeluaran = $totalToko + $totalOpex;

            $countToko = $tokoQuery->count();
            $countOpex = $opexQuery->count();

            Log::debug('Hasil perhitungan summary', [
                'total_toko' => $totalToko,
                'total_opex' => $totalOpex,
                'count_toko' => $countToko,
                'count_opex' => $countOpex
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Summary berhasil diambil',
                'data' => [
                    'total_pengeluaran_toko' => $totalToko,
                    'total_pengeluaran_operasional' => $totalOpex,
                    'total_semua_pengeluaran' => $totalPengeluaran,
                    'jumlah_transaksi_toko' => $countToko,
                    'jumlah_transaksi_operasional' => $countOpex,
                    'periode' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error pada getSummary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
