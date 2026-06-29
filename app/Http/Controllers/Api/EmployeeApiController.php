<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Violation;
use App\Models\SalarySetting;
use App\Models\ProfitPresentase;
use Carbon\Carbon;

class EmployeeApiController extends Controller
{
    /**
     * Get list of employees for the authenticated owner
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            // Only owner (jabatan == 1) can manage employees
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat mengelola karyawan.'
                ], 403);
            }

            $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
                ->leftJoin('cabangs', 'users.cabang_id', '=', 'cabangs.id')
                ->where('user_details.id_upline', $user->id)
                ->whereIn('user_details.jabatan', [2, 3]) // 2: Kasir, 3: Teknisi
                ->select([
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.cabang_id',
                    'user_details.fullname',
                    'user_details.jabatan',
                    'user_details.status_user',
                    'user_details.no_telp',
                    'user_details.alamat_user',
                    'cabangs.nama_cabang'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar karyawan berhasil diambil',
                'data' => $employees
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error get employees: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat menambahkan karyawan.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:6',
                'jabatan' => 'required|in:2,3', // 2: Kasir, 3: Teknisi
                'cabang_id' => 'nullable|exists:cabangs,id',
                'no_telp' => 'nullable|string|max:20',
                'alamat_user' => 'nullable|string',
                'status_user' => 'nullable|in:0,1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Create User
            $newUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'cabang_id' => $request->cabang_id,
            ]);

            // Create User Detail
            $kode_invite = 'INV' . $newUser->id . $request->jabatan . rand(500, 1000);
            
            UserDetail::create([
                'kode_user' => $newUser->id,
                'foto_user' => '-',
                'fullname' => $request->name,
                'alamat_user' => $request->alamat_user ?? '',
                'no_telp' => $request->no_telp ?? '-',
                'jabatan' => $request->jabatan,
                'id_upline' => $user->id,
                'status_user' => $request->status_user ?? '1',
                'kode_invite' => $kode_invite,
                'link_twitter' => '-',
                'link_facebook' => '-',
                'link_instagram' => '-',
                'link_linkedin' => '-',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil ditambahkan',
                'data' => [
                    'id' => $newUser->id,
                    'name' => $newUser->name,
                    'email' => $newUser->email,
                    'cabang_id' => $newUser->cabang_id
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menambah karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an employee's details
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat mengedit karyawan.'
                ], 403);
            }

            $targetUser = User::where('id', $id)->first();
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan tidak ditemukan.'
                ], 404);
            }

            $userDetail = UserDetail::where('kode_user', $id)->first();
            if (!$userDetail || $userDetail->id_upline != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Karyawan ini tidak berada di bawah kepemilikan Anda.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $id,
                'password' => 'nullable|string|min:6',
                'jabatan' => 'required|in:2,3',
                'cabang_id' => 'nullable|exists:cabangs,id',
                'no_telp' => 'nullable|string|max:20',
                'alamat_user' => 'nullable|string',
                'status_user' => 'required|in:0,1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'cabang_id' => $request->cabang_id,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $targetUser->update($updateData);

            $userDetail->update([
                'fullname' => $request->name,
                'alamat_user' => $request->alamat_user ?? $userDetail->alamat_user,
                'no_telp' => $request->no_telp ?? $userDetail->no_telp,
                'jabatan' => $request->jabatan,
                'status_user' => $request->status_user,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil diperbarui',
                'data' => [
                    'id' => $targetUser->id,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                    'cabang_id' => $targetUser->cabang_id
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error update employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an employee
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat menghapus karyawan.'
                ], 403);
            }

            $targetUser = User::where('id', $id)->first();
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan tidak ditemukan.'
                ], 404);
            }

            $userDetail = UserDetail::where('kode_user', $id)->first();
            if (!$userDetail || $userDetail->id_upline != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Karyawan ini tidak berada di bawah kepemilikan Anda.'
                ], 403);
            }

            DB::beginTransaction();

            $userDetail->delete();
            $targetUser->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error delete employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get list of violations for a user
     */
    public function violationsIndex(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            if (!$detail) {
                return response()->json(['success' => false, 'message' => 'User detail tidak ditemukan'], 404);
            }

            $userId = $request->query('user_id');

            // If not owner, they can only view their own violations
            if ($detail->jabatan != '1') {
                $userId = $user->id;
            } else {
                // If owner, user_id parameter is required
                if (!$userId) {
                    return response()->json(['success' => false, 'message' => 'Parameter user_id wajib diisi untuk owner.'], 400);
                }
                
                $targetDetail = UserDetail::where('kode_user', $userId)->first();
                if (!$targetDetail || $targetDetail->id_upline != $user->id) {
                    return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan atau bukan milik Anda.'], 403);
                }
            }

            $violations = Violation::where('user_id', $userId)
                ->orderBy('violation_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar pelanggaran berhasil diambil',
                'data' => $violations
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error get violations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pelanggaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Record a new violation
     */
    public function violationsStore(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            if (!$detail || $detail->jabatan != '1') {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Hanya owner yang dapat membuat catatan pelanggaran.'], 403);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'violation_date' => 'required|date',
                'type' => 'required|in:telat,alpha,kelalaian,komplain,lainnya',
                'description' => 'required|string',
                'penalty_amount' => 'nullable|numeric|min:0',
                'penalty_percentage' => 'nullable|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $targetDetail = UserDetail::where('kode_user', $request->user_id)->first();
            if (!$targetDetail || $targetDetail->id_upline != $user->id) {
                return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan atau bukan milik Anda.'], 403);
            }

            $violation = Violation::create([
                'user_id' => $request->user_id,
                'violation_date' => $request->violation_date,
                'type' => $request->type,
                'description' => $request->description,
                'penalty_amount' => $request->penalty_amount ?? 0,
                'penalty_percentage' => $request->penalty_percentage ?? 0,
                'status' => 'pending',
                'created_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pelanggaran berhasil dicatat',
                'data' => $violation
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error store violation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat pelanggaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process or Forgive a violation
     */
    public function violationsUpdateStatus(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            if (!$detail || $detail->jabatan != '1') {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Hanya owner yang dapat mengubah status pelanggaran.'], 403);
            }

            $validator = Validator::make($request->all(), [
                'violation_id' => 'required|exists:violations,id',
                'status' => 'required|in:processed,forgiven',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $violation = Violation::with('user')->findOrFail($request->violation_id);

            $targetDetail = UserDetail::where('kode_user', $violation->user_id)->first();
            if (!$targetDetail || $targetDetail->id_upline != $user->id) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Karyawan ini tidak berada di bawah kepemilikan Anda.'], 403);
            }

            if ($violation->status !== 'pending') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggaran ini sudah diproses sebelumnya'
                ], 400);
            }

            $violation->update([
                'status' => $request->status,
                'processed_at' => now(),
                'processed_by' => $user->id
            ]);

            if ($request->status === 'processed') {
                $penaltyResult = $this->applyViolationPenalty($violation);

                if (!$penaltyResult['success']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => $penaltyResult['message']
                    ], 400);
                }
            }

            DB::commit();

            $message = $request->status === 'processed'
                ? 'Pelanggaran berhasil diproses dan denda diterapkan'
                : 'Pelanggaran berhasil dimaafkan';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $violation->fresh()
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error update violation status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status pelanggaran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reverse a processed violation penalty denda
     */
    public function reversePenalty(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            if (!$detail || $detail->jabatan != '1') {
                return response()->json(['success' => false, 'message' => 'Unauthorized. Hanya owner yang dapat membatalkan denda.'], 403);
            }

            $validator = Validator::make($request->all(), [
                'violation_id' => 'required|exists:violations,id',
                'reason' => 'required|string|min:10'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $violation = Violation::with('user')->findOrFail($request->violation_id);

            $targetDetail = UserDetail::where('kode_user', $violation->user_id)->first();
            if (!$targetDetail || $targetDetail->id_upline != $user->id) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Karyawan ini tidak berada di bawah kepemilikan Anda.'], 403);
            }

            if (!$violation->applied_penalty_amount || $violation->status !== 'processed') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggaran ini belum memiliki denda yang diterapkan'
                ], 400);
            }

            if ($violation->reversed_at) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Denda ini sudah pernah dibatalkan sebelumnya'
                ], 400);
            }

            $salarySetting = SalarySetting::where('user_id', $violation->user_id)->first();

            if (!$salarySetting) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Pengaturan kompensasi tidak ditemukan'
                ], 404);
            }

            if ($salarySetting->compensation_type === 'fixed') {
                $pp = ProfitPresentase::where('kode_user', $violation->user_id)
                    ->whereDate('tgl_profit', $violation->violation_date->toDateString())
                    ->where('kode_service', 0)
                    ->where('profit', '<', 0)
                    ->first();
                if ($pp) {
                    if ($pp->is_cair) {
                        $userDetail = UserDetail::where('kode_user', $violation->user_id)->first();
                        if ($userDetail) {
                            $userDetail->increment('saldo', abs($pp->profit));
                        }
                    }
                    $pp->delete();
                }
            }

            $violation->update([
                'status' => 'forgiven',
                'reversal_reason' => $request->reason,
                'reversed_at' => now(),
                'reversed_by' => $user->id
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Denda berhasil dibatalkan dan status diubah',
                'data' => $violation->fresh()
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error reverse penalty: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan denda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF for monthly violations
     */
    public function violationsPdf(Request $request)
    {
        try {
            if ($request->has('token')) {
                auth()->setToken($request->token);
                auth()->authenticate();
            }

            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }

            $detail = \App\Models\UserDetail::where('kode_user', $user->id)->first();
            if (!$detail || $detail->jabatan != '1') {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }

            $userId = $request->query('user_id');
            $month = $request->query('month');
            $year = $request->query('year');

            if (!$userId || !$month || !$year) {
                return response()->json(['success' => false, 'message' => 'Parameter user_id, month, dan year wajib diisi.'], 400);
            }

            $targetDetail = \App\Models\UserDetail::where('kode_user', $userId)->first();
            if (!$targetDetail || $targetDetail->id_upline != $user->id) {
                return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan atau bukan milik Anda.'], 403);
            }

            $targetUser = \App\Models\User::find($userId);

            $violations = \App\Models\Violation::where('user_id', $userId)
                ->whereMonth('violation_date', $month)
                ->whereYear('violation_date', $year)
                ->orderBy('violation_date', 'asc')
                ->get();

            $pdf = \PDF::loadView('pdf.violation_recap', [
                'violations' => $violations,
                'targetUser' => $targetUser,
                'targetDetail' => $targetDetail,
                'month' => $month,
                'year' => $year
            ]);

            return $pdf->download("Rekap_Pelanggaran_{$targetUser->name}_{$month}_{$year}.pdf");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generate pdf violations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to apply penalty to salary settings
     */
    private function applyViolationPenalty(Violation $violation)
    {
        try {
            $salarySetting = SalarySetting::where('user_id', $violation->user_id)->first();

            if (!$salarySetting) {
                return ['success' => false, 'message' => 'Tidak ada pengaturan kompensasi untuk karyawan ini'];
            }

            $penaltyAmount = 0;

            if ($salarySetting->compensation_type === 'fixed') {
                if ($violation->penalty_amount > 0) {
                    $penaltyAmount = $violation->penalty_amount;
                } elseif ($violation->penalty_percentage > 0) {
                    $penaltyAmount = ($salarySetting->basic_salary * $violation->penalty_percentage) / 100;
                }
            } else { // 'percentage'
                if ($violation->penalty_percentage > 0) {
                    $lastMonthProfit = $this->calculateLastMonthProfit($violation->user_id);
                    $penaltyAmount = ($lastMonthProfit * $violation->penalty_percentage) / 100;
                } elseif ($violation->penalty_amount > 0) {
                    $penaltyAmount = $violation->penalty_amount;
                }
            }

            $violation->update([
                'applied_penalty_amount' => $penaltyAmount,
                'applied_at' => now()
            ]);

            if ($salarySetting->compensation_type === 'fixed' && $penaltyAmount > 0) {
                $exists = ProfitPresentase::where('kode_user', $violation->user_id)
                    ->whereDate('tgl_profit', Carbon::parse($violation->violation_date)->toDateString())
                    ->where('kode_service', 0)
                    ->where('profit', '<', 0)
                    ->exists();

                if (!$exists) {
                    ProfitPresentase::create([
                        'tgl_profit' => Carbon::parse($violation->violation_date)->toDateString(),
                        'kode_service' => 0,
                        'kode_presentase' => $salarySetting->id,
                        'kode_user' => $violation->user_id,
                        'profit' => -$penaltyAmount,
                        'profit_toko' => 0,
                        'is_cair' => 0,
                    ]);
                }
            }

            return ['success' => true, 'penalty_amount' => $penaltyAmount];

        } catch (\Exception $e) {
            Log::error('Error apply violation penalty helper: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menerapkan denda: ' . $e->getMessage()];
        }
    }

    /**
     * Helper to calculate last month's profit for percentage penalty
     */
    private function calculateLastMonthProfit($userId)
    {
        $lastMonth = Carbon::now()->subMonth();
        $startDate = $lastMonth->startOfMonth();
        $endDate = $lastMonth->endOfMonth();

        $services = \App\Models\Sevices::where('id_teknisi', $userId)
            ->whereIn('status_services', ['Selesai', 'Diambil'])
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->get();

        if ($services->isEmpty()) {
            return 0;
        }

        $serviceIds = $services->pluck('id');

        return ProfitPresentase::whereIn('kode_service', $serviceIds)
            ->where('kode_user', $userId)
            ->sum(DB::raw('profit + profit_toko'));
    }
}
