<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penarikan;
use App\Models\UserDetail;
use App\Services\WhatsAppService;
use App\Traits\KategoriLaciTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDataController extends Controller
{
    use KategoriLaciTrait;

    public function getUserProfile($kode_user)
    {
        // Ambil saldo dari user_detail
        $saldo = DB::table('user_details')
            ->where('kode_user', $kode_user)
            ->value('saldo') ?? 0;

        // Hitung total penarikan dalam satu bulan dari tabel penarikan
        $total_penarikan = DB::table('penarikans')
            ->where('kode_user', $kode_user)
            ->whereMonth('tgl_penarikan', date('m'))
            ->whereYear('tgl_penarikan', date('Y'))
            ->sum('jumlah_penarikan');

        // Hitung total komisi dalam satu bulan dari tabel profit_presentases
        $total_komisi = DB::table('profit_presentases')
            ->where('kode_user', $kode_user)
            ->whereMonth('tgl_profit', date('m'))
            ->whereYear('tgl_profit', date('Y'))
            ->sum('profit');

        // PERBAIKAN: Pastikan semua return sebagai number, bukan string
        return response()->json([
            'kode_user' => $kode_user,
            'saldo' =>  $saldo,
            'total_penarikan' => ($total_penarikan ?? 0),
            'total_komisi' =>  ($total_komisi ?? 0),
        ]);
    }

    public function getKaryawan()
    {
         // Mendapatkan user yang sedang login
        $currentUser = $this->getThisUser();

        // Cek apakah user ini adalah admin (misalnya jabatan 0 atau 1)
        if (in_array($currentUser->jabatan, [0, 1])) {
            // Ambil semua user di bawah upline yang sama, tapi exclude admin
            $karyawan = UserDetail::where('id_upline', $currentUser->id_upline)
                                ->whereNotIn('jabatan', [0, 1])
                                ->select('fullname','saldo','id','jabatan','id_upline')
                                ->get();

            // PERBAIKAN: Cast saldo ke float untuk memastikan tipe data konsisten
            $karyawan = $karyawan->map(function($item) {
                return [
                    'id' => (int) $item->id,
                    'fullname' => $item->fullname,
                    'saldo' => (float) $item->saldo,
                    'jabatan' => $item->jabatan,
                    'id_upline' => (int) $item->id_upline,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Data karyawan ditemukan.',
                'data' => $karyawan
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengakses data karyawan.',
                'data' => []
            ], 403);
        }
    }

    public function store_penarikan(Request $request)
    {
        $user = $this->getThisUser();
        $pegawais = UserDetail::where([['kode_user', '=', $user->kode_user]])->get()->first();

        // Validasi input - HAPUS VALIDASI LACI KARENA TIDAK DIBUTUHKAN UNTUK KARYAWAN
        $request->validate([
            'jumlah_penarikan' => 'required|numeric|min:1',
            'catatan_penarikan' => 'nullable|string|max:255',
        ]);

        $jumlahPenarikan = (float) preg_replace('/[^0-9.]/', '', $request->jumlah_penarikan);

        // Cek saldo pengguna
        if ($pegawais->saldo < $jumlahPenarikan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi'
            ], 400);
        }

        // Generate kode penarikan
        $kode = 'PEN' . date('Ymd') . $this->getThisUser()->id_upline . $this->getThisUser()->kode_user;

        // Simpan data penarikan
        $create = Penarikan::create([
            'tgl_penarikan' => date('Y-m-d h:i:s'),
            'kode_penarikan' => $kode,
            'kode_user' => $this->getThisUser()->kode_user,
            'kode_owner' => $user->id_upline,
            'jumlah_penarikan' => $jumlahPenarikan,
            'catatan_penarikan' => $request->catatan_penarikan ?? '-',
            'status_penarikan' => '1',
            'dari_saldo' => $user->saldo,
            // TIDAK ADA id_kategori - akan diassign nanti oleh admin
        ]);

        if ($create) {
            // Update saldo user
            $new_saldo = $user->saldo - $jumlahPenarikan;
            $pegawais->update(['saldo' => $new_saldo]);

            // WhatsApp notification code (tetap sama)
            $whatsappStatus = 'Pesan WhatsApp tidak dikirim: Nomor telepon tidak tersedia';
            $admin = UserDetail::where([['kode_user', '=', $pegawais->id_upline]])->get()->first();
            $validPhoneNumbers = [];
            $whatsAppService = app(WhatsAppService::class);

            if (!empty($admin->no_telp) && $whatsAppService->isValidPhoneNumber($admin->no_telp)) {
                $validPhoneNumbers[] = $admin->no_telp;
            }

            if (!empty($pegawais->no_telp) && $whatsAppService->isValidPhoneNumber($pegawais->no_telp)) {
                $validPhoneNumbers[] = $pegawais->no_telp;
            }

            if (count($validPhoneNumbers) > 0) {
                try {
                    $waResult = $whatsAppService->penarikanNotification([
                        'teknisi' => $pegawais->fullname,
                        'jumlah' => 'Rp ' . number_format($jumlahPenarikan, 0, ',', '.'),
                        'catatan' => $request->catatan_penarikan != null ? $request->catatan_penarikan : '-',
                        'no_hp' => $validPhoneNumbers,
                    ]);

                    if ($waResult['status']) {
                        $whatsappStatus = 'Pesan WhatsApp berhasil dikirim ke semua penerima';
                    } else {
                        $successCount = count(array_filter($waResult['details'], function($detail) {
                            return $detail['status'] === true;
                        }));

                        if ($successCount > 0) {
                            $whatsappStatus = "Pesan WhatsApp berhasil dikirim ke {$successCount} dari " . count($validPhoneNumbers) . " penerima";
                        } else {
                            $whatsappStatus = 'Pesan WhatsApp gagal dikirim: ' . $waResult['message'];
                        }
                    }
                } catch (\Exception $waException) {
                    \Log::error("Failed to send WhatsApp notification: " . $waException->getMessage(), [
                        'penarikan' => $pegawais->fullname,
                        'recipients' => $validPhoneNumbers,
                        'exception' => $waException
                    ]);

                    $whatsappStatus = 'Pesan WhatsApp gagal dikirim: Terjadi kesalahan sistem';
                }
            } else {
                $whatsappStatus = 'Pesan WhatsApp tidak dikirim: Tidak ada nomor telepon valid';
            }

            // PERBAIKAN: Return data dengan format konsisten
            return response()->json([
                'status' => 'success',
                'message' => 'Penarikan berhasil dibuat',
                'data' => [
                    'id' => (int) $create->id,
                    'kode_penarikan' => $create->kode_penarikan,
                    'jumlah_penarikan' => (float) $create->jumlah_penarikan,
                    'dari_saldo' => (float) $create->dari_saldo,
                    'saldo_setelah' => (float) $new_saldo,
                    'tgl_penarikan' => $create->tgl_penarikan,
                    'catatan_penarikan' => $create->catatan_penarikan,
                ]
            ], 201);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan, coba lagi nanti'
        ], 500);
    }

    public function adminWithdrawEmployee(Request $request)
    {
        // Pastikan yang akses adalah admin
        $user = $this->getThisUser();
        if ($user->jabatan != '1') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Hanya admin yang dapat melakukan penarikan saldo karyawan.'
            ], 403);
        }

        // Validasi input - PERBAIKAN: Update nama field
        $request->validate([
            'kode_user' => 'required|numeric',
            'jumlah_penarikan' => 'required|numeric|min:1',
            'catatan_penarikan' => 'nullable|string|max:255',
            'id_kategorilaci' => 'required|numeric|exists:kategori_lacis,id', // PERUBAHAN NAMA FIELD
        ]);

        $jumlahPenarikan = (float) preg_replace('/[^0-9.]/', '', $request->jumlah_penarikan);
        $targetEmployee = UserDetail::where('kode_user', $request->kode_user)->first();

        if (!$targetEmployee) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan'
            ], 404);
        }

        // Generate kode penarikan khusus untuk admin
        $kode = 'ADM' . date('Ymd') . $user->kode_user . $request->kode_user;

        try {
            // Simpan data penarikan ke tabel penarikans
            $create = Penarikan::create([
                'tgl_penarikan' => date('Y-m-d H:i:s'),
                'kode_penarikan' => $kode,
                'kode_user' => $request->kode_user,
                'kode_owner' => $user->kode_user,
                'jumlah_penarikan' => $jumlahPenarikan,
                'catatan_penarikan' => $request->catatan_penarikan ?? "Penarikan oleh admin untuk {$targetEmployee->fullname}",
                'status_penarikan' => '1',
                'dari_saldo' => $targetEmployee->saldo,
            ]);

            if ($create) {
                // Update saldo karyawan (bisa minus)
                $newSaldo = $targetEmployee->saldo - $jumlahPenarikan;
                $targetEmployee->update(['saldo' => $newSaldo]);

                // CATAT KE LACI dengan reference system - PERBAIKAN: gunakan field yang benar
                $keterangan = "Penarikan admin untuk {$targetEmployee->fullname} oleh {$user->fullname} - " . ($request->catatan_penarikan ?? '-');

                $this->recordLaciHistory(
                    $request->id_kategorilaci, // PERUBAHAN: gunakan field yang benar
                    null, // masuk
                    $jumlahPenarikan, // keluar
                    $keterangan,
                    'penarikan', // reference_type
                    $create->id, // reference_id
                    $kode // reference_code
                );

                // Log aktivitas admin
                \Log::info("Admin withdrawal", [
                    'admin_id' => $user->kode_user,
                    'admin_name' => $user->fullname,
                    'employee_id' => $request->kode_user,
                    'employee_name' => $targetEmployee->fullname,
                    'amount' => $jumlahPenarikan,
                    'old_balance' => $targetEmployee->saldo,
                    'new_balance' => $newSaldo,
                    'note' => $request->catatan_penarikan,
                    'withdrawal_code' => $kode,
                    'kategori_laci' => $request->id_kategorilaci // PERUBAHAN: gunakan field yang benar
                ]);

                // PERBAIKAN: Return data dengan format yang konsisten
                return response()->json([
                    'success' => true,
                    'message' => "Penarikan berhasil untuk {$targetEmployee->fullname}",
                    'data' => [
                        'id' => (int) $create->id,
                        'kode_penarikan' => $kode,
                        'employee_name' => $targetEmployee->fullname,
                        'amount' => (float) $jumlahPenarikan,
                        'old_balance' => (float) ($targetEmployee->saldo + $jumlahPenarikan),
                        'new_balance' => (float) $newSaldo,
                        'admin_name' => $user->fullname,
                        'laci_id' => (int) $request->id_kategorilaci,
                    ]
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data penarikan'
            ], 500);

        } catch (\Exception $e) {
            \Log::error("Admin withdrawal error", [
                'error' => $e->getMessage(),
                'admin_id' => $user->kode_user,
                'employee_id' => $request->kode_user,
                'amount' => $jumlahPenarikan
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses penarikan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Method untuk admin melihat semua history penarikan dengan info laci
    public function adminWithdrawalHistory(Request $request)
    {
        $user = $this->getThisUser();
        if ($user->jabatan != '1') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = DB::table('penarikans as p')
            ->leftJoin('user_details as u', 'p.kode_user', '=', 'u.kode_user')
            ->leftJoin('history_laci as hl', function($join) {
                $join->on('hl.reference_type', '=', DB::raw("'penarikan'"))
                     ->on('hl.reference_id', '=', 'p.id');
            })
            ->leftJoin('kategori_lacis as kl', 'hl.id_kategori', '=', 'kl.id')
            ->leftJoin('user_details as admin', 'hl.kode_owner', '=', 'admin.kode_user')
            ->select([
                'p.id',
                'p.kode_penarikan',
                'p.kode_user',
                'u.fullname as nama_karyawan',
                'u.jabatan',
                'p.jumlah_penarikan',
                'p.catatan_penarikan',
                'p.tgl_penarikan',
                'p.dari_saldo',
                'p.status_penarikan',
                'hl.id_kategori',
                'kl.name_laci',
                'hl.keterangan as catatan_admin',
                'admin.fullname as assigned_by_name',
                'hl.created_at as assigned_at',
                'p.created_at'
            ])
            ->where('u.id_upline', $user->id_upline)
            ->orderBy('p.created_at', 'desc');

        // Filter berdasarkan status laci assignment
        if ($request->has('laci_status')) {
            if ($request->laci_status === 'assigned') {
                $query->whereNotNull('hl.id_kategori');
            } elseif ($request->laci_status === 'unassigned') {
                $query->whereNull('hl.id_kategori');
            }
        }

        // Filter lainnya
        if ($request->has('kode_user')) {
            $query->where('p.kode_user', $request->kode_user);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('p.tgl_penarikan', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        if (!$request->has('start_date') && !$request->has('end_date')) {
            $query->whereMonth('p.tgl_penarikan', date('m'))
                  ->whereYear('p.tgl_penarikan', date('Y'));
        }

        $perPage = $request->get('per_page', 20);
        $withdrawals = $query->paginate($perPage);

        // PERBAIKAN: Format semua data numerik dengan konsisten
        $formattedData = $withdrawals->getCollection()->map(function($item) {
            return [
                'id' => (int) $item->id,
                'kode_penarikan' => $item->kode_penarikan,
                'kode_user' => $item->kode_user,
                'nama_karyawan' => $item->nama_karyawan,
                'jabatan' => $item->jabatan,
                'jumlah_penarikan' => (float) $item->jumlah_penarikan,
                'catatan_penarikan' => $item->catatan_penarikan,
                'tgl_penarikan' => $item->tgl_penarikan,
                'dari_saldo' => (float) $item->dari_saldo,
                'status_penarikan' => $item->status_penarikan,
                'id_kategorilaci' => $item->id_kategori ? (int) $item->id_kategori : null,
                'name_laci' => $item->name_laci,
                'catatan_admin' => $item->catatan_admin,
                'assigned_by_name' => $item->assigned_by_name,
                'assigned_at' => $item->assigned_at,
                'created_at' => $item->created_at,
                // TAMBAHAN: Saldo setelah penarikan untuk memudahkan frontend
                'saldo_setelah' => (float) ($item->dari_saldo - $item->jumlah_penarikan),
            ];
        });

        // Replace collection dengan data yang sudah diformat
        $withdrawals->setCollection($formattedData);

        // Hitung statistik
        $stats = $this->calculateWithdrawalStats($user->id_upline, $request);

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
            'stats' => $stats
        ]);
    }

    // Method untuk assign laci ke penarikan yang belum ada lacinya
    public function assignLaciToWithdrawal(Request $request, $withdrawalId)
    {
        $user = $this->getThisUser();

        if ($user->jabatan != '1') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Hanya admin yang dapat mengassign laci.'
            ], 403);
        }

        $request->validate([
            'id_kategorilaci' => 'required|numeric|exists:kategori_lacis,id',
            'catatan_admin' => 'nullable|string|max:500'
        ]);

        try {
            $withdrawal = DB::table('penarikans as p')
                ->leftJoin('user_details as u', 'p.kode_user', '=', 'u.kode_user')
                ->select('p.*', 'u.fullname')
                ->where('p.id', $withdrawalId)
                ->first();

            if (!$withdrawal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data penarikan tidak ditemukan'
                ], 404);
            }

            // Cek apakah sudah pernah diassign
            if ($this->isTransactionRecorded('penarikan', $withdrawalId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Penarikan ini sudah pernah diassign ke laci'
                ], 400);
            }

            // Catat ke history laci dengan reference
            $keterangan = "Admin assignment - Penarikan {$withdrawal->fullname}";
            if ($request->catatan_admin) {
                $keterangan .= " | {$request->catatan_admin}";
            }

            $this->recordLaciHistory(
                $request->id_kategorilaci,
                null, // masuk
                $withdrawal->jumlah_penarikan, // keluar
                $keterangan,
                'penarikan', // reference_type
                $withdrawalId, // reference_id
                $withdrawal->kode_penarikan // reference_code
            );

            // Log aktivitas
            \Log::info("Laci assigned to withdrawal", [
                'admin_id' => $user->kode_user,
                'admin_name' => $user->fullname,
                'withdrawal_id' => $withdrawalId,
                'withdrawal_code' => $withdrawal->kode_penarikan,
                'employee_name' => $withdrawal->fullname,
                'amount' => $withdrawal->jumlah_penarikan,
                'laci_id' => $request->id_kategorilaci,
                'note' => $request->catatan_admin
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Laci berhasil diassign ke penarikan',
                'data' => [
                    'withdrawal_id' => (int) $withdrawalId,
                    'laci_id' => (int) $request->id_kategorilaci,
                    'assigned_by' => $user->fullname,
                    'assigned_at' => now()->toISOString(),
                    'amount' => (float) $withdrawal->jumlah_penarikan,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Error assigning laci to withdrawal", [
                'error' => $e->getMessage(),
                'withdrawal_id' => $withdrawalId,
                'admin_id' => $user->kode_user
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Method untuk bulk assign laci
    public function bulkAssignLaci(Request $request)
    {
        $user = $this->getThisUser();

        if ($user->jabatan != '1') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'withdrawal_ids' => 'required|array|min:1',
            'withdrawal_ids.*' => 'numeric|exists:penarikans,id',
            'id_kategorilaci' => 'required|numeric|exists:kategori_lacis,id', // PERUBAHAN: gunakan field yang benar
            'catatan_admin' => 'nullable|string|max:500'
        ]);

        try {
            $withdrawalIds = $request->withdrawal_ids;
            $laciId = (int) $request->id_kategorilaci; // PERUBAHAN: gunakan field yang benar
            $successCount = 0;
            $errors = [];

            foreach ($withdrawalIds as $withdrawalId) {
                try {
                    // Cek apakah sudah pernah diassign
                    if ($this->isTransactionRecorded('penarikan', $withdrawalId)) {
                        continue; // Skip yang sudah ada
                    }

                    $withdrawal = DB::table('penarikans as p')
                        ->leftJoin('user_details as u', 'p.kode_user', '=', 'u.kode_user')
                        ->select('p.*', 'u.fullname')
                        ->where('p.id', $withdrawalId)
                        ->first();

                    if ($withdrawal) {
                        // Catat ke history laci dengan reference
                        $keterangan = "Bulk assignment - Penarikan {$withdrawal->fullname}";
                        if ($request->catatan_admin) {
                            $keterangan .= " | {$request->catatan_admin}";
                        }

                        $this->recordLaciHistory(
                            $laciId,
                            null,
                            $withdrawal->jumlah_penarikan,
                            $keterangan,
                            'penarikan',
                            $withdrawalId,
                            $withdrawal->kode_penarikan
                        );

                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "ID {$withdrawalId}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil assign {$successCount} dari " . count($withdrawalIds) . " penarikan",
                'data' => [
                    'success_count' => $successCount,
                    'total_count' => count($withdrawalIds),
                    'laci_id' => $laciId,
                    'assigned_by' => $user->fullname,
                    'assigned_at' => now()->toISOString(),
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Method untuk employee melihat history penarikan sendiri
    public function employeeWithdrawalHistory(Request $request)
    {
        $user = $this->getThisUser();

        $query = DB::table('penarikans as p')
            ->leftJoin('history_laci as hl', function($join) {
                $join->on('hl.reference_type', '=', DB::raw("'penarikan'"))
                     ->on('hl.reference_id', '=', 'p.id');
            })
            ->leftJoin('kategori_lacis as kl', 'hl.id_kategori', '=', 'kl.id')
            ->leftJoin('user_details as admin', 'hl.kode_owner', '=', 'admin.kode_user')
            ->select([
                'p.id',
                'p.kode_penarikan',
                'p.jumlah_penarikan',
                'p.catatan_penarikan',
                'p.tgl_penarikan',
                'p.dari_saldo',
                'p.status_penarikan',
                'kl.name_laci',
                'hl.keterangan as catatan_admin',
                'admin.fullname as assigned_by_name',
                'hl.created_at as assigned_at',
                'p.created_at'
            ])
            ->where('p.kode_user', $user->kode_user)
            ->orderBy('p.created_at', 'desc');

        // Filter berdasarkan bulan jika diminta
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('p.tgl_penarikan', $request->month)
                  ->whereYear('p.tgl_penarikan', $request->year);
        }

        if (!$request->has('month') && !$request->has('year')) {
            $query->where('p.tgl_penarikan', '>=', now()->subMonths(3));
        }

        $perPage = $request->get('per_page', 15);
        $withdrawals = $query->paginate($perPage);

        // PERBAIKAN: Format data dengan konsisten untuk employee
        $formattedData = $withdrawals->getCollection()->map(function($item) {
            return [
                'id' => (int) $item->id,
                'kode_penarikan' => $item->kode_penarikan,
                'jumlah_penarikan' => (float) $item->jumlah_penarikan,
                'catatan_penarikan' => $item->catatan_penarikan,
                'tgl_penarikan' => $item->tgl_penarikan,
                'dari_saldo' => (float) $item->dari_saldo,
                'status_penarikan' => $item->status_penarikan,
                'name_laci' => $item->name_laci,
                'catatan_admin' => $item->catatan_admin,
                'assigned_by_name' => $item->assigned_by_name,
                'assigned_at' => $item->assigned_at,
                'created_at' => $item->created_at,
                // TAMBAHAN: Saldo setelah penarikan
                'saldo_setelah' => (float) ($item->dari_saldo - $item->jumlah_penarikan),
            ];
        });

        $withdrawals->setCollection($formattedData);

        // Statistik personal dengan format konsisten
        $stats = [
            'total_amount_this_month' => (float) (DB::table('penarikans')
                ->where('kode_user', $user->kode_user)
                ->whereMonth('tgl_penarikan', date('m'))
                ->whereYear('tgl_penarikan', date('Y'))
                ->sum('jumlah_penarikan') ?? 0),
            'total_count_this_month' => (int) (DB::table('penarikans')
                ->where('kode_user', $user->kode_user)
                ->whereMonth('tgl_penarikan', date('m'))
                ->whereYear('tgl_penarikan', date('Y'))
                ->count()),
            'current_balance' => (float) $user->saldo
        ];

        return response()->json([
            'success' => true,
            'data' => $withdrawals,
            'stats' => $stats
        ]);
    }

    // Helper method untuk menghitung statistik
    private function calculateWithdrawalStats($uplineId, $request)
    {
        $baseQuery = DB::table('penarikans as p')
            ->leftJoin('user_details as u', 'p.kode_user', '=', 'u.kode_user')
            ->where('u.id_upline', $uplineId);

        // Apply filters
        if ($request->has('start_date') && $request->has('end_date')) {
            $baseQuery->whereBetween('p.tgl_penarikan', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        } elseif (!$request->has('start_date') && !$request->has('end_date')) {
            $baseQuery->whereMonth('p.tgl_penarikan', date('m'))
                      ->whereYear('p.tgl_penarikan', date('Y'));
        }

        $totalAmount = (float) ((clone $baseQuery)->sum('p.jumlah_penarikan') ?? 0);
        $totalCount = (int) ((clone $baseQuery)->count());

        // Hitung yang sudah assigned (ada di history_laci dengan reference)
        $assignedCount = (int) ((clone $baseQuery)
            ->leftJoin('history_laci as hl', function($join) {
                $join->on('hl.reference_type', '=', DB::raw("'penarikan'"))
                     ->on('hl.reference_id', '=', 'p.id');
            })
            ->whereNotNull('hl.id_kategori')
            ->count());

        return [
            'total_amount' => $totalAmount,
            'total_count' => $totalCount,
            'assigned_count' => $assignedCount,
            'unassigned_count' => $totalCount - $assignedCount,
        ];
    }

    // Method untuk mendapatkan ringkasan penarikan admin
    public function adminWithdrawalSummary()
    {
        $user = $this->getThisUser();
        if ($user->jabatan != '1') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $today = date('Y-m-d');
            $thisMonth = date('Y-m');

            // Query untuk penarikan dalam upline yang sama
            $baseQuery = DB::table('penarikans as p')
                ->leftJoin('user_details as u', 'p.kode_user', '=', 'u.kode_user')
                ->where('u.id_upline', $user->id_upline);

            // Subquery untuk assigned count dengan reference system
            $assignedSubquery = function($query, $dateFilter = null) {
                $subQuery = $query->leftJoin('history_laci as hl', function($join) {
                    $join->on('hl.reference_type', '=', DB::raw("'penarikan'"))
                         ->on('hl.reference_id', '=', 'p.id');
                })->whereNotNull('hl.id_kategori');

                if ($dateFilter) {
                    if ($dateFilter['type'] === 'date') {
                        $subQuery->whereDate('p.tgl_penarikan', $dateFilter['value']);
                    } elseif ($dateFilter['type'] === 'month') {
                        $subQuery->where('p.tgl_penarikan', 'like', $dateFilter['value'] . '%');
                    }
                }

                return $subQuery->count();
            };

            // PERBAIKAN: Pastikan semua nilai numerik dikembalikan dengan tipe yang konsisten
            $summary = [
                'today' => [
                    'count' => (int) ((clone $baseQuery)->whereDate('p.tgl_penarikan', $today)->count()),
                    'total' => (float) ((clone $baseQuery)->whereDate('p.tgl_penarikan', $today)->sum('p.jumlah_penarikan') ?? 0),
                    'unassigned_count' => (int) ((clone $baseQuery)->whereDate('p.tgl_penarikan', $today)->count() -
                                         $assignedSubquery(clone $baseQuery, ['type' => 'date', 'value' => $today]))
                ],
                'this_month' => [
                    'count' => (int) ((clone $baseQuery)->where('p.tgl_penarikan', 'like', $thisMonth . '%')->count()),
                    'total' => (float) ((clone $baseQuery)->where('p.tgl_penarikan', 'like', $thisMonth . '%')->sum('p.jumlah_penarikan') ?? 0),
                    'unassigned_count' => (int) ((clone $baseQuery)->where('p.tgl_penarikan', 'like', $thisMonth . '%')->count() -
                                         $assignedSubquery(clone $baseQuery, ['type' => 'month', 'value' => $thisMonth]))
                ],
                'unassigned_total' => [
                    'count' => (int) ((clone $baseQuery)->count() - $assignedSubquery(clone $baseQuery)),
                    'amount' => (float) ((clone $baseQuery)->sum('p.jumlah_penarikan') -
                               (DB::table('penarikans as p2')
                                   ->leftJoin('user_details as u2', 'p2.kode_user', '=', 'u2.kode_user')
                                   ->leftJoin('history_laci as hl2', function($join) {
                                       $join->on('hl2.reference_type', '=', DB::raw("'penarikan'"))
                                            ->on('hl2.reference_id', '=', 'p2.id');
                                   })
                                   ->where('u2.id_upline', $user->id_upline)
                                   ->whereNotNull('hl2.id_kategori')
                                   ->sum('p2.jumlah_penarikan') ?? 0))
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // TAMBAHAN: Helper method untuk mengecek apakah transaksi sudah tercatat
    private function isTransactionRecorded($referenceType, $referenceId)
    {
        return DB::table('history_laci')
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->exists();
    }

    // TAMBAHAN: Helper method untuk format response yang konsisten
    private function formatNumberResponse($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_numeric($value)) {
                    // Format sebagai float untuk decimal, int untuk integer
                    if (in_array($key, ['id', 'count', 'total_count', 'assigned_count', 'unassigned_count'])) {
                        $data[$key] = (int) $value;
                    } else {
                        $data[$key] = (float) $value;
                    }
                } elseif (is_array($value)) {
                    $data[$key] = $this->formatNumberResponse($value);
                }
            }
        }
        return $data;
    }

    // TAMBAHAN: Method untuk validasi numeric input
    private function validateAndFormatAmount($amount)
    {
        // Remove formatting characters but keep decimal point
        $cleanAmount = preg_replace('/[^0-9.]/', '', $amount);

        // Validate numeric
        if (!is_numeric($cleanAmount)) {
            throw new \InvalidArgumentException('Jumlah harus berupa angka yang valid');
        }

        // Convert to float and validate minimum
        $numericAmount = (float) $cleanAmount;
        if ($numericAmount <= 0) {
            throw new \InvalidArgumentException('Jumlah harus lebih besar dari 0');
        }

        return $numericAmount;
    }
}
